<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CareemOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|string',
            'order' => 'required|array',
            'order.id' => 'required|string',
            'order.items' => 'required|array',
            'order.items.*.product_id' => 'required|string',
            'order.items.*.name' => 'required|string',
            'order.items.*.quantity' => 'required|numeric',
            'order.items.*.price' => 'required|numeric',
        ];
    }
}
