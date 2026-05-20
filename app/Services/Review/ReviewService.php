<?php

namespace App\Services\Review;

use App\Models\Order;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use App\Support\OracleId;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Customer has bought this product at least once (non-cancelled order).
     */
    public function hasPurchasedProduct(User $user, string $productId): bool
    {
        if (! $user->customer) {
            return false;
        }

        return Order::query()
            ->where('customer_id', $user->user_id)
            ->where('status', '!=', 'cancelled')
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->exists();
    }

    public function create(User $user, array $data): Review
    {
        $customer = $user->customer;
        abort_if(! $customer, 403, 'Only customer accounts can leave reviews.');

        $productId = $data['product_id'];

        if (! $this->hasPurchasedProduct($user, $productId)) {
            throw ValidationException::withMessages([
                'review' => 'You can leave a review after you have purchased this product at least once.',
            ]);
        }

        $body = isset($data['review_body']) ? trim((string) $data['review_body']) : '';

        return Review::create([
            'review_id' => OracleId::next('REVIEW', 'review_id', 'RE'),
            'rating' => $data['rating'],
            'review_body' => $body !== '' ? $body : null,
            'review_date' => now(),
            'customer_id' => $customer->customer_id,
            'product_id' => $productId,
        ]);
    }

    public function createComment(User $user, Review $review, string $body): ReviewComment
    {
        $customer = $user->customer;
        abort_if(! $customer, 403, 'Only customer accounts can comment on reviews.');

        if (! $this->hasPurchasedProduct($user, $review->product_id)) {
            throw ValidationException::withMessages([
                'comment' => 'You can comment after you have purchased this product at least once.',
            ]);
        }

        return ReviewComment::create([
            'comment_id' => OracleId::next('REVIEW_COMMENT', 'comment_id', 'RC'),
            'review_id' => $review->review_id,
            'comment_body' => $body,
            'comment_date' => now(),
            'customer_id' => $customer->customer_id,
        ]);
    }

    public function traderReply(Review $review, string $body): Review
    {
        $review->trader_reply = $body;
        $review->trader_reply_date = now();
        $review->save();

        return $review;
    }
}
