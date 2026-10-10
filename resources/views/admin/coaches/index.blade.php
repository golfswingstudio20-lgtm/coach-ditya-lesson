@extends('admin.layouts.panel')

@section('title', 'About Coach')

@section('content')
    <style>
        .ab-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
        .ab-grid .form-card { max-width: none; }
        .ab-side { position: sticky; top: 84px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); padding: 18px; }
        .ab-side h3 { font-size: 14px; margin-bottom: 4px; }
        .ab-side p { color: var(--text-muted); font-size: 11.5px; line-height: 1.55; }
        .ab-side ul { margin: 10px 0 14px 16px; color: var(--text-soft); font-size: 11.5px; line-height: 1.7; }
        .ab-side .btn { width: 100%; justify-content: center; }
        @media (max-width: 1000px) { .ab-grid { grid-template-columns: 1fr; } .ab-side { position: static; } }
    </style>

    <section class="page-head">
        <div>
            <h1>About Coach</h1>
            <p>Profil coach yang tampil di halaman Home. Bisa juga diedit langsung dari halaman Home (tombol ✎ Edit About Coach).</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('home') }}#coach" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat di Home</a>
        </div>
    </section>

    <div class="ab-grid">
        <form method="POST" action="{{ $coach->exists ? route('admin.coaches.update', $coach) : route('admin.coaches.store') }}" enctype="multipart/form-data" class="form-card" id="coachForm" @if (\App\Support\Uploads::directEnabled()) data-direct-upload="{{ route('admin.uploads.sign') }}" @endif>
            @csrf
            @if ($coach->exists) @method('PUT') @endif

            @if ($errors->any())
                <div class="error-box"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            @include('admin.coaches._fields')

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan profil coach</button>
            </div>
        </form>

        <aside class="ab-side">
            <h3>Tampil di Home</h3>
            <p>Bagian "About Coach" di halaman Home menampilkan:</p>
            <ul>
                <li>Label & judul bagian</li>
                <li>Foto, nama, jabatan</li>
                <li>Artikel / tentang coach</li>
                <li>Tahun melatih, murid, jumlah sertifikasi</li>
                <li>Riwayat pengalaman (timeline)</li>
                <li>Sertifikasi & prestasi</li>
                <li>Keahlian & motto</li>
            </ul>
            <a href="{{ route('home') }}#coach" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat di Home</a>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('coachForm').addEventListener('submit', () => {
            const b = document.getElementById('btnSubmit'); b.disabled = true; b.textContent = 'Menyimpan...';
        });
    </script>
@endpush
