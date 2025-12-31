<?php

require __DIR__.'/vendor/autoload.php';

use App\Services\CareemMenuTransformer;
use App\Models\Location;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get first location or use sample data
$location = Location::first();

if (!$location || empty($location->opening_hours)) {
    echo "No location with opening hours found. Using sample data.\n\n";
    $location = new Location();
    $location->id = 1;
    $location->name = 'Sample Location';
    $location->opening_hours = [
        'monday' => ['open' => '08:00', 'close' => '21:00'],
        'tuesday' => ['open' => '08:00', 'close' => '21:00'],
        'wednesday' => ['open' => '08:00', 'close' => '21:00'],
        'thursday' => ['open' => '08:00', 'close' => '21:00'],
        'friday' => ['open' => '08:00', 'close' => '02:00'], // Overnight
        'saturday' => ['open' => '10:00', 'close' => '23:30'],
        'sunday' => ['open' => '10:00', 'close' => '22:00'],
    ];
}

echo "Location ID: {$location->id}\n";
echo "Location Name: {$location->name}\n\n";

echo "Opening Hours (from DB):\n";
echo json_encode($location->opening_hours, JSON_PRETTY_PRINT) . "\n\n";

// Transform
$transformer = new CareemMenuTransformer();
$operationalHours = $transformer->transformOperationalHours($location->opening_hours);

echo "Transformed Operational Hours (for Careem API):\n";
echo json_encode($operationalHours, JSON_PRETTY_PRINT) . "\n\n";

echo "Payload that would be sent to Careem:\n";
$payload = ['operational_hours' => $operationalHours];
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";
