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
        if ($liveClass->status === 'completed') {
            return false;
        }

        if ($liveClass->isUpcoming()) {
            return false;
        }

        // Method 1: Check via M3U8 playlist
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
}