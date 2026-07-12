<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YouTubeApiService
{
    protected $apiKey;
    protected $enabled;
    protected $timeout;

    public function __construct()
    {
        $this->apiKey = config('liveclass.youtube.api_key');
        $this->enabled = config('liveclass.youtube.enabled', true);
        $this->timeout = config('liveclass.youtube.timeout', 5);
    }

    /**
     * Check if YouTube API is configured and enabled
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->apiKey) && $this->apiKey !== 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    }

    /**
     * Get video details from YouTube
     */
    public function getVideoDetails(string $videoId): ?array
    {
        if (!$this->isConfigured()) {
            Log::warning('YouTube API not configured');
            return null;
        }

        $cacheKey = "youtube_video_{$videoId}";
        
        return Cache::remember($cacheKey, 300, function () use ($videoId) {
            return $this->fetchVideoDetails($videoId);
        });
    }

    /**
     * Fetch video details from YouTube API
     */
    private function fetchVideoDetails(string $videoId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)->get(
                config('liveclass.youtube.endpoints.videos'),
                [
                    'part' => 'snippet,statistics,liveStreamingDetails,status,contentDetails',
                    'id' => $videoId,
                    'key' => $this->apiKey
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['items'])) {
                    return $data['items'][0];
                }
                
                Log::warning("YouTube video not found: {$videoId}");
                return null;
            }

            Log::error("YouTube API error: " . $response->status() . " - " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("YouTube API exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a video is currently live
     */
    public function isVideoLive(string $videoId): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $cacheKey = "youtube_live_{$videoId}";
        
        return Cache::remember($cacheKey, 30, function () use ($videoId) {
            $details = $this->fetchVideoDetails($videoId);
            
            if (!$details) {
                return false;
            }

            // Check live broadcast content
            if (isset($details['snippet']['liveBroadcastContent'])) {
                return $details['snippet']['liveBroadcastContent'] === 'live';
            }

            // Check live streaming details
            if (isset($details['liveStreamingDetails'])) {
                $liveDetails = $details['liveStreamingDetails'];
                
                // If there's an actual start time and no end time, it's live
                if (isset($liveDetails['actualStartTime']) && !isset($liveDetails['actualEndTime'])) {
                    return true;
                }

                // If scheduled start exists but no actual start, it's upcoming
                if (isset($liveDetails['scheduledStartTime']) && !isset($liveDetails['actualStartTime'])) {
                    return false;
                }
            }

            return false;
        });
    }

    /**
     * Get live viewer count
     */
    public function getViewerCount(string $videoId): int
    {
        if (!$this->isConfigured()) {
            return 0;
        }

        $cacheKey = "youtube_viewers_{$videoId}";
        
        return Cache::remember($cacheKey, 15, function () use ($videoId) {
            try {
                $response = Http::timeout($this->timeout)->get(
                    config('liveclass.youtube.endpoints.videos'),
                    [
                        'part' => 'liveStreamingDetails',
                        'id' => $videoId,
                        'key' => $this->apiKey
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
        });
    }

    /**
     * Get channel details
     */
    public function getChannelDetails(string $channelId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $cacheKey = "youtube_channel_{$channelId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($channelId) {
            try {
                $response = Http::timeout($this->timeout)->get(
                    config('liveclass.youtube.endpoints.channels'),
                    [
                        'part' => 'snippet,statistics',
                        'id' => $channelId,
                        'key' => $this->apiKey
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    return !empty($data['items']) ? $data['items'][0] : null;
                }
            } catch (\Exception $e) {
                Log::error("YouTube channel error: " . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Search for videos
     */
    public function searchVideos(string $query, int $maxResults = 10): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)->get(
                config('liveclass.youtube.endpoints.search'),
                [
                    'part' => 'snippet',
                    'q' => $query,
                    'type' => 'video',
                    'maxResults' => $maxResults,
                    'key' => $this->apiKey
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['items'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error("YouTube search error: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Get upcoming live broadcasts for a channel
     */
    public function getUpcomingBroadcasts(string $channelId, int $maxResults = 10): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)->get(
                config('liveclass.youtube.endpoints.live_broadcasts'),
                [
                    'part' => 'snippet,status,contentDetails',
                    'channelId' => $channelId,
                    'broadcastStatus' => 'upcoming',
                    'maxResults' => $maxResults,
                    'key' => $this->apiKey
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['items'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error("YouTube broadcasts error: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Get video thumbnail URL
     */
    public function getThumbnailUrl(string $videoId, string $quality = 'medium'): ?string
    {
        $qualities = [
            'default' => 'default.jpg',
            'medium' => 'mqdefault.jpg',
            'high' => 'hqdefault.jpg',
            'standard' => 'sddefault.jpg',
            'maxres' => 'maxresdefault.jpg'
        ];

        if (!isset($qualities[$quality])) {
            $quality = 'medium';
        }

        return "https://img.youtube.com/vi/{$videoId}/{$qualities[$quality]}";
    }

    /**
     * Extract video ID from various YouTube URL formats
     */
    public function extractVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([^&\s]+)/',
            '/(?:youtube\.com\/embed\/)([^&\s?]+)/',
            '/(?:youtube\.com\/v\/)([^&\s?]+)/',
            '/(?:youtu\.be\/)([^&\s?]+)/',
            '/(?:youtube\.com\/live\/)([^&\s?]+)/',
            '/(?:youtube\.com\/shorts\/)([^&\s?]+)/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Validate YouTube API key
     */
    public function validateApiKey(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get(
                'https://www.googleapis.com/youtube/v3/videos',
                [
                    'part' => 'snippet',
                    'id' => 'M7lc1UVf-VE', // Test video ID
                    'key' => $this->apiKey
                ]
            );

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}