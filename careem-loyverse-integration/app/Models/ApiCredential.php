<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ApiCredential extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service',
        'credential_type',
        'credential_value',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_credentials';

    /**
     * Get the credential_value attribute (decrypt).
     */
    public function getCredentialValueAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt credential', [
                'service' => $this->service,
                'credential_type' => $this->credential_type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set the credential_value attribute (encrypt).
     */
    public function setCredentialValueAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['credential_value'] = null;
            return;
        }

        $this->attributes['credential_value'] = Crypt::encryptString($value);
    }

    /**
     * Scope a query to only include active credentials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by service.
     */
    public function scopeForService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope a query to filter by credential type.
     */
    public function scopeForCredentialType($query, string $credentialType)
    {
        return $query->where('credential_type', $credentialType);
    }

    /**
     * Get active credentials for a specific service.
     * Returns an associative array of credential_type => credential_value
     */
    public static function getActiveCredentials(string $service): ?array
    {
        $credentials = self::active()
            ->forService($service)
            ->get();

        if ($credentials->isEmpty()) {
            return null;
        }

        $result = [];
        foreach ($credentials as $credential) {
            $result[$credential->credential_type] = $credential->credential_value;
        }

        return $result;
    }

    /**
     * Get a single credential value for a service and type.
     */
    public static function getCredential(string $service, string $credentialType): ?string
    {
        $credential = self::active()
            ->forService($service)
            ->forCredentialType($credentialType)
            ->first();

        return $credential ? $credential->credential_value : null;
    }

    /**
     * Store or update credentials for a service.
     * Accepts an array of credential_type => credential_value pairs.
     */
    public static function storeCredentials(string $service, array $credentials, bool $isActive = true): void
    {
        foreach ($credentials as $credentialType => $credentialValue) {
            self::updateOrCreate(
                [
                    'service' => $service,
                    'credential_type' => $credentialType,
                ],
                [
                    'credential_value' => $credentialValue,
                    'is_active' => $isActive,
                ]
            );
        }
    }

    /**
     * Store or update a single credential.
     */
    public static function storeCredential(string $service, string $credentialType, string $credentialValue, bool $isActive = true): self
    {
        return self::updateOrCreate(
            [
                'service' => $service,
                'credential_type' => $credentialType,
            ],
            [
                'credential_value' => $credentialValue,
                'is_active' => $isActive,
            ]
        );
    }

    /**
     * Deactivate all credentials for a service.
     */
    public static function deactivateService(string $service): bool
    {
        return self::where('service', $service)->update(['is_active' => false]) > 0;
    }

    /**
     * Activate all credentials for a service.
     */
    public static function activateService(string $service): bool
    {
        return self::where('service', $service)->update(['is_active' => true]) > 0;
    }

    /**
     * Delete all credentials for a service.
     */
    public static function deleteService(string $service): bool
    {
        return self::where('service', $service)->delete() > 0;
    }
}
