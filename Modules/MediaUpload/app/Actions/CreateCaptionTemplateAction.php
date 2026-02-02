<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\DTOs\CaptionTemplateDTO;
use Modules\MediaUpload\Models\CaptionTemplate;

class CreateCaptionTemplateAction
{
    public function execute(CaptionTemplateDTO $dto, ?int $userId = null): CaptionTemplate
    {
        $userId = $userId ?? auth()->id();
        return CaptionTemplate::create(array_merge($dto->toArray(), ['user_id' => $userId]));
    }
}
