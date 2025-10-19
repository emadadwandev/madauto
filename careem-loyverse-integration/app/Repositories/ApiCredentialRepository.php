<?php

namespace App\Repositories;

use App\Models\ApiCredential;

class ApiCredentialRepository
{
    /**
     * Get all active credentials for a service.
     * Returns an associative array of credential_type => credential_value
     */
    public function getActiveCredentials(string $service): ?array
    {
        $credentials = ApiCredential::where('service', $service)
            ->where('is_active', true)
            ->get();

        if ($credentials->isEmpty()) {
            return null;
        }

        $result = [];
        foreach ($credentials as $credential) {
            // The model accessor already decrypts the value
            $result[$credential->credential_type] = $credential->credential_value;
        }

        return $result;
    }

    /**
     * Get a single credential value for a service and type.
     */
    public function getCredential(string $service, string $credentialType): ?string
    {
        $apiCredential = ApiCredential::where('service', $service)
            ->where('credential_type', $credentialType)
            ->where('is_active', true)
            ->first();

        if (!$apiCredential) {
            return null;
        }

        // The model accessor already decrypts the value
        return $apiCredential->credential_value;
    }
}
