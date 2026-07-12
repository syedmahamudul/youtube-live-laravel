<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClass extends Model
{
    protected $fillable = [
        'title', 'description', 'class_code', 'teacher_name',
        'stream_key', 'stream_url', 'thumbnail', 'is_live',
        'scheduled_start', 'scheduled_end', 'actual_start', 'actual_end',
        'max_students', 'status', 'viewer_count'
    ];

    protected $casts = [
        'is_live' => 'boolean',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
    ];

    public function startClass()
    {
        $this->update([
            'is_live' => true,
            'status' => 'live',
            'actual_start' => now(),
            'actual_end' => null
        ]);
    }

    public function endClass()
    {
        $this->update([
            'is_live' => false,
            'status' => 'completed',
            'actual_end' => now()
        ]);
    }

    public function isUpcoming()
    {
        return $this->status === 'upcoming' && 
               $this->scheduled_start && 
               $this->scheduled_start->isFuture();
    }

    public function isCompleted()
    {
        return $this->status === 'completed' || 
               ($this->scheduled_end && $this->scheduled_end->isPast() && !$this->is_live);
    }

    public function getStatusLabelAttribute()
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

    public function getStatusColorAttribute()
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

    public function scopeLive($query)
    {
        return $query->where('is_live', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
                     ->where('scheduled_start', '>', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}