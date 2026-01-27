<?php

declare(strict_types=1);

namespace Modules\AiIntegration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Models\User;

/**
 * AI Prompt Template Model
 * 
 * Stores reusable prompt templates with variables
 */
class AiPromptTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'template',
        'variables',
        'category',
        'provider_slug',
        'model',
        'settings',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Render template with provided variables
     */
    public function render(array $variables = []): string
    {
        $template = $this->template;
        
        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", (string) $value, $template);
            $template = str_replace("{{ $key }}", (string) $value, $template);
        }

        return $template;
    }

    /**
     * Check if template can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system;
    }
}
