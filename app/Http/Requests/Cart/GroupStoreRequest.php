<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class GroupStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shop_id' => 'required|integer|exists:shops,id',
            'shop_product_id' => 'required|integer|exists:shop_products,id',
            'quantity' => 'required|numeric',
            'cart_id' => 'nullable|integer|exists:carts,id',
            'user_cart_uuid' => 'nullable|string|exists:user_carts,uuid',
            'name' => 'nullable|string'
        ];
    }

}
