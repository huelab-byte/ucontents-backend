<?php

declare(strict_types=1);

namespace Modules\Support\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Support\Models\SupportTicketReply;

/**
 * @mixin SupportTicketReply
 */
class SupportTicketReplyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'is_internal' => $this->is_internal,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    $isAdmin = request()->is('api/v1/admin/*');
                    $downloadUrl = $isAdmin 
                        ? url('/api/v1/admin/support/attachments/' . $attachment->storageFile->id . '/download')
                        : url('/api/v1/customer/support/attachments/' . $attachment->storageFile->id . '/download');
                    
                    return [
                        'id' => $attachment->id,
                        'storage_file' => [
                            'id' => $attachment->storageFile->id,
                            'url' => $attachment->storageFile->url,
                            'download_url' => $downloadUrl,
                            'original_name' => $attachment->storageFile->original_name,
                            'size' => $attachment->storageFile->size,
                            'mime_type' => $attachment->storageFile->mime_type,
                        ],
                    ];
                });
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
