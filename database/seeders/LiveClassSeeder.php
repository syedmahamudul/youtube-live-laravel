<?php

namespace Database\Seeders;

use App\Models\LiveClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LiveClassSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['upcoming', 'live', 'completed'];

        $youtubeVideos = [
            'https://www.youtube.com/watch?v=PkZNo7MFNFg',
            'https://www.youtube.com/watch?v=rfscVS0vtbw',
            'https://www.youtube.com/watch?v=3JluqTojuME',
            'https://www.youtube.com/watch?v=OK_JCtrrv-c',
            'https://www.youtube.com/watch?v=fBNz5xF-Kx4',
            'https://www.youtube.com/watch?v=9He4UBLyk8Y',
            'https://www.youtube.com/watch?v=Ke90Tje7VS0',
            'https://www.youtube.com/watch?v=TlB_eWDSMt4',
            'https://www.youtube.com/watch?v=Q33KBiDriJY',
            'https://www.youtube.com/watch?v=HGTJBPNC-Gw',
            'https://www.youtube.com/watch?v=7S_tz1z_5bA',
            'https://www.youtube.com/watch?v=ysEN5RaKOlA',
            'https://www.youtube.com/watch?v=Oe421EPjeBE',
            'https://www.youtube.com/watch?v=w7ejDZ8SWv8',
            'https://www.youtube.com/watch?v=SqcY0GlETPk',
            'https://www.youtube.com/watch?v=Ukg_U3CnJWI',
            'https://www.youtube.com/watch?v=1Rs2ND1ryYc',
            'https://www.youtube.com/watch?v=WGJJIrtnfpk',
            'https://www.youtube.com/watch?v=ZxKM3DCV2kE',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ];

        for ($i = 1; $i <= 20; $i++) {

            $status = $statuses[array_rand($statuses)];

            LiveClass::create([
                'title' => "Live Class {$i}",
                'description' => "Description for Live Class {$i}.",
                'class_code' => 'CLS' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'teacher_name' => "Teacher {$i}",
                'stream_key' => Str::random(32),

                // YouTube video URL
                'stream_url' => $youtubeVideos[$i - 1],

                'thumbnail' => "thumbnails/class{$i}.jpg",
                'is_live' => $status === 'live',
                'scheduled_start' => now()->addDays($i),
                'scheduled_end' => now()->addDays($i)->addHours(2),
                'actual_start' => $status !== 'upcoming'
                    ? now()->subDays(rand(1, 5))
                    : null,
                'actual_end' => $status === 'completed'
                    ? now()->subDays(rand(1, 5))->addHours(2)
                    : null,
                'max_students' => rand(50, 200),
                'status' => $status,
                'viewer_count' => $status === 'live'
                    ? rand(10, 150)
                    : rand(0, 300),
            ]);
        }
    }
}