<?php

declare(strict_types=1);

namespace Modules\SocialConnection\DTOs;

/**
 * DTO for bulk assigning channels to a group
 */
class BulkAssignGroupDTO
{
    public function __construct(
        public readonly array $channelIds,
        public readonly ?int $groupId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            channelIds: $data['channel_ids'] ?? [],
            groupId: $data['group_id'] ?? null,
        );
    }
}
