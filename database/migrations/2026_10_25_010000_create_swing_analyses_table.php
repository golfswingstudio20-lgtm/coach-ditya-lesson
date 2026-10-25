<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil AI Swing Analysis (Golf Analyzer).
 * Video dianalisis di browser customer (MediaPipe Pose) dan TIDAK di-upload;
 * yang disimpan hanya skor, sudut, tahapan swing, saran latihan, dan gambar kecil tiap tahapan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swing_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('camera_view', 20)->default('face_on');
            $table->string('handedness', 10)->default('right');
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('metrics')->nullable();
            $table->json('phases')->nullable();
            $table->json('tips')->nullable();
            $table->text('insight')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedSmallInteger('frames')->default(0);
            $table->text('coach_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swing_analyses');
    }
};
