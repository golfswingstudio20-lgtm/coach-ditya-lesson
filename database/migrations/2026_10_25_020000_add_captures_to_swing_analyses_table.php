<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capture dari Studio Video (frame video + garis/sudut buatan coach/customer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('swing_analyses', function (Blueprint $table) {
            $table->json('captures')->nullable()->after('tips');
        });
    }

    public function down(): void
    {
        Schema::table('swing_analyses', function (Blueprint $table) {
            $table->dropColumn('captures');
        });
    }
};
