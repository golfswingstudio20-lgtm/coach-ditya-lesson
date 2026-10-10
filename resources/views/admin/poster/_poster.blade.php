{{--
    Poster jadwal 1080 × 1350 (pas untuk feed Instagram & WhatsApp).
    Variabel: $poster (dari SchedulePoster::build)
--}}
@php
    use App\Support\SchedulePoster as SP;

    $days   = $poster['days'];
    $matrix = count($days) > 2;
    $notes  = SP::notes();
    $logo   = SP::logoDataUri();
    $sub    = SP::show('poster_subtitle');
    $footer = SP::show('poster_footer');
    $contact = SP::show('poster_contact');
    $website = SP::show('poster_website');
    $ig      = SP::show('poster_ig');

    // Hitung tinggi baris supaya semua muat di 1350 px
    $rowCount  = $matrix ? max(1, count($poster['hours'])) : max(1, collect($days)->max(fn ($d) => count($d['rows'])));
    $noteLines = collect($notes)->sum(fn ($n) => max(1, (int) ceil(mb_strlen($n) / 70)));
    $notesH    = $notes ? 86 + $noteLines * 33 : 0;
    $footH     = ($contact || $website || $ig) ? 110 : ($footer ? 60 : 20);
    $avail     = 1350 - 330 - $notesH - $footH - 40;
    $rowH      = (int) max(26, min($matrix ? 60 : 74, floor(($avail - 92) / $rowCount)));
    $fs        = round(min(count($days) === 1 ? 28 : 24, max(13, $rowH * .4)), 1);

    $ico = [
        'cal'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="17" rx="3"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/></svg>',
        'wa'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>',
        'web'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9.5"/><path d="M2.5 12h19M12 2.5c2.6 2.8 3.9 6 3.9 9.5s-1.3 6.7-3.9 9.5c-2.6-2.8-3.9-6-3.9-9.5S9.4 5.3 12 2.5Z"/></svg>',
        'ig'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor" stroke="none"/></svg>',
        'flag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 21V3l11 4.5L6 12"/><ellipse cx="9" cy="21" rx="6" ry="1.2"/></svg>',
    ];
