{{-- Bagian "About Coach" di halaman Home: profil 1 coach (tema Fairway). Data dari Admin > About Coach / tombol edit di Home. --}}
@php
    use App\Support\Icons;

    $hcReady   = class_exists(\App\Models\Coach::class) && \Illuminate\Support\Facades\Schema::hasTable('coaches');
    $hcIsAdmin = auth()->check() && auth()->user()->role === 'admin';
    $hcCoach   = $hcReady ? ($hcIsAdmin ? \App\Models\Coach::main() : \App\Models\Coach::active()->ordered()->first()) : null;
    if (! $hcCoach && $hcIsAdmin && $hcReady) {
        $hcCoach = new \App\Models\Coach(['name' => 'Nama Coach', 'is_active' => true, 'skills' => []]);
    }
@endphp

@if ($hcCoach)
@php
    $hcExp   = collect($hcCoach->experiences ?? [])->filter(fn ($e) => ! empty($e['text']));
    $hcCert  = collect($hcCoach->certifications ?? []);
    $hcAch   = collect($hcCoach->achievements ?? []);
    $hcSkill = collect($hcCoach->skills ?? []);
    $hcStats = array_filter([
        $hcCoach->years_experience ? [$hcCoach->years_experience . '+', \App\Support\Content::get('coach_stat_years')] : null,
        $hcCoach->students ? [$hcCoach->students, \App\Support\Content::get('coach_stat_students')] : null,
        $hcCert->isNotEmpty() ? [$hcCert->count(), \App\Support\Content::get('coach_stat_cert')] : null,
    ]);
