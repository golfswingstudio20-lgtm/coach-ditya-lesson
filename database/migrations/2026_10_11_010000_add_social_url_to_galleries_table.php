<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Link Instagram / YouTube tambahan untuk video yang di-upload (tombol "Lihat di Instagram"). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('galleries', 'social_url')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->string('social_url', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('galleries', 'social_url')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->dropColumn('social_url');
            });
        }
    }
};
