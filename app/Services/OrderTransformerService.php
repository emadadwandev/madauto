<?php

namespace App\Services;

use App\Models\SyncLog;
use Illuminate\Support\Facades\Log;

class OrderTransformerService
{
    protected $productMappingService;

    protected $loyverseApiService;

    public function __construct(
        ProductMappingService $productMappingService,
        LoyverseApiService $loyverseApiService
    ) {
        $this->productMappingService = $productMappingService;
        $this->loyverseApiService = $loyverseApiService;
    }

    /**
     * Transform delivery platform order to Loyverse receipt format.
     *
     * @param  array  $orderPayload  Full order data from platform
     * @param  int|null  $orderId  Order ID for logging
     * @return array Transformed receipt data ready for Loyverse API
     *
     * @throws \Exception If transformation fails
     */
    public function transform(array $orderPayload, ?int $orderId = null): array
    {
        try {
            // Detect platform from Order model if orderId provided
            $platform = 'careem'; // default
            if ($orderId) {
                $order = \App\Models\Order::find($orderId);
                if ($order) {
                    $platform = $order->platform;
                }
            }

            // Extract order data
            $orderData = $orderPayload['order'] ?? $orderPayload;
            $items = $orderData['items'] ?? [];
            $pricing = $orderData['pricing'] ?? [];
            $payment = $orderData['payment'] ?? [];

            // Get platform-specific customer
            $customer = $platform === 'talabat'
                ? $this->loyverseApiService->findOrCreateTalabatCustomer()
                : $this->loyverseApiService->findOrCreateCareemCustomer();

            // Map items to Loyverse format
            $mappingResult = $this->productMappingService->mapOrderItems($items, $orderId, $platform);
            $mappedItems = $mappingResult['mapped'];
            $unmappedItems = $mappingResult['unmapped'];

            // Log unmapped items
            if (! empty($unmappedItems) && $orderId) {
                SyncLog::logFailure(
                    $orderId,
                    'product_mapping',
                    count($unmappedItems).' products could not be mapped',
                    ['unmapped_items' => $unmappedItems]
                );

                Log::warning('Unmapped products in order', [
                    'order_id' => $orderId,
                    'careem_order_id' => $careemOrder['order_id'] ?? null,
                    'unmapped_count' => count($unmappedItems),
                    'unmapped_items' => $unmappedItems,
                ]);
            }

            // If no items could be mapped, throw exception
            if (empty($mappedItems)) {
                throw new \Exception('No products could be mapped to Loyverse items. Please configure product mappings.');
            }

            // Transform items to Loyverse line_items format
            $lineItems = [];
            foreach ($mappedItems as $mappedItem) {
                $originalItem = $mappedItem['original_item'];

                $lineItem = [
                    'item_id' => $mappedItem['loyverse_item_id'],
                    'quantity' => $mappedItem['quantity'],
                    'price' => $mappedItem['price'],
                ];

                // Add variant ID if available
                if (! empty($mappedItem['loyverse_variant_id'])) {
                    $lineItem['variant_id'] = $mappedItem['loyverse_variant_id'];
                }

                // Add line note for special instructions or modifiers
                $lineNotes = [];
                if (! empty($originalItem['special_instructions'])) {
                    $lineNotes[] = $originalItem['special_instructions'];
                }
                if (! empty($originalItem['modifiers'])) {
                    foreach ($originalItem['modifiers'] as $modifier) {
                        $lineNotes[] = $modifier['name'] ?? 'Modifier';
                    }
                }
                if (! empty($lineNotes)) {
                    $lineItem['line_note'] = implode(', ', $lineNotes);
                }

                $lineItems[] = $lineItem;
            }

            // Use platform-specific payment type
            $paymentTypeName = ucfirst($platform); // 'Careem' or 'Talabat'
            $paymentType = $this->loyverseApiService->getPaymentTypeByName($paymentTypeName);

            if (! $paymentType) {
                // If platform payment type doesn't exist, fall back to default
                Log::warning("{$paymentTypeName} payment type not found in Loyverse", [
                    'order_id' => $orderId,
                    'platform' => $platform,
                    'message' => "Please create a payment type named '{$paymentTypeName}' in Loyverse POS for better tracking",
                ]);

                if ($orderId) {
                    SyncLog::logWarning(
                        $orderId,
                        'payment_mapping',
                        "{$paymentTypeName} payment type not found in Loyverse. Using default payment type. Please create a payment type named '{$paymentTypeName}' in Loyverse.",
                        [
                            'platform' => $platform,
                            'suggestion' => "Create '{$paymentTypeName}' payment type in Loyverse POS",
                        ]
                    );
                }

                // Fall back to first available payment type
                $paymentTypes = $this->loyverseApiService->getPaymentTypes();
                $paymentType = $paymentTypes[0] ?? null;

                if (! $paymentType) {
                    throw new \Exception('No payment types configured in Loyverse');
                }
            }

            // Build receipt data
            $platformName = ucfirst($platform);
            $receipt = [
                'receipt_type' => config('loyverse.receipt_defaults.receipt_type', 'SALE'),
                'receipt_date' => isset($orderData['created_at'])
                    ? date('c', strtotime($orderData['created_at']))
                    : now()->toIso8601String(),
                'note' => "{$platformName} Order: ".($orderPayload['order_id'] ?? 'N/A'),
                'source' => config('loyverse.receipt_defaults.source', 'API'),
                'dining_option' => config('loyverse.receipt_defaults.dining_option', 'DELIVERY'),
                'customer_id' => $customer['id'],
                'line_items' => $lineItems,
                'payments' => [
                    [
                        'payment_type_id' => $paymentType['id'],
                        'amount' => $pricing['total'] ?? $this->calculateTotal($lineItems),
                    ],
                ],
            ];

            // Add optional fields if configured
            if ($storeId = config('loyverse.store_id')) {
                $receipt['store_id'] = $storeId;
            }

            if ($posDeviceId = config('loyverse.pos_device_id')) {
                $receipt['pos_device_id'] = $posDeviceId;
            }

            if ($employeeId = config('loyverse.employee_id')) {
                $receipt['employee_id'] = $employeeId;
            }

            // Log successful transformation
            if ($orderId) {
                SyncLog::logSuccess(
                    $orderId,
                    'order_transform',
                    'Order transformed successfully',
                    [
                        'mapped_items_count' => count($mappedItems),
                        'unmapped_items_count' => count($unmappedItems),
                        'total_amount' => $receipt['payments'][0]['amount'],
                    ]
                );
            }

            return $receipt;

        } catch (\Exception $e) {
            if ($orderId) {
                SyncLog::logFailure(
                    $orderId,
                    'order_transform',
                    'Order transformation failed: '.$e->getMessage(),
                    [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            Log::error('Order transformation failed', [
                'order_id' => $orderId,
                'platform_order_id' => $orderPayload['order_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate total from line items.
     */
    protected function calculateTotal(array $lineItems): float
    {
        $total = 0;

        foreach ($lineItems as $item) {
            $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }

        return round($total, 2);
    }

    /**
     * Validate Careem order structure.
     */
    public function validateCareemOrder(array $careemOrder): bool
    {
        // Check required fields
        $required = ['order_id', 'order'];

        foreach ($required as $field) {
            if (! isset($careemOrder[$field])) {
                return false;
            }
        }

        // Check order.items exists and is an array
        $order = $careemOrder['order'] ?? [];
        if (! isset($order['items']) || ! is_array($order['items']) || empty($order['items'])) {
            return false;
        }

        return true;
    }

    /**
     * Get transformation summary (for logging/debugging).
     */
    public function getTransformationSummary(array $careemOrder, ?int $orderId = null): array
    {
        $orderData = $careemOrder['order'] ?? $careemOrder;
        $items = $orderData['items'] ?? [];

        $mappingResult = $this->productMappingService->mapOrderItems($items, $orderId);

        return [
            'total_items' => count($items),
            'mapped_items' => count($mappingResult['mapped']),
            'unmapped_items' => count($mappingResult['unmapped']),
            'unmapped_details' => $mappingResult['unmapped'],
            'can_process' => ! empty($mappingResult['mapped']),
        ];
    }
}
