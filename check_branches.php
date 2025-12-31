<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CareemBranch;

$tenantId = '019abb4e-9bc7-706e-a1fd-989bdc5c1709';

echo "Careem Branches for tenant:\n\n";
$branches = CareemBranch::where('tenant_id', $tenantId)->get();

foreach ($branches as $branch) {
    echo "Branch: {$branch->name}\n";
    echo "  - ID: {$branch->id}\n";
    echo "  - Careem Branch ID: {$branch->careem_branch_id}\n";
    echo "  - Careem Brand ID: {$branch->careem_brand_id}\n";
    echo "  - POS Integration: " . ($branch->pos_integration_enabled ? 'Yes' : 'No') . "\n";
    echo "  - Suggested Catalog ID: catalog_{$branch->careem_branch_id}\n\n";
}
