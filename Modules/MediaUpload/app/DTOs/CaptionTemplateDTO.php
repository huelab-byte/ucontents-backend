<?php

declare(strict_types=1);

namespace Modules\MediaUpload\DTOs;

readonly class CaptionTemplateDTO
{
    public function __construct(
        public string $name,
        public string $font = 'Arial',
        public int $fontSize = 32,
        public string $fontWeight = 'regular',
        public string $fontColor = '#FFFFFF',
        public string $outlineColor = '#000000',
        public int $outlineSize = 3,
        public string $position = 'bottom',
        public int $positionOffset = 30,
        public int $wordsPerCaption = 3,
        public bool $wordHighlighting = false,
        public ?string $highlightColor = null,
        public string $highlightStyle = 'text',
        public int $backgroundOpacity = 70,
        public bool $enableAlternatingLoop = false,
        public int $loopCount = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            font: $data['font'] ?? 'Arial',
            fontSize: (int) ($data['font_size'] ?? 32),
            fontWeight: $data['font_weight'] ?? 'regular',
            fontColor: $data['font_color'] ?? '#FFFFFF',
            outlineColor: $data['outline_color'] ?? '#000000',
            outlineSize: (int) ($data['outline_size'] ?? 3),
            position: $data['position'] ?? 'bottom',
            positionOffset: (int) ($data['position_offset'] ?? 30),
            wordsPerCaption: (int) ($data['words_per_caption'] ?? 3),
            wordHighlighting: (bool) ($data['word_highlighting'] ?? false),
            highlightColor: $data['highlight_color'] ?? null,
            highlightStyle: $data['highlight_style'] ?? 'text',
            backgroundOpacity: (int) ($data['background_opacity'] ?? 70),
            enableAlternatingLoop: (bool) ($data['enable_alternating_loop'] ?? false),
            loopCount: (int) ($data['loop_count'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'font' => $this->font,
            'font_size' => $this->fontSize,
            'font_weight' => $this->fontWeight,
            'font_color' => $this->fontColor,
            'outline_color' => $this->outlineColor,
            'outline_size' => $this->outlineSize,
            'position' => $this->position,
            'position_offset' => $this->positionOffset,
            'words_per_caption' => $this->wordsPerCaption,
            'word_highlighting' => $this->wordHighlighting,
            'highlight_color' => $this->highlightColor,
            'highlight_style' => $this->highlightStyle,
            'background_opacity' => $this->backgroundOpacity,
            'enable_alternating_loop' => $this->enableAlternatingLoop,
            'loop_count' => $this->loopCount,
        ];
    }
}
