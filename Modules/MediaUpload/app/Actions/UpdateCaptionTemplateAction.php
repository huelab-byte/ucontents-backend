<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Actions;

use Modules\MediaUpload\Models\CaptionTemplate;

class UpdateCaptionTemplateAction
{
    public function execute(CaptionTemplate $template, array $data): CaptionTemplate
    {
        $allowed = [
            'name', 'font', 'font_size', 'font_weight', 'font_color', 'outline_color', 'outline_size',
            'position', 'position_offset', 'words_per_caption', 'word_highlighting', 'highlight_color',
            'highlight_style', 'background_opacity', 'enable_alternating_loop', 'loop_count',
        ];
        $payload = array_intersect_key($data, array_flip($allowed));
        $template->update($payload);
        return $template->fresh();
    }
}
