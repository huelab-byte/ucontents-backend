<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SocialConnection\Models\SocialConnectionChannel;
use Modules\SocialConnection\Models\SocialConnectionGroup;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $connections = [
            'channels' => [],
            'groups' => [],
        ];
        $channelIds = [];
        $groupIds = [];
        foreach ($this->connections as $conn) {
            if ($conn->connection_type === 'channel') {
                $connections['channels'][] = (int) $conn->connection_id;
                $channelIds[] = (int) $conn->connection_id;
            } else {
                $connections['groups'][] = (int) $conn->connection_id;
                $groupIds[] = (int) $conn->connection_id;
            }
        }

        $connectedTo = $this->resolveConnectedTo($channelIds, $groupIds);

        $contentSource = $this->resolveContentSource();

        $postedCount = $this->contentItems()->where('status', 'published')->count();
        $totalCount = $this->contentItems()->count();
        $remainingCount = $this->contentItems()->whereIn('status', ['pending', 'scheduled'])->count();

        $logoUrl = null;
        if ($this->brand_logo_storage_file_id && $this->relationLoaded('brandLogo') && $this->brandLogo) {
            $logoUrl = $this->brandLogo->url ?? null;
        }

        return [
            'id' => (string) $this->id,
            'brand' => [
                'name' => $this->brand_name,
                'logo' => $logoUrl,
                'projectName' => $this->project_name,
            ],
            'connections' => $connections,
            'connectedTo' => $connectedTo,
            'contentSourceType' => $this->content_source_type,
            'contentSource' => $contentSource,
            'totalPost' => $totalCount,
            'postedAmount' => $postedCount,
            'remainingContent' => $remainingCount,
            'startedDate' => ($this->started_at ?? $this->created_at)?->toISOString() ?? '',
            'status' => $this->status,
            'scheduleCondition' => $this->schedule_condition,
            'scheduleInterval' => $this->schedule_interval,
            'repostEnabled' => $this->repost_enabled,
            'repostCondition' => $this->repost_condition,
            'repostInterval' => $this->repost_interval,
            'repostMaxCount' => $this->repost_max_count,
            'contentSourceConfig' => $this->content_source_config,
        ];
    }

    /**
     * Resolve channel types to platform flags (facebook, instagram, tiktok, youtube)
     */
    private function resolveConnectedTo(array $channelIds, array $groupIds): array
    {
        $platforms = ['facebook' => false, 'instagram' => false, 'tiktok' => false, 'youtube' => false];

        $allChannelIds = $channelIds;
        if (! empty($groupIds) && class_exists(SocialConnectionGroup::class)) {
            $groupChannels = SocialConnectionGroup::query()
                ->whereIn('id', $groupIds)
                ->where('user_id', $this->user_id)
                ->with('channels')
                ->get();
            foreach ($groupChannels as $group) {
                foreach ($group->channels as $ch) {
                    $allChannelIds[] = $ch->id;
                }
            }
        }

        if (empty($allChannelIds) || ! class_exists(SocialConnectionChannel::class)) {
            return $platforms;
        }

        $channels = SocialConnectionChannel::query()
            ->whereIn('id', array_unique($allChannelIds))
            ->where('user_id', $this->user_id)
            ->get(['id', 'type', 'provider']);

        foreach ($channels as $ch) {
            $type = $ch->type ?? '';
            $provider = $ch->provider ?? '';
            if (in_array($type, ['facebook_page', 'facebook_profile'], true)) {
                $platforms['facebook'] = true;
            } elseif ($type === 'instagram_business') {
                $platforms['instagram'] = true;
            } elseif ($type === 'youtube_channel' || $provider === 'google') {
                $platforms['youtube'] = true;
            } elseif ($type === 'tiktok_profile' || $provider === 'tiktok') {
                $platforms['tiktok'] = true;
            }
        }

        return $platforms;
    }

    /**
     * Resolve content source to display names
     * - For media_upload: returns "MediaUpload/FolderName" format
     * - For csv_file: returns the CSV filename
     */
    private function resolveContentSource(): array
    {
        $contentSource = [];
        
        if ($this->content_source_type === 'media_upload') {
            $folderIds = $this->content_source_config['folder_ids'] ?? [];
            if (!empty($folderIds) && class_exists(\Modules\MediaUpload\Models\MediaUploadFolder::class)) {
                $folders = \Modules\MediaUpload\Models\MediaUploadFolder::whereIn('id', $folderIds)
                    ->where('user_id', $this->user_id)
                    ->get(['id', 'name']);
                
                foreach ($folders as $folder) {
                    $contentSource[] = "Media Upload / {$folder->name}";
                }
            }
        } elseif ($this->content_source_type === 'csv_file') {
            // For CSV imports, show the filename from config
            $filename = $this->content_source_config['filename'] ?? $this->content_source_config['csv_filename'] ?? null;
            if ($filename) {
                $contentSource[] = $filename;
            } else {
                $contentSource[] = 'CSV Import';
            }
        }
        
        return $contentSource;
    }
}
