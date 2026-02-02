<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services\Posting;

/**
 * Data Transfer Object for posting results
 */
class PostResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalPostId = null,
        public readonly ?string $error = null,
        public readonly ?string $errorCode = null,
        public readonly array $metadata = []
    ) {}

    public static function success(string $externalPostId, array $metadata = []): self
    {
        return new self(
            success: true,
            externalPostId: $externalPostId,
            metadata: $metadata
        );
    }

    public static function failure(string $error, ?string $errorCode = null, array $metadata = []): self
    {
        return new self(
            success: false,
            error: $error,
            errorCode: $errorCode,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'external_post_id' => $this->externalPostId,
            'error' => $this->error,
            'error_code' => $this->errorCode,
            'metadata' => $this->metadata,
        ];
    }
}
