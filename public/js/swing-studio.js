/*
 * Studio Video Golf Analyzer.
 * Dibuka setelah analisis (video lokal) atau dari halaman hasil (video tersimpan, putar ulang).
 *
 *  - Slow motion 0.25x / 0.5x / 1x, maju-mundur per frame, lompat ke tahapan swing
 *  - Video tampil bersih; garis hanya muncul kalau dinyalakan:
 *      Garis Coach (alignment rapi dari posisi setup), Rangka, Sudut tubuh, Jejak tangan
 *  - Shot Tracer ala aplikasi tracer: garis lengkung jejak bola terbang, muncul bertahap setelah impact
 *  - Alat gambar saat dijeda: garis (+sudut), sudut 3 titik, lingkaran
 *  - Capture frame + garis, tersimpan ke hasil analisis
 */
const KEYS = [0, 11, 12, 13, 14, 15, 16, 23, 24, 25, 26, 27, 28];
const BONES = [[11, 12], [11, 13], [13, 15], [12, 14], [14, 16], [11, 23], [12, 24], [23, 24], [23, 25], [25, 27], [24, 26], [26, 28]];
const STEP = 1 / 30;
const INK = '#14141A';

/** Data pose ringkas dari server -> [{t, pose}] (pose berindeks seperti MediaPipe). */
export function expandPose(rows) {
    return (rows || []).map((r) => {
        const pose = [];
        KEYS.forEach((k, i) => { pose[k] = { x: r[1 + i * 3], y: r[2 + i * 3], v: r[3 + i * 3] }; });
        return { t: r[0], pose };
    });
}

