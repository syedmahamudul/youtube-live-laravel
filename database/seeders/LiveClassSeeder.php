<?php

namespace Database\Seeders;

use App\Models\LiveClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LiveClassSeeder extends Seeder
{
    public function run(): void
    {
        LiveClass::create([
            'title' => 'Live Class 1',
            'description' => 'Description for Live Class 1.',
            'class_code' => 'CLS0001',
            'teacher_name' => 'Teacher 1',
            'stream_key' => Str::random(32),
            'stream_url' => 'https://www.youtube.com/watch?v=PkZNo7MFNFg',
            'thumbnail' => 'thumbnails/class1.jpg',
            'is_live' => true,
            'scheduled_start' => now()->addDay(),
            'scheduled_end' => now()->addDay()->addHours(2),
            'actual_start' => now(),
            'actual_end' => null,
            'max_students' => 100,
            'status' => 'live',
            'viewer_count' => 25,
        ]);
    }
}