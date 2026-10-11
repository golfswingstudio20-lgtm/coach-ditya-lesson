<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Memindahkan event yang dulu hardcode ke database.
     * Hanya berjalan jika tabel events masih kosong.
     */
    public function run(): void
    {
        if (Event::exists()) {
            return;
        }

        Event::create([
            'title'               => 'Golf Coaching Clinic',
            'description'         => 'Sesi coaching golf bersama coach berpengalaman dengan suasana latihan yang lebih intensif dan terarah.',
            'poster'              => 'images/event-poster-1.jpg',
            'event_date'          => '2026-10-17',
            'start_time'          => '09:00',
            'end_time'            => '12:00',
            'location'            => 'Padang Golf Modernland, Tangerang',
            'registration_status' => 'open',
            'price'               => 500000,
            'price_unit'          => 'person',
            'note'                => 'Event ini terbatas untuk peserta yang melakukan pembayaran. Pastikan Anda melakukan pembayaran untuk mengamankan tempat.',
            'is_active'           => true,
        ]);
    }
}
