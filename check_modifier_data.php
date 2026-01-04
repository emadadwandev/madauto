<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = \App\Models\Tenant::where('subdomain', 'dw')->first();
app()->instance('tenant', $tenant);

echo "=== Modifier Data Check ===\n\n";

$groupCount = \DB::table('modifier_groups')->where('tenant_id', $tenant->id)->count();
$modifierCount = \DB::table('modifiers')->where('tenant_id', $tenant->id)->count();

echo "Modifier Groups: {$groupCount}\n";
echo "Modifiers: {$modifierCount}\n\n";

if ($groupCount > 0) {
    echo "=== Sample Modifier Group ===\n";
    $group = \DB::table('modifier_groups')->where('tenant_id', $tenant->id)->first();
    print_r($group);

    echo "\n=== Sample Modifier ===\n";
    $modifier = \DB::table('modifiers')->where('tenant_id', $tenant->id)->first();
    print_r($modifier);
}
