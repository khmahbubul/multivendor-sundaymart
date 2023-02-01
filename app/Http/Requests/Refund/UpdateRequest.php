<?php

namespace App\Http\Requests\Refund;

use App\Models\Refund;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'order_id' => 'required|integer|exists:orders,id',
            'user_id' => 'required|integer|exists:users,id',
            'status' => ['required','string',Rule::in(Refund::STATUS)],
            'message_seller' => 'nullable|string',
            'message_user' => 'nullable|string',
            'images' => 'nullable|array',
        ];
    }
}
