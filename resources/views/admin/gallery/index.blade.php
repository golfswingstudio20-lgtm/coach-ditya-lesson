@extends('admin.gallery.layout')

@section('title', 'Kelola Galeri')

@section('content')
    @php
        $tabs = [
            ''      => ['label' => 'Semua', 'count' => $counts['all']],
            'image' => ['label' => 'Foto',  'count' => $counts['image']],
            'video' => ['label' => 'Video', 'count' => $counts['video']],
        ];
    @endphp

    <section class="page-head">
        <div>
            <h1>Kelola Galeri</h1>
            <p>Foto dan video berstatus "Tampil" langsung muncul di halaman Galeri untuk customer dan visitor.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('galeri') }}" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat halaman publik</a>
            <a href="{{ route('admin.gallery.create') }}" class="btn btn-primary">＋ Tambah foto / video</a>
        </div>
    </section>

    <nav class="tabs">
        @foreach ($tabs as $tabType => $tab)
            <a href="{{ route('admin.gallery.index', $tabType ? ['type' => $tabType] : []) }}"
               class="tab {{ ($type ?? '') === $tabType ? 'active' : '' }}">
                {{ $tab['label'] }}<span>{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>

    @if ($galleries->isEmpty())
        <div class="empty-state">
            Belum ada {{ $type === 'video' ? 'video' : ($type === 'image' ? 'foto' : 'foto atau video') }} di galeri.
            <br>
            <a href="{{ route('admin.gallery.create') }}" class="btn btn-primary">＋ Tambah sekarang</a>
        </div>
    @else
        <section class="gallery-grid">
            @foreach ($galleries as $gallery)
                <article class="item-card {{ $gallery->isActive() ? '' : 'inactive' }}">
                    <div class="item-thumb">
                        @if ($gallery->thumbnail_url)
                            <img src="{{ $gallery->thumbnail_url }}" alt="{{ $gallery->title }}" loading="lazy">
                        @elseif ($gallery->video_file_url)
                            <video src="{{ $gallery->video_file_url }}#t=1" muted preload="metadata"></video>
                        @elseif ($gallery->video_platform === 'instagram')
                            <div class="no-thumb" style="background:linear-gradient(135deg,#f9ce34,#ee2a7b 50%,#6228d7);color:#fff">IG</div>
                        @else
                            <div class="no-thumb">▧</div>
                        @endif

                        @if ($gallery->type === 'video')
                            <span class="play-icon">▶</span>
                        @endif

                        <div class="badges">
                            <span class="badge type">
                                {{ $gallery->type === 'image' ? 'Foto' : ['youtube' => 'YouTube', 'instagram' => 'Instagram'][$gallery->video_platform] ?? 'Video' }}
                            </span>
                            <span class="badge {{ $gallery->status }}">
                                {{ $gallery->isActive() ? 'Tampil' : 'Disembunyikan' }}
                            </span>
                        </div>
                    </div>

                    <div class="item-body">
                        <strong>{{ $gallery->title }}</strong>
                        <small>{{ $gallery->category ?: '—' }}</small>
                        <div class="item-meta">
                            Urutan {{ $gallery->sort_order }} · Diperbarui {{ $gallery->updated_at?->diffForHumans() }}
                        </div>
                    </div>

                    <div class="item-actions">
                        <a href="{{ route('admin.gallery.edit', $gallery) }}">Edit</a>

                        <form method="POST" action="{{ route('admin.gallery.update', $gallery) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="quick_toggle" value="1">
                            <button type="submit">{{ $gallery->isActive() ? 'Sembunyikan' : 'Tampilkan' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.gallery.destroy', $gallery) }}"
                              data-confirm="Hapus &quot;{{ $gallery->title }}&quot; dari galeri?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger">Hapus</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm(form.dataset.confirm)) event.preventDefault();
            });
        });
    </script>
@endpush
