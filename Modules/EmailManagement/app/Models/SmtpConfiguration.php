<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * SMTP Configuration Model
 */
class SmtpConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'is_active',
        'is_default',
        'options',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'options' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get email logs using this SMTP configuration
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Set the password (encrypt it)
     */
    public function setPasswordAttribute(?string $value): void
    {
        if ($value !== null) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get the decrypted password
     */
    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the default SMTP configuration
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Set this configuration as default
     */
    public function setAsDefault(): void
    {
        // Remove default from other configurations
        static::where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get site name from General Settings with fallback
     */
    private function getSiteName(): string
    {
        try {
            if (class_exists(\Modules\GeneralSettings\Services\GeneralSettingsService::class)) {
                $settingsService = app(\Modules\GeneralSettings\Services\GeneralSettingsService::class);
                $siteName = $settingsService->get('branding.site_name');
                if (!empty($siteName)) {
                    return $siteName;
                }
            }
        } catch (\Exception $e) {
            // Fallback to config
        }
        
        return config('app.name', 'uContents');
    }

    /**
     * Get Laravel mail configuration array
     */
    public function toMailConfig(): array
    {
        return [
            'transport' => 'smtp',
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'password' => $this->password,
            'from' => [
                'address' => $this->from_address,
                'name' => $this->from_name ?? $this->getSiteName(),
            ],
        ];
    }
}
