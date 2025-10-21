<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TalabatOrderRequest extends FormRequest
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
            // Basic order information
            'order_id' => 'required|string',
            'order' => 'required|array',

            // Order details
            'order.id' => 'nullable|string',
            'order.reference_id' => 'nullable|string',
            'order.items' => 'required|array|min:1',

            // Item validation
            'order.items.*.id' => 'nullable|string',
            'order.items.*.product_id' => 'nullable|string',
            'order.items.*.sku' => 'nullable|string',
            'order.items.*.name' => 'required|string',
            'order.items.*.quantity' => 'required|numeric|min:1',
            'order.items.*.price' => 'nullable|numeric|min:0',
            'order.items.*.unit_price' => 'nullable|numeric|min:0',

            // Pricing (optional - will calculate if not provided)
            'order.pricing' => 'nullable|array',
            'order.pricing.subtotal' => 'nullable|numeric',
            'order.pricing.tax' => 'nullable|numeric',
            'order.pricing.total' => 'nullable|numeric',

            // Payment (optional)
            'order.payment' => 'nullable|array',
            'order.payment.method' => 'nullable|string',

            // Customer info (optional)
            'order.customer' => 'nullable|array',
            'order.customer.name' => 'nullable|string',
            'order.customer.phone' => 'nullable|string',

            // Delivery info (optional)
            'order.delivery' => 'nullable|array',
            'order.delivery.address' => 'nullable|string',

            // Timestamps
            'order.created_at' => 'nullable|string',
            'order.updated_at' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order.required' => 'Order details are required',
            'order.items.required' => 'Order must contain at least one item',
            'order.items.*.name.required' => 'Item name is required',
            'order.items.*.quantity.required' => 'Item quantity is required',
            'order.items.*.quantity.min' => 'Item quantity must be at least 1',
        ];
    }
}