@endphp
<style>
    @font-face { font-family: 'PSerif'; font-weight: 600; src: url('{{ asset('fonts/poster/playfair-display-latin-600-normal.woff2') }}') format('woff2'); }
    @font-face { font-family: 'PSerif'; font-weight: 700; src: url('{{ asset('fonts/poster/playfair-display-latin-700-normal.woff2') }}') format('woff2'); }
    @font-face { font-family: 'PSerif'; font-weight: 600; font-style: italic; src: url('{{ asset('fonts/poster/playfair-display-latin-600-italic.woff2') }}') format('woff2'); }
    @font-face { font-family: 'PSerif'; font-weight: 700; font-style: italic; src: url('{{ asset('fonts/poster/playfair-display-latin-700-italic.woff2') }}') format('woff2'); }
    @font-face { font-family: 'PSans'; font-weight: 400; src: url('{{ asset('fonts/poster/inter-latin-400-normal.woff2') }}') format('woff2'); }
    @font-face { font-family: 'PSans'; font-weight: 500; src: url('{{ asset('fonts/poster/inter-latin-500-normal.woff2') }}') format('woff2'); }
    @font-face { font-family: 'PSans'; font-weight: 600; src: url('{{ asset('fonts/poster/inter-latin-600-normal.woff2') }}') format('woff2'); }
    @font-face { font-family: 'PSans'; font-weight: 700; src: url('{{ asset('fonts/poster/inter-latin-700-normal.woff2') }}') format('woff2'); }

    .psx, .psx * { box-sizing: border-box; margin: 0; padding: 0; }
    .psx { position: relative; width: 1080px; height: 1350px; overflow: hidden; display: flex; flex-direction: column; padding: 56px 64px 44px; background: #f3f1ea; color: #17261d; font-family: 'PSans', Arial, sans-serif; }
    .psx-deco1 { position: absolute; right: -170px; top: -190px; width: 520px; height: 520px; border-radius: 50%; background: #e3ebdc; }
    .psx-deco2 { position: absolute; right: 118px; top: 196px; width: 64px; height: 64px; border-radius: 50%; background: #cde8a3; }
    .psx-deco3 { position: absolute; left: -90px; bottom: -110px; width: 300px; height: 300px; border-radius: 50%; border: 36px solid #e3ebdc; }
    .psx > *:not([class^="psx-deco"]) { position: relative; z-index: 1; }

    .psx-head { display: flex; align-items: center; gap: 26px; }
    .psx-logo { width: 112px; height: 112px; flex: 0 0 112px; border-radius: 50%; background: #fff; box-shadow: 0 14px 32px rgba(23, 46, 33, .14); display: grid; place-items: center; overflow: hidden; color: #1f4d33; }
    .psx-logo img { width: 100%; height: 100%; object-fit: contain; }
    .psx-logo svg { width: 54px; height: 54px; }
    .psx-sub { color: #3d7d57; font-size: 21px; font-weight: 700; letter-spacing: 6px; text-transform: uppercase; }
    .psx-title { margin-top: 4px; font-family: 'PSerif', Georgia, serif; font-size: 78px; font-weight: 700; line-height: 1; letter-spacing: -1.5px; color: #17261d; }
    .psx-title em { font-style: italic; color: #3d7d57; }
    .psx-date { align-self: flex-start; display: inline-flex; align-items: center; gap: 14px; margin-top: 28px; padding: 16px 28px; border-radius: 99px; background: #1f4d33; color: #cde8a3; font-size: 25px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; }
    .psx-date svg { width: 28px; height: 28px; }

    .psx-grid { margin-top: 30px; display: grid; gap: 22px; }
    .psx-day { background: #fff; border-radius: 30px; overflow: hidden; box-shadow: 0 16px 40px rgba(23, 46, 33, .08); }
    .psx-day-hd { display: flex; align-items: baseline; justify-content: space-between; padding: 18px 28px; background: #1f4d33; color: #fff; }
    .psx-day-hd b { font-family: 'PSerif', Georgia, serif; font-size: 34px; font-weight: 700; }
    .psx-day-hd span { color: #cde8a3; font-size: 19px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
    .psx-rows { padding: 10px 22px 16px; }
    .psx-row { display: flex; align-items: center; gap: 14px; border-bottom: 1px dashed #e2e6df; }
    .psx-row:last-child { border-bottom: 0; }
    .psx-time { flex: 0 0 auto; width: 7.2em; color: #3c4a42; font-weight: 600; font-variant-numeric: tabular-nums; }
    .psx-pill { flex: 1; min-width: 0; text-align: center; padding: .38em .8em; border-radius: 99px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; line-height: 1.15; overflow: hidden; overflow-wrap: anywhere; }
    .psx-pill.on { background: #1f4d33; color: #fff; }
    .psx-pill.off { background: #eef5e6; color: #4c8a63; font-weight: 600; letter-spacing: 2px; }
    .psx-empty { padding: 26px; text-align: center; color: #77837b; font-weight: 600; }

    .psx-table { background: #fff; border-radius: 28px; overflow: hidden; box-shadow: 0 16px 40px rgba(23, 46, 33, .08); }
    .psx-table table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .psx-table th { padding: 14px 4px; background: #1f4d33; color: #fff; font-family: 'PSerif', Georgia, serif; font-size: 25px; font-weight: 700; }
    .psx-table th small { display: block; margin-top: 2px; font-family: 'PSans', Arial, sans-serif; color: #cde8a3; font-size: 14px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
    .psx-table th:first-child { width: 150px; }
    .psx-table td { padding: 0 5px; border-bottom: 1px dashed #e2e6df; text-align: center; }
    .psx-table tr:last-child td { border-bottom: 0; }
    .psx-table td:first-child { color: #3c4a42; font-weight: 600; font-variant-numeric: tabular-nums; }
    .psx-table .psx-pill { display: block; padding: .32em .3em; font-size: .86em; letter-spacing: .2px; white-space: normal; line-height: 1.15; }
    .psx-table .psx-pill.off { letter-spacing: .6px; font-size: .74em; }
    .psx-na { color: #c3c9c0; }

    .psx-notes { margin-top: 24px; padding: 24px 32px 26px; border-radius: 28px; background: #1f4d33; color: #fff; }
    .psx-notes h3 { font-family: 'PSerif', Georgia, serif; font-size: 30px; font-style: italic; font-weight: 700; color: #cde8a3; }
    .psx-notes ul { list-style: none; margin-top: 10px; display: grid; gap: 7px; }
    .psx-notes li { position: relative; padding-left: 26px; font-size: 20px; line-height: 1.32; color: rgba(255, 255, 255, .92); }
    .psx-notes li::before { content: ""; position: absolute; left: 2px; top: .5em; width: 9px; height: 9px; border-radius: 50%; background: #cde8a3; }

    .psx-foot { margin-top: auto; padding-top: 22px; text-align: center; }
    .psx-tag { font-family: 'PSerif', Georgia, serif; font-size: 26px; font-style: italic; font-weight: 700; color: #1f4d33; }
    .psx-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; margin-top: 12px; }
    .psx-links span { display: inline-flex; align-items: center; gap: 9px; padding: 10px 18px; border-radius: 99px; background: #fff; box-shadow: 0 8px 20px rgba(23, 46, 33, .07); color: #17261d; font-size: 19px; font-weight: 600; }
    .psx-links svg { width: 22px; height: 22px; color: #3d7d57; }
</style>

<div class="psx" id="posterNode" data-no-mobile>
    <span class="psx-deco1"></span><span class="psx-deco2"></span><span class="psx-deco3"></span>

    <div class="psx-head">
        @if (SP::get('poster_show_logo') === '1')
            <div class="psx-logo">@if ($logo)<img src="{{ $logo }}" alt="">@else{!! $ico['flag'] !!}@endif</div>
        @endif
        <div>
            @if ($sub)<div class="psx-sub">{{ $sub }}</div>@endif
            <div class="psx-title">{!! SP::titleHtml() !!}</div>
        </div>
    </div>

    <div class="psx-date">{!! $ico['cal'] !!} {{ $poster['range'] }}</div>

    @if ($matrix)
        <div class="psx-grid">
            <div class="psx-table" style="font-size: {{ $fs }}px">
                <table>
                    <thead><tr><th>Time</th>@foreach ($days as $d)<th>{{ $d['short'] }}<small>{{ $d['dm'] }}</small></th>@endforeach</tr></thead>
                    <tbody>
                        @forelse ($poster['hours'] as $h => $label)
                            <tr style="height: {{ $rowH }}px">
                                <td>{{ $label }}</td>
                                @foreach ($days as $d)
                                    @php $cell = $d['rows'][$h] ?? null; @endphp
                                    <td>@if ($cell)<span class="psx-pill {{ $cell['booked'] ? 'on' : 'off' }}">{{ $cell['label'] }}</span>@else<span class="psx-na">–</span>@endif</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($days) + 1 }}" class="psx-empty">Tidak ada jadwal</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="psx-grid" style="grid-template-columns: repeat({{ count($days) }}, minmax(0, 1fr)); font-size: {{ $fs }}px">
            @foreach ($days as $d)
                <div class="psx-day">
                    <div class="psx-day-hd"><b>{{ $d['day'] }}</b><span>{{ $d['dm'] }}</span></div>
                    <div class="psx-rows">
                        @forelse ($d['rows'] as $r)
                            <div class="psx-row" style="height: {{ $rowH }}px">
                                <span class="psx-time">{{ $r['time'] }}</span>
                                <span class="psx-pill {{ $r['booked'] ? 'on' : 'off' }}" @if (count($days) > 1 && mb_strlen($r['label']) > 11) style="font-size: {{ mb_strlen($r['label']) > 16 ? '.74em' : '.84em' }}" @endif>{{ $r['label'] }}</span>
                            </div>
                        @empty
                            <div class="psx-empty">Libur / tidak ada jadwal</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($notes)
        <div class="psx-notes">
            <h3>Notes</h3>
            <ul>@foreach ($notes as $n)<li>{{ $n }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="psx-foot">
        @if ($footer)<div class="psx-tag">{{ $footer }}</div>@endif
        @if ($contact || $website || $ig)
            <div class="psx-links">
                @if ($contact)<span>{!! $ico['wa'] !!} {{ $contact }}</span>@endif
                @if ($website)<span>{!! $ico['web'] !!} {{ $website }}</span>@endif
                @if ($ig)<span>{!! $ico['ig'] !!} {{ $ig }}</span>@endif
            </div>
        @endif
    </div>
</div>
