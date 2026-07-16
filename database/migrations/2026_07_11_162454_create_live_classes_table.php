<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('live_classes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('class_code')->nullable();
            $table->string('teacher_name');
            $table->string('stream_key')->nullable();
            $table->string('youtube_video_id')->nullable();
            $table->string('stream_url')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_live')->default(false);
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->integer('max_students')->default(100);
            $table->string('status')->default('upcoming');
            $table->integer('viewer_count')->default(0);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_classes');
    }
};
