{{--
    Kotak percakapan chat (dipakai halaman member & admin).
    Variabel: $messages (array), $pollUrl, $sendUrl, $viewer ('member'|'admin'),
              $peerName (nama lawan bicara), $emptyText
--}}
@once
<style>
    .ct { display: flex; flex-direction: column; min-height: 0; height: 100%; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .09); border-radius: 22px; background: var(--d-surface, #fff); overflow: hidden; font-family: var(--fw-sans, Inter, system-ui, Arial, sans-serif); color: var(--d-text, #17261d); box-shadow: 0 1px 2px rgba(var(--d-shadow-rgb, 23, 46, 33), .04), 0 8px 24px rgba(var(--d-shadow-rgb, 23, 46, 33), .06); }
    .ct-head { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); background: var(--d-surface, #fff); }
    .ct-avatar { width: 44px; height: 44px; flex: 0 0 44px; display: grid; place-items: center; border-radius: 50%; background: var(--d-tint, #e5eee6); color: var(--d-ink-green, #1f4d33); font-weight: 700; font-size: 17px; }
    .ct-head strong { display: block; font-size: 15px; font-weight: 600; }
    .ct-head small { display: flex; align-items: center; gap: 6px; color: var(--d-muted, #77837b); font-size: 12px; }
    .ct-head small i { width: 7px; height: 7px; border-radius: 50%; background: #3d9a5f; }
    .ct-body { flex: 1; min-height: 0; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; scroll-behavior: smooth; overscroll-behavior: contain; background: var(--d-surface-2, #f8f7f2); }
    .ct-body::-webkit-scrollbar { width: 6px; } .ct-body::-webkit-scrollbar-thumb { background: rgba(var(--d-ink-rgb, 23, 46, 33), .15); border-radius: 9px; }
    .ct-date { align-self: center; margin: 8px 0 4px; padding: 4px 12px; border-radius: 99px; background: var(--d-bg-2, #ecebe3); color: var(--d-muted, #6b776f); font-size: 11.5px; font-weight: 600; }
    .ct-msg { max-width: min(78%, 520px); padding: 10px 13px 6px; border-radius: 18px; font-size: 14.5px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
    .ct-msg.them { align-self: flex-start; background: var(--d-surface, #fff); border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); border-bottom-left-radius: 6px; }
    .ct-msg.mine { align-self: flex-end; background: var(--d-btn, #1f4d33); color: #fff; border-bottom-right-radius: 6px; }
    .ct-meta { display: block; margin-top: 3px; font-size: 10.5px; text-align: right; opacity: .65; }
    .ct-bc { align-self: stretch; max-width: none; padding: 14px 16px; border-radius: 18px; background: var(--d-surface-2, #fdf6e8); border: 1px solid rgba(233, 162, 59, .35); color: var(--d-text, #17261d); }
    .ct-bc b { display: flex; align-items: center; gap: 7px; margin-bottom: 4px; color: var(--d-orange-ink, #a2650c); font-size: 11px; letter-spacing: 1.2px; text-transform: uppercase; }
    .ct-bc strong { display: block; margin-bottom: 4px; font-size: 15px; }
    .ct-empty { margin: auto; max-width: 300px; text-align: center; color: var(--d-muted, #77837b); font-size: 14px; line-height: 1.6; }
    .ct-empty span { display: block; margin-bottom: 8px; font-size: 34px; }
    .ct-form { display: flex; align-items: flex-end; gap: 8px; padding: 10px 12px; border-top: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); background: var(--d-surface, #fff); }
    .ct-form textarea { flex: 1; min-height: 46px; max-height: 140px; resize: none; padding: 12px 16px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .14); border-radius: 23px; background: var(--d-surface-2, #f8f7f2); color: var(--d-text, #17261d); font: 16px/1.4 var(--fw-sans, Inter, Arial, sans-serif); outline: none; }
    .ct-form textarea:focus { border-color: var(--d-ink-green, #1f4d33); background: var(--d-surface, #fff); }
    .ct-form button { width: 46px; height: 46px; flex: 0 0 46px; display: grid; place-items: center; border: 0; border-radius: 50%; background: var(--d-btn, #1f4d33); color: #fff; cursor: pointer; }
    .ct-form button:disabled { opacity: .5; cursor: wait; }
    .ct-form button svg { width: 20px; height: 20px; }
    .ct-error { padding: 6px 14px 0; color: var(--d-red-ink, #c9413a); font-size: 12px; }
</style>
@endonce

<div class="ct" id="chatThread"
     data-poll="{{ $pollUrl }}" data-send="{{ $sendUrl }}" data-viewer="{{ $viewer }}">
    <div class="ct-head">
        @if (! empty($peerPhoto))
            <div class="ct-avatar" style="background:url('{{ $peerPhoto }}') center top / cover;color:transparent">{{ mb_strtoupper(mb_substr($peerName, 0, 1)) }}</div>
        @else
            <div class="ct-avatar">{{ mb_strtoupper(mb_substr($peerName, 0, 1)) }}</div>
        @endif
        <div>
            <strong>{{ $peerName }}</strong>
            <small><i></i>{{ $peerSub ?? ($viewer === 'member' ? 'Coach biasanya membalas di jam operasional' : 'Member') }}</small>
        </div>
    </div>

    <div class="ct-body" id="chatBody" aria-live="polite">
        <div class="ct-empty" id="chatEmpty" @if (count($messages)) hidden @endif>
            <span>💬</span>{{ $emptyText }}
        </div>
    </div>

    <p class="ct-error" id="chatError" hidden></p>

    <form class="ct-form" id="chatForm">
        <label for="chatInput" class="sr-only" style="position:absolute;left:-9999px">Tulis pesan</label>
        <textarea id="chatInput" rows="1" maxlength="2000" placeholder="Tulis pesan..." required></textarea>
        <button type="submit" id="chatSend" aria-label="Kirim">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
        </button>
    </form>
</div>

<script>
(function () {
    'use strict';
    var root = document.getElementById('chatThread');
    if (!root) return;
    var body = document.getElementById('chatBody');
    var empty = document.getElementById('chatEmpty');
    var form = document.getElementById('chatForm');
    var input = document.getElementById('chatInput');
    var sendBtn = document.getElementById('chatSend');
    var errorEl = document.getElementById('chatError');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '{{ csrf_token() }}';
    var lastId = 0, lastDate = null, polling = false;
    var initial = @json($messages);

    function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }

    function add(m) {
        if (m.id <= lastId) return;
        lastId = m.id;
        empty.hidden = true;
        if (m.date !== lastDate) { body.appendChild(el('div', 'ct-date', m.date)); lastDate = m.date; }
        var node;
        if (m.broadcast) {
            node = el('div', 'ct-msg ct-bc');
            node.appendChild(el('b', null, '📢 Pengumuman'));
            if (m.title) node.appendChild(el('strong', null, m.title));
            node.appendChild(document.createTextNode(m.body));
        } else {
            node = el('div', 'ct-msg ' + (m.mine ? 'mine' : 'them'));
            node.appendChild(document.createTextNode(m.body));
        }
        node.appendChild(el('span', 'ct-meta', m.time + (m.mine ? (m.read ? ' · dibaca' : ' · terkirim') : '')));
        body.appendChild(node);
    }

    function toBottom() { body.scrollTop = body.scrollHeight; }

    initial.forEach(add);
    toBottom();

    /*
     * Cek pesan baru secara hemat (mengurangi pemakaian server Vercel):
     * - cepat (4 dtk) setelah ada pesan / sedang mengetik, lalu melambat bertahap sampai 60 dtk
     * - setelah 15 menit tanpa aktivitas: tiap 3 menit
     * - tab tidak dilihat / HP dikunci: berhenti total, lanjut lagi saat dibuka
     */
    var STEPS = [4000, 4000, 6000, 10000, 15000, 30000, 60000], step = 0, timer = null, lastActive = Date.now();

    function schedule() {
        clearTimeout(timer);
        if (document.hidden) return;
        var wait = Date.now() - lastActive > 15 * 60000 ? 180000 : STEPS[Math.min(step, STEPS.length - 1)];
        timer = setTimeout(poll, wait);
    }
    function wake() { lastActive = Date.now(); step = 0; schedule(); }

    function poll() {
        if (polling || document.hidden) return;
        polling = true;
        fetch(root.dataset.poll + '?after=' + lastId, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d || !d.messages || !d.messages.length) { step++; return; }
                var near = body.scrollHeight - body.scrollTop - body.clientHeight < 120;
                d.messages.forEach(add);
                if (near) toBottom();
                step = 0; lastActive = Date.now();
            })
            .catch(function () { step++; })
            .finally(function () { polling = false; schedule(); });
    }

    schedule();
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { clearTimeout(timer); return; }
        lastActive = Date.now(); step = 0; poll();
    });
    window.addEventListener('focus', function () { if (Date.now() - lastActive > 30000) wake(); });
    ['keydown', 'pointerdown', 'scroll'].forEach(function (ev) {
        root.addEventListener(ev, function () { if (step > 2 || Date.now() - lastActive > 60000) wake(); else lastActive = Date.now(); }, { passive: true, capture: true });
    });

    function grow() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; }
    input.addEventListener('input', grow);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 821px)').matches) { e.preventDefault(); form.requestSubmit(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text) return;
        sendBtn.disabled = true; errorEl.hidden = true;
        fetch(root.dataset.send, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ body: text })
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok) throw new Error((res.d && res.d.message) || 'Pesan gagal dikirim.');
                input.value = ''; grow();
                add(res.d.message); toBottom(); wake();
            })
            .catch(function (err) { errorEl.textContent = err.message || 'Pesan gagal dikirim. Coba lagi.'; errorEl.hidden = false; })
            .finally(function () { sendBtn.disabled = false; input.focus(); });
    });
})();
</script>
