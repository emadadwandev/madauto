<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Look at the raw webhook logs
$logs = \Illuminate\Support\Facades\Storage::disk('local')->files('webhooks');

echo "=== Recent Webhook Files ===\n";
foreach (array_slice($logs, -5) as $log) {
    echo "- $log\n";
}

if (!empty($logs)) {
    $latestLog = array_pop($logs);
    echo "\n=== Latest Webhook ($latestLog) ===\n";
    $content = \Illuminate\Support\Facades\Storage::disk('local')->get($latestLog);
    echo $content;
}
