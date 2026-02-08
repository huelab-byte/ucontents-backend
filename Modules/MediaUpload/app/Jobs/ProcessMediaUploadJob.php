<?php

declare(strict_types=1);

namespace Modules\MediaUpload\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Modules\MediaUpload\Models\MediaUploadQueue;
use Modules\MediaUpload\Models\MediaUpload;
use Modules\MediaUpload\Models\MediaUploadFolder;
use Modules\MediaUpload\Models\MediaUploadContentSettings;
use Modules\MediaUpload\Services\ContentGenerationService;
use Modules\MediaUpload\Services\VideoProcessingService;
use Modules\MediaUpload\Services\CaptionBurnService;
use Modules\MediaUpload\Actions\UpsertContentSettingsAction;
use Modules\StorageManagement\Actions\UploadFileAction;

class ProcessMediaUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 600;

    public function __construct(
        public int $queueId
    ) {
        $this->queue = config('mediaupload.module.upload.queue_name', 'default');
    }

    public function handle(
        ContentGenerationService $contentService,
        VideoProcessingService $videoService,
        CaptionBurnService $captionBurnService,
        UploadFileAction $uploadAction,
        UpsertContentSettingsAction $upsertSettingsAction
    ): void {
        $queueItem = MediaUploadQueue::with('folder')->find($this->queueId);
        if (!$queueItem) {
            Log::error('Media upload queue item not found', ['queue_id' => $this->queueId]);
            return;
        }

        $folder = $queueItem->folder;
        if (!$folder) {
            $queueItem->markAsFailed('Folder not found');
            return;
        }

        try {
            $queueItem->markAsProcessing();

            $disk = Storage::disk('local');
            $filePath = $queueItem->file_path;
            if (!$filePath || !$disk->exists($filePath)) {
                $resolvedPath = $filePath ? $disk->path($filePath) : '(empty path)';
                Log::error('ProcessMediaUploadJob: temporary file not found', [
                    'queue_id' => $this->queueId,
                    'file_path' => $filePath,
                    'resolved_path' => $resolvedPath,
                ]);
                throw new \RuntimeException('Temporary file not found at ' . ($filePath ?? 'null'));
            }

            $localPath = $disk->path($filePath);

            $queueItem->updateProgress(15);

            $settings = $folder->contentSettings;
            if (!$settings) {
                $settings = $upsertSettingsAction->execute($folder, [
                    'content_source_type' => 'title',
                    'heading_length' => 10,
                    'caption_length' => 30,
                    'hashtag_count' => 3,
                ]);
            }

            $title = pathinfo($queueItem->file_name, PATHINFO_FILENAME);
            $queueItem->updateProgress(25);

            $content = $contentService->generate(
                $localPath,
                $title,
                $settings,
                $queueItem->user_id
            );

            $queueItem->updateProgress(50);

            // Get loop count and enable_reverse from caption_config (per-upload) or fall back to folder settings
            $captionConfig = $queueItem->caption_config;
            $loopCount = (is_array($captionConfig) && isset($captionConfig['loop_count']))
                ? (int) $captionConfig['loop_count']
                : ($settings->default_loop_count ?? 1);
            $enableReverse = (is_array($captionConfig) && isset($captionConfig['enable_reverse']))
                ? (bool) $captionConfig['enable_reverse']
                : ($settings->default_enable_reverse ?? false);

            $outputPath = storage_path('app/temp/mu_output_' . uniqid() . '.mp4');
            $videoService->processVideo($localPath, $outputPath, $loopCount, $enableReverse);

            $queueItem->updateProgress(60);

            // Check if video caption burning is enabled (default: false)
            $enableVideoCaption = (is_array($captionConfig) && isset($captionConfig['enable_video_caption']))
                ? (bool) $captionConfig['enable_video_caption']
                : false;

            // Only burn captions if enabled
            if ($enableVideoCaption) {
                $captionTemplate = $settings->default_caption_template_id
                    ? \Modules\MediaUpload\Models\CaptionTemplate::find($settings->default_caption_template_id)
                    : null;

                $captionText = $contentService->generateInVideoCaption(
                    $localPath,
                    $title,
                    $settings,
                    $captionTemplate,
                    $queueItem->user_id,
                    is_array($captionConfig) ? $captionConfig : null
                );
                if ($captionText === '') {
                    $captionText = trim($content['social_caption'] ?? '') ?: trim($content['youtube_heading'] ?? '') ?: $title;
                }
                if ($captionText !== '') {
                    $propsBeforeBurn = $videoService->getVideoProperties($outputPath);
                    $duration = (float) ($propsBeforeBurn['duration'] ?? 0);
                    $vidWidth = (int) ($propsBeforeBurn['width'] ?? 1920);
                    $vidHeight = (int) ($propsBeforeBurn['height'] ?? 1080);
                    if ($duration > 0) {
                        $burnedPath = storage_path('app/temp/mu_burned_' . uniqid() . '.mp4');
                        $templateOrConfig = is_array($captionConfig) && !empty($captionConfig)
                            ? $captionConfig
                            : $captionTemplate;

                        Log::info('Caption burn config', [
                            'queue_id' => $this->queueId,
                            'caption_config' => $captionConfig,
                            'template_or_config' => $templateOrConfig instanceof \Modules\MediaUpload\Models\CaptionTemplate
                                ? ['type' => 'template', 'id' => $templateOrConfig->id, 'words_per_caption' => $templateOrConfig->words_per_caption]
                                : ['type' => 'config', 'data' => $templateOrConfig],
                            'loop_count' => $loopCount,
                            'duration_after_loop' => $duration,
                        ]);

                        $captionBurnService->burnCaptions(
                            $outputPath,
                            $burnedPath,
                            $captionText,
                            $duration,
                            $templateOrConfig,
                            $vidWidth,
                            $vidHeight
                        );
                        if (file_exists($outputPath)) {
                            @unlink($outputPath);
                        }
                        $outputPath = $burnedPath;
                    }
                }
            }

            $queueItem->updateProgress(70);

            $props = $videoService->getVideoProperties($outputPath);
            $uploadedFile = new UploadedFile($outputPath, $queueItem->file_name, $queueItem->mime_type, null, true);
            // Use folder's storage_path (user-defined name) instead of generated folder-{id}
            $storagePath = $folder->getFullStoragePath();

            // Final file is always stored via StorageManagement (active storage)
            $storageFile = $uploadAction->execute($uploadedFile, $storagePath, $queueItem->user_id);

            $queueItem->updateProgress(85);

            $mediaUpload = MediaUpload::create([
                'user_id' => $queueItem->user_id,
                'folder_id' => $folder->id,
                'storage_file_id' => $storageFile->id,
                'title' => $title,
                'status' => 'ready',
                'caption_template_id' => $settings->default_caption_template_id,
                'loop_count' => $loopCount,
                'enable_reverse' => $enableReverse,
                'youtube_heading' => $content['youtube_heading'] ?? null,
                'social_caption' => $content['social_caption'] ?? null,
                'hashtags' => $content['hashtags'] ?? [],
                'video_metadata' => $props,
                'processed_at' => now(),
            ]);

            $queueItem->markAsCompleted($mediaUpload->id);

            Storage::disk('local')->delete($queueItem->file_path);
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
        } catch (\Throwable $e) {
            $queueItem->markAsFailed($e->getMessage());
            Log::error('ProcessMediaUploadJob failed', [
                'queue_id' => $this->queueId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Do not rethrow: we already marked the queue item as failed. Let Laravel remove the
            // job from the queue so the worker continues to the next job instead of retrying
            // (retries rarely help for "No AI API key", "file not found", etc.).
        }
    }

    public function failed(\Throwable $e): void
    {
        $item = MediaUploadQueue::find($this->queueId);
        if ($item && $item->status !== 'failed') {
            $item->markAsFailed($e->getMessage());
        }
        Log::error('ProcessMediaUploadJob permanently failed', ['queue_id' => $this->queueId, 'error' => $e->getMessage()]);
    }
}
