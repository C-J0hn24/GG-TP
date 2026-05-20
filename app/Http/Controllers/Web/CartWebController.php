<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Basket\AddBasketItemRequest;
use App\Http\Requests\Basket\RemoveCartItemRequest;
use App\Http\Requests\Basket\UpdateCartItemRequest;
use App\Services\Basket\BasketService;
use App\Services\Basket\GuestCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class CartWebController extends Controller
{
    public function __construct(
        protected BasketService $basketService,
        protected GuestCartService $guestCart,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        try {
            if (Auth::check()) {
                $basket = $this->basketService->getBasket(Auth::user());
                $summary = $this->basketService->summary($basket);
            } else {
                $summary = $this->guestCart->summary();
            }
        } catch (Throwable $e) {
            report($e);

            if (Auth::check()) {
                return redirect()->route('home')->withErrors([
                    'cart' => 'Could not load your basket. Please try again.',
                ]);
            }

            $summary = ['items' => [], 'total' => 0.0];
        }

        return view('cart.index', [
            'cartItems' => $summary['items'],
            'total' => $summary['total'],
            'isGuest' => ! Auth::check(),
        ]);
    }

    public function addItem(AddBasketItemRequest $request): RedirectResponse
    {
        $productId = (string) $request->validated('product_id');
        $qty = max(1, min(20, (int) $request->input('quantity', 1)));

        try {
            if (Auth::check()) {
                for ($i = 0; $i < $qty; $i++) {
                    $this->basketService->addItem(Auth::user(), $productId);
                }
            } else {
                $this->guestCart->addItem($productId, $qty);
            }
        } catch (Throwable $e) {
            return back()->with('status', $e->getMessage());
        }

        return redirect()->route('cart')->with('status', 'Added to basket.');
    }

    public function updateItem(UpdateCartItemRequest $request): RedirectResponse
    {
        $quantity = (int) $request->validated('quantity');

        try {
            if (Auth::check()) {
                $basketItemId = (string) $request->validated('basket_item_id');
                $this->basketService->updateItemQuantity(Auth::user(), $basketItemId, $quantity);
            } else {
                $productId = (string) $request->validated('product_id');
                $this->guestCart->setQuantity($productId, $quantity);
            }
        } catch (Throwable $e) {
            return redirect()->route('cart')->withErrors([
                'cart' => $e->getMessage() ?: 'Could not update quantity.',
            ]);
        }

        return redirect()->route('cart');
    }

    public function removeItem(RemoveCartItemRequest $request): RedirectResponse
    {
        try {
            if (Auth::check()) {
                $basketItemId = (string) $request->validated('basket_item_id');
                $this->basketService->removeItem(Auth::user(), $basketItemId);
            } else {
                $productId = (string) $request->validated('product_id');
                $this->guestCart->setQuantity($productId, 0);
            }
        } catch (Throwable $e) {
            return redirect()->route('cart')->withErrors([
                'cart' => $e->getMessage() ?: 'Could not remove item.',
            ]);
        }

        return redirect()->route('cart')->with('status', 'Item removed from your cart.');
    }
}