@endphp
<style>
    .hc { position: relative; padding: 72px 0; background: var(--fw-bg, var(--d-bg, #f3f1ea)); color: var(--fw-text, var(--d-text, #17261d)); }
    .hc *, .hc *::before, .hc *::after { box-sizing: border-box; }
    .hc-inner { width: min(1180px, 100% - 48px); margin: 0 auto; display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); column-gap: 56px; align-items: start; grid-template-areas: "photo head" "photo body"; grid-template-rows: auto 1fr; }
    .hc-head { grid-area: head; }
    .hc-body { grid-area: body; }

    .hc-photo-wrap { grid-area: photo; position: sticky; top: 96px; }
    .hc-photo { position: relative; aspect-ratio: 4 / 5; border-radius: 28px; overflow: hidden; background: var(--d-bg-2, #d9e3d6) center top / cover no-repeat; box-shadow: var(--fw-shadow-lg); }
    .hc-photo canvas { width: 100%; height: 100%; display: block; }
    .hc-photo::after { content: ""; position: absolute; inset: 55% 0 0; background: linear-gradient(to bottom, rgba(var(--d-ink-rgb, 23, 46, 33), 0), rgba(var(--d-ink-rgb, 23, 46, 33), .78)); }
    .hc-badge { position: absolute; z-index: 1; top: 16px; left: 16px; padding: 6px 12px; border-radius: 99px; background: rgba(var(--d-glass-rgb, 255, 255, 255), .92); color: var(--d-ink-green, var(--fw-green)); font-size: 12px; font-weight: 600; }
    .hc-photo-name { position: absolute; z-index: 1; left: 22px; right: 22px; bottom: 20px; color: #fff; }
    .hc-photo-name strong { display: block; font-family: var(--fw-serif); font-size: 28px; font-weight: 600; line-height: 1.1; }
    .hc-photo-name span { display: block; margin-top: 4px; color: rgba(255, 255, 255, .82); font-size: 13.5px; }
    .hc-float { position: absolute; z-index: 2; right: -18px; top: 18%; padding: 14px 16px; border-radius: 18px; background: var(--fw-surface, var(--d-surface, #fff)); box-shadow: var(--fw-shadow-lg); text-align: center; }
    .hc-float strong { display: block; font-family: var(--fw-serif); color: var(--d-ink-green, var(--fw-green)); font-size: 30px; font-weight: 600; line-height: 1; }
    .hc-float span { color: var(--fw-muted); font-size: 11.5px; }

    .hc h2 { margin: 8px 0 6px; font-family: var(--fw-serif); font-size: clamp(30px, 3.6vw, 44px); font-weight: 600; line-height: 1.1; letter-spacing: -.5px; }
    .hc h2 span { font-style: italic; color: var(--fw-green-3); }
    .hc-role { margin-bottom: 18px; color: var(--fw-muted); font-size: 15px; }
    .hc-bio { color: var(--fw-text-2); font-size: 15.5px; line-height: 1.8; white-space: pre-line; }

    .hc-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 22px; }
    .hc-stats div { padding: 14px 16px; border-radius: 16px; background: var(--fw-surface); border: 1px solid var(--fw-line); box-shadow: var(--fw-shadow); }
    .hc-stats strong { display: block; font-family: var(--fw-serif); color: var(--d-ink-green, var(--fw-green)); font-size: 28px; font-weight: 600; line-height: 1.1; }
    .hc-stats span { color: var(--fw-muted); font-size: 12.5px; }

    .hc-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
    .hc-box { padding: 18px; border-radius: 18px; background: var(--fw-surface); border: 1px solid var(--fw-line); box-shadow: var(--fw-shadow); }
    .hc-box.wide { grid-column: 1 / -1; }
    .hc-box h3 { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; font-size: 15px; font-weight: 700; }
    .hc-box h3 i { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 10px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .hc-box h3 i svg { width: 17px; height: 17px; }
    .hc-time { position: relative; display: grid; gap: 14px; padding-left: 20px; }
    .hc-time::before { content: ""; position: absolute; left: 5px; top: 6px; bottom: 6px; width: 2px; background: linear-gradient(var(--fw-green-3), rgba(61, 125, 87, .15)); }
    .hc-time div { position: relative; }
    .hc-time div::before { content: ""; position: absolute; left: -20px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: var(--d-surface, #fff); border: 3px solid var(--fw-green-3); }
    .hc-time small { display: block; color: var(--fw-green-3); font-size: 12px; font-weight: 600; }
    .hc-time span { font-size: 14px; color: var(--fw-text); }
    .hc-list { display: grid; gap: 9px; list-style: none; margin: 0; padding: 0; }
    .hc-list li { display: flex; gap: 10px; font-size: 14px; line-height: 1.45; color: var(--fw-text-2); }
    .hc-list li::before { content: "✓"; flex: 0 0 20px; height: 20px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); font-size: 11px; font-weight: 700; }
    .hc-list.trophy li::before { content: "★"; background: var(--d-orange-tint, #fdf1de); color: #c9861c; }
    .hc-chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .hc-chips span { padding: 7px 14px; border-radius: 99px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); font-size: 13px; font-weight: 600; }
    .hc-quote { margin: 16px 0 0; padding: 18px 20px; border-radius: 18px; background: var(--fw-green); color: #fff; font-family: var(--fw-serif); font-size: 18px; font-style: italic; line-height: 1.5; }
    .hc-cta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }

    @media (max-width: 900px) {
        .hc-inner { grid-template-columns: 1fr; grid-template-areas: "head" "photo" "body"; grid-template-rows: auto; }
        .hc-photo-wrap { position: relative; top: 0; max-width: 440px; width: 100%; margin: 0 auto; }
        .hc-float { right: -6px; }
        .hc-body { margin-top: 22px; }
    }
    @media (max-width: 820px) {
        .hc { padding: 40px 0 44px; }
        .hc-inner { width: calc(100% - 32px); }
        .hc-cols { grid-template-columns: 1fr; }
        .hc-bio { font-size: 14.5px; }
        .hc-stats { gap: 8px; }
        .hc-stats div { padding: 12px 10px; text-align: center; }
        .hc-stats strong { font-size: 24px; }
        .hc-cta .fw-btn { flex: 1; }
    }
</style>

<section class="hc" id="coach" aria-labelledby="hcTitle">
    <div class="hc-inner">
        <div class="hc-photo-wrap">
            <div class="hc-photo" id="hcPhoto" @if ($hcCoach->photo_url) style="background-image:url('{{ $hcCoach->photo_url }}')" @endif>
                @unless ($hcCoach->photo_url)<canvas width="480" height="600" id="hcArt" aria-hidden="true"></canvas>@endunless
                @if ($hcCoach->badge)<span class="hc-badge">{{ $hcCoach->badge }}</span>@endif
                <div class="hc-photo-name">
                    <strong>{{ $hcCoach->name }}</strong>
                    @if ($hcCoach->role)<span>{{ $hcCoach->role }}</span>@endif
                </div>
            </div>
            @if ($hcCoach->years_experience)
                <div class="hc-float"><strong>{{ $hcCoach->years_experience }}+</strong><span style="display:block;max-width:9ch">{{ \App\Support\Content::get('coach_float') }}</span></div>
            @endif
        </div>

        <div class="hc-head">
            <span class="fw-eyebrow">{{ $hcCoach->section_label_text }}</span>
            <h2 id="hcTitle">{!! $hcCoach->section_title_html !!}</h2>
            <div class="hc-role">{{ $hcCoach->name }}@if ($hcCoach->role) · {{ $hcCoach->role }}@endif</div>
        </div>

        <div class="hc-body">
            @if ($hcCoach->bio)<p class="hc-bio">{{ $hcCoach->bio }}</p>@endif

            @if ($hcStats)
                <div class="hc-stats">
                    @foreach ($hcStats as [$val, $label])<div><strong>{{ $val }}</strong><span>{{ $label }}</span></div>@endforeach
                </div>
            @endif

            <div class="hc-cols">
                @if ($hcExp->isNotEmpty())
                    <div class="hc-box @if ($hcCert->isEmpty() && $hcAch->isEmpty()) wide @endif">
                        <h3><i>{!! Icons::svg('history') !!}</i>{{ \App\Support\Content::get('coach_exp_title') }}</h3>
                        <div class="hc-time">
                            @foreach ($hcExp as $e)<div>@if (! empty($e['period']))<small>{{ $e['period'] }}</small>@endif<span>{{ $e['text'] }}</span></div>@endforeach
                        </div>
                    </div>
                @endif
                @if ($hcCert->isNotEmpty() || $hcAch->isNotEmpty())
                    <div class="hc-box @if ($hcExp->isEmpty()) wide @endif">
                        @if ($hcCert->isNotEmpty())
                            <h3><i>{!! Icons::svg('award') !!}</i>{{ \App\Support\Content::get('coach_cert_title') }}</h3>
                            <ul class="hc-list">@foreach ($hcCert as $c)<li>{{ $c }}</li>@endforeach</ul>
                        @endif
                        @if ($hcAch->isNotEmpty())
                            <h3 @if ($hcCert->isNotEmpty()) style="margin-top: 18px" @endif><i>{!! Icons::svg('trophy') !!}</i>{{ \App\Support\Content::get('coach_ach_title') }}</h3>
                            <ul class="hc-list trophy">@foreach ($hcAch as $a)<li>{{ $a }}</li>@endforeach</ul>
                        @endif
                    </div>
                @endif
                @if ($hcSkill->isNotEmpty())
                    <div class="hc-box wide">
                        <h3><i>{!! Icons::svg('golf') !!}</i>{{ \App\Support\Content::get('coach_skill_title') }}</h3>
                        <div class="hc-chips">@foreach ($hcSkill as $s)<span>{{ $s }}</span>@endforeach</div>
                    </div>
                @endif
            </div>

            @if ($hcCoach->quote)<blockquote class="hc-quote">“{{ $hcCoach->quote }}”</blockquote>@endif

            <div class="hc-cta">
                <a class="fw-btn" href="{{ auth()->check() ? route('booking') : route('login') }}">{{ \App\Support\Content::get('coach_btn_book') }}</a>
                @if (Route::has('chat') && auth()->check() && auth()->user()->role === 'customer')
                    <a class="fw-btn ghost" href="{{ route('chat') }}">{!! Icons::svg('chat') !!} {{ \App\Support\Content::get('coach_btn_chat') }}</a>
                @else
                    <a class="fw-btn ghost" href="{{ route('program') }}">{{ \App\Support\Content::get('coach_btn_program') }}</a>
                @endif
                @if ($hcIsAdmin)<button type="button" class="ce-btn light" data-ce-open="coach_text">✎ Edit judul kotak & tombol</button>@endif
            </div>
        </div>
    </div>
    @if ($hcIsAdmin)
        @include('partials.home-coach-editor', ['coach' => $hcCoach])
    @endif
</section>

<script>
(function () {
    'use strict';
    var cv = document.getElementById('hcArt');
    if (!cv) return;
    var x = cv.getContext('2d'), w = cv.width, h = cv.height;
    var sky = x.createLinearGradient(0, 0, 0, h);
    sky.addColorStop(0, '#e9efe1'); sky.addColorStop(.62, '#cfdcc6'); sky.addColorStop(1, '#9fb89a');
    x.fillStyle = sky; x.fillRect(0, 0, w, h);
    var g = x.createRadialGradient(w * .72, h * .24, 4, w * .72, h * .24, w * .5);
    g.addColorStop(0, 'rgba(255,244,214,.9)'); g.addColorStop(1, 'rgba(255,244,214,0)'); x.fillStyle = g; x.fillRect(0, 0, w, h);
    x.fillStyle = '#7fa178'; x.beginPath(); x.moveTo(0, h * .7); x.bezierCurveTo(w * .3, h * .6, w * .6, h * .74, w, h * .64); x.lineTo(w, h); x.lineTo(0, h); x.fill();
    x.save(); x.translate(w * .46, h * .82); x.fillStyle = '#1f4d33'; x.strokeStyle = '#1f4d33'; x.lineCap = 'round'; x.rotate(-.08);
    x.beginPath(); x.arc(0, -h * .5, w * .07, 0, Math.PI * 2); x.fill();
    x.lineWidth = w * .11; x.beginPath(); x.moveTo(0, -h * .42); x.lineTo(w * .02, -h * .2); x.stroke();
    x.lineWidth = w * .06; x.beginPath(); x.moveTo(w * .02, -h * .2); x.lineTo(-w * .06, 0); x.moveTo(w * .02, -h * .2); x.lineTo(w * .1, 0); x.stroke();
    x.lineWidth = w * .045; x.beginPath(); x.moveTo(0, -h * .38); x.lineTo(w * .14, -h * .5); x.stroke();
    x.lineWidth = w * .012; x.strokeStyle = '#ffffff'; x.beginPath(); x.moveTo(w * .14, -h * .5); x.lineTo(w * .32, -h * .66); x.stroke();
    x.restore();
})();
</script>
@endif
