<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video swing (disimpan di Supabase Storage) + data pose ringkas,
 * supaya hasil analisis bisa diputar ulang di Studio Video lengkap dengan garisnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('swing_analyses', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('captures');
            $table->json('pose_data')->nullable()->after('video_path');
        });
    }

    public function down(): void
    {
        Schema::table('swing_analyses', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'pose_data']);
        });
    }
};