export async function openStudio(o) {
    const root = o.root;
    const $ = (s) => root.querySelector(s);
    const stage = $('#st-stage'), video = $('#st-video'), canvas = $('#st-canvas'), ctx = canvas.getContext('2d');
    const playBtn = $('#st-play'), scrub = $('#st-scrub'), timeEl = $('#st-time'), msg = $('#st-msg'), statusEl = $('#st-status'), gallery = $('#st-gallery');

    root.hidden = false;
    if (o.remote) video.crossOrigin = 'anonymous';
    video.muted = true;
    video.playsInline = true;
    video.preload = 'auto';
    video.src = o.src;
    if (!o.width || !o.height) {
        await new Promise((res, rej) => {
            video.addEventListener('loadedmetadata', res, { once: true });
            video.addEventListener('error', () => rej(new Error('Video tidak bisa diputar.')), { once: true });
        });
    }
    const W = o.width || video.videoWidth, H = o.height || video.videoHeight, ratio = W / H;
    const frames = (o.frames || []).filter((f) => f.pose && f.pose[11]);
    const lead = o.hand === 'left' ? { sh: 12, el: 14, wr: 16, hip: 24, kn: 26, an: 28 } : { sh: 11, el: 13, wr: 15, hip: 23, kn: 25, an: 27 };
    const phaseT = Object.fromEntries((o.phases || []).map((p) => [p.key, p.t]));
    const hasPose = frames.length > 0;

    // Semua garis mati dulu: video tampil bersih
    const show = { coach: false, skel: false, angles: false, path: false, tracer: false };
    let tool = 'none';
    const shapes = [];
    let draft = null;
    let tracer = null; // {s:{x,y}, c:{x,y}, e:{x,y}, t0, dur}

    video.defaultPlaybackRate = 0.5;
    video.playbackRate = 0.5;
    stage.style.aspectRatio = W + ' / ' + H;
    stage.style.width = 'min(100%, calc(68vh * ' + ratio.toFixed(4) + '))';
    if ($('#st-result')) {
        if (o.resultUrl) $('#st-result').href = o.resultUrl; else $('#st-result').hidden = true;
    }
    if (!hasPose) root.querySelectorAll('[data-needs-pose]').forEach((b) => { b.disabled = true; b.title = 'Data gerakan tidak tersedia untuk video ini'; });
    root.scrollIntoView({ behavior: 'smooth', block: 'start' });

    /* ---------- util ---------- */
    function say(t) { msg.textContent = t || ''; msg.hidden = !t; }
    function status(t) { if (statusEl) { statusEl.textContent = t || ''; statusEl.hidden = !t; } }
    function poseAt(t) {
        if (!frames.length) return null;
        let lo = 0, hi = frames.length - 1;
        while (lo < hi) { const m = (lo + hi) >> 1; if (frames[m].t < t) lo = m + 1; else hi = m; }
        const a = frames[Math.max(0, lo - 1)], b = frames[lo];
        return Math.abs(a.t - t) < Math.abs(b.t - t) ? a.pose : b.pose;
    }
    const mid = (a, b) => ({ x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 });
    function angleAt(a, b, c) {
        const v1 = { x: (a.x - b.x) * W, y: (a.y - b.y) * H }, v2 = { x: (c.x - b.x) * W, y: (c.y - b.y) * H };
        const cos = (v1.x * v2.x + v1.y * v2.y) / ((Math.hypot(v1.x, v1.y) * Math.hypot(v2.x, v2.y)) || 1);
        return Math.acos(Math.max(-1, Math.min(1, cos))) * 180 / Math.PI;
    }
    function fmt(t) { return (t || 0).toFixed(2) + ' s'; }
    function setupPose() { return poseAt(phaseT.setup ?? 0); }

    // Perkiraan posisi bola di setup (bisa diubah dengan "Tandai bola")
    let ball = (() => {
        const p = setupPose();
        if (!p) return { x: 0.5, y: 0.85 };
        const ank = mid(p[27], p[28]), wr = mid(p[15], p[16]);
        if (o.view === 'down_the_line') return { x: wr.x + (wr.x - ank.x) * 0.35, y: Math.min(0.97, ank.y + 0.01) };
        return { x: ank.x * 0.55 + p[lead.an].x * 0.45, y: Math.min(0.97, ank.y + 0.012) };
    })();

    /* ---------- menggambar ---------- */
    function roundRect(c, x, y, w, h, r) {
        c.beginPath();
        c.moveTo(x + r, y); c.arcTo(x + w, y, x + w, y + h, r); c.arcTo(x + w, y + h, x, y + h, r);
        c.arcTo(x, y + h, x, y, r); c.arcTo(x, y, x + w, y, r); c.closePath();
    }

    function render(c, w, h, t, scale) {
        const X = (p) => p.x * w, Y = (p) => p.y * h;
        const lw = (n) => n * scale;
        c.lineCap = 'round';
        c.lineJoin = 'round';
        c.setLineDash([]);

        const tag = (x, y, text, bg) => {
            c.font = '700 ' + lw(12) + 'px Fredoka, system-ui, sans-serif';
            const tw = c.measureText(text).width + lw(12);
            x = Math.max(tw / 2 + lw(4), Math.min(w - tw / 2 - lw(4), x));
            y = Math.max(lw(14), Math.min(h - lw(14), y));
            c.fillStyle = bg || '#FFFFFF';
            c.strokeStyle = INK;
            c.lineWidth = lw(1.5);
            roundRect(c, x - tw / 2, y - lw(10), tw, lw(20), lw(7));
            c.fill(); c.stroke();
            c.fillStyle = INK;
            c.textAlign = 'center';
            c.textBaseline = 'middle';
            c.fillText(text, x, y + lw(1));
        };
        // Garis rapi: garis gelap tipis di bawah, warna di atas
        const line = (x1, y1, x2, y2, color, width = 2.6) => {
            c.strokeStyle = 'rgba(20,20,26,.75)'; c.lineWidth = lw(width + 2.2);
            c.beginPath(); c.moveTo(x1, y1); c.lineTo(x2, y2); c.stroke();
            c.strokeStyle = color; c.lineWidth = lw(width);
            c.beginPath(); c.moveTo(x1, y1); c.lineTo(x2, y2); c.stroke();
        };
        // Perpanjang garis dari titik A melewati B sampai tepi video
        const toEdge = (ax, ay, bx, by) => {
            const dx = bx - ax, dy = by - ay;
            let k = Infinity;
            if (dx > 0) k = Math.min(k, (w - ax) / dx); else if (dx < 0) k = Math.min(k, -ax / dx);
            if (dy > 0) k = Math.min(k, (h - ay) / dy); else if (dy < 0) k = Math.min(k, -ay / dy);
            if (!isFinite(k)) k = 1;
            return [ax + dx * k, ay + dy * k];
        };

        /* Garis Coach: semua dari posisi setup, diam sebagai patokan */
        if (show.coach) {
            const p0 = setupPose();
            const bx = ball.x * w, by = ball.y * h;
            if (p0 && o.view === 'down_the_line') {
                const hands = mid(p0[15], p0[16]);
                const sh = mid(p0[11], p0[12]);
                const hip = mid(p0[23], p0[24]);
                const away = Math.sign(hip.x - ball.x) || 1; // arah pantat (menjauhi bola)
                // 1. Shaft plane: bola -> tangan
                const [sx, sy] = toEdge(bx, by, X(hands), Y(hands));
                line(bx, by, sx, sy, '#E8453C');
                // 2. Shoulder plane: bola -> bahu
                const [qx, qy] = toEdge(bx, by, X(sh), Y(sh));
                line(bx, by, qx, qy, '#FFE08A');
                // 3. Butt line: garis tegak di belakang pinggul
                const hipW = Math.abs(p0[23].x - p0[24].x) * w;
                const buttX = X(hip) + away * Math.max(lw(16), hipW * 0.9 + lw(10));
                line(buttX, Y(sh) - lw(30), buttX, h - lw(4), '#7CC8EC');
                // 4. Kotak kepala
                const nose = p0[0];
                const r = Math.max(lw(16), Math.abs(p0[11].y - nose.y) * h * 0.55);
                c.strokeStyle = 'rgba(20,20,26,.75)'; c.lineWidth = lw(4.8);
                c.strokeRect(X(nose) - r, Y(nose) - r * 1.2, r * 2, r * 2.2);
                c.strokeStyle = '#FFFFFF'; c.lineWidth = lw(2.6);
                c.strokeRect(X(nose) - r, Y(nose) - r * 1.2, r * 2, r * 2.2);
                // Label kecil di ujung garis
                tag(sx + (bx - sx) * 0.08, sy + (by - sy) * 0.08, 'Shaft plane', '#FFC7C2');
                tag(qx + (bx - qx) * 0.08, qy + (by - qy) * 0.08 + lw(22), 'Shoulder plane', '#FFE08A');
                tag(buttX, Y(sh) - lw(42), 'Butt line', '#CDEBFF');
            } else if (p0) {
                // Face-on: garis tegak bola, kaki, kepala + garis tanah (alignment)
                const footL = Math.min(p0[27].x, p0[28].x) * w, footR = Math.max(p0[27].x, p0[28].x) * w;
                const top = Y(p0[0]) - lw(60), ground = Math.max(Y(p0[27]), Y(p0[28])) + lw(6);
                line(footL - lw(8), top, footL - lw(8), ground, '#7CC8EC');
                line(footR + lw(8), top, footR + lw(8), ground, '#7CC8EC');
                line(bx, top, bx, h - lw(4), '#E8453C');
                // Alignment stick: sejajar garis kaki, lewat depan bola
                const a1 = p0[27], a2 = p0[28];
                const ang = Math.atan2((a2.y - a1.y) * h, (a2.x - a1.x) * w);
                const cx = Math.cos(ang), cy = Math.sin(ang);
                line(bx - cx * w, by + lw(14) - cy * w, bx + cx * w, by + lw(14) + cy * w, '#FFE08A', 3);
                // Kotak kepala (cek sway)
                const nose = p0[0];
                const r = Math.max(lw(16), Math.abs(p0[11].x - p0[12].x) * w * 0.33);
                c.strokeStyle = 'rgba(20,20,26,.75)'; c.lineWidth = lw(4.8);
                c.strokeRect(X(nose) - r, Y(nose) - r * 1.25, r * 2, r * 2.3);
                c.strokeStyle = '#FFFFFF'; c.lineWidth = lw(2.6);
                c.strokeRect(X(nose) - r, Y(nose) - r * 1.25, r * 2, r * 2.3);
                tag(bx, top - lw(14), 'Bola', '#FFC7C2');
                tag((footL + footR) / 2, ground + lw(30), 'Lebar stance', '#CDEBFF');
            }
            // penanda bola
            c.strokeStyle = INK; c.lineWidth = lw(4.5);
            c.beginPath(); c.arc(bx, by, lw(8), 0, Math.PI * 2); c.stroke();
            c.strokeStyle = '#FFFFFF'; c.lineWidth = lw(2.5);
            c.beginPath(); c.arc(bx, by, lw(8), 0, Math.PI * 2); c.stroke();
        }

        /* Jejak tangan (tarikan grip/stick) sampai waktu sekarang */
        if (show.path) {
            const pts = frames.filter((f) => f.t <= t + 1e-6).map((f) => mid(f.pose[15], f.pose[16]));
            if (pts.length > 1) {
                c.strokeStyle = 'rgba(20,20,26,.6)'; c.lineWidth = lw(6);
                c.beginPath(); c.moveTo(X(pts[0]), Y(pts[0])); pts.forEach((p) => c.lineTo(X(p), Y(p))); c.stroke();
                c.strokeStyle = '#FFE08A'; c.lineWidth = lw(3.5);
                c.beginPath(); c.moveTo(X(pts[0]), Y(pts[0])); pts.forEach((p) => c.lineTo(X(p), Y(p))); c.stroke();
            }
        }

        const pose = poseAt(t);

        /* Rangka tubuh */
        if (show.skel && pose) {
            [[INK, 6.5], ['#22A04B', 3.5]].forEach(([col, wd]) => {
                c.strokeStyle = col; c.lineWidth = lw(wd);
                BONES.forEach(([a, b]) => { if (pose[a].v > 0.3 && pose[b].v > 0.3) { c.beginPath(); c.moveTo(X(pose[a]), Y(pose[a])); c.lineTo(X(pose[b]), Y(pose[b])); c.stroke(); } });
            });
            KEYS.forEach((k) => {
                if (!pose[k] || pose[k].v < 0.3) return;
                c.fillStyle = '#FFFFFF'; c.strokeStyle = INK; c.lineWidth = lw(1.8);
                c.beginPath(); c.arc(X(pose[k]), Y(pose[k]), lw(4), 0, Math.PI * 2); c.fill(); c.stroke();
            });
        }

        /* Sudut tubuh */
        if (show.angles && pose) {
            const knee = angleAt(pose[lead.hip], pose[lead.kn], pose[lead.an]);
            const arm = angleAt(pose[lead.sh], pose[lead.el], pose[lead.wr]);
            const hipM = mid(pose[23], pose[24]), shM = mid(pose[11], pose[12]);
            const spine = Math.abs(Math.atan2((shM.x - hipM.x) * w, (hipM.y - shM.y) * h) * 180 / Math.PI);
            tag(X(pose[lead.kn]) + lw(40), Y(pose[lead.kn]), 'Lutut ' + Math.round(knee) + '°');
            tag(X(pose[lead.el]) + lw(44), Y(pose[lead.el]), 'Lengan ' + Math.round(arm) + '°');
            tag(X(hipM) - lw(52), (Y(hipM) + Y(shM)) / 2, 'Punggung ' + Math.round(spine) + '°');
        }

        /* Shot Tracer: garis lengkung bola, tumbuh bertahap sejak impact */
        if (show.tracer && tracer) {
            let p = (t - tracer.t0) / tracer.dur;
            if (video.ended || t >= (video.duration || 0) - 0.02) p = 1;
            p = Math.max(0, Math.min(1, p));
            if (p > 0) {
                const ease = 1 - Math.pow(1 - p, 2.2);
                const S = { x: tracer.s.x * w, y: tracer.s.y * h }, C = { x: tracer.c.x * w, y: tracer.c.y * h }, E = { x: tracer.e.x * w, y: tracer.e.y * h };
                const at = (u) => ({ x: (1 - u) * (1 - u) * S.x + 2 * (1 - u) * u * C.x + u * u * E.x, y: (1 - u) * (1 - u) * S.y + 2 * (1 - u) * u * C.y + u * u * E.y });
                const n = 60;
                const pts = [];
                for (let i = 0; i <= n * ease; i++) pts.push(at(i / n));
                pts.push(at(ease));
                const stroke = (col, wd, blur) => {
                    c.shadowColor = col; c.shadowBlur = blur ? lw(blur) : 0;
                    c.strokeStyle = col; c.lineWidth = lw(wd);
                    c.beginPath(); c.moveTo(pts[0].x, pts[0].y); pts.forEach((q) => c.lineTo(q.x, q.y)); c.stroke();
                };
                stroke('rgba(232,69,60,.35)', 12, 14);
                stroke(tracer.color || '#E8453C', 5, 6);
                stroke('rgba(255,255,255,.9)', 1.6, 0);
                c.shadowBlur = 0;
            }
        }

        /* Penanda bola saat menandai */
        if (tool === 'ball' || tool === 'trace-end' || tool === 'trace-apex') {
            c.strokeStyle = '#E8453C'; c.lineWidth = lw(3);
            c.beginPath(); c.arc(ball.x * w, ball.y * h, lw(10), 0, Math.PI * 2); c.stroke();
        }

        /* Gambar buatan pengguna */
        const all = draft ? shapes.concat([draft]) : shapes;
        all.forEach((s) => drawShape(c, s, w, h, lw, tag));
    }

    function drawShape(c, s, w, h, lw, tag) {
        const P = (p) => [p.x * w, p.y * h];
        const seg = (pts) => {
            [[INK, 5.5], ['#FFE08A', 3.2]].forEach(([col, wd]) => {
                c.strokeStyle = col; c.lineWidth = lw(wd);
                c.beginPath(); c.moveTo(pts[0][0], pts[0][1]); pts.slice(1).forEach(([x, y]) => c.lineTo(x, y)); c.stroke();
            });
        };
        const dot = (p) => { const [x, y] = P(p); c.fillStyle = '#FFE08A'; c.strokeStyle = INK; c.lineWidth = lw(1.5); c.beginPath(); c.arc(x, y, lw(4.5), 0, Math.PI * 2); c.fill(); c.stroke(); };
        if (s.type === 'line' && s.b) {
            const a = P(s.a), b = P(s.b);
            seg([a, b]); dot(s.a); dot(s.b);
            let deg = Math.abs(Math.atan2(b[1] - a[1], b[0] - a[0]) * 180 / Math.PI);
            if (deg > 90) deg = 180 - deg;
            tag((a[0] + b[0]) / 2, (a[1] + b[1]) / 2 - lw(18), Math.round(deg) + '°', '#FFE08A');
        } else if (s.type === 'angle' && s.pts.length) {
            const pts = s.pts.map(P);
            if (pts.length > 1) seg(pts);
            s.pts.forEach(dot);
            if (s.pts.length === 3) {
                const deg = angleAt(s.pts[0], s.pts[1], s.pts[2]);
                const [bx, by] = pts[1];
                const a1 = Math.atan2(pts[0][1] - by, pts[0][0] - bx), a2 = Math.atan2(pts[2][1] - by, pts[2][0] - bx);
                let d = a2 - a1; while (d > Math.PI) d -= 2 * Math.PI; while (d < -Math.PI) d += 2 * Math.PI;
                c.strokeStyle = '#FFE08A'; c.lineWidth = lw(2.5);
                c.beginPath(); c.arc(bx, by, lw(24), a1, a1 + d, d < 0); c.stroke();
                tag(bx, by - lw(38), Math.round(deg) + '°', '#FFE08A');
            }
        } else if (s.type === 'circle' && s.r) {
            const [x, y] = P(s.c);
            [[INK, 5.5], ['#FFE08A', 3.2]].forEach(([col, wd]) => { c.strokeStyle = col; c.lineWidth = lw(wd); c.beginPath(); c.arc(x, y, s.r * w, 0, Math.PI * 2); c.stroke(); });
        }
    }

    function draw() {
        const r = stage.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        if (canvas.width !== Math.round(r.width * dpr) || canvas.height !== Math.round(r.height * dpr)) {
            canvas.width = Math.round(r.width * dpr);
            canvas.height = Math.round(r.height * dpr);
        }
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        render(ctx, canvas.width, canvas.height, video.currentTime, dpr * Math.max(0.7, r.width / 520));
        timeEl.textContent = fmt(video.currentTime) + ' / ' + fmt(video.duration);
        if (!scrub.matches(':active')) scrub.value = String(video.currentTime);
    }
    function loop() {
        draw();
        if (!video.paused) {
            if (video.requestVideoFrameCallback) video.requestVideoFrameCallback(loop);
            else requestAnimationFrame(loop);
        }
    }

    /* ---------- kontrol ---------- */
    const ready = () => { scrub.max = String(video.duration); video.playbackRate = 0.5; video.currentTime = phaseT.setup ?? 0; };
    if (video.readyState >= 1) ready(); else video.addEventListener('loadedmetadata', ready, { once: true });
    video.addEventListener('seeked', draw);
    video.addEventListener('play', () => { playBtn.textContent = '❚❚ Jeda'; loop(); });
    video.addEventListener('pause', () => { playBtn.textContent = '▶ Putar'; draw(); });
    video.addEventListener('ended', () => { playBtn.textContent = '↻ Putar lagi'; draw(); });
    window.addEventListener('resize', draw);

    playBtn.addEventListener('click', () => {
        if (video.ended) video.currentTime = 0;
        video.paused ? video.play() : video.pause();
    });
    $('#st-prev').addEventListener('click', () => { video.pause(); video.currentTime = Math.max(0, video.currentTime - STEP); });
    $('#st-next').addEventListener('click', () => { video.pause(); video.currentTime = Math.min(video.duration, video.currentTime + STEP); });
    scrub.addEventListener('input', () => { video.pause(); video.currentTime = Number(scrub.value); });
    root.querySelectorAll('[data-speed]').forEach((b) => b.addEventListener('click', () => {
        video.playbackRate = Number(b.getAttribute('data-speed'));
        root.querySelectorAll('[data-speed]').forEach((x) => x.classList.toggle('on', x === b));
    }));
    root.querySelectorAll('[data-jump]').forEach((b) => {
        if (phaseT[b.getAttribute('data-jump')] == null) b.disabled = true;
        b.addEventListener('click', () => {
            const t = phaseT[b.getAttribute('data-jump')];
            if (t != null) { video.pause(); video.currentTime = t; }
        });
    });
    root.querySelectorAll('[data-show]').forEach((b) => {
        b.classList.toggle('on', !!show[b.getAttribute('data-show')]);
        b.addEventListener('click', () => {
            const k = b.getAttribute('data-show');
            if (k === 'tracer' && !tracer) { say('Buat shot tracer dulu: klik "Tracer otomatis" atau "Tandai ujung bola".'); return; }
            show[k] = !show[k];
            b.classList.toggle('on', show[k]);
            draw();
        });
    });
    function setShow(k, v) { show[k] = v; root.querySelector('[data-show="' + k + '"]')?.classList.toggle('on', v); }

    const hints = {
        line: 'Tarik garis di atas video. Angka = sudut terhadap garis datar.',
        angle: 'Klik 3 titik: ujung pertama, titik sudut, ujung kedua.',
        circle: 'Tarik dari tengah ke luar untuk membuat lingkaran.',
        ball: 'Klik tepat di bola pada posisi setup.',
        'trace-end': 'Klik titik terakhir bola terlihat (atau arah bola keluar layar).',
        'trace-apex': 'Klik titik tertinggi lengkungan bola.',
    };
    root.querySelectorAll('[data-tool]').forEach((b) => b.addEventListener('click', () => {
        const t = b.getAttribute('data-tool');
        tool = tool === t ? 'none' : t;
        draft = null;
        root.querySelectorAll('[data-tool]').forEach((x) => x.classList.toggle('on', x.getAttribute('data-tool') === tool));
        canvas.style.cursor = tool === 'none' ? 'pointer' : 'crosshair';
        if (tool !== 'none') video.pause();
        if (tool === 'trace-end' || tool === 'trace-apex') { video.currentTime = phaseT.impact ?? video.currentTime; }
        say(hints[tool] || '');
        draw();
    }));
    $('#st-undo').addEventListener('click', () => { draft = null; shapes.pop(); draw(); });
    $('#st-clear').addEventListener('click', () => { draft = null; shapes.length = 0; draw(); });
    $('#st-trace-clear')?.addEventListener('click', () => { tracer = null; setShow('tracer', false); say(''); draw(); });

    function pt(e) {
        const r = canvas.getBoundingClientRect();
        return { x: Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)), y: Math.max(0, Math.min(1, (e.clientY - r.top) / r.height)) };
    }
    function makeTracer(end, apex) {
        const s = { x: ball.x, y: ball.y };
        const top = Math.min(s.y, end.y);
        // Bola keluar lewat atas layar: lengkung naik terus; bola jatuh terlihat: ada puncak di atas
        const exitsTop = end.y < 0.3;
        const m = apex || (exitsTop
            ? { x: s.x + (end.x - s.x) * 0.62, y: end.y + (s.y - end.y) * 0.22 }
            : { x: s.x + (end.x - s.x) * 0.55, y: Math.max(0.03, top - Math.max(0.12, Math.abs(end.x - s.x) * ratio * 0.25)) });
        // Kurva melewati titik puncak m di tengah: C = 2m - (S+E)/2
        const c = { x: 2 * m.x - (s.x + end.x) / 2, y: 2 * m.y - (s.y + end.y) / 2 };
        const t0 = phaseT.impact ?? video.currentTime;
        const remain = Math.max(0.4, (video.duration || t0 + 1.2) - t0 - 0.05);
        tracer = { s, c, e: end, apex: m, t0, dur: Math.min(1.6, remain) };
        setShow('tracer', true);
    }

    canvas.addEventListener('pointerdown', (e) => {
        const p = pt(e);
        if (tool === 'none') { if (video.ended) video.currentTime = 0; video.paused ? video.play() : video.pause(); return; }
        e.preventDefault();
        canvas.setPointerCapture(e.pointerId);
        if (tool === 'ball') {
            ball = p;
            if (tracer) makeTracer(tracer.e, null);
            say('Posisi bola disimpan.');
        } else if (tool === 'trace-end') {
            makeTracer(p, tracer ? null : null);
            say('Tracer dibuat. Putar video untuk melihat bola terbang. Mau atur tinggi lengkungan? Pilih "Tandai puncak".');
        } else if (tool === 'trace-apex') {
            if (!tracer) { say('Tandai ujung bola dulu.'); return; }
            makeTracer(tracer.e, p);
            say('Tinggi lengkungan diatur. Putar video untuk melihat hasilnya.');
        } else if (tool === 'line') draft = { type: 'line', a: p, b: p };
        else if (tool === 'circle') draft = { type: 'circle', c: p, r: 0 };
        else if (tool === 'angle') {
            if (!draft || draft.type !== 'angle') draft = { type: 'angle', pts: [] };
            draft.pts.push(p);
            if (draft.pts.length === 3) { shapes.push(draft); draft = null; }
        }
        draw();
    });
    canvas.addEventListener('pointermove', (e) => {
        if (!draft || !e.buttons) return;
        const p = pt(e);
        if (draft.type === 'line') draft.b = p;
        if (draft.type === 'circle') draft.r = Math.hypot(p.x - draft.c.x, (p.y - draft.c.y) * (H / W));
        draw();
    });
    canvas.addEventListener('pointerup', () => {
        if (!draft) return;
        if (draft.type === 'line') { if (Math.hypot(draft.a.x - draft.b.x, draft.a.y - draft.b.y) > 0.01) shapes.push(draft); draft = null; }
        else if (draft.type === 'circle') { if (draft.r > 0.005) shapes.push(draft); draft = null; }
        draw();
    });

    /* ---------- Tracer otomatis: cari bola bergerak setelah impact ---------- */
    const work = document.createElement('canvas');
    const SW = 320, SH = Math.max(40, Math.round(320 / ratio));
    work.width = SW; work.height = SH;
    const wctx = work.getContext('2d', { willReadFrequently: true });
    let busy = false;

    function seek(t) {
        return new Promise((res) => {
            const done = () => { video.removeEventListener('seeked', done); res(); };
            video.addEventListener('seeked', done);
            video.currentTime = Math.max(0, Math.min((video.duration || 0) - 0.001, t));
            setTimeout(done, 1200);
        });
    }
    function gray() {
        wctx.drawImage(video, 0, 0, SW, SH);
        const d = wctx.getImageData(0, 0, SW, SH).data, g = new Uint8ClampedArray(SW * SH);
        for (let i = 0, j = 0; j < g.length; i += 4, j++) g[j] = (d[i] * 0.299 + d[i + 1] * 0.587 + d[i + 2] * 0.114) | 0;
        return g;
    }
    function refineBall(g) {
        const cx = Math.round(ball.x * SW), cy = Math.round(ball.y * SH), R = Math.round(SW * 0.06);
        let best = -1, bx = cx, by = cy;
        for (let y = Math.max(0, cy - R); y < Math.min(SH, cy + R); y++) {
            for (let x = Math.max(0, cx - R); x < Math.min(SW, cx + R); x++) {
                const v = g[y * SW + x] - Math.hypot(x - cx, y - cy) * 1.5;
                if (v > best) { best = v; bx = x; by = y; }
            }
        }
        if (g[by * SW + bx] > 150) ball = { x: (bx + 0.5) / SW, y: (by + 0.5) / SH };
    }
    function blobs(g, prev, box) {
        const seen = new Uint8Array(SW * SH), out = [];
        for (let i = 0; i < g.length; i++) {
            if (seen[i] || g[i] < 165 || Math.abs(g[i] - prev[i]) < 45) continue;
            const stack = [i]; seen[i] = 1; let n = 0, sx = 0, sy = 0;
            while (stack.length && n < 400) {
                const k = stack.pop(), x = k % SW, y = (k / SW) | 0;
                n++; sx += x; sy += y;
                for (const nk of [k - 1, k + 1, k - SW, k + SW]) {
                    if (nk < 0 || nk >= g.length || seen[nk] || Math.abs((nk % SW) - x) > 1) continue;
                    if (g[nk] >= 165 && Math.abs(g[nk] - prev[nk]) >= 45) { seen[nk] = 1; stack.push(nk); }
                }
            }
            if (n <= 60) {
                const p = { x: (sx / n + 0.5) / SW, y: (sy / n + 0.5) / SH };
                if (!(box && p.x > box.x1 && p.x < box.x2 && p.y > box.y1 && p.y < box.y2)) out.push(p);
            }
        }
        return out;
    }
    function bodyBox(t) {
        const p = poseAt(t);
        if (!p) return null;
        const pts = [0, 11, 12, 13, 14, 15, 16, 23, 24, 25, 26].map((k) => p[k]);
        const xs = pts.map((q) => q.x), ys = pts.map((q) => q.y);
        return { x1: Math.min(...xs) - 0.05, x2: Math.max(...xs) + 0.05, y1: Math.min(...ys) - 0.08, y2: Math.max(...ys) + 0.02 };
    }

    $('#st-trace-auto')?.addEventListener('click', async () => {
        if (busy) return;
        if (phaseT.impact == null) return say('Waktu impact tidak diketahui. Pakai "Tandai ujung bola".');
        busy = true;
        video.pause();
        const btn = $('#st-trace-auto');
        btn.disabled = true;
        try {
            say('Mencari bola…');
            await seek(phaseT.setup ?? 0);
            refineBall(gray());
            const start = { x: ball.x, y: ball.y };
            const t0 = phaseT.impact - 0.06, t1 = Math.min(video.duration, phaseT.impact + 0.7), step = 1 / 60;
            await seek(t0);
            let prev = gray();
            const trail = [];
            let last = start, dir = null, miss = 0;
            for (let t = t0 + step; t <= t1; t += step) {
                say('Melacak bola… ' + Math.round(((t - t0) / (t1 - t0)) * 100) + '%');
                await seek(t);
                const g = gray();
                const cands = blobs(g, prev, bodyBox(t));
                prev = g;
                let pick = null, bestD = Infinity;
                for (const c of cands) {
                    const d = Math.hypot(c.x - last.x, (c.y - last.y) / ratio);
                    if (d < 0.003 || d > (trail.length ? 0.35 : 0.25)) continue;
                    if (dir) { if ((c.x - last.x) * dir.x + (c.y - last.y) * dir.y <= 0) continue; }
                    else if (c.y > start.y + 0.02) continue;
                    if (d < bestD) { bestD = d; pick = c; }
                }
                if (pick) {
                    trail.push(pick);
                    const dl = Math.hypot(pick.x - last.x, pick.y - last.y) || 1;
                    dir = { x: (pick.x - last.x) / dl, y: (pick.y - last.y) / dl };
                    last = pick; miss = 0;
                } else if (trail.length && ++miss > 6) break;
            }
            if (trail.length >= 2) {
                const lastP = trail[trail.length - 1];
                // Perpanjang arah terakhir sampai tepi atas/samping, puncak dari titik tengah yang terdeteksi
                const end = { x: Math.max(0.02, Math.min(0.98, lastP.x + (lastP.x - start.x) * 0.6)), y: Math.max(0.02, lastP.y - Math.abs(lastP.y - start.y) * 0.2) };
                makeTracer(end, null);
                say('Bola terdeteksi. Putar video untuk melihat shot tracer. Bisa dirapikan dengan "Tandai ujung bola" / "Tandai puncak".');
            } else {
                say('Bola tidak terdeteksi otomatis (biasanya terlalu cepat keluar layar). Pakai "Tandai ujung bola": klik arah bola keluar layar, tracer langsung dibuat.');
            }
            await seek(phaseT.impact);
        } catch (e) {
            say('Tracer otomatis gagal. Pakai "Tandai ujung bola".');
        } finally {
            busy = false;
            btn.disabled = false;
            draw();
        }
    });

    /* ---------- capture ---------- */
    $('#st-capture').addEventListener('click', async () => {
        video.pause();
        const cw = Math.min(960, W), ch = Math.round(cw / ratio);
        const cap = document.createElement('canvas');
        cap.width = cw; cap.height = ch;
        const c = cap.getContext('2d');
        let data;
        try {
            c.drawImage(video, 0, 0, cw, ch);
            render(c, cw, ch, video.currentTime, Math.max(1, cw / 520));
            const bar = Math.round(cw / 22);
            c.fillStyle = 'rgba(20,20,26,.75)';
            c.fillRect(0, ch - bar, cw, bar);
            c.font = '700 ' + Math.round(cw / 40) + 'px Fredoka, system-ui, sans-serif';
            c.fillStyle = '#FFFFFF'; c.textBaseline = 'middle'; c.textAlign = 'left';
            c.fillText('Coach Ditya Lesson · ' + fmt(video.currentTime), Math.round(cw / 60), ch - bar / 2);
            data = cap.toDataURL('image/jpeg', 0.82);
        } catch (e) {
            say('Capture tidak bisa dibuat dari video ini (izin video). Coba muat ulang halaman.');
            return;
        }
        const item = document.createElement('div');
        item.className = 'st-cap';
        item.innerHTML = '<img alt="Capture swing"><span>Menyimpan…</span><a download>Unduh</a>';
        item.querySelector('img').src = data;
        const a = item.querySelector('a');
        a.href = data;
        a.download = 'swing-' + Date.now() + '.jpg';
        gallery.prepend(item);
        gallery.hidden = false;
        if (!o.captureUrl) { item.querySelector('span').textContent = 'Siap diunduh'; return; }
        try {
            const res = await fetch(o.captureUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': o.csrf },
                credentials: 'same-origin',
                body: JSON.stringify({ image: data }),
            });
            const j = await res.json().catch(() => ({}));
            item.querySelector('span').textContent = res.ok && j.ok ? '✓ Tersimpan di hasil' : (j.message || 'Gagal disimpan');
        } catch (e) {
            item.querySelector('span').textContent = 'Gagal disimpan';
        }
    });

    draw();
    return { status, draw };
}
