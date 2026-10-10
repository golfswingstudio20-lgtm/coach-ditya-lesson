<?php

namespace App\Http\Controllers;

use App\Support\Uploads;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    /**
     * Menampilkan daftar event.
     */
    public function index(Request $request): View
    {
        $filter = in_array($request->query('filter'), ['upcoming', 'past'], true)
            ? $request->query('filter')
            : null;

        $events = Event::query()
            ->when($filter === 'upcoming', fn ($query) => $query->upcoming()->chronological())
            ->when($filter === 'past', fn ($query) => $query->past()->orderByDesc('event_date'))
            ->when(! $filter, fn ($query) => $query->orderByDesc('event_date'))
            ->get();

        $counts = [
            'all'      => Event::count(),
            'upcoming' => Event::upcoming()->count(),
            'past'     => Event::past()->count(),
        ];

        return view('admin.events.index', compact('events', 'counts', 'filter'));
    }

    /**
     * Menampilkan form tambah event.
     */
    public function create(): View
    {
        $event = new Event([
            'registration_status' => 'open',
            'price'               => 0,
            'price_unit'          => 'person',
            'is_active'           => true,
            'note'                => 'Event ini terbatas untuk peserta yang melakukan pembayaran. Pastikan Anda melakukan pembayaran untuk mengamankan tempat.',
        ]);

        return view('admin.events.create', compact('event'));
    }

    /**
     * Menyimpan event baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $event = new Event($this->validatedData($request));

        $this->handlePoster($request, $event);
        $event->save();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit event.
     */
    public function edit(Event $event): View
    {
        return view('admin.events.edit', compact('event'));
    }

    /**
     * Mengupdate event.
     * quick_toggle=1 hanya membalik status tampil / sembunyi.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        if ($request->boolean('quick_toggle')) {
            $event->update(['is_active' => ! $event->is_active]);

            return back()->with(
                'success',
                $event->is_active
                    ? 'Event ditampilkan ke customer & visitor.'
                    : 'Event disembunyikan dari customer & visitor.'
            );
        }

        $event->fill($this->validatedData($request, $event));

        $this->handlePoster($request, $event);
        $event->save();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event berhasil diperbarui.');
    }

    /**
     * Menghapus event beserta posternya.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->deleteStoredFile($event->poster);
        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event berhasil dihapus.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function validatedData(Request $request, ?Event $event = null): array
    {
        $request->validate([
            'title'               => ['required', 'string', 'max:150'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'poster_file'         => [Rule::requiredIf(! $event?->poster && ! $request->filled('poster_file_path')), 'nullable', 'file'],
            'event_date'          => ['required', 'date'],
            'start_time'          => ['nullable', 'date_format:H:i'],
            'end_time'            => ['nullable', 'date_format:H:i', Rule::when($request->filled('start_time'), 'after:start_time')],
            'location'            => ['nullable', 'string', 'max:150'],
            'registration_status' => ['required', Rule::in(array_keys(Event::STATUSES))],
            'price'               => ['required', 'integer', 'min:0'],
            'price_unit'          => ['nullable', 'string', 'max:30'],
            'note'                => ['nullable', 'string', 'max:500'],
            'is_active'           => ['nullable', 'boolean'],
            'organizer'           => ['nullable', 'string', 'max:120'],
            'pay_qr_file'         => ['nullable', 'file'],
            'pay_accounts'        => ['nullable', 'string', 'max:1000'],
            'pay_instructions'    => ['nullable', 'string', 'max:1500'],
            'pay_contact'         => ['nullable', 'string', 'max:30'],
            'pay_link'            => ['nullable', 'url', 'max:500'],
            'referral_code'       => ['nullable', 'string', 'max:40'],
            'referral_note'       => ['nullable', 'string', 'max:200'],
        ], [
            'pay_link.url'         => 'Link pendaftaran harus berupa alamat lengkap (diawali https://).',
            'title.required'       => 'Judul event wajib diisi.',
            'poster_file.required' => 'Poster event wajib diunggah.',
            'poster_file.image'    => 'Poster harus berupa gambar.',
            'poster_file.max'      => 'Ukuran poster maksimal 5 MB.',
            'event_date.required'  => 'Tanggal event wajib diisi.',
            'start_time.date_format' => 'Format jam mulai tidak valid.',
            'end_time.date_format' => 'Format jam selesai tidak valid.',
            'end_time.after'       => 'Jam selesai harus setelah jam mulai.',
            'price.required'       => 'Harga wajib diisi (isi 0 untuk event gratis).',
        ]);

        return [
            'title'               => $request->input('title'),
            'description'         => $request->input('description'),
            'event_date'          => $request->input('event_date'),
            'start_time'          => $request->input('start_time'),
            'end_time'            => $request->input('end_time'),
            'location'            => $request->input('location'),
            'registration_status' => $request->input('registration_status'),
            'price'               => (int) $request->input('price', 0),
            'price_unit'          => $request->input('price_unit') ?: 'person',
            'note'                => $request->input('note'),
            'is_active'           => $request->boolean('is_active'),
            'organizer'           => $request->input('organizer'),
            'pay_accounts'        => $request->input('pay_accounts'),
            'pay_instructions'    => $request->input('pay_instructions'),
            'pay_contact'         => $request->input('pay_contact'),
            'pay_link'            => $request->input('pay_link'),
            'referral_code'       => $request->filled('referral_code') ? strtoupper(trim($request->input('referral_code'))) : null,
            'referral_note'       => $request->input('referral_note'),
        ];
    }

    private function handlePoster(Request $request, Event $event): void
    {
        // QR pembayaran penyelenggara
        if ($request->boolean('remove_pay_qr')) {
            $this->deleteStoredFile($event->pay_qr);
            $event->pay_qr = null;
        }
        if ($qr = Uploads::take($request, 'pay_qr_file', 'events/payments')) {
            $this->deleteStoredFile($event->pay_qr);
            $event->pay_qr = $qr;
        }

        $poster = Uploads::take($request, 'poster_file', 'events/posters');
        if (! $poster) {
            return;
        }

        $this->deleteStoredFile($event->poster);
        $event->poster = $poster;
    }

    /**
     * Hanya menghapus poster yang diunggah lewat admin (storage events/),
     * file bawaan di public/images tidak tersentuh.
     */
    private function deleteStoredFile(?string $path): void
    {
        Uploads::delete($path, 'events/');
    }
}
