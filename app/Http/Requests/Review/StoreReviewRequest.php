<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->customer !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['sometimes', 'required', 'string', 'max:20'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_body' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

