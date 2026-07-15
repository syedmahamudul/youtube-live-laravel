<?php

namespace App\Http\Controllers;

use App\Models\LiveClass;
use App\Services\LiveClassService;
use Illuminate\Http\Request;

class LiveClassController extends Controller
{
    protected $liveClassService;

    public function __construct(LiveClassService $liveClassService)
    {
        $this->liveClassService = $liveClassService;
    }

    public function index()
    {
        $liveClasses = LiveClass::where('status', 'live')->get();
        $upcomingClasses = LiveClass::upcoming()->orderBy('scheduled_start')->limit(5)->get();
        $completedClasses = LiveClass::completed()->orderBy('actual_end', 'desc')->limit(5)->get();

        return view('live-classes.index', compact('liveClasses', 'upcomingClasses', 'completedClasses'));
    }

    public function show($id)
    {
        $class = LiveClass::findOrFail($id);
        $stats = $this->liveClassService->getClassStats($class);
            dd($stats);  
        return view('live-classes.show', compact('class', 'stats'));
    }

    public function getStatus($id)
    {
        $class = LiveClass::findOrFail($id);
        $stats = $this->liveClassService->getClassStats($class);
        
        return response()->json($stats);
    }

    public function getAllLive()
    {
        $classes = LiveClass::live()->get()->map(function($class) {
            return [
                'id' => $class->id,
                'title' => $class->title,
                'teacher_name' => $class->teacher_name,
                'status_label' => $class->status_label,
                'viewer_count' => $this->liveClassService->getClassStats($class)['viewer_count'],
                'thumbnail' => $class->thumbnail
            ];
        });

        return response()->json($classes);
    }
}