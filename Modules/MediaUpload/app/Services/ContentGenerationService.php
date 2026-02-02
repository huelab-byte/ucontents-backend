<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Services;

use Modules\MediaUpload\Models\MediaUploadContentSettings;
use Modules\MediaUpload\Models\CaptionTemplate;
use Modules\MediaUpload\Actions\GenerateContentFromTitleAction;
use Modules\MediaUpload\Actions\GenerateContentFromFramesAction;
use Modules\MediaUpload\Actions\GenerateContentFromPromptAction;
use Modules\MediaUpload\Actions\GenerateInVideoCaptionAction;
use Modules\MediaUpload\Actions\ExtractAndMergeFramesAction;
use Illuminate\Support\Facades\Log;

class ContentGenerationService
{
    public function __construct(
        private GenerateContentFromTitleAction $fromTitleAction,
        private GenerateContentFromFramesAction $fromFramesAction,
        private GenerateContentFromPromptAction $fromPromptAction,
        private GenerateInVideoCaptionAction $inVideoCaptionAction,
        private ExtractAndMergeFramesAction $extractFramesAction
    ) {}

    /**
     * Generate youtube_heading, social_caption, hashtags from video/title based on folder content settings.
     */
    public function generate(
        string $videoPath,
        string $title,
        MediaUploadContentSettings $settings,
        ?int $userId = null
    ): array {
        $opts = [
            'heading_length' => $settings->heading_length,
            'heading_emoji' => $settings->heading_emoji,
            'caption_length' => $settings->caption_length,
            'hashtag_count' => $settings->hashtag_count,
        ];

        return match ($settings->content_source_type) {
            'title' => $this->fromTitleAction->execute($title, $opts, $userId),
            'frames' => $this->generateFromFrames($videoPath, $title, $opts, $userId),
            'prompt' => $this->fromPromptAction->execute(
                $settings->custom_prompt ?? 'Describe this video.',
                $opts,
                $userId
            ),
            default => $this->fromTitleAction->execute($title, $opts, $userId),
        };
    }

    private function generateFromFrames(string $videoPath, string $title, array $opts, ?int $userId): array
    {
        $mergedPath = $this->extractFramesAction->execute($videoPath);
        try {
            return $this->fromFramesAction->execute($mergedPath, $title, $opts, $userId);
        } finally {
            if ($mergedPath && file_exists($mergedPath)) {
                @unlink($mergedPath);
            }
        }
    }

    /**
     * Generate a short, punchy in-video caption for burning onto the video.
     * Uses the selected content source (title, frames, or prompt) for video context.
     * Respects words_per_caption from caption config (upload settings) or template.
     *
     * @param array|null $captionConfig Optional config from upload (words_per_caption, etc.)
     */
    public function generateInVideoCaption(
        string $videoPath,
        string $title,
        MediaUploadContentSettings $settings,
        ?CaptionTemplate $captionTemplate,
        ?int $userId = null,
        ?array $captionConfig = null
    ): string {
        $wordsPerCaption = 3;
        if (is_array($captionConfig) && array_key_exists('words_per_caption', $captionConfig) && $captionConfig['words_per_caption'] !== null && $captionConfig['words_per_caption'] !== '') {
            $wordsPerCaption = max(1, min(20, (int) $captionConfig['words_per_caption']));
        } elseif ($captionTemplate) {
            $wordsPerCaption = max(1, min(20, (int) $captionTemplate->words_per_caption));
        }

        try {
            return match ($settings->content_source_type) {
                'frames' => $this->generateInVideoCaptionFromFrames($videoPath, $title, $wordsPerCaption, $userId),
                'prompt' => $this->inVideoCaptionAction->executeFromText(
                    $settings->custom_prompt ?? $title,
                    $wordsPerCaption,
                    $userId
                ),
                default => $this->inVideoCaptionAction->executeFromText($title, $wordsPerCaption, $userId),
            };
        } catch (\Throwable $e) {
            Log::warning('In-video caption generation failed, using title', [
                'error' => $e->getMessage(),
                'title' => $title,
            ]);
            $words = preg_split('/\s+/u', trim($title), $wordsPerCaption + 1, PREG_SPLIT_NO_EMPTY);
            return implode(' ', array_slice($words, 0, $wordsPerCaption)) ?: 'Watch this';
        }
    }

    private function generateInVideoCaptionFromFrames(
        string $videoPath,
        string $title,
        int $wordsPerCaption,
        ?int $userId
    ): string {
        $mergedPath = $this->extractFramesAction->execute($videoPath);
        try {
            return $this->inVideoCaptionAction->executeFromFrames($mergedPath, $title, $wordsPerCaption, $userId);
        } finally {
            if ($mergedPath && file_exists($mergedPath)) {
                @unlink($mergedPath);
            }
        }
    }
}
