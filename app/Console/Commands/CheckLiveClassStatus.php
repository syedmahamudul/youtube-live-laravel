<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LiveClassService;
use App\Models\LiveClass;
use Carbon\Carbon;

class CheckLiveClassStatus extends Command
{
    protected $signature = 'liveclass:check-status 
                           {--class= : Check specific class by ID}
                           {--force : Force update even if cached}';
    protected $description = 'Check and update live class statuses';

    protected $liveClassService;

    public function __construct(LiveClassService $liveClassService)
    {
        parent::__construct();
        $this->liveClassService = $liveClassService;
    }

    public function handle()
    {
        $classId = $this->option('class');
        
        if ($classId) {
            $class = LiveClass::find($classId);
            if (!$class) {
                $this->error('Class not found!');
                return 1;
            }
            
            $this->checkAndUpdateClass($class);
        } else {
            $this->info('Checking all live classes...');
            
            // Get classes to check
            $classes = LiveClass::where('status', 'live')
                ->orWhere('status', 'upcoming')
                ->orWhere(function($query) {
                    $query->where('scheduled_start', '>', Carbon::now()->subHours(24))
                          ->orWhere('actual_start', '>', Carbon::now()->subHours(24));
                })
                ->get();

            $this->info("Found {$classes->count()} classes to check");
            
            $bar = $this->output->createProgressBar($classes->count());
            $bar->start();

            foreach ($classes as $class) {
                $this->checkAndUpdateClass($class);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('All class statuses updated successfully!');
        }

        return 0;
    }

    private function checkAndUpdateClass(LiveClass $class)
    {
        $isLive = $this->liveClassService->isClassLive($class);
        
        // Update based on actual stream status
        if ($isLive && !$class->is_live && $class->status !== 'completed') {
            $class->startClass();
            $this->info("✅ Class '{$class->title}' started");
        } elseif (!$isLive && $class->is_live) {
            $class->endClass();
            $this->info("⏹️ Class '{$class->title}' ended");
        } elseif ($class->status === 'upcoming' && Carbon::now()->gt($class->scheduled_start)) {
            // If scheduled start passed and not live, mark as missed
            if (!$class->is_live) {
                $class->update(['status' => 'completed']);
                $this->warn("⚠️ Class '{$class->title}' missed its scheduled time");
            }
        } else {
            $this->line("ℹ️ Class '{$class->title}' status unchanged: {$class->status_label}");
        }
    }
}