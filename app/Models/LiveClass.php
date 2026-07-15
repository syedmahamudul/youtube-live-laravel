<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClass extends Model
{
    protected $fillable = [
        'title', 'description', 'class_code', 'teacher_name',
        'stream_key', 'stream_url', 'thumbnail', 'is_live',
        'scheduled_start', 'scheduled_end', 'actual_start', 'actual_end',
        'max_students', 'status', 'viewer_count',
        'youtube_video_id', 'youtube_embed_url', 'stream_type'
    ];

    protected $casts = [
        'is_live' => 'boolean',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
    ];

    /**
     * Check if this is a YouTube class
     */
    public function isYouTubeClass(): bool
    {
        return $this->stream_type === 'youtube' || !empty($this->youtube_video_id);
    }

    /**
     * Check if class is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming' && 
               $this->scheduled_start && 
               $this->scheduled_start->isFuture();
    }

    /**
     * Check if class is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || 
               ($this->scheduled_end && $this->scheduled_end->isPast() && !$this->is_live);
    }

    /**
     * Start the class
     */
    public function startClass(): void
    {
        $this->update([
            'is_live' => true,
            'status' => 'live',
            'actual_start' => now(),
            'actual_end' => null
        ]);
    }

    /**
     * End the class
     */
    public function endClass(): void
    {
        $this->update([
            'is_live' => false,
            'status' => 'completed',
            'actual_end' => now()
        ]);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_live) {
            return '🔴 Live Now';
        } elseif ($this->isCompleted()) {
            return '✅ Completed';
        } elseif ($this->isUpcoming()) {
            return '⏳ Upcoming';
        } else {
            return '⚫ Offline';
        }
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_live) {
            return 'danger';
        } elseif ($this->isCompleted()) {
            return 'success';
        } elseif ($this->isUpcoming()) {
            return 'warning';
        } else {
            return 'secondary';
        }
    }

    /**
     * Get YouTube embed URL
     */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        if ($this->youtube_video_id) {
            return "https://www.youtube.com/embed/{$this->youtube_video_id}";
        }
        return $this->stream_url;
    }

    /**
     * Get YouTube embed URL with autoplay
     */
    public function getYoutubeAutoplayUrlAttribute(): ?string
    {
        if ($this->youtube_video_id) {
            $params = http_build_query([
                'autoplay' => 1,
                'rel' => 0,
                'modestbranding' => 1,
                'enablejsapi' => 1
            ]);
            return "https://www.youtube.com/embed/{$this->youtube_video_id}?{$params}";
        }
        return null;
    }

    /**
     * Get YouTube watch URL
     */
    public function getYoutubeWatchUrlAttribute(): ?string
    {
        if ($this->youtube_video_id) {
            return "https://www.youtube.com/watch?v={$this->youtube_video_id}";
        }
        return null;
    }

    /**
     * Extract YouTube video ID from various URL formats
     */
    public static function extractYouTubeId(string $url): ?string
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
     * Scope for live classes
     */
    public function scopeLive($query)
    {
        return $query->where('is_live', true);
    }

    /**
     * Scope for upcoming classes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
                     ->where('scheduled_start', '>', now());
    }

    /**
     * Scope for completed classes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for YouTube classes
     */
    public function scopeYouTube($query)
    {
        return $query->where('stream_type', 'youtube')
                     ->orWhereNotNull('youtube_video_id');
    }
}