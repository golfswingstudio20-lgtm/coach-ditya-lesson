<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran event diatur manual per event (bayar langsung ke penyelenggara),
 * plus kode referal coach yang diisi member saat membayar.
 */
return new class extends Migration
{
    private array $cols = ['organizer', 'pay_qr', 'pay_accounts', 'pay_instructions', 'pay_contact', 'pay_link', 'referral_code', 'referral_note'];

    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'organizer')) {
                $table->string('organizer', 120)->nullable();
            }
            if (! Schema::hasColumn('events', 'pay_qr')) {
                $table->string('pay_qr')->nullable();
            }
            if (! Schema::hasColumn('events', 'pay_accounts')) {
                $table->text('pay_accounts')->nullable();      // satu baris: Bank | Nomor | Atas nama
            }
            if (! Schema::hasColumn('events', 'pay_instructions')) {
                $table->text('pay_instructions')->nullable();
            }
            if (! Schema::hasColumn('events', 'pay_contact')) {
                $table->string('pay_contact', 30)->nullable(); // WhatsApp penyelenggara
            }
            if (! Schema::hasColumn('events', 'pay_link')) {
                $table->string('pay_link', 500)->nullable();   // link pendaftaran / pembayaran penyelenggara
            }
            if (! Schema::hasColumn('events', 'referral_code')) {
                $table->string('referral_code', 40)->nullable();
            }
            if (! Schema::hasColumn('events', 'referral_note')) {
                $table->string('referral_note', 200)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            foreach ($this->cols as $c) {
                if (Schema::hasColumn('events', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
