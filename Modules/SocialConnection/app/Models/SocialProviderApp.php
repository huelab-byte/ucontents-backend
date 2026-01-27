<?php

declare(strict_types=1);

namespace Modules\SocialConnection\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialProviderApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'enabled',
        'client_id',
        'client_secret',
        'scopes',
        'extra',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'client_secret' => 'encrypted',
            'scopes' => 'array',
            'extra' => 'array',
        ];
    }
}

