<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewCommentRequest;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\Review;
use App\Services\Review\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewWebController extends Controller
{
    public function __construct(protected ReviewService $service)
    {
    }

    public function store(StoreReviewRequest $request, string $id): RedirectResponse
    {
        $body = trim((string) ($request->validated('review_body') ?? ''));

        $this->service->create(Auth::user(), [
            'product_id' => $id,
            'rating' => (int) $request->validated('rating'),
            'review_body' => $body !== '' ? $body : null,
        ]);

        return redirect()
            ->route('products.show', $id)
            ->with('status', 'Thank you — your review has been posted.');
    }

    public function storeComment(StoreReviewCommentRequest $request, string $reviewId): RedirectResponse
    {
        $review = Review::query()->findOrFail($reviewId);

        $this->service->createComment(
            Auth::user(),
            $review,
            $request->validated('comment_body')
        );

        return redirect()
            ->route('products.show', $review->product_id)
            ->with('status', 'Your comment has been posted.');
    }
}
