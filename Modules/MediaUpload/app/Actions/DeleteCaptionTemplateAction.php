<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\Models\CaptionTemplate;

class DeleteCaptionTemplateAction
{
    public function execute(CaptionTemplate $template): void
    {
        $template->delete();
    }
}
