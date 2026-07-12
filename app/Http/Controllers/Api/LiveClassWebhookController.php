<?php

namespace App\Http\Controllers\Api;

use App\Models\LiveClass;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class LiveClassWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $classCode = $request->input('class_code');
        $streamKey = $request->input('stream_key');

        $class = LiveClass::where('class_code', $classCode)
            ->orWhere('stream_key', $streamKey)
            ->first();

        if (!$class) {
            Log::warning("Webhook: Class not found", ['class_code' => $classCode, 'stream_key' => $streamKey]);
            return response()->json(['error' => 'Class not found'], 404);
        }

        switch ($event) {
            case 'class.start':
            case 'stream.start':
                $class->startClass();
                Log::info("Webhook: Class started", ['id' => $class->id, 'title' => $class->title]);
                break;
            
            case 'class.end':
            case 'stream.stop':
                $class->endClass();
                Log::info("Webhook: Class ended", ['id' => $class->id, 'title' => $class->title]);
                break;
            
            default:
                return response()->json(['error' => 'Unknown event'], 400);
        }

        return response()->json([
            'success' => true,
            'class' => [
                'id' => $class->id,
                'title' => $class->title,
                'status' => $class->status,
                'is_live' => $class->is_live
            ]
        ]);
    }
}