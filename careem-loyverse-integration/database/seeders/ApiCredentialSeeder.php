<?php

namespace Database\Seeders;

use App\Models\ApiCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class ApiCredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApiCredential::create([
            'service' => 'loyverse',
            'credentials' => Crypt::encryptString(json_encode([
                'token' => env('LOYVERSE_ACCESS_TOKEN'),
            ])),
            'is_active' => true,
        ]);

        ApiCredential::create([
            'service' => 'careem',
            'credentials' => Crypt::encryptString(json_encode([
                'webhook_secret' => env('CAREEM_WEBHOOK_SECRET'),
            ])),
            'is_active' => true,
        ]);
    }
}
