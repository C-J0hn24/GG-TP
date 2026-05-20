<?php

namespace App\Http\Requests\Basket;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'string', 'max:20', 'required_without:basket_item_id'],
            'basket_item_id' => ['nullable', 'string', 'max:20', 'required_without:product_id'],
        ];
    }
}
