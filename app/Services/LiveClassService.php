<?php

namespace App\Services;

use App\Models\LiveClass;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LiveClassService
{
    public function isClassLive(LiveClass $liveClass): bool
    {
        return Cache::remember("live_class_status_{$liveClass->id}", 15, function () use ($liveClass) {
            return $this->checkClassStatus($liveClass);
        });
    }

    private function checkClassStatus(LiveClass $liveClass): bool
    {
        // If class is completed, it's not live
        if ($liveClass->status === 'completed') {
            return false;
        }

        // If class is upcoming, it's not live yet
        if ($liveClass->isUpcoming()) {
            return false;
        }

        // Check if it's a YouTube class by checking if youtube_video_id exists
        if (!empty($liveClass->youtube_video_id)) {
            return $this->checkYouTubeLiveStatus($liveClass);
        }

        // Method 1: Check via M3U8 playlist (for HLS streams)
        if ($this->checkM3U8Stream($liveClass)) {
            return true;
        }

        // Method 2: Check via RTMP stats
        if ($this->checkRTMPStream($liveClass)) {
            return true;
        }

        // Method 3: Check via video server API
        if ($this->checkVideoServerAPI($liveClass)) {
            return true;
        }

        return false;
    }

    /**
     * Check YouTube live status
     */
    private function checkYouTubeLiveStatus(LiveClass $liveClass): bool
    {
        // dd('kk');
        if (empty($liveClass->youtube_video_id)) {
            return false;
        }

        try {
            $apiKey = env('YOUTUBE_API_KEY');
            
            if (!$apiKey || $apiKey === 'AIzaSy_Your_Generated_Key_Here') {
                Log::warning('YouTube API key not configured');
                return false;
            }

            $response = Http::timeout(5)->get(
                'https://www.googleapis.com/youtube/v3/videos',
                [
                    'part' => 'liveStreamingDetails,snippet,status',
                    'id' => $liveClass->youtube_video_id,
                    'key' => $apiKey
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                
                if (empty($data['items'])) {
                    return false;
                }

                $video = $data['items'][0];
                
                // Check if it's currently live
                if (isset($video['snippet']['liveBroadcastContent'])) {
                    return $video['snippet']['liveBroadcastContent'] === 'live';
                }

                // Check live streaming details
                if (isset($video['liveStreamingDetails'])) {
                    $details = $video['liveStreamingDetails'];
                    if (isset($details['actualStartTime']) && !isset($details['actualEndTime'])) {
                        return true;
                    }
                }

                return false;
            }
            
            Log::error("YouTube API error: " . $response->status());
            return false;

        } catch (\Exception $e) {
            Log::error("YouTube API exception: " . $e->getMessage());
            return false;
        }
    }

    private function checkM3U8Stream(LiveClass $liveClass): bool
    {  
        if (!$liveClass->stream_url) {
            return false;
        }

        try {
            $m3u8Url = rtrim($liveClass->stream_url, '/') . '/playlist.m3u8';
            $response = Http::timeout(3)->head($m3u8Url);
            
            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                return str_contains($contentType, 'application/vnd.apple.mpegurl') || 
                       str_contains($contentType, 'audio/mpegurl');
            }
        } catch (\Exception $e) {
            Log::info("M3U8 check failed: " . $e->getMessage());
        }
        
        return false;
    }

    private function checkRTMPStream(LiveClass $liveClass): bool
    {
        try {
            $response = Http::timeout(3)->get(
                config('liveclass.rtmp_stats_url') . '?app=live&name=' . $liveClass->stream_key
            );

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['live']['streams'][$liveClass->stream_key]);
            }
        } catch (\Exception $e) {
            Log::info("RTMP check failed: " . $e->getMessage());
        }
        return false;
    }

    private function checkVideoServerAPI(LiveClass $liveClass): bool
    {
        try {
            $response = Http::timeout(3)->get(
                config('liveclass.video_api_url') . '/room/' . $liveClass->class_code . '/status',
                ['Authorization' => 'Bearer ' . config('liveclass.api_token')]
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['is_active'] ?? false;
            }
        } catch (\Exception $e) {
            Log::info("Video API check failed: " . $e->getMessage());
        }
        return false;
    }

    public function updateAllClassStatuses(): void
    {
        $classes = LiveClass::where('status', 'live')
            ->orWhere('status', 'upcoming')
            ->orWhere(function($query) {
                $query->where('actual_start', '>', now()->subHours(24))
                      ->orWhere('scheduled_start', '>', now()->subHours(24));
            })
            ->get();

        foreach ($classes as $class) {
            $isActuallyLive = $this->isClassLive($class);
            
            if ($isActuallyLive && !$class->is_live && $class->status !== 'completed') {
                $class->startClass();
                Log::info("Class {$class->title} automatically marked as live");
            } elseif (!$isActuallyLive && $class->is_live) {
                $class->endClass();
                Log::info("Class {$class->title} automatically marked as ended");
            }
        }
    }

    public function getClassStats(LiveClass $liveClass): array
    {
        return [
            'is_live' => $this->isClassLive($liveClass),
            'viewer_count' => $this->getViewerCount($liveClass),
            'duration' => $liveClass->actual_start ? 
                now()->diffForHumans($liveClass->actual_start, true) : null,
            'status' => $liveClass->status_label,
            'status_color' => $liveClass->status_color
        ];
    }
  
    private function getViewerCount(LiveClass $liveClass): int
    {
        // For YouTube classes
        if (!empty($liveClass->youtube_video_id)) {
            return $this->getYouTubeViewerCount($liveClass->youtube_video_id);
        }

        try {
            $response = Http::timeout(3)->get(
                config('liveclass.rtmp_stats_url') . '?app=live&name=' . $liveClass->stream_key
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['live']['streams'][$liveClass->stream_key]['nclients'] ?? 0;
            }
        } catch (\Exception $e) {
            Log::info("Viewer count fetch failed: " . $e->getMessage());
        }

        return $liveClass->viewer_count ?? 0;
    }

    /**
     * Get YouTube viewer count
     */
    private function getYouTubeViewerCount(string $videoId): int
    {
        try {
            $apiKey = config('youtube.api_key') ?? env('YOUTUBE_API_KEY');
            
            if (!$apiKey || $apiKey === 'AIzaSy_Your_Generated_Key_Here') {
                return 0;
            }

            $response = Http::timeout(5)->get(
                'https://www.googleapis.com/youtube/v3/videos',
                [
                    'part' => 'liveStreamingDetails',
                    'id' => $videoId,
                    'key' => $apiKey
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['items']) && 
                    isset($data['items'][0]['liveStreamingDetails']['concurrentViewers'])) {
                    return (int) $data['items'][0]['liveStreamingDetails']['concurrentViewers'];
                }
            }
        } catch (\Exception $e) {
            Log::error("YouTube viewer count error: " . $e->getMessage());
        }

        return 0;
    }
}