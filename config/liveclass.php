<?php

return [
    /*
    |--------------------------------------------------------------------------
    | YouTube API Configuration
    |--------------------------------------------------------------------------
    */
    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY', ''),
        'enabled' => env('YOUTUBE_API_ENABLED', true),
        'timeout' => env('YOUTUBE_API_TIMEOUT', 5),
        
        // OAuth Configuration (optional)
        'client_id' => env('YOUTUBE_CLIENT_ID', ''),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET', ''),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', ''),
        
        // API Endpoints
        'endpoints' => [
            'videos' => 'https://www.googleapis.com/youtube/v3/videos',
            'search' => 'https://www.googleapis.com/youtube/v3/search',
            'channels' => 'https://www.googleapis.com/youtube/v3/channels',
            'live_broadcasts' => 'https://www.googleapis.com/youtube/v3/liveBroadcasts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Streaming Server Configuration
    |--------------------------------------------------------------------------
    */
    'streaming_server_url' => env('STREAMING_SERVER_URL', 'http://localhost:8080'),
    'hls_path' => env('HLS_PATH', '/hls'),
    'rtmp_stats_url' => env('LIVECLASS_RTMP_STATS_URL', 'http://localhost:8080/stats'),
    'video_api_url' => env('LIVECLASS_VIDEO_API_URL', 'http://localhost:5080/api'),
    'api_token' => env('LIVECLASS_API_TOKEN', ''),
];