<?php

declare(strict_types=1);

namespace Modules\SocialConnection\DTOs;

/**
 * DTO for creating a social connection group
 */
class CreateGroupDTO
{
    public function __construct(
        public readonly string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
        );
    }
}
