<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Folder galeri: latihan, prestasi, youtube, instagram. Isi lama dimasukkan otomatis. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('galleries', 'album')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->string('album', 30)->nullable()->index();
            });
        }

        DB::table('galleries')->whereNull('album')->orderBy('id')->get()->each(function ($g) {
            $links = strtolower(($g->video_url ?? '') . ' ' . ($g->social_url ?? ''));
            $album = $g->type !== 'video' ? 'latihan'
                : (str_contains($links, 'instagram.com') ? 'instagram'
                : (str_contains($links, 'youtu') ? 'youtube' : 'instagram'));

            DB::table('galleries')->where('id', $g->id)->update(['album' => $album]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('galleries', 'album')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->dropColumn('album');
            });
        }
    }
};
