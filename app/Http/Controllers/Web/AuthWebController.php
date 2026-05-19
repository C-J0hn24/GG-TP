<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasswordResetService;
use App\Services\Basket\BasketService;
use App\Services\Basket\GuestCartService;
use App\Support\AppUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class AuthWebController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected EmailVerificationService $emailVerificationService,
        protected GuestCartService $guestCart,
        protected BasketService $basketService,
        protected PasswordResetService $passwordResetService,
    ) {
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin($request);
        }

        return view('auth.login', [
            'checkoutAfterLogin' => $request->boolean('checkout'),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->validated('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && $this->authService->passwordMatches($request->validated('password'), (string) $user->password)) {
            if ($this->authService->userNeedsEmailVerification($user)) {
                return redirect()
                    ->route('verify-email', ['email' => $user->email])
                    ->withErrors(['email' => 'Please verify your email before logging in.']);
            }

            if (strtoupper((string) ($user->account_status ?? 'ACTIVE')) !== 'ACTIVE') {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
            }
        }

        $user = $this->authService->attemptLogin(
            $request->validated(),
            $request->boolean('remember')
        );

        if (!$user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        if ($user->role === 'trader') {
            return redirect('/GG-TP/trader-portal/trader/dashboard.php');
        }

        try {
            $this->guestCart->mergeIntoUserBasket($user, $this->basketService);
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->redirectAfterLogin($request);
    }

    private function redirectAfterLogin(Request $request): RedirectResponse
    {
        $checkout = $request->boolean('checkout');

        $intended = session()->pull('url.intended');
        if (is_string($intended) && $intended !== '') {
            if (str_contains($intended, '/checkout')) {
                $checkout = true;
            }
            $fixed = AppUrl::fixApplicationUrl($intended);
            if ($fixed !== null && $fixed !== '') {
                return redirect()->to($fixed);
            }
        }

        $default = $checkout ? route('checkout.collection-slot') : route('home');

        return redirect()->to($default);
    }

    public function showRegister(): View|RedirectResponse
    {
        if (strtolower((string) request('type', 'customer')) === 'trader') {
            return redirect(url('trader-portal/register.php'));
        }

        return view('auth.register');
    }

    public function register(RegisterCustomerRequest $request): RedirectResponse
    {
        try {
            $result = $this->authService->registerCustomer($request->validated());
        } catch (RuntimeException $e) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()
            ->route('verify-email', ['email' => $result['user']->email])
            ->with('status', 'Account created. Enter the 6-digit code sent to your email.');
    }

    public function showVerifyEmail(): View
    {
        return view('auth.verify-email', [
            'email' => request('email', session('pending_verification_email')),
        ]);
    }

    public function verifyEmail(VerifyEmailRequest $request): RedirectResponse
    {
        $this->emailVerificationService->verifySignupCode(
            $request->validated('email'),
            $request->validated('code'),
        );

        return redirect()
            ->route('login')
            ->with('status', 'Email verified successfully. You can sign in now.');
    }

    public function resendVerification(ResendVerificationRequest $request): RedirectResponse
    {
        try {
            $this->emailVerificationService->resendSignupCode($request->validated('email'));
        } catch (RuntimeException $e) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $e->getMessage()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'No account found for this email.']);
        }

        return redirect()
            ->route('verify-email', ['email' => $request->validated('email')])
            ->with('status', 'A new verification code has been sent to your email.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendForgotPassword(ForgotPasswordRequest $request): RedirectResponse
    {
        try {
            $this->passwordResetService->sendResetLink($request->validated('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Could not send reset email. Try again later.']);
        }

        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
    }

    public function showResetPassword(): View
    {
        return view('auth.reset-password', [
            'token' => request('token', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        try {
            $this->passwordResetService->resetPassword(
                $request->validated('token'),
                $request->validated('password'),
            );
        } catch (RuntimeException $e) {
            return back()
                ->withInput($request->only('token'))
                ->withErrors(['password' => $e->getMessage()]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Password updated. You can sign in now.');
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}
