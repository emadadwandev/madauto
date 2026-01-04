<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Order Details #{{ $order->careem_order_id }}
            </h2>
            <a href="{{ route('orders.index', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Orders
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200">
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Order Header -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Platform</label>
                                <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($order->platform === 'careem') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($order->platform) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Order ID</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $order->careem_order_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Status</label>
                                <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($order->status === 'accepted') bg-blue-100 text-blue-800
                                    @elseif($order->status === 'ready') bg-green-100 text-green-800
                                    @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Created At</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $order->created_at->format('Y-m-d H:i:s') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mb-6 flex gap-3">
                        @if($order->platform_status !== 'accepted' && $order->platform_status !== 'ready' && $order->platform_status !== 'cancelled')
                        <form action="{{ route('orders.accept', ['subdomain' => request()->route('subdomain'), 'order' => $order->id]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Accept Order
                            </button>
                        </form>
                        @endif

                        @if($order->platform_status === 'accepted' && $order->platform_status !== 'ready' && $order->platform_status !== 'cancelled')
                        <form action="{{ route('orders.mark-ready', ['subdomain' => request()->route('subdomain'), 'order' => $order->id]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Mark Ready
                            </button>
                        </form>
                        @endif

                        @if($order->platform_status !== 'cancelled')
                        <form action="{{ route('orders.cancel', ['subdomain' => request()->route('subdomain'), 'order' => $order->id]) }}" method="POST" class="inline"
                              onsubmit="return confirm('Are you sure you want to cancel this order?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel Order
                            </button>
                        </form>
                        @endif
                    </div>

                    <!-- Order Data -->
                    <div class="space-y-6">
                        @php
                            $data = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;
                            $branch = $data['branch'] ?? null;
                            $price = $data['price'] ?? null;
                            $items = $data['items'] ?? [];
                            $customer = $data['customer'] ?? null;
                            $delivery_address = $data['delivery_address'] ?? null;
                        @endphp

                        <!-- Branch Information -->
                        @if($branch)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Branch Information</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Branch Name</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $branch['name'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Branch ID</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $branch['id'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">State</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $branch['state'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Order Details -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Details</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Delivery Type</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ ucfirst($data['delivery_type'] ?? 'N/A') }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Merchant Pay Type</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ ucfirst($data['merchant_pay_type'] ?? 'N/A') }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Order Status</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ ucfirst($data['status'] ?? 'N/A') }}</p>
                                    </div>
                                    @if(isset($data['notes']) && !empty($data['notes']))
                                    <div class="col-span-3">
                                        <label class="block text-sm font-medium text-gray-500">Notes</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $data['notes'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        @if($customer)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Information</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    @if(isset($customer['name']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Name</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $customer['name'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($customer['phone']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Phone</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $customer['phone'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($customer['email']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Email</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $customer['email'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Delivery Address -->
                        @if($delivery_address)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Delivery Address</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    @if(isset($delivery_address['title']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Title</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $delivery_address['title'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($delivery_address['address']))
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-500">Address</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $delivery_address['address'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($delivery_address['district']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">District</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $delivery_address['district'] }}</p>
                                    </div>
                                    @endif
                                    @if(isset($delivery_address['city']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">City</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $delivery_address['city'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Order Items -->
                        @if(!empty($items))
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Items</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($items as $item)
                                            @php
                                                // API response uses: id, unit_price, item_price, total_price, groups
                                                // Webhook uses: name, price, options/modifiers
                                                $itemId = $item['id'] ?? 'Unknown';

                                                // Try to lookup item name from multiple sources
                                                $itemName = $item['name'] ?? null;
                                                $catalogItem = null;
                                                $productMapping = null;

                                                if (!$itemName && $itemId && $itemId !== 'Unknown') {
                                                    // First try catalog items table
                                                    $catalogItem = \App\Models\CareemCatalogItem::findByItemId((string)$itemId);
                                                    if ($catalogItem) {
                                                        $itemName = $catalogItem->name;
                                                    } else {
                                                        // Then try product mappings table (which stores platform_name)
                                                        $productMapping = \App\Models\ProductMapping::where('platform', 'careem')
                                                            ->where('platform_product_id', (string)$itemId)
                                                            ->where('tenant_id', tenant()->id)
                                                            ->first();
                                                        $itemName = $productMapping ? $productMapping->platform_name : "Item #$itemId";
                                                    }
                                                } elseif (!$itemName) {
                                                    $itemName = "Item #$itemId";
                                                }

                                                // Lookup SKU from catalog/mapping if not provided
                                                $itemSku = $item['sku'] ?? null;
                                                if (!$itemSku) {
                                                    if ($catalogItem) {
                                                        $itemSku = $catalogItem->sku ?? ($item['id'] ?? 'N/A');
                                                    } elseif ($productMapping) {
                                                        $itemSku = $productMapping->platform_sku ?? ($item['id'] ?? 'N/A');
                                                    } else {
                                                        $itemSku = $item['id'] ?? 'N/A';
                                                    }
                                                }

                                                $itemQuantity = $item['quantity'] ?? 1;
                                                $itemUnitPrice = $item['unit_price'] ?? ($item['price'] ?? 0);
                                                $itemTotalPrice = $item['total_price'] ?? ($item['item_price'] ?? ($itemUnitPrice * $itemQuantity));
                                                $itemNotes = $item['notes'] ?? ($item['special_instructions'] ?? '');

                                                // Support: groups (API), options (API alt), modifiers (webhook)
                                                $itemModifiers = $item['groups'] ?? ($item['options'] ?? ($item['modifiers'] ?? []));
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <p class="text-sm font-medium text-gray-900">{{ $itemName }}</p>
                                                    @if(!empty($itemNotes))
                                                    <p class="text-xs text-gray-500 mt-1">Note: {{ $itemNotes }}</p>
                                                    @endif
                                                    @if(!empty($itemModifiers))
                                                    <div class="mt-2 space-y-1">
                                                        @foreach($itemModifiers as $modifier)
                                                        @php
                                                            $modifierName = $modifier['name'] ?? $modifier['group_name'] ?? null;
                                                            $modifierId = $modifier['id'] ?? $modifier['group_id'] ?? null;
                                                            $modifierDisplayName = $modifierName ?? ($modifierId ? "Modifier #$modifierId" : 'Modifier');
                                                            $modifierPrice = $modifier['price'] ?? $modifier['modifier_price'] ?? 0;
                                                            $modifierQty = $modifier['quantity'] ?? 1;
                                                            // Handle nested options within groups
                                                            $modifierOptions = $modifier['options'] ?? [];
                                                        @endphp
                                                        @if(!empty($modifierOptions))
                                                            <p class="text-xs font-medium text-gray-700 mt-1">{{ $modifierDisplayName }}:</p>
                                                            @foreach($modifierOptions as $option)
                                                            @php
                                                                // Try multiple fields for option name/id
                                                                $optionName = $option['name'] ?? $option['option_name'] ?? null;
                                                                $optionId = $option['id'] ?? $option['option_id'] ?? null;

                                                                // If no name, try to display ID or show as "Option"
                                                                $displayName = $optionName ?? ($optionId ? "Option #$optionId" : 'Option');
                                                            @endphp
                                                            <p class="text-xs text-gray-600 ml-2">+ {{ $displayName }}
                                                                @if(isset($option['price']) && $option['price'] > 0)
                                                                (+{{ number_format($option['price'], 2) }})
                                                                @endif
                                                                @if(isset($option['quantity']) && $option['quantity'] > 1)
                                                                x{{ $option['quantity'] }}
                                                                @endif
                                                            </p>
                                                            @endforeach
                                                        @else
                                                            <p class="text-xs text-gray-600">+ {{ $modifierDisplayName }}
                                                                @if($modifierPrice > 0)
                                                                (+{{ number_format($modifierPrice, 2) }})
                                                                @endif
                                                                @if($modifierQty > 1)
                                                                x{{ $modifierQty }}
                                                                @endif
                                                            </p>
                                                        @endif
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-500">{{ $itemSku }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 text-center">{{ $itemQuantity }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($itemUnitPrice, 2) }}</td>
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">
                                                    {{ number_format($itemTotalPrice, 2) }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Price Breakdown -->
                        @if($price)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Price Summary</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Original Total:</span>
                                        <span class="text-gray-900 font-medium">{{ number_format($price['original_total_price'] ?? 0, 2) }}</span>
                                    </div>
                                    @if(isset($price['merchant_discount_amount']) && $price['merchant_discount_amount'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Merchant Discount:</span>
                                        <span class="text-red-600">-{{ number_format($price['merchant_discount_amount'], 2) }}</span>
                                    </div>
                                    @endif
                                    @if(isset($price['careem_discount_amount']) && $price['careem_discount_amount'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Careem Discount:</span>
                                        <span class="text-red-600">-{{ number_format($price['careem_discount_amount'], 2) }}</span>
                                    </div>
                                    @endif
                                    @if(isset($price['free_delivery_discount_value']) && $price['free_delivery_discount_value'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Free Delivery Discount:</span>
                                        <span class="text-red-600">-{{ number_format($price['free_delivery_discount_value'], 2) }}</span>
                                    </div>
                                    @endif
                                    @if(isset($price['merchant_promo_amount']) && $price['merchant_promo_amount'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Merchant Promo:</span>
                                        <span class="text-red-600">-{{ number_format($price['merchant_promo_amount'], 2) }}</span>
                                    </div>
                                    @endif
                                    @if(isset($price['careem_promo_amount']) && $price['careem_promo_amount'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Careem Promo:</span>
                                        <span class="text-red-600">-{{ number_format($price['careem_promo_amount'], 2) }}</span>
                                    </div>
                                    @endif
                                    @if(isset($price['tax_percentage']) && $price['tax_percentage'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Tax ({{ $price['tax_percentage'] }}%):</span>
                                        <span class="text-gray-900">{{ number_format($price['total_taxable_price'] ?? 0, 2) }}</span>
                                    </div>
                                    @endif
                                    @if(isset($price['delivery_fee']) && $price['delivery_fee'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Delivery Fee:</span>
                                        <span class="text-gray-900">{{ number_format($price['delivery_fee'], 2) }}</span>
                                    </div>
                                    @endif
                                    @if(isset($price['service_fee']) && $price['service_fee'] > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Service Fee:</span>
                                        <span class="text-gray-900">{{ number_format($price['service_fee'], 2) }}</span>
                                    </div>
                                    @endif
                                    <div class="border-t border-gray-300 pt-2 mt-2">
                                        <div class="flex justify-between text-base font-semibold">
                                            <span class="text-gray-900">Total Amount:</span>
                                            <span class="text-gray-900">{{ number_format($price['total_amount'] ?? 0, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Raw Data (Collapsible) -->
                        <div>
                            <details class="group">
                                <summary class="cursor-pointer list-none">
                                    <div class="flex items-center justify-between p-4 bg-gray-100 rounded-lg hover:bg-gray-200">
                                        <h3 class="text-sm font-medium text-gray-700">View Raw JSON Data</h3>
                                        <svg class="w-5 h-5 text-gray-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </summary>
                                <div class="mt-2 bg-gray-50 rounded-lg p-4">
                                    <pre class="text-xs text-gray-700 overflow-auto max-h-96">{{ json_encode($order->order_data, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </details>
                        </div>
                    </div>

                        @if($order->loyverseOrder)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Loyverse Sync Status</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Sync Status</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $order->loyverseOrder->sync_status }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Receipt Number</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $order->loyverseOrder->receipt_number ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Synced At</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $order->loyverseOrder->synced_at ? $order->loyverseOrder->synced_at->format('Y-m-d H:i:s') : 'N/A' }}</p>
                                    </div>
                                    @if($order->loyverseOrder->error_message)
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-500">Error Message</label>
                                        <p class="mt-1 text-sm text-red-600">{{ $order->loyverseOrder->error_message }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
