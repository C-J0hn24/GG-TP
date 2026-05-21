<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Requests\Checkout\PayPalCaptureRequest;
use App\Http\Requests\Checkout\PayPalStartRequest;
use App\Services\Basket\BasketService;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\InvoiceEmailService;
use App\Services\Checkout\PayPalService;
use App\Support\AppUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use RuntimeException;

class CheckoutWebController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected BasketService $basketService,
        protected PayPalService $payPalService,
        protected InvoiceEmailService $invoiceEmailService,
    ) {
    }

    public function showSlotPicker(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login', ['checkout' => 1]);
        }

        $summary = $this->basketService->summary($this->basketService->getBasket(Auth::user()));

        if (empty($summary['items'])) {
            return redirect()->route('cart')->with('status', 'Your cart is empty.');
        }

        return view('checkout.collection-slot', [
            'slotDate' => $request->query('slot_date'),
            'slotTime' => $request->query('slot_time'),
            'cartTotal' => $summary['total'],
            'paypalClientId' => $this->payPalService->clientId(),
            'paypalCurrency' => $this->payPalService->currency(),
            'paypalSdkUrl' => $this->payPalService->sdkScriptBaseUrl(),
            'paypalConfigured' => $this->payPalService->isConfigured(),
            'paypalChargeAmount' => $this->payPalService->chargeAmount((float) $summary['total']),
        ]);
    }

    /** Full-page PayPal (sandbox) — uses checkoutnow, not popup checkoutweb. */
    public function startPayPalRedirect(PayPalStartRequest $request): RedirectResponse
    {
        if (!$this->payPalService->isConfigured()) {
            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal is not configured.']);
        }

        $summary = $this->basketService->summary($this->basketService->getBasket(Auth::user()));
        if (empty($summary['items'])) {
            return redirect()->route('cart')->with('status', 'Your cart is empty.');
        }

        $returnUrl = route('checkout.paypal.return', [], true);
        $cancelUrl = route('checkout.paypal.cancel', [], true);

        try {
            $paypalOrder = $this->payPalService->createCheckoutOrderForRedirect(
                (float) $summary['total'],
                $returnUrl,
                $cancelUrl,
            );
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'Could not start PayPal. '.(config('app.debug') ? $e->getMessage() : 'Try again.')]);
        }

        Session::put('paypal_checkout', [
            'checkout' => $request->checkoutPayload(),
            'paypal_order_id' => $paypalOrder['id'],
            'amount' => $paypalOrder['amount'],
            'currency' => $paypalOrder['currency'],
        ]);

        return redirect()->away($paypalOrder['approve_url']);
    }

    public function paypalReturn(Request $request): RedirectResponse
    {
        $orderId = (string) $request->query('token', '');
        $pending = Session::pull('paypal_checkout');

        if ($orderId === '' || !is_array($pending)) {
            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal session expired. Please try again.']);
        }

        if (($pending['paypal_order_id'] ?? '') !== $orderId) {
            $orderId = (string) ($pending['paypal_order_id'] ?? $orderId);
        }

        return $this->completePayPalCheckout($orderId, (array) ($pending['checkout'] ?? []));
    }

    public function paypalCancel(): RedirectResponse
    {
        Session::forget('paypal_checkout');

        return redirect()->route('checkout.collection-slot')
            ->with('status', 'PayPal payment cancelled.');
    }

    public function createPayPalOrder(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$this->payPalService->isConfigured()) {
            return response()->json(['message' => 'PayPal is not configured'], 503);
        }

        $summary = $this->basketService->summary($this->basketService->getBasket(Auth::user()));

        if (empty($summary['items'])) {
            return response()->json(['message' => 'Basket is empty'], 422);
        }

        try {
            $paypalOrder = $this->payPalService->createCheckoutOrder((float) $summary['total']);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => 'Could not start PayPal checkout',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 502);
        }

        return response()->json([
            'id' => $paypalOrder['id'],
            'status' => $paypalOrder['status'],
            'amount' => $paypalOrder['amount'],
            'currency' => $paypalOrder['currency'],
        ]);
    }

    public function capturePayPal(PayPalCaptureRequest $request): RedirectResponse
    {
        if (!$this->payPalService->isConfigured()) {
            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal sandbox is not configured. Add credentials to .env']);
        }

        try {
            $capture = $this->payPalService->captureOrder($request->validated('paypal_order_id'));
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal payment could not be completed. Try again.']);
        }

        if (!$this->payPalService->isCaptureCompleted($capture)) {
            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal payment was not completed.']);
        }

        return $this->finishCheckoutAfterCapture(
            $request->validated('paypal_order_id'),
            $capture,
            $request->checkoutPayload(),
        );
    }

    protected function completePayPalCheckout(string $paypalOrderId, array $checkoutPayload): RedirectResponse
    {
        if (!$this->payPalService->isConfigured()) {
            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal is not configured.']);
        }

        try {
            $capture = $this->payPalService->captureOrder($paypalOrderId);
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal payment could not be completed. Try again.']);
        }

        if (!$this->payPalService->isCaptureCompleted($capture)) {
            return redirect()->route('checkout.collection-slot')
                ->withErrors(['paypal' => 'PayPal payment was not completed.']);
        }

        return $this->finishCheckoutAfterCapture($paypalOrderId, $capture, $checkoutPayload);
    }

    protected function finishCheckoutAfterCapture(string $paypalOrderId, array $capture, array $checkoutPayload): RedirectResponse
    {
        $captureId = $this->payPalService->extractCaptureId($capture);
        if ($captureId !== '') {
            $existingOrderId = Session::get('grocerygo_order_for_capture_'.$captureId);
            if (is_string($existingOrderId) && $existingOrderId !== '') {
                return $this->redirectAfterSuccessfulCheckout($existingOrderId, Auth::user());
            }
        }

        $payload = $checkoutPayload;
        $payload['payment_method'] = 'paypal';
        $payload['paypal_order_id'] = $paypalOrderId;
        $payload['paypal_capture_id'] = $captureId;
        $payload['paid_amount'] = $this->payPalService->extractCapturedAmount($capture);

        $result = $this->checkoutService->checkout(Auth::user(), $payload);
        $orderId = (string) $result['order']->order_id;

        if ($captureId !== '') {
            Session::put('grocerygo_order_for_capture_'.$captureId, $orderId);
        }

        return $this->redirectAfterSuccessfulCheckout($orderId, Auth::user(), $result['order']);
    }

    /** Fallback when PayPal is not used (local dev). */
    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        $result = $this->checkoutService->checkout(Auth::user(), $request->validated());
        $orderId = (string) $result['order']->order_id;

        return $this->redirectAfterSuccessfulCheckout($orderId, Auth::user(), $result['order']);
    }

    protected function redirectAfterSuccessfulCheckout(
        string $orderId,
        \App\Models\User $user,
        ?\App\Models\Order $order = null,
    ): RedirectResponse {
        $token = bin2hex(random_bytes(16));
        Session::put([
            'payment_success_order_id' => $orderId,
            'payment_success_token' => $token,
            'invoice_payment_success' => 'Payment successful! Thank you for your order.',
        ]);

        if ($order !== null) {
            $emailNotice = $this->invoiceEmailService->sendForOrder($user, $order);
            if ($emailNotice !== null) {
                Session::put('payment_email_notice', $emailNotice);
            }
        }

        return redirect()->away(AppUrl::paymentSuccessUrl([
            'order_id' => $orderId,
            'token' => $token,
        ]));
    }
}
