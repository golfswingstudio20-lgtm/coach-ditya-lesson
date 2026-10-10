@extends('admin.gallery.layout')

@section('title', 'Folder Galeri')

@section('content')
    @php
        $du = \App\Support\Uploads::directEnabled() ? route('admin.uploads.sign') : null;
        $icons = ['prestasi' => '🏆', 'latihan' => '📸', 'youtube' => '▶', 'instagram' => '◎'];
    @endphp

    <style>
        .fa-list { display: grid; gap: 12px; max-width: 860px; }
        .fa-card { border: 1px solid var(--border); border-radius: 18px; background: var(--panel); box-shadow: var(--shadow); overflow: hidden; }
        .fa-card.off { opacity: .7; }
        .fa-row { display: flex; align-items: center; gap: 14px; padding: 14px 16px; }
        .fa-cover { position: relative; width: 58px; height: 58px; flex: 0 0 58px; padding: 3px; border-radius: 50%; background: linear-gradient(135deg, #cde8a3, #3d7d57); }
        .fa-cover.k-instagram { background: linear-gradient(135deg, #f9ce34, #ee2a7b 55%, #6228d7); }
        .fa-cover.k-youtube { background: linear-gradient(135deg, #ff4e45, #b31217); }
        .fa-cover span { width: 100%; height: 100%; display: grid; place-items: center; border-radius: 50%; border: 3px solid var(--panel); background: var(--accent-soft) center / cover no-repeat; font-size: 20px; }
        .fa-info { flex: 1; min-width: 0; }
        .fa-info b { display: block; font-size: 15px; color: var(--text); }
        .fa-info small { display: block; color: var(--text-muted); font-size: 12.5px; }
        .fa-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .fa-tags span { padding: 2px 9px; border-radius: 99px; background: var(--accent-soft); color: var(--d-ink-green, var(--lime)); font-size: 11px; font-weight: 700; }
        .fa-tags span.off { background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #8a5608); }
        .fa-act { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
        .fa-act form { margin: 0; }
        .fa-btn { height: 34px; min-width: 34px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--border); border-radius: 99px; background: var(--d-surface, #fff); color: var(--text-soft); font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .fa-btn:hover { border-color: var(--lime); color: var(--d-ink-green, var(--lime)); }
        .fa-btn.danger { color: var(--d-red-ink, #c9413a); }
        .fa-edit { border-top: 1px solid var(--line); }
        .fa-edit > summary { list-style: none; padding: 10px 16px; color: var(--d-ink-green, var(--lime)); font-size: 13px; font-weight: 700; cursor: pointer; }
        .fa-edit > summary::-webkit-details-marker { display: none; }
        .fa-form { display: grid; gap: 12px; padding: 4px 16px 16px; }
        .fa-form .field { margin: 0; }
        .fa-kind { display: flex; gap: 8px; }
        .fa-kind label { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1px solid var(--border); border-radius: 99px; font-size: 13px; cursor: pointer; }
        .fa-kind label:has(input:checked) { border-color: var(--lime); color: var(--d-ink-green, var(--lime)); font-weight: 700; }
        .fa-kind input { accent-color: var(--lime); }
        .fa-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
        .fa-del { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 12px 16px; border-top: 1px dashed var(--line); font-size: 12.5px; color: var(--text-muted); }
        .fa-del select { height: 34px; padding: 0 10px; border: 1px solid var(--border); border-radius: 10px; background: var(--d-surface, #fff); color: var(--text); }
        .fa-new { max-width: 860px; margin-bottom: 16px; border: 1px dashed rgba(var(--d-green-rgb, 31, 77, 51), .4); border-radius: 18px; background: var(--panel); }
        .fa-new > summary { list-style: none; padding: 14px 16px; font-weight: 800; cursor: pointer; color: var(--d-ink-green, var(--lime)); }
        .fa-new > summary::-webkit-details-marker { display: none; }
        @media (max-width: 640px) { .fa-row { flex-wrap: wrap; } .fa-act { width: 100%; justify-content: flex-start; } }
    </style>

    <section class="page-head">
        <div>
            <h1>Folder Galeri</h1>
            <p>Atur folder yang tampil sebagai bulatan di halaman Galeri: nama, keterangan, foto sampul, urutan, dan tampil/sembunyi.</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('admin.gallery.index') }}" class="btn btn-ghost">← Isi galeri</a>
            <a href="{{ route('galeri') }}" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat halaman publik</a>
        </div>
    </section>

    @if ($errors->any())
        <div class="error-box" style="max-width:860px"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <details class="fa-new" @if (old('_new')) open @endif>
        <summary>＋ Tambah folder baru</summary>
        <form method="POST" action="{{ route('admin.gallery-albums.store') }}" enctype="multipart/form-data" class="fa-form" @if ($du) data-direct-upload="{{ $du }}" @endif>
            @csrf
            <input type="hidden" name="_new" value="1">
            <div class="field-row">
                <div class="field"><label>Nama folder</label><input type="text" name="name" maxlength="60" required class="input" value="{{ old('_new') ? old('name') : '' }}" placeholder="Contoh: Turnamen 2026"></div>
                <div class="field"><label>Keterangan singkat</label><input type="text" name="description" maxlength="120" class="input" value="{{ old('_new') ? old('description') : '' }}" placeholder="Contoh: Dokumentasi turnamen antar klub"></div>
            </div>
            <div class="field">
                <span class="field-label">Isi folder</span>
                <div class="fa-kind">
                    <label><input type="radio" name="kind" value="image" checked> 📸 Foto</label>
                    <label><input type="radio" name="kind" value="video"> ▶ Video</label>
                </div>
            </div>
            <div class="field"><label>Foto sampul (opsional)</label><input type="file" name="cover_file" accept="image/*" data-folder="gallery/albums" class="input"><p class="field-hint">Jika kosong, sampul memakai foto pertama di folder.</p></div>
            <div class="fa-foot"><span></span><button type="submit" class="btn btn-primary">Buat folder</button></div>
        </form>
    </details>

    <div class="fa-list">
        @foreach ($albums as $a)
            <article class="fa-card {{ $a->is_active ? '' : 'off' }}">
                <div class="fa-row">
                    <div class="fa-cover k-{{ $a->key }}"><span @if ($a->cover_url) style="background-image:url('{{ $a->cover_url }}')" @endif>@unless ($a->cover_url){{ $icons[$a->key] ?? ($a->kind === 'video' ? '▶' : '📁') }}@endunless</span></div>
                    <div class="fa-info">
                        <b>{{ $a->name }}</b>
                        <small>{{ $a->description ?: '—' }}</small>
                        <div class="fa-tags">
                            <span>{{ $a->kind === 'video' ? 'Video' : 'Foto' }}</span>
                            <span>{{ $counts[$a->key] ?? 0 }} isi</span>
                            @unless ($a->is_active)<span class="off">Disembunyikan</span>@endunless
                        </div>
                    </div>
                    <div class="fa-act">
                        <form method="POST" action="{{ route('admin.gallery-albums.update', $a) }}">@csrf @method('PUT')<input type="hidden" name="move" value="up"><button class="fa-btn" type="submit" title="Naikkan" aria-label="Naikkan" @disabled($loop->first)>↑</button></form>
                        <form method="POST" action="{{ route('admin.gallery-albums.update', $a) }}">@csrf @method('PUT')<input type="hidden" name="move" value="down"><button class="fa-btn" type="submit" title="Turunkan" aria-label="Turunkan" @disabled($loop->last)>↓</button></form>
                        <a class="fa-btn" href="{{ route('admin.gallery.index', ['album' => $a->key]) }}">Isi folder</a>
                        <form method="POST" action="{{ route('admin.gallery-albums.update', $a) }}">@csrf @method('PUT')<input type="hidden" name="quick_toggle" value="1"><button class="fa-btn" type="submit">{{ $a->is_active ? 'Sembunyikan' : 'Tampilkan' }}</button></form>
                    </div>
                </div>

                <details class="fa-edit" @if (old('_album') == $a->id) open @endif>
                    <summary>✎ Ubah nama, keterangan &amp; sampul</summary>
                    <form method="POST" action="{{ route('admin.gallery-albums.update', $a) }}" enctype="multipart/form-data" class="fa-form" @if ($du) data-direct-upload="{{ $du }}" @endif>
                        @csrf @method('PUT')
                        <input type="hidden" name="_album" value="{{ $a->id }}">
                        <div class="field-row">
                            <div class="field"><label>Nama folder</label><input type="text" name="name" maxlength="60" required class="input" value="{{ old('_album') == $a->id ? old('name') : $a->name }}"></div>
                            <div class="field"><label>Keterangan singkat</label><input type="text" name="description" maxlength="120" class="input" value="{{ old('_album') == $a->id ? old('description') : $a->description }}"></div>
                        </div>
                        <div class="field">
                            <span class="field-label">Isi folder</span>
                            <div class="fa-kind">
                                <label><input type="radio" name="kind" value="image" @checked($a->kind === 'image')> 📸 Foto</label>
                                <label><input type="radio" name="kind" value="video" @checked($a->kind === 'video')> ▶ Video</label>
                            </div>
                        </div>
                        <div class="field">
                            <label>Foto sampul</label>
                            <input type="file" name="cover_file" accept="image/*" data-folder="gallery/albums" class="input">
                            @if ($a->cover)<label class="checkbox"><input type="checkbox" name="remove_cover" value="1"> Hapus foto sampul (pakai foto pertama di folder)</label>@endif
                        </div>
                        <div class="fa-foot"><span></span><button type="submit" class="btn btn-primary">Simpan folder</button></div>
                    </form>
                    @if ($albums->count() > 1)
                        <form method="POST" action="{{ route('admin.gallery-albums.destroy', $a) }}" class="fa-del" data-confirm="Hapus folder &quot;{{ $a->name }}&quot;? Isinya akan dipindah, tidak ikut terhapus.">
                            @csrf @method('DELETE')
                            Hapus folder ini, pindahkan isinya ke
                            <select name="move_to" aria-label="Pindahkan isi ke">
                                @foreach ($albums->where('id', '!=', $a->id) as $o)<option value="{{ $o->key }}" @selected($o->kind === $a->kind && $loop->first)>{{ $o->name }}</option>@endforeach
                            </select>
                            <button type="submit" class="fa-btn danger">Hapus folder</button>
                        </form>
                    @endif
                </details>
            </article>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
    </script>
@endpush
