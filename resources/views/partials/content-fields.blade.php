{{-- Isian satu bagian Konten Website. Variabel: $key (kunci bagian), $section (dari Content::sections()) --}}
@php
    use App\Support\Content;
    use App\Support\Icons;

    $ceOld   = session('content_section') === $key;
    $ceVal   = fn ($f) => $ceOld ? old($f, Content::get($f)) : Content::get($f);
    $ceBag   = $errors->getBag('content');
@endphp
@once
<style>
    .ce-f { display: grid; gap: 14px; color: var(--d-text, #17261d); }
    .ce-row { display: grid; gap: 6px; min-width: 0; }
    .ce-row > label, .ce-lbl { color: var(--d-text-2, #3c4a42); font-size: 13px; font-weight: 600; }
    .ce-row input[type=text], .ce-row textarea {
        width: 100%; min-height: 44px; padding: 10px 14px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .16); border-radius: 12px;
        background: var(--d-surface, #fff); color: var(--d-text, #17261d); font: inherit; font-size: 14.5px; line-height: 1.45; outline: none;
    }
    .ce-row textarea { min-height: 84px; resize: vertical; }
    .ce-row input:focus, .ce-row textarea:focus { border-color: var(--d-ink-green, #1f4d33); box-shadow: 0 0 0 4px rgba(var(--d-green-rgb, 31, 77, 51), .1); }
    .ce-hint { color: var(--d-muted, #77837b); font-size: 12px; line-height: 1.45; }
    .ce-err { color: var(--d-red-ink, #c9413a); font-size: 12px; font-weight: 600; }
    .ce-errbox { padding: 10px 12px; border-radius: 12px; background: var(--d-red-tint, #fbe7e5); border: 1px solid rgba(201, 65, 58, .3); color: var(--d-red-ink, #8f2a24); font-size: 13px; }
    .ce-group { padding-top: 14px; border-top: 1px dashed rgba(var(--d-ink-rgb, 23, 46, 33), .14); }
    .ce-toggle { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 14px; background: var(--d-tint-2, #f0f5ef); cursor: pointer; font-size: 14px; font-weight: 600; }
    .ce-toggle input { width: 20px; height: 20px; accent-color: #1f4d33; }
    .ce-img { display: flex; gap: 14px; align-items: flex-start; }
    .ce-img-prev { flex: 0 0 132px; height: 88px; border-radius: 12px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .1); background: var(--d-tint, #e5eee6) center / cover no-repeat; }
    .ce-img > div:last-child { flex: 1; min-width: 0; display: grid; gap: 8px; }
    .ce-file { display: inline-flex; align-items: center; justify-content: center; height: 42px; padding: 0 16px; border: 1px dashed #3d7d57; border-radius: 12px; color: var(--d-ink-green, #1f4d33); background: var(--d-tint-2, #f0f5ef); font-size: 13.5px; font-weight: 600; cursor: pointer; }
    .ce-file input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .ce-check { display: flex; align-items: center; gap: 8px; color: var(--d-text-2, #3c4a42); font-size: 13px; cursor: pointer; }
    .ce-icons { display: flex; flex-wrap: wrap; gap: 6px; }
    .ce-icons label { cursor: pointer; }
    .ce-icons input { position: absolute; opacity: 0; pointer-events: none; }
    .ce-icons span { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .14); background: var(--d-surface, #fff); color: var(--d-text-2, #3c4a42); }
    .ce-icons span svg { width: 19px; height: 19px; }
    .ce-icons input:checked + span { background: var(--d-btn, #1f4d33); border-color: var(--d-btn, #1f4d33); color: #fff; }
    .ce-icons input:focus-visible + span { outline: 2px solid #3d7d57; outline-offset: 2px; }
</style>
<script>
document.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.matches || !input.matches('[data-ce-file]') || !input.files[0]) return;
    var prev = input.closest('.ce-img').querySelector('[data-ce-prev]');
    if (prev) prev.style.backgroundImage = 'url(' + URL.createObjectURL(input.files[0]) + ')';
});
</script>
@endonce

<div class="ce-f">
    @if ($ceOld && $ceBag->any())
        <div class="ce-errbox">@foreach ($ceBag->all() as $m)<div>{{ $m }}</div>@endforeach</div>
    @endif

    @foreach ($section['fields'] as $f => $def)
        @php
            [$type, $label] = $def;
            $hint = $def[3] ?? null;
            $id   = 'ce_' . $key . '_' . $f;
            $newGroup = preg_match('/^home_feat[2-3]_icon$/', $f);
        @endphp

        @if ($type === 'toggle')
            <input type="hidden" name="{{ $f }}" value="0">
            <label class="ce-toggle"><span>{{ $label }}</span><input type="checkbox" name="{{ $f }}" value="1" @checked($ceVal($f) !== '0')></label>
        @elseif ($type === 'image')
            @php $url = Content::imageUrl($f) ?? ($f === 'bg_public' ? \App\Support\Brand::background('public') : null); @endphp
            <div class="ce-row">
                <span class="ce-lbl">{{ $label }}</span>
                <div class="ce-img">
                    <div class="ce-img-prev" data-ce-prev style="{{ $url ? "background-image:url('" . e($url) . "')" : '' }}"></div>
                    <div>
                        <label class="ce-file">{!! Icons::svg('upload') !!}&nbsp; Pilih foto<input type="file" name="{{ $f }}" accept="image/*" data-folder="content" data-ce-file></label>
                        @if (\App\Models\Setting::get($f))
                            <label class="ce-check"><input type="checkbox" name="remove_{{ $f }}" value="1"> Hapus, pakai foto bawaan</label>
                        @endif
                        @if ($hint)<span class="ce-hint">{{ $hint }}</span>@endif
                    </div>
                </div>
            </div>
        @elseif ($type === 'icon')
            <div class="ce-row {{ $newGroup ? 'ce-group' : '' }}">
                <span class="ce-lbl">{{ $label }}</span>
                <div class="ce-icons">
                    @foreach (Content::ICONS as $ic => $icLabel)
                        <label title="{{ $icLabel }}"><input type="radio" name="{{ $f }}" value="{{ $ic }}" @checked($ceVal($f) === $ic)><span>{!! Icons::svg($ic) !!}</span></label>
                    @endforeach
                </div>
            </div>
        @else
            <div class="ce-row">
                <label for="{{ $id }}">{{ $label }}</label>
                @if ($type === 'textarea')
                    <textarea name="{{ $f }}" id="{{ $id }}" maxlength="2000">{{ $ceVal($f) }}</textarea>
                @else
                    <input type="text" name="{{ $f }}" id="{{ $id }}" maxlength="255" value="{{ $ceVal($f) }}">
                @endif
                @if ($hint)<span class="ce-hint">{{ $hint }}</span>@endif
                @if ($ceOld && $ceBag->has($f))<span class="ce-err">{{ $ceBag->first($f) }}</span>@endif
            </div>
        @endif
    @endforeach
</div>
