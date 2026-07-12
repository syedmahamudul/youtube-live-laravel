<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YouTubeApiService;

class ValidateYouTubeConfig extends Command
{
    protected $signature = 'youtube:validate';
    protected $description = 'Validate YouTube API configuration';

    protected $youTubeApi;

    public function __construct(YouTubeApiService $youTubeApi)
    {
        parent::__construct();
        $this->youTubeApi = $youTubeApi;
    }

    public function handle()
    {
        $this->info('Validating YouTube API Configuration...');
        $this->newLine();

        // Check if configured
        if (!$this->youTubeApi->isConfigured()) {
            $this->error('❌ YouTube API is not configured or disabled.');
            $this->info('Please check:');
            $this->info('  - YOUTUBE_API_KEY in .env file');
            $this->info('  - YOUTUBE_API_ENABLED=true in .env file');
            return 1;
        }

        $this->info('✅ YouTube API is configured');

        // Validate API key
        $this->info('Validating API key...');
        if ($this->youTubeApi->validateApiKey()) {
            $this->info('✅ API key is valid');
        } else {
            $this->error('❌ API key is invalid or has insufficient permissions');
            $this->info('Make sure:');
            $this->info('  - The API key is correct');
            $this->info('  - YouTube Data API v3 is enabled in Google Cloud Console');
            $this->info('  - The API key has proper restrictions');
            return 1;
        }

        // Test video details fetch
        $this->info('Testing video details fetch...');
        $testVideoId = 'M7lc1UVf-VE';
        $details = $this->youTubeApi->getVideoDetails($testVideoId);
        
        if ($details) {
            $this->info('✅ Successfully fetched video details');
            $this->line('  Title: ' . ($details['snippet']['title'] ?? 'N/A'));
            $this->line('  Channel: ' . ($details['snippet']['channelTitle'] ?? 'N/A'));
        } else {
            $this->error('❌ Failed to fetch video details');
        }

        $this->newLine();
        $this->info('YouTube API configuration validated successfully!');
        $this->info('All systems ready for YouTube integration.');

        return 0;
    }
}