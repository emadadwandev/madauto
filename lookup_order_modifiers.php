<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = \App\Models\Tenant::where('subdomain', 'dw')->first();
app()->instance('tenant', $tenant);

echo "=== Looking up Order Modifier IDs ===\n\n";

// From the order dump, we have:
$groupIds = [20, 21, 22, 23, 24];
$optionIds = [227, 231, 240, 237];

echo "Group Lookups:\n";
foreach ($groupIds as $id) {
    $group = \DB::table('modifier_groups')->where('id', $id)->where('tenant_id', $tenant->id)->first();
    if ($group) {
        echo "  ID {$id}: {$group->name}\n";
    } else {
        echo "  ID {$id}: NOT FOUND\n";
    }
}

echo "\nOption Lookups:\n";
foreach ($optionIds as $id) {
    $modifier = \DB::table('modifiers')->where('id', $id)->where('tenant_id', $tenant->id)->first();
    if ($modifier) {
        echo "  ID {$id}: {$modifier->name} (Price: +{$modifier->price_adjustment})\n";
    } else {
        echo "  ID {$id}: NOT FOUND\n";
    }
}
