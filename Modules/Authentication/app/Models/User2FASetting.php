<?php

declare(strict_types=1);

namespace Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Models\User;

/**
 * User 2FA Settings Model
 */
class User2FASetting extends Model
{
    use HasFactory;

    protected $table = 'user_2fa_settings';

    protected $fillable = [
        'user_id',
        'enabled',
        'secret_key',
        'backup_codes',
        'enabled_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'backup_codes' => 'array',
        'enabled_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_key',
        'backup_codes',
    ];

    /**
     * Get the user that owns these 2FA settings
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if 2FA is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Use a backup code (remove it from the list)
     */
    public function useBackupCode(string $code): bool
    {
        $codes = $this->backup_codes ?? [];
        $index = array_search($code, $codes);

        if ($index !== false) {
            unset($codes[$index]);
            $this->update(['backup_codes' => array_values($codes)]);
            return true;
        }

        return false;
    }
}
