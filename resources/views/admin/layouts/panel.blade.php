@php
    $pendingCount = \App\Models\Booking::where('status', 'pending')->count();
    $adminName    = auth()->user()->name ?? 'Admin';

    $flashType    = session('error') ? 'error' : (session('success') ? 'success' : null);
    $flashMessage = session('error') ?? session('success');
@endphp
<!DOCTYPE html>
<html lang="id" class="fw-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Admin {{ \App\Support\Brand::name() }}</title>
    @include('partials.brand-head')

    @include('partials.fw-head')
    <style>
        :root {
            --bg:          #f3f1ea;
            --bg-deep:     #ecebe3;
            --panel:       #ffffff;
            --lime:        #1f4d33;
            --lime-strong: #2a6444;
            --ink-dark:    #ffffff;
            --text:        #17261d;
            --text-soft:   #3c4a42;
            --text-muted:  #77837b;
            --line:        rgba(23, 46, 33, .09);
            --border:      rgba(23, 46, 33, .1);
            --radius:      18px;
            --accent-soft: #e5eee6;
            --shadow:      0 1px 2px rgba(23, 46, 33, .04), 0 8px 24px rgba(23, 46, 33, .06);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; }
        body { overflow: hidden; background: var(--bg); color: var(--text); font-family: var(--fw-sans, Inter, system-ui, Arial, sans-serif); font-size: 14px; -webkit-font-smoothing: antialiased; }
        button, input, select, textarea { font-family: inherit; }
        button { cursor: pointer; }
        [hidden] { display: none !important; }

        .thin-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .thin-scroll::-webkit-scrollbar-thumb { background: rgba(var(--d-ink-rgb, 23, 46, 33), .15); border-radius: 10px; }

        /* ---------- Layout ---------- */
        .admin-page { width: 100%; height: 100vh; display: grid; grid-template-columns: 264px 1fr; grid-template-rows: 70px 1fr; overflow: hidden; background: var(--bg); }
        .admin-page.sidebar-toggled { grid-template-columns: 0 1fr; }
        .admin-page.sidebar-toggled .sidebar { display: none; }

        /* ---------- Topbar ---------- */
        .topbar { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; background: rgba(var(--d-bg-rgb, 243, 241, 234), .9); border-bottom: 1px solid var(--line); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); }
        .top-left, .top-right { display: flex; align-items: center; gap: 16px; }
        .brand { display: flex; align-items: center; gap: 10px; color: var(--text); text-decoration: none; font-family: var(--fw-serif, Georgia, serif); font-size: 19px; font-weight: 600; letter-spacing: -.2px; }
        .brand-icon { font-size: 28px; }
        .brand .accent { color: var(--d-ink-green-2, #3d7d57); }
        .menu-toggle { width: 40px; height: 40px; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 50%; background: var(--d-surface, #fff); color: var(--text); }
        .menu-toggle svg { width: 19px; height: 19px; }
        .admin-profile { display: flex; align-items: center; gap: 10px; padding: 4px 14px 4px 4px; border: 1px solid var(--line); border-radius: 99px; background: var(--d-surface, #fff); text-decoration: none; color: var(--text); }
        .admin-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--lime); color: #fff; font-size: 14px; font-weight: 700; }
        .admin-name strong { display: block; font-size: 13.5px; font-weight: 600; }
        .admin-name small  { display: block; color: var(--text-muted); font-size: 11.5px; }

        /* ---------- Sidebar ---------- */
        .sidebar { grid-column: 1; grid-row: 2; height: calc(100vh - 70px); padding: 16px 14px; overflow: hidden; background: var(--d-surface, #fff); border-right: 1px solid var(--line); }
        .sidebar-scroll { height: 100%; overflow-y: auto; }
        .sidebar-section { margin-bottom: 14px; }
        .sidebar-title { margin: 14px 12px 8px; color: var(--text-muted); font-size: 11px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; }
        .sidebar-link { width: 100%; min-height: 44px; display: flex; align-items: center; gap: 12px; margin-bottom: 3px; padding: 0 12px; border: 0; border-radius: 14px; background: transparent; color: var(--text-soft); text-align: left; text-decoration: none; font-size: 14px; font-weight: 500; transition: background .15s, color .15s; }
        .sidebar-link:hover { background: var(--bg); color: var(--d-ink-green, var(--lime)); }
        .sidebar-link.active { background: var(--lime); color: #fff; box-shadow: 0 8px 18px rgba(var(--d-green-rgb, 31, 77, 51), .22); }
        .sidebar-icon { width: 22px; height: 22px; display: grid; place-items: center; color: var(--d-muted, #8a958e); }
        .sidebar-icon svg { width: 20px; height: 20px; }
        .sidebar-link.active .sidebar-icon { color: #fff; }
        .sidebar-badge { margin-left: auto; min-width: 22px; height: 22px; padding: 0 7px; display: flex; align-items: center; justify-content: center; border-radius: 99px; background: #e9a23b; color: #fff; font-size: 11px; font-weight: 700; }
        .sidebar-link.active .sidebar-badge { background: var(--d-lime, #cde8a3); color: var(--d-ink-green, var(--lime)); }
        .sidebar-divider { height: 1px; margin: 12px 10px; background: var(--line); }

        /* ---------- Main ---------- */
        .main { grid-column: 2; grid-row: 2; min-width: 0; height: calc(100vh - 70px); padding: 26px 28px 20px; overflow: hidden; background: var(--bg); }
        .main-scroll { height: 100%; overflow: auto; padding-right: 2px; }

        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .page-head h1 { font-family: var(--fw-serif, Georgia, serif); font-size: 32px; font-weight: 600; letter-spacing: -.4px; }
        .page-head p  { margin-top: 6px; color: var(--text-muted); font-size: 13.5px; max-width: 620px; line-height: 1.55; }
        .head-actions { display: flex; gap: 9px; flex-wrap: wrap; }

        .btn { height: 40px; padding: 0 18px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border-radius: 99px; border: 1px solid transparent; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; }
        .btn-primary { background: var(--lime); color: #fff; }
        .btn-primary:hover { background: var(--lime-strong); }
        .btn-primary:disabled { opacity: .6; cursor: wait; }
        .btn-outline { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .35); background: var(--d-surface, #fff); color: var(--d-ink-green, var(--lime)); }
        .btn-outline:hover { background: var(--accent-soft); }
        .btn-ghost { border-color: var(--border); background: var(--d-surface, #fff); color: var(--text-soft); }
        .btn-ghost:hover { border-color: rgba(var(--d-ink-rgb, 23, 46, 33), .25); }

        /* ---------- Tabs ---------- */
        .tabs { display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; }
        .tab { height: 36px; padding: 0 16px; display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid var(--border); background: var(--d-surface, #fff); color: var(--text-soft); text-decoration: none; font-size: 13px; font-weight: 500; }
        .tab span { margin-left: 6px; color: var(--text-muted); }
        .tab.active { background: var(--lime); border-color: var(--lime); color: #fff; }
        .tab.active span { color: rgba(255, 255, 255, .7); }

        /* ---------- Grid kartu ---------- */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
        .item-card { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; box-shadow: var(--shadow); }
        .item-card.inactive .item-thumb > img, .item-card.inactive .item-thumb > video { opacity: .4; filter: grayscale(.6); }
        .item-thumb { position: relative; aspect-ratio: 16 / 10; background: var(--d-line-solid, #dfe5dc); }
        .item-thumb img, .item-thumb video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .no-thumb { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 34px; }
        .play-icon { position: absolute; inset: 0; margin: auto; width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(var(--d-glass-rgb, 255, 255, 255), .92); color: var(--d-ink-green, var(--lime)); font-size: 16px; pointer-events: none; }
        .badges { position: absolute; top: 10px; left: 10px; right: 10px; display: flex; justify-content: space-between; gap: 6px; }
        .badge { padding: 4px 9px; border-radius: 999px; font-size: 10.5px; font-weight: 600; }
        .badge.type     { background: rgba(var(--d-glass-rgb, 255, 255, 255), .92); color: var(--text); }
        .badge.active   { background: var(--lime); color: #fff; }
        .badge.inactive { background: var(--d-bg-2, #ecebe3); color: var(--d-muted, #66716a); }
        .item-body { padding: 14px 14px 4px; flex: 1; }
        .item-body strong { display: block; font-size: 14.5px; font-weight: 600; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .item-body small  { display: block; margin-top: 4px; color: var(--d-ink-green-2, #3d7d57); font-size: 12px; font-weight: 500; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .item-meta { margin-top: 6px; color: var(--text-muted); font-size: 11.5px; }
        .item-actions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; padding: 12px 14px 14px; }
        .item-actions form { display: contents; }
        .item-actions a, .item-actions button { height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 99px; border: 1px solid var(--border); background: var(--d-surface, #fff); color: var(--text-soft); font-size: 12px; font-weight: 600; text-decoration: none; }
        .item-actions a:hover, .item-actions button:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .4); color: var(--d-ink-green, var(--lime)); }
        .item-actions .danger:hover { border-color: rgba(201, 65, 58, .5); color: var(--d-red-ink, #c9413a); }

        .empty-state { padding: 60px 20px; text-align: center; border: 1px dashed rgba(var(--d-ink-rgb, 23, 46, 33), .18); border-radius: var(--radius); background: var(--d-surface, #fff); color: var(--text-muted); font-size: 14px; line-height: 1.8; }
        .empty-state .btn { margin-top: 12px; }

        /* ---------- Form ---------- */
        .form-card { max-width: 720px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); padding: 24px; box-shadow: var(--shadow); }
        .field { margin-bottom: 16px; }
        .field > label, .field-label { display: block; margin-bottom: 6px; color: var(--text-soft); font-size: 13px; font-weight: 600; }
        .field-hint { margin-top: 5px; color: var(--text-muted); font-size: 12px; line-height: 1.45; }
        .field-error { margin-top: 5px; color: var(--d-red-ink, #c9413a); font-size: 12px; font-weight: 500; }
        .input { width: 100%; height: 44px; padding: 0 14px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .16); border-radius: 12px; outline: none; background: var(--d-surface, #fff); color: var(--text); font-size: 14px; }
        textarea.input { height: 100px; padding: 10px 14px; resize: vertical; line-height: 1.5; }
        .input:focus { border-color: var(--lime); box-shadow: 0 0 0 4px rgba(var(--d-green-rgb, 31, 77, 51), .1); }
        .input.is-invalid { border-color: rgba(201, 65, 58, .6); }
        input[type="file"].input { height: auto; padding: 9px 12px; font-size: 13px; color: var(--text-soft); }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .segmented { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 4px; border-radius: 99px; background: var(--bg-deep); }
        .segmented input { position: absolute; opacity: 0; pointer-events: none; }
        .segmented label { height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 99px; color: var(--text-soft); font-size: 13px; font-weight: 500; cursor: pointer; }
        .segmented input:checked + label { background: var(--lime); color: #fff; }
        .segmented input:focus-visible + label { outline: 2px solid var(--lime); outline-offset: 2px; }

        .checkbox { display: flex; align-items: center; gap: 9px; height: 40px; color: var(--text-soft); font-size: 13.5px; cursor: pointer; }
        .checkbox input { width: 17px; height: 17px; accent-color: var(--lime); }

        .preview { margin-top: 9px; width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; border: 1px solid var(--line); }
        .current-file { margin-top: 7px; color: var(--text-muted); font-size: 12px; word-break: break-all; }

        .form-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 8px; padding-top: 16px; border-top: 1px solid var(--line); }

        .error-box { margin-bottom: 16px; padding: 12px 14px; border: 1px solid rgba(201, 65, 58, .3); border-radius: 12px; background: var(--d-red-tint, #fbe7e5); color: var(--d-red-ink, #8f2a24); font-size: 13px; line-height: 1.6; }
        .error-box ul { padding-left: 18px; }

        /* ---------- Event ---------- */
        .event-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
        .event-item { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; box-shadow: var(--shadow); }
        .event-item.inactive .event-poster img { opacity: .4; filter: grayscale(.6); }
        .event-poster { position: relative; aspect-ratio: 4 / 5; background: var(--d-line-solid, #dfe5dc); }
        .event-poster img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .badge.open   { background: var(--lime); color: #fff; }
        .badge.closed { background: var(--d-bg-2, #ecebe3); color: var(--d-muted, #66716a); }
        .badge.full   { background: #e9a23b; color: #fff; }
        .badge.past   { background: rgba(23, 38, 29, .7); color: #fff; }
        .event-facts { margin-top: 8px; display: grid; gap: 4px; color: var(--text-muted); font-size: 12px; }
        .event-facts b { color: var(--text-soft); font-weight: 600; }
        .event-price { margin-top: 8px; color: var(--d-ink-green, var(--lime)); font-size: 18px; font-weight: 700; }
        .field-row.three { grid-template-columns: 1fr 1fr 1fr; }
        select.input { cursor: pointer; }
        .poster-preview { margin-top: 9px; width: 100%; max-width: 260px; border-radius: 12px; border: 1px solid var(--line); display: block; }
        @media (max-width: 760px) { .field-row.three { grid-template-columns: 1fr; } }

        /* ---------- Program ---------- */
        .program-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; }
        .program-item { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; box-shadow: var(--shadow); }
        .program-item.inactive .program-banner { opacity: .4; filter: grayscale(.6); }
        .program-banner { position: relative; height: 130px; background-image: var(--card-img); background-position: var(--card-pos, center); background-size: cover; }
        .program-level { color: var(--d-ink-green-2, #3d7d57); font-size: 11px; font-weight: 600; letter-spacing: 1.2px; margin-bottom: 4px; text-transform: uppercase; }
        .program-desc { margin-top: 5px; color: var(--text-muted); font-size: 12.5px; line-height: 1.45; }
        .feature-list { list-style: none; margin-top: 8px; display: grid; gap: 4px; }
        .feature-list li { position: relative; padding-left: 12px; color: var(--text-soft); font-size: 12px; }
        .feature-list li::before { content: ""; position: absolute; left: 0; top: 6px; width: 5px; height: 5px; border-radius: 50%; background: var(--lime); }
        .banner-preview { margin-top: 9px; height: 130px; max-width: 360px; border-radius: 12px; border: 1px solid var(--line); background-image: var(--card-img); background-position: var(--card-pos, center); background-size: cover; }

        /* ---------- Contact ---------- */
        .section-card { border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); padding: 22px; margin-bottom: 18px; box-shadow: var(--shadow); }
        .section-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .section-card-head h2 { font-size: 17px; font-weight: 700; }
        .section-card-head p { margin-top: 4px; color: var(--text-muted); font-size: 13px; }
        .location-rows { display: grid; gap: 10px; }
        .location-row { display: grid; grid-template-columns: 38px minmax(0, 1fr) auto; align-items: center; gap: 14px; padding: 14px; border: 1px solid var(--line); border-radius: 16px; background: var(--d-surface-2, #f8f7f2); }
        .location-row.inactive { opacity: .55; }
        .location-index { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--lime); color: #fff; font-size: 14px; font-weight: 700; }
        .location-row strong { display: block; font-size: 14.5px; font-weight: 600; }
        .location-row .meta { margin-top: 3px; color: var(--d-ink-green-2, #3d7d57); font-size: 12.5px; font-weight: 500; }
        .location-row .note { margin-top: 4px; color: var(--text-muted); font-size: 12px; }
        .location-row .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .location-row .actions form { display: contents; }
        .location-row .actions a, .location-row .actions button { height: 34px; padding: 0 14px; display: inline-flex; align-items: center; border-radius: 99px; border: 1px solid var(--border); background: var(--d-surface, #fff); color: var(--text-soft); font-size: 12px; font-weight: 600; text-decoration: none; }
        .location-row .actions a:hover, .location-row .actions button:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .4); color: var(--d-ink-green, var(--lime)); }
        .location-row .actions .danger:hover { border-color: rgba(201, 65, 58, .5); color: var(--d-red-ink, #c9413a); }
        .map-preview { margin-top: 10px; width: 100%; height: 240px; border: 1px solid var(--line); border-radius: 14px; }
        .inline-actions { display: flex; gap: 8px; align-items: center; }
        @media (max-width: 760px) { .location-row { grid-template-columns: 38px minmax(0, 1fr); } .location-row .actions { grid-column: 1 / -1; } }

        /* ---------- Customer ---------- */
        .stat-row { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        .stat-box { padding: 16px; border: 1px solid var(--border); border-radius: 16px; background: var(--panel); box-shadow: var(--shadow); }
        .stat-box span { display: block; color: var(--text-muted); font-size: 12.5px; font-weight: 500; }
        .stat-box strong { display: block; margin-top: 6px; font-size: 26px; font-weight: 700; line-height: 1; }
        .stat-box small { display: block; margin-top: 6px; color: var(--d-ink-green-2, #3d7d57); font-size: 12px; }

        .toolbar { display: grid; grid-template-columns: minmax(0, 1fr) 190px auto auto; gap: 8px; margin-bottom: 14px; }
        .toolbar .btn { height: 44px; }

        .table-card { border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; box-shadow: var(--shadow); }
        .table-scroll { overflow-x: auto; }
        .data-table { width: 100%; min-width: 760px; border-collapse: collapse; }
        .data-table th { height: 42px; padding: 0 14px; background: var(--d-surface-2, #f8f7f2); color: var(--text-muted); text-align: left; font-size: 11.5px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
        .data-table td { padding: 12px 14px; border-top: 1px solid var(--line); color: var(--text-soft); font-size: 13.5px; vertical-align: middle; }
        .data-table tbody tr:hover { background: var(--d-surface-2, #f8f7f2); }
        .data-table .num { text-align: center; }

        .person { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .person-avatar { width: 40px; height: 40px; flex: 0 0 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--accent-soft); color: var(--d-ink-green, var(--lime)); font-size: 13px; font-weight: 700; }
        .person-avatar.offline { background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #a2650c); }
        .person strong { display: block; color: var(--text); font-size: 14px; font-weight: 600; }
        .person small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 12px; }

        .row-actions { display: flex; gap: 6px; justify-content: flex-end; }
        .row-actions a { height: 32px; padding: 0 14px; display: inline-flex; align-items: center; border-radius: 99px; border: 1px solid var(--border); background: var(--d-surface, #fff); color: var(--text-soft); font-size: 12px; font-weight: 600; text-decoration: none; }
        .row-actions a:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .4); color: var(--d-ink-green, var(--lime)); }

        .status-pill { display: inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .status-pill.pending   { background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #a2650c); }
        .status-pill.booked    { background: var(--accent-soft); color: var(--d-ink-green, var(--lime)); }
        .status-pill.cancelled, .status-pill.rejected { background: var(--d-red-tint, #fbe7e5); color: var(--d-red-ink, #c9413a); }
        .status-pill.neutral   { background: var(--d-bg-2, #ecebe3); color: var(--d-muted, #66716a); }
        .muted { color: var(--text-muted); }

        .pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 14px; border-top: 1px solid var(--line); color: var(--text-muted); font-size: 12.5px; }
        .pager-links { display: flex; gap: 6px; }
        .pager-links a, .pager-links span { height: 32px; padding: 0 14px; display: inline-flex; align-items: center; border-radius: 99px; border: 1px solid var(--border); font-size: 12.5px; font-weight: 600; text-decoration: none; color: var(--text-soft); background: var(--d-surface, #fff); }
        .pager-links span { opacity: .4; }
        .pager-links a:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .4); color: var(--d-ink-green, var(--lime)); }

        .profile-grid { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 16px; align-items: start; }
        .profile-card { padding: 22px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); box-shadow: var(--shadow); }
        .profile-head { display: flex; align-items: center; gap: 14px; padding-bottom: 16px; border-bottom: 1px solid var(--line); }
        .profile-head .person-avatar { width: 64px; height: 64px; flex-basis: 64px; font-size: 20px; }
        .profile-head h2 { font-size: 18px; font-weight: 700; }
        .profile-list { display: grid; gap: 10px; padding: 16px 0; border-bottom: 1px solid var(--line); }
        .profile-list div { display: grid; grid-template-columns: 100px minmax(0, 1fr); gap: 8px; font-size: 13.5px; }
        .profile-list span:first-child { color: var(--text-muted); }
        .profile-list span:last-child { color: var(--text); font-weight: 500; word-break: break-word; }
        .profile-actions { display: grid; gap: 8px; padding-top: 16px; }
        .profile-actions .btn { width: 100%; }

        .upcoming-card { margin-bottom: 16px; padding: 18px; border-radius: var(--radius); background: var(--lime); color: #fff; }
        .upcoming-card span { color: #cde8a3; font-size: 11.5px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; }
        .upcoming-card strong { display: block; margin-top: 6px; font-size: 19px; font-weight: 700; }
        .upcoming-card small { display: block; margin-top: 4px; color: rgba(255, 255, 255, .75); font-size: 12.5px; }
        .table-title { padding: 14px 16px; font-size: 15px; font-weight: 700; border-bottom: 1px solid var(--line); }

        @media (max-width: 1050px) { .stat-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } .profile-grid { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .toolbar { grid-template-columns: 1fr 1fr; } .toolbar .input:first-child { grid-column: 1 / -1; } }

        /* ---------- Toast ---------- */
        .toast { position: fixed; top: 84px; right: 24px; z-index: 120; padding: 13px 18px; border-radius: 14px; background: var(--lime); color: #fff; font-size: 14px; font-weight: 600; box-shadow: 0 16px 40px rgba(var(--d-shadow-rgb, 23, 46, 33), .3); }
        .toast.error { background: #c9413a; color: #fff; }

        /* ---------- Responsive ---------- */
        @media (max-width: 1050px) { .admin-page { grid-template-columns: 220px 1fr; } }
        @media (max-width: 820px) {
            body { overflow: auto; }
            .admin-page { display: block; height: auto; min-height: 100vh; }
            .topbar { position: sticky; top: 0; z-index: 100; height: 62px; padding: 0 16px; }
            .menu-toggle { display: none; }
            .sidebar { display: none; }
            .main { height: auto; padding: 18px 16px; }
            .main-scroll { height: auto; overflow: visible; }
            .field-row { grid-template-columns: 1fr; }
            .page-head h1 { font-size: 26px; }
            .admin-profile { padding: 0; border: 0; background: transparent; }
            .admin-name { display: none; }
            .brand { font-size: 16.5px; }
            .brand-icon { font-size: 25px; }
            .toast { top: 72px; left: 16px; right: 16px; }
        }
    </style>

    @stack('styles')
</head>
<body class="fw-admin">

<div class="admin-page" id="adminPage">

    <header class="topbar">
        <div class="top-left">
            <a href="{{ route('admin.dashboard') }}" class="brand">
                @include('partials.brand-logo', ['iconClass' => 'brand-icon', 'accentClass' => 'accent'])
            </a>
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka/tutup menu">{!! \App\Support\Icons::svg('menu') !!}</button>
        </div>

        <div class="top-right">
            <a href="{{ route('home') }}" class="menu-toggle" style="display:grid" title="Lihat website" target="_blank" rel="noopener">{!! \App\Support\Icons::svg('eye') !!}</a>
            <button type="button" class="fw-theme-btn" data-theme-toggle aria-label="Ganti mode terang/gelap" title="Mode terang/gelap">{!! \App\Support\Icons::svg('moon', 'i-moon') !!}{!! \App\Support\Icons::svg('sun', 'i-sun') !!}</button>
            @include('partials.notification-bell')

            <a href="{{ Route::has('admin.menu') ? route('admin.menu') : route('admin.settings.index') }}" class="admin-profile">
                <div class="admin-avatar">{{ \App\Support\Icons::initials($adminName) }}</div>
                <div class="admin-name">
                    <strong>{{ $adminName }}</strong>
                    <small>Admin · Coach</small>
                </div>
            </a>
        </div>
    </header>

    <aside class="sidebar">
        <div class="sidebar-scroll thin-scroll">
            <div class="sidebar-section">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('home') !!}</span> Dashboard
                </a>
            </div>

            <div class="sidebar-title">Booking</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.dashboard') }}#booking-list" class="sidebar-link">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('list') !!}</span> All Booking
                </a>
                <a href="{{ route('admin.dashboard', ['status' => 'pending']) }}#booking-list" class="sidebar-link">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('clock') !!}</span> Pending Booking
                    <span class="sidebar-badge">{{ $pendingCount }}</span>
                </a>
                <a href="{{ route('admin.offline-booking.create') }}" class="sidebar-link">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('plus-c') !!}</span> Create Offline Booking
                </a>
                <a href="{{ route('admin.schedule-blocks.index') }}" class="sidebar-link {{ request()->routeIs('admin.schedule-blocks.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('calendar') !!}</span> Kelola Jadwal
                </a>
                <a href="{{ route('admin.poster.index') }}" class="sidebar-link {{ request()->routeIs('admin.poster.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('image') !!}</span> Poster Jadwal
                </a>
            </div>

            <div class="sidebar-divider"></div>

            <div class="sidebar-title">Content</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.content.index') }}" class="sidebar-link {{ request()->routeIs('admin.content.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('edit') !!}</span> Konten Website
                </a>
                <a href="{{ route('admin.gallery.index') }}" class="sidebar-link {{ request()->routeIs('admin.gallery.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('image') !!}</span> Gallery
                </a>
                <a href="{{ route('admin.events.index') }}" class="sidebar-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('flag') !!}</span> Event
                </a>
                <a href="{{ route('admin.programs.index') }}" class="sidebar-link {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('star') !!}</span> Program
                </a>
                <a href="{{ route('admin.coaches.index') }}" class="sidebar-link {{ request()->routeIs('admin.coaches.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('coach') !!}</span> About Coach
                </a>
                <a href="{{ route('admin.contact.index') }}" class="sidebar-link {{ request()->routeIs('admin.contact.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('phone') !!}</span> Contact
                </a>
            </div>

            <div class="sidebar-title">Customer</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.customers.index') }}" class="sidebar-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('users') !!}</span> Customer
                </a>
                @php $chatUnread = class_exists(\App\Models\ChatMessage::class) && \Illuminate\Support\Facades\Schema::hasTable('chat_messages') ? \App\Models\ChatMessage::unreadForAdmin() : 0; @endphp
                <a href="{{ route('admin.chat.index') }}" class="sidebar-link {{ request()->routeIs('admin.chat.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('chat') !!}</span> Chat Member
                    @if ($chatUnread > 0)<span class="sidebar-badge">{{ $chatUnread }}</span>@endif
                </a>
            </div>

            <div class="sidebar-title">System</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">{!! \App\Support\Icons::svg('settings') !!}</span> Settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link">
                        <span class="sidebar-icon">{!! \App\Support\Icons::svg('logout') !!}</span> Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main">
        <div class="main-scroll thin-scroll">
            @yield('content')
        </div>
    </main>
</div>

@if ($flashType)
    <div class="toast {{ $flashType === 'error' ? 'error' : '' }}" id="toast">{{ $flashMessage }}</div>
@endif

<script>
    document.getElementById('menuToggle')?.addEventListener('click', () => {
        document.getElementById('adminPage').classList.toggle('sidebar-toggled');
    });

    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.remove(), 3500);
</script>

<script src="{{ asset('js/direct-upload.js') }}?v={{ @filemtime(public_path('js/direct-upload.js')) }}" defer></script>
@stack('scripts')

</body>
</html>
