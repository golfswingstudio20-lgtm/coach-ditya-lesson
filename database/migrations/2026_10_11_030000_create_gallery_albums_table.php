<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Folder galeri yang bisa diatur admin (nama, keterangan, sampul, urutan, tampil). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gallery_albums')) {
            Schema::create('gallery_albums', function (Blueprint $table) {
                $table->id();
                $table->string('key', 40)->unique();
                $table->string('name', 60);
                $table->string('description', 120)->nullable();
                $table->string('kind', 10)->default('image'); // image | video
                $table->string('cover')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        $defaults = [
            ['prestasi', 'Prestasi Murid', 'Pencapaian & turnamen murid', 'image', 1],
            ['latihan', 'Momen Latihan', 'Suasana sesi latihan & lapangan', 'image', 2],
            ['youtube', 'Video YouTube', 'Tutorial & tips dari YouTube', 'video', 3],
            ['instagram', 'Video Instagram', 'Reel & video latihan', 'video', 4],
        ];

        foreach ($defaults as [$key, $name, $desc, $kind, $order]) {
            if (! DB::table('gallery_albums')->where('key', $key)->exists()) {
                DB::table('gallery_albums')->insert([
                    'key' => $key, 'name' => $name, 'description' => $desc, 'kind' => $kind,
                    'sort_order' => $order, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_albums');
    }
};
