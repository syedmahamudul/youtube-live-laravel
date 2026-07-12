<?php

namespace App\Http\Controllers\Api;

use App\Models\LiveClass;
use App\Services\LiveClassService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LiveClassController extends Controller
{
    protected $liveClassService;

    public function __construct(LiveClassService $liveClassService)
    {
        $this->liveClassService = $liveClassService;
    }

    public function status($id)
    {
        $class = LiveClass::findOrFail($id);
        $stats = $this->liveClassService->getClassStats($class);

        return response()->json($stats);
    }

    public function currentLiveClasses()
    {
        $classes = LiveClass::live()
            ->with(['teacher', 'students'])
            ->get()
            ->map(function ($class) {
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'teacher' => $class->teacher_name,
                    'status' => $class->status_label,
                    'viewers' => $this->liveClassService->getClassStats($class)['viewer_count'],
                    'started_at' => $class->actual_start?->diffForHumans()
                ];
            });

        return response()->json($classes);
    }

    public function upcomingClasses()
    {
        $classes = LiveClass::upcoming()
            ->orderBy('scheduled_start', 'asc')
            ->limit(10)
            ->get();

        return response()->json($classes);
    }

    public function getClassDetails($id)
    {
        $class = LiveClass::with(['teacher', 'students'])->findOrFail($id);
        
        return response()->json([
            'class' => $class,
            'stats' => $this->liveClassService->getClassStats($class),
            'is_upcoming' => $class->isUpcoming(),
            'is_completed' => $class->isCompleted(),
            'can_join' => $class->is_live || $class->isUpcoming()
        ]);
    }
}