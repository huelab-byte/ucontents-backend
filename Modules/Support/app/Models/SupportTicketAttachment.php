<?php

declare(strict_types=1);

namespace Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\StorageManagement\Models\StorageFile;

class SupportTicketAttachment extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'support_ticket_reply_id',
        'storage_file_id',
    ];

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function supportTicketReply(): BelongsTo
    {
        return $this->belongsTo(SupportTicketReply::class, 'support_ticket_reply_id');
    }

    public function storageFile(): BelongsTo
    {
        return $this->belongsTo(StorageFile::class, 'storage_file_id');
    }
}
