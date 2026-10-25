/*
 * Studio Video Golf Analyzer.
 * Dibuka setelah AI selesai menganalisis video (video tetap di perangkat, tidak di-upload).
 *
 * Fitur:
 *  - Slow motion (0.25x / 0.5x / 1x), maju-mundur per frame, lompat ke tahapan swing
 *  - Overlay otomatis: rangka tubuh, sudut (lutut, lengan depan, punggung), jejak tarikan stick (jalur tangan),
 *    garis plane & garis alignment stick, posisi + jejak bola terbang (beta)
 *  - Alat gambar saat video dijeda: garis (dengan sudut terhadap horizontal), sudut 3 titik, lingkaran
 *  - Capture: frame + semua garis disimpan ke hasil analisis dan bisa diunduh
 */
const BONES = [[11, 12], [11, 13], [13, 15], [12, 14], [14, 16], [11, 23], [12, 24], [23, 24], [23, 25], [25, 27], [24, 26], [26, 28]];
const JOINTS = [0, 11, 12, 13, 14, 15, 16, 23, 24, 25, 26, 27, 28];
const STEP = 1 / 30;

export function openStudio(o) {
    const root = o.root;
    const $ = (s) => root.querySelector(s);
    const W = o.width, H = o.height, ratio = W / H;
    const frames = o.frames.filter((f) => f.pose);
    const lead = o.hand === 'left' ? { sh: 12, el: 14, wr: 16, hip: 24, kn: 26, an: 28 } : { sh: 11, el: 13, wr: 15, hip: 23, kn: 25, an: 27 };
    const phaseT = Object.fromEntries(o.phases.map((p) => [p.key, p.t]));

    const stage = $('#st-stage'), video = $('#st-video'), canvas = $('#st-canvas'), ctx = canvas.getContext('2d');
    const playBtn = $('#st-play'), scrub = $('#st-scrub'), timeEl = $('#st-time'), msg = $('#st-msg'), gallery = $('#st-gallery');

    const show = { skel: true, angles: true, path: true, plane: true, ball: true };
    let tool = 'none';
    const shapes = [];
    let draft = null;
    let ball = { x: 0.5, y: 0.85, auto: true };
    let ballTrail = [];
    let busy = false;
    let club = null;      // jejak kepala stick hasil pelacakan: [{t, x, y}]
    let clubBusy = false;

    root.hidden = false;
    video.src = o.src;
    video.muted = true;
    video.playsInline = true;
    video.preload = 'auto';
    video.defaultPlaybackRate = 0.5;
    video.playbackRate = 0.5;
    stage.style.aspectRatio = W + ' / ' + H;
    stage.style.width = 'min(100%, calc(68vh * ' + ratio.toFixed(4) + '))';
    if (o.resultUrl) $('#st-result').href = o.resultUrl;
    root.scrollIntoView({ behavior: 'smooth', block: 'start' });

    /* ---------- util ---------- */
    function say(t) { msg.textContent = t || ''; msg.hidden = !t; }
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
    function guessBall() {
        const p = poseAt(phaseT.setup ?? 0);
        if (!p) return { x: 0.5, y: 0.85, auto: true };
        const ank = mid(p[27], p[28]);
        const wr = mid(p[15], p[16]);
        return { x: o.view === 'down_the_line' ? wr.x + (wr.x - ank.x) * 0.25 : (ank.x * 0.6 + p[lead.an].x * 0.4), y: Math.min(0.98, ank.y + 0.015), auto: true };
    }
    function fmt(t) { return t.toFixed(2) + ' s'; }
    ball = guessBall();

    /* ---------- gambar overlay (dipakai layar & capture) ---------- */
    function render(c, w, h, t, scale) {
        const X = (p) => p.x * w, Y = (p) => p.y * h;
        const lw = (n) => n * scale;
        const pose = poseAt(t);
        c.lineCap = 'round';
        c.lineJoin = 'round';

        const label = (x, y, text, bg) => {
            c.font = '700 ' + lw(13) + 'px Fredoka, system-ui, sans-serif';
            const tw = c.measureText(text).width + lw(12);
            c.fillStyle = bg || '#FFFFFF';
            c.strokeStyle = '#14141A';
            c.lineWidth = lw(2);
            roundRect(c, x - tw / 2, y - lw(11), tw, lw(22), lw(8));
            c.fill(); c.stroke();
            c.fillStyle = '#14141A';
            c.textAlign = 'center';
            c.textBaseline = 'middle';
            c.fillText(text, x, y + lw(1));
        };

        // Garis rapi: garis gelap tipis di bawah, warna di atas
        const line = (x1, y1, x2, y2, color, width = 3) => {
            c.setLineDash([]);
            c.strokeStyle = 'rgba(20,20,26,.75)'; c.lineWidth = lw(width + 2.4);
            c.beginPath(); c.moveTo(x1, y1); c.lineTo(x2, y2); c.stroke();
            c.strokeStyle = color; c.lineWidth = lw(width);
            c.beginPath(); c.moveTo(x1, y1); c.lineTo(x2, y2); c.stroke();
        };
        // Titik di tepi video bila garis dari A ditarik lurus melewati B
        const toEdge = (ax, ay, bx, by) => {
            const dx = bx - ax, dy = by - ay;
            let k = Infinity;
            if (dx > 0) k = Math.min(k, (w - ax) / dx); else if (dx < 0) k = Math.min(k, -ax / dx);
            if (dy > 0) k = Math.min(k, (h - ay) / dy); else if (dy < 0) k = Math.min(k, -ay / dy);
            if (!isFinite(k) || k <= 0) k = 1;
            return [ax + dx * k, ay + dy * k];
        };

        // Garis plane & alignment stick (dari posisi setup, diam sebagai patokan)
        if (show.plane) {
            const p0 = poseAt(phaseT.setup ?? 0);
            if (p0) {
                const bx = ball.x * w, by = ball.y * h;
                const hands = mid(p0[15], p0[16]);
                // Plane: dari bola lurus melewati tangan saat setup (arah shaft), sampai tepi video
                const [px, py] = toEdge(bx, by, X(hands), Y(hands));
                line(bx, by, px, py, '#E8453C');
                if (o.view === 'down_the_line') {
                    // Kamera dari belakang: garis target & garis ujung kaki lurus ke depan (searah alignment stick)
                    // Garis ujung kaki: lewat kaki yang paling dekat ke bola, sedikit ke depan (ujung sepatu)
                    const near = Math.abs(p0[27].x * w - bx) < Math.abs(p0[28].x * w - bx) ? p0[27] : p0[28];
                    const toeX = X(near) + Math.sign(bx - X(near)) * lw(12);
                    const topY = Math.max(lw(10), Y(mid(p0[23], p0[24])));
                    line(bx, h - lw(4), bx, topY, '#FFE08A');
                    line(toeX, h - lw(4), toeX, topY, '#FFE08A', 2);
                    label(bx, topY - lw(14), 'Alignment', '#FFE08A');
                } else {
                    // Kamera dari depan: alignment stick sejajar garis kaki, lewat bola
                    const a1 = p0[27], a2 = p0[28];
                    const fx = (a2.x - a1.x) * w, fy = (a2.y - a1.y) * h;
                    const [lx1, ly1] = toEdge(bx, by, bx - fx, by - fy);
                    const [lx2, ly2] = toEdge(bx, by, bx + fx, by + fy);
                    line(lx1, ly1, lx2, ly2, '#FFE08A');
                    label(Math.min(w - lw(50), Math.max(lw(50), bx)), by + lw(20), 'Alignment', '#FFE08A');
                }
                label(Math.min(w - lw(40), Math.max(lw(40), bx + (px - bx) * 0.45)), by + (py - by) * 0.45, 'Plane', '#FFC7C2');
                // Penanda bola
                c.strokeStyle = '#14141A'; c.lineWidth = lw(4.5);
                c.beginPath(); c.arc(bx, by, lw(8), 0, Math.PI * 2); c.stroke();
                c.strokeStyle = '#FFFFFF'; c.lineWidth = lw(2.5);
                c.beginPath(); c.arc(bx, by, lw(8), 0, Math.PI * 2); c.stroke();
            }
        }

        // Jejak stick: jalur kepala stick (hasil pelacakan video) sampai waktu sekarang
        if (show.path) {
            const pts = club ? club.filter((q) => q.t <= t + 1e-6) : [];
            if (pts.length > 1) {
                c.setLineDash([]);
                c.strokeStyle = 'rgba(20,20,26,.7)'; c.lineWidth = lw(6.5);
                c.beginPath(); c.moveTo(pts[0].x * w, pts[0].y * h); pts.forEach((q) => c.lineTo(q.x * w, q.y * h)); c.stroke();
                c.strokeStyle = '#FFE08A'; c.lineWidth = lw(4);
                c.beginPath(); c.moveTo(pts[0].x * w, pts[0].y * h); pts.forEach((q) => c.lineTo(q.x * w, q.y * h)); c.stroke();
                // Batang stick saat ini: tangan -> kepala stick
                if (pose) {
                    const hd = mid(pose[15], pose[16]), last = pts[pts.length - 1];
                    if (t - last.t < 0.12) {
                        c.strokeStyle = 'rgba(20,20,26,.7)'; c.lineWidth = lw(4.5);
                        c.beginPath(); c.moveTo(X(hd), Y(hd)); c.lineTo(last.x * w, last.y * h); c.stroke();
                        c.strokeStyle = '#FFFFFF'; c.lineWidth = lw(2.2);
                        c.beginPath(); c.moveTo(X(hd), Y(hd)); c.lineTo(last.x * w, last.y * h); c.stroke();
                    }
                }
            }
        }

        // Rangka tubuh
        if (show.skel && pose) {
            c.strokeStyle = '#14141A';
            c.lineWidth = lw(7);
            BONES.forEach(([a, b]) => { if (pose[a].v > 0.3 && pose[b].v > 0.3) { c.beginPath(); c.moveTo(X(pose[a]), Y(pose[a])); c.lineTo(X(pose[b]), Y(pose[b])); c.stroke(); } });
            c.strokeStyle = '#22A04B';
            c.lineWidth = lw(4);
            BONES.forEach(([a, b]) => { if (pose[a].v > 0.3 && pose[b].v > 0.3) { c.beginPath(); c.moveTo(X(pose[a]), Y(pose[a])); c.lineTo(X(pose[b]), Y(pose[b])); c.stroke(); } });
            JOINTS.forEach((k) => {
                if (pose[k].v < 0.3) return;
                c.fillStyle = '#FFFFFF'; c.strokeStyle = '#14141A'; c.lineWidth = lw(2);
                c.beginPath(); c.arc(X(pose[k]), Y(pose[k]), lw(4.5), 0, Math.PI * 2); c.fill(); c.stroke();
            });
        }

        // Sudut otomatis
        if (show.angles && pose) {
            const knee = angleAt(pose[lead.hip], pose[lead.kn], pose[lead.an]);
            const arm = angleAt(pose[lead.sh], pose[lead.el], pose[lead.wr]);
            const hipM = mid(pose[23], pose[24]), shM = mid(pose[11], pose[12]);
            const spine = Math.abs(Math.atan2((shM.x - hipM.x) * w, (hipM.y - shM.y) * h) * 180 / Math.PI);
            label(X(pose[lead.kn]) + lw(34), Y(pose[lead.kn]), 'Lutut ' + Math.round(knee) + '°');
            label(X(pose[lead.el]) + lw(40), Y(pose[lead.el]), 'Lengan ' + Math.round(arm) + '°');
            label(X(hipM) - lw(46), (Y(hipM) + Y(shM)) / 2, 'Punggung ' + Math.round(spine) + '°');
        }

        // Bola + jejak terbang
        if (show.ball) {
            const shown = ballTrail.filter((p) => p.t <= t + 1e-6);
            if (shown.length) {
                c.strokeStyle = '#FFFFFF'; c.lineWidth = lw(4);
                c.shadowColor = 'rgba(0,0,0,.6)'; c.shadowBlur = lw(4);
                c.beginPath(); c.moveTo(ball.x * w, ball.y * h);
                shown.forEach((p) => c.lineTo(p.x * w, p.y * h));
                c.stroke();
                c.shadowBlur = 0;
            }
            const atImpact = phaseT.impact != null && t > phaseT.impact + 0.02 && ballTrail.length;
            if (!atImpact) {
                c.strokeStyle = '#E8453C'; c.lineWidth = lw(3);
                c.beginPath(); c.arc(ball.x * w, ball.y * h, lw(11), 0, Math.PI * 2); c.stroke();
                if (tool === 'ball') label(ball.x * w, ball.y * h - lw(24), 'Bola');
            }
        }

        // Gambar buatan pengguna
        const all = draft ? shapes.concat([draft]) : shapes;
        all.forEach((s) => drawShape(c, s, w, h, lw, label));
    }

    function drawShape(c, s, w, h, lw, label) {
        c.strokeStyle = '#FFE08A';
        c.fillStyle = '#FFE08A';
        c.lineWidth = lw(3.5);
        const P = (p) => [p.x * w, p.y * h];
        const dot = (p) => { const [x, y] = P(p); c.beginPath(); c.arc(x, y, lw(5), 0, Math.PI * 2); c.fill(); c.strokeStyle = '#14141A'; c.lineWidth = lw(1.5); c.stroke(); c.strokeStyle = '#FFE08A'; c.lineWidth = lw(3.5); };
        if (s.type === 'line' && s.b) {
            const [x1, y1] = P(s.a), [x2, y2] = P(s.b);
            c.beginPath(); c.moveTo(x1, y1); c.lineTo(x2, y2); c.stroke();
            dot(s.a); dot(s.b);
            let deg = Math.abs(Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI);
            if (deg > 90) deg = 180 - deg;
            label((x1 + x2) / 2, (y1 + y2) / 2 - lw(18), Math.round(deg) + '°', '#FFE08A');
        } else if (s.type === 'angle' && s.pts.length) {
            const pts = s.pts.map(P);
            c.beginPath(); c.moveTo(pts[0][0], pts[0][1]);
            pts.slice(1).forEach(([x, y]) => c.lineTo(x, y));
            c.stroke();
            s.pts.forEach(dot);
            if (s.pts.length === 3) {
                const deg = angleAt(s.pts[0], s.pts[1], s.pts[2]);
                const [bx, by] = pts[1];
                const a1 = Math.atan2(pts[0][1] - by, pts[0][0] - bx), a2 = Math.atan2(pts[2][1] - by, pts[2][0] - bx);
                let d = a2 - a1; while (d > Math.PI) d -= 2 * Math.PI; while (d < -Math.PI) d += 2 * Math.PI;
                c.beginPath(); c.arc(bx, by, lw(26), a1, a1 + d, d < 0); c.stroke();
                label(bx, by - lw(40), Math.round(deg) + '°', '#FFE08A');
            }
        } else if (s.type === 'circle' && s.r) {
            const [x, y] = P(s.c);
            c.beginPath(); c.arc(x, y, s.r * w, 0, Math.PI * 2); c.stroke();
        }
    }

    function roundRect(c, x, y, w, h, r) {
        c.beginPath();
        c.moveTo(x + r, y); c.arcTo(x + w, y, x + w, y + h, r); c.arcTo(x + w, y + h, x, y + h, r);
        c.arcTo(x, y + h, x, y, r); c.arcTo(x, y, x + w, y, r); c.closePath();
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
        timeEl.textContent = fmt(video.currentTime) + ' / ' + fmt(video.duration || 0);
        if (!scrub.matches(':active')) scrub.value = String(video.currentTime);
    }

    function loop() {
        draw();
        if (!video.paused) {
            if (video.requestVideoFrameCallback) video.requestVideoFrameCallback(loop);
            else requestAnimationFrame(loop);
        }
    }

    /* ---------- kontrol video ---------- */
    video.addEventListener('loadedmetadata', () => { scrub.max = String(video.duration); video.playbackRate = 0.5; video.currentTime = phaseT.setup ?? 0; });
    video.addEventListener('seeked', draw);
    video.addEventListener('play', () => { playBtn.textContent = '❚❚ Jeda'; loop(); });
    video.addEventListener('pause', () => { playBtn.textContent = '▶ Putar'; draw(); });
    video.addEventListener('ended', () => { playBtn.textContent = '▶ Putar'; });
    window.addEventListener('resize', draw);

    playBtn.addEventListener('click', () => (video.paused ? video.play() : video.pause()));
    $('#st-prev').addEventListener('click', () => { video.pause(); video.currentTime = Math.max(0, video.currentTime - STEP); });
    $('#st-next').addEventListener('click', () => { video.pause(); video.currentTime = Math.min(video.duration, video.currentTime + STEP); });
    scrub.addEventListener('input', () => { video.pause(); video.currentTime = Number(scrub.value); });
    root.querySelectorAll('[data-speed]').forEach((b) => b.addEventListener('click', () => {
        video.playbackRate = Number(b.getAttribute('data-speed'));
        root.querySelectorAll('[data-speed]').forEach((x) => x.classList.toggle('on', x === b));
    }));
    root.querySelectorAll('[data-jump]').forEach((b) => b.addEventListener('click', () => {
        const t = phaseT[b.getAttribute('data-jump')];
        if (t != null) { video.pause(); video.currentTime = t; }
    }));
    root.querySelectorAll('[data-show]').forEach((b) => {
        b.classList.toggle('on', show[b.getAttribute('data-show')]);
        b.addEventListener('click', () => { const k = b.getAttribute('data-show'); show[k] = !show[k]; b.classList.toggle('on', show[k]); draw(); });
    });
    root.querySelectorAll('[data-tool]').forEach((b) => b.addEventListener('click', () => {
        const t = b.getAttribute('data-tool');
        tool = tool === t ? 'none' : t;
        draft = null;
        root.querySelectorAll('[data-tool]').forEach((x) => x.classList.toggle('on', x.getAttribute('data-tool') === tool));
        canvas.style.cursor = tool === 'none' ? 'pointer' : 'crosshair';
        if (tool !== 'none') video.pause();
        say({ line: 'Tarik garis di atas video. Angka menunjukkan sudut terhadap garis datar.', angle: 'Klik 3 titik: ujung pertama, titik sudut, ujung kedua.', circle: 'Tarik dari tengah ke luar untuk membuat lingkaran.', ball: 'Klik tepat di bola pada posisi setup.' }[tool] || '');
        draw();
    }));
    $('#st-undo').addEventListener('click', () => { draft = null; shapes.pop(); draw(); });
    $('#st-clear').addEventListener('click', () => { draft = null; shapes.length = 0; draw(); });

    /* ---------- menggambar ---------- */
    function pt(e) {
        const r = canvas.getBoundingClientRect();
        return { x: Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)), y: Math.max(0, Math.min(1, (e.clientY - r.top) / r.height)) };
    }
    canvas.addEventListener('pointerdown', (e) => {
        const p = pt(e);
        if (tool === 'none') { video.paused ? video.play() : video.pause(); return; }
        e.preventDefault();
        canvas.setPointerCapture(e.pointerId);
        if (tool === 'ball') {
            ball = { x: p.x, y: p.y, auto: false };
            ballTrail = [];
            say('Posisi bola disimpan. Garis plane & jejak stick diperbarui.');
            trackClub();
            draw();
            return;
        }
        if (tool === 'line') draft = { type: 'line', a: p, b: p };
        if (tool === 'circle') draft = { type: 'circle', c: p, r: 0 };
        if (tool === 'angle') {
            if (!draft || draft.type !== 'angle') draft = { type: 'angle', pts: [] };
            draft.pts.push(p);
            if (draft.pts.length === 3) { shapes.push(draft); draft = null; }
        }
        draw();
    });
    canvas.addEventListener('pointermove', (e) => {
        if (!draft) return;
        const p = pt(e);
        if (draft.type === 'line' && e.buttons) draft.b = p;
        if (draft.type === 'circle' && e.buttons) draft.r = Math.hypot((p.x - draft.c.x), (p.y - draft.c.y) * (H / W));
        draw();
    });
    canvas.addEventListener('pointerup', () => {
        if (!draft) return;
        if (draft.type === 'line') { if (Math.hypot(draft.a.x - draft.b.x, draft.a.y - draft.b.y) > 0.01) shapes.push(draft); draft = null; }
        if (draft && draft.type === 'circle') { if (draft.r > 0.005) shapes.push(draft); draft = null; }
        draw();
    });

    /* ---------- deteksi bola terbang (beta) ---------- */
    const work = document.createElement('canvas');
    const SW = 320, SH = Math.max(40, Math.round(320 / ratio));
    work.width = SW; work.height = SH;
    const wctx = work.getContext('2d', { willReadFrequently: true });

    function seek(t) {
        return new Promise((res) => {
            const done = () => { video.removeEventListener('seeked', done); res(); };
            video.addEventListener('seeked', done);
            video.currentTime = Math.max(0, Math.min(video.duration - 0.001, t));
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
        if (g[by * SW + bx] > 150) ball = { x: (bx + 0.5) / SW, y: (by + 0.5) / SH, auto: ball.auto };
    }
    function blobs(g, prev, bodyBox) {
        const seen = new Uint8Array(SW * SH), out = [];
        for (let i = 0; i < g.length; i++) {
            if (seen[i] || g[i] < 165 || Math.abs(g[i] - prev[i]) < 45) continue;
            const stack = [i]; seen[i] = 1; let n = 0, sx = 0, sy = 0;
            while (stack.length && n < 400) {
                const k = stack.pop(), x = k % SW, y = (k / SW) | 0;
                n++; sx += x; sy += y;
                for (const nk of [k - 1, k + 1, k - SW, k + SW]) {
                    if (nk < 0 || nk >= g.length || seen[nk]) continue;
                    if (Math.abs((nk % SW) - x) > 1) continue;
                    if (g[nk] >= 165 && Math.abs(g[nk] - prev[nk]) >= 45) { seen[nk] = 1; stack.push(nk); }
                }
            }
            if (n >= 1 && n <= 60) {
                const p = { x: (sx / n + 0.5) / SW, y: (sy / n + 0.5) / SH };
                const inBody = bodyBox && p.x > bodyBox.x1 && p.x < bodyBox.x2 && p.y > bodyBox.y1 && p.y < bodyBox.y2;
                if (!inBody) out.push(p);
            }
        }
        return out;
    }
    function bodyBoxAt(t) {
        const p = poseAt(t);
        if (!p) return null;
        const pts = [0, 11, 12, 13, 14, 15, 16, 23, 24, 25, 26].map((k) => p[k]);
        const xs = pts.map((q) => q.x), ys = pts.map((q) => q.y);
        return { x1: Math.min(...xs) - 0.05, x2: Math.max(...xs) + 0.05, y1: Math.min(...ys) - 0.08, y2: Math.max(...ys) + 0.02 };
    }

    $('#st-track').addEventListener('click', async () => {
        if (busy) return;
        if (phaseT.impact == null) return say('Tahap impact tidak ditemukan, bola tidak bisa dilacak.');
        busy = true;
        video.pause();
        const btn = $('#st-track');
        btn.disabled = true;
        try {
            say('Mencari bola di posisi setup…');
            await seek(phaseT.setup ?? 0);
            if (!ball.found && ball.auto) refineBall(gray());
            const start = { x: ball.x, y: ball.y };
            const t0 = phaseT.impact - 0.06, t1 = Math.min(video.duration, phaseT.impact + 0.7), step = 1 / 60;
            await seek(t0);
            let prev = gray();
            const trail = [];
            let last = start, dir = null, miss = 0;
            for (let t = t0 + step; t <= t1; t += step) {
                say('Melacak bola terbang… ' + Math.round(((t - t0) / (t1 - t0)) * 100) + '%');
                await seek(t);
                const g = gray();
                const cands = blobs(g, prev, bodyBoxAt(t));
                prev = g;
                let pick = null, bestD = Infinity;
                for (const c of cands) {
                    const d = Math.hypot(c.x - last.x, (c.y - last.y) / ratio);
                    if (d < 0.003 || d > (trail.length ? 0.35 : 0.25)) continue;
                    if (dir) {
                        const dot = (c.x - last.x) * dir.x + (c.y - last.y) * dir.y;
                        if (dot <= 0) continue;
                    } else if (c.y > start.y + 0.02) continue;
                    if (d < bestD) { bestD = d; pick = c; }
                }
                if (pick) {
                    trail.push({ x: pick.x, y: pick.y, t });
                    const dl = Math.hypot(pick.x - last.x, pick.y - last.y) || 1;
                    dir = { x: (pick.x - last.x) / dl, y: (pick.y - last.y) / dl };
                    last = pick; miss = 0;
                } else if (trail.length && ++miss > 6) break;
            }
            ballTrail = trail;
            show.ball = true;
            root.querySelector('[data-show="ball"]')?.classList.add('on');
            await seek(trail.length ? trail[trail.length - 1].t : phaseT.impact + 0.1);
            say(trail.length >= 2
                ? 'Bola terdeteksi di ' + trail.length + ' frame. Putar slow motion untuk melihat jejak bola.'
                : 'Bola belum terdeteksi: biasanya bola terlalu cepat keluar dari layar. Hasil lebih baik dengan video slow-motion (120/240 fps), cahaya terang, dan bola terlihat jelas.');
        } catch (e) {
            say('Pelacakan bola gagal. Coba lagi.');
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
        c.drawImage(video, 0, 0, cw, ch);
        render(c, cw, ch, video.currentTime, Math.max(1, cw / 520));
        c.font = '700 ' + Math.round(cw / 40) + 'px Fredoka, system-ui, sans-serif';
        c.fillStyle = 'rgba(20,20,26,.75)';
        c.fillRect(0, ch - Math.round(cw / 22), cw, Math.round(cw / 22));
        c.fillStyle = '#FFFFFF';
        c.textBaseline = 'middle';
        c.textAlign = 'left';
        c.fillText('Coach Ditya Lesson · ' + fmt(video.currentTime), Math.round(cw / 60), ch - Math.round(cw / 44));
        const data = cap.toDataURL('image/jpeg', 0.82);

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

    /* ---------- Analisis gambar video (pakai video kedua di belakang layar, video utama tidak terganggu) ---------- */
    const vw = document.createElement('video');
    vw.muted = true; vw.playsInline = true; vw.preload = 'auto'; vw.src = o.src;
    const AW = 480, AH = Math.max(60, Math.round(480 / ratio));
    const acv = document.createElement('canvas');
    acv.width = AW; acv.height = AH;
    const actx = acv.getContext('2d', { willReadFrequently: true });

    function vwSeek(t) {
        return new Promise((res) => {
            const done = () => { vw.removeEventListener('seeked', done); res(); };
            vw.addEventListener('seeked', done);
            vw.currentTime = Math.max(0, Math.min((vw.duration || 0) - 0.001, t));
            setTimeout(done, 1500);
        });
    }
    function grab() {
        actx.drawImage(vw, 0, 0, AW, AH);
        return actx.getImageData(0, 0, AW, AH).data;
    }

    // Cari bola: benda kecil, bulat, putih di tanah di depan/antara kaki pada posisi setup.
    // Area & ukuran bola dihitung dari tinggi badan golfer di video itu, jadi ikut jarak kamera & posisi golfer.
    async function detectBall() {
        const p = poseAt(phaseT.setup ?? 0);
        if (!p) return;
        await vwSeek(phaseT.setup ?? 0);
        const d = grab();
        const ank = mid(p[27], p[28]), wr = mid(p[15], p[16]);
        const bodyPx = Math.max(40, Math.abs(ank.y - p[0].y) * AH); // tinggi kepala->kaki (piksel analisis)
        const ankX = ank.x * AW, ankY = ank.y * AH;
        let x1, x2;
        if (o.view === 'down_the_line') {
            // Kamera samping/serong: bola di depan kaki, searah tangan menjulur (bisa jauh untuk driver)
            const side = Math.sign(wr.x - ank.x) || 1;
            const a = ankX - side * bodyPx * 0.15, b = ankX + side * bodyPx * 1.35;
            x1 = Math.min(a, b); x2 = Math.max(a, b);
        } else {
            // Kamera depan: bola di antara / sedikit di luar kedua kaki
            const lo = Math.min(p[27].x, p[28].x) * AW, hi = Math.max(p[27].x, p[28].x) * AW;
            const pad = Math.max(bodyPx * 0.2, (hi - lo) * 0.4);
            x1 = lo - pad; x2 = hi + pad;
        }
        // Perspektif: bola yang jauh dari kaki terlihat lebih tinggi dari mata kaki
        const X1 = Math.max(0, Math.floor(x1)), X2 = Math.min(AW - 1, Math.ceil(x2));
        const Y1 = Math.max(0, Math.floor(ankY - bodyPx * 0.4)), Y2 = Math.min(AH - 1, Math.ceil(ankY + bodyPx * 0.25));
        // Ukuran bola kira-kira 3% tinggi badan
        const minA = Math.max(2, Math.pow(bodyPx * 0.007, 2)), maxA = Math.pow(bodyPx * 0.08, 2);
        // Sepatu & sarung tangan (sering putih) tidak boleh dianggap bola
        // (kamera depan: bola di antara kaki, jadi zona sepatu dibatasi lebar kuda-kuda)
        const stancePx = Math.abs(p[27].x - p[28].x) * AW;
        const shoeR = o.view === 'down_the_line' ? bodyPx * 0.13 : Math.min(bodyPx * 0.1, stancePx * 0.4), handR = bodyPx * 0.12;
        const nearShoe = (x, y) => [p[27], p[28]].some((q) => Math.hypot(x - q.x * AW, y - q.y * AH) < shoeR)
            || Math.hypot(x - wr.x * AW, y - wr.y * AH) < handR;
        const white = (i) => { const r = d[i], g = d[i + 1], b = d[i + 2]; const mn = Math.min(r, g, b), mx = Math.max(r, g, b); return mn >= 150 && mx - mn <= 70; };
        const seen = new Uint8Array(AW * AH);
        const guess = guessBall();
        let best = null;
        for (let y = Y1; y <= Y2; y++) {
            for (let x = X1; x <= X2; x++) {
                const k = y * AW + x;
                if (seen[k] || !white(k * 4)) continue;
                const stack = [k]; seen[k] = 1;
                let n = 0, sx = 0, sy = 0, lum = 0, mnx = x, mxx = x, mny = y, mxy = y;
                while (stack.length && n < 2000) {
                    const q = stack.pop(), qx = q % AW, qy = (q / AW) | 0;
                    n++; sx += qx; sy += qy; lum += d[q * 4] + d[q * 4 + 1] + d[q * 4 + 2];
                    mnx = Math.min(mnx, qx); mxx = Math.max(mxx, qx); mny = Math.min(mny, qy); mxy = Math.max(mxy, qy);
                    for (const nk of [q - 1, q + 1, q - AW, q + AW]) {
                        if (nk < 0 || nk >= AW * AH || seen[nk] || Math.abs((nk % AW) - qx) > 1) continue;
                        const ny = (nk / AW) | 0, nx = nk % AW;
                        if (nx < X1 || nx > X2 || ny < Y1 || ny > Y2) continue;
                        if (white(nk * 4)) { seen[nk] = 1; stack.push(nk); }
                    }
                }
                const bw = mxx - mnx + 1, bh = mxy - mny + 1;
                if (n < minA || n > maxA || bw / bh > 2.4 || bh / bw > 2.4 || n / (bw * bh) < 0.45) continue;
                const px = sx / n + 0.5, py = sy / n + 0.5;
                if (nearShoe(px, py)) continue;
                const cx = px / AW, cy = py / AH;
                // Utamakan yang paling putih & bulat; jarak ke perkiraan hanya penentu kecil
                const bright = lum / n / 3 / 255;
                const dist = Math.hypot(cx - guess.x, (cy - guess.y) / ratio);
                const score = (1 - bright) + (1 - n / (bw * bh)) * 0.3 + dist * 0.6;
                if (!best || score < best.score) best = { x: cx, y: cy, score };
            }
        }
        if (best && ball.auto) {
            ball = { x: best.x, y: best.y, auto: true, found: true };
            say('Bola terdeteksi otomatis. Kalau posisinya meleset, pakai "Tandai bola" lalu klik bolanya.');
        } else if (ball.auto) {
            say('Bola belum terdeteksi otomatis. Pakai "Tandai bola" lalu klik bolanya supaya garis plane pas.');
        }
        draw();
    }

    // Jejak stick: cari kepala stick = bagian bergerak paling jauh dari tangan, sejauh panjang stick
    async function trackClub() {
        if (clubBusy || !frames.length) return;
        clubBusy = true;
        try {
            const p0 = poseAt(phaseT.setup ?? 0);
            const h0 = mid(p0[15], p0[16]);
            const L = Math.hypot((ball.x - h0.x) * AW, (ball.y - h0.y) * AH); // panjang stick (piksel analisis)
            if (L < 10) return;
            const tStart = phaseT.setup ?? frames[0].t, tEnd = Math.min((vw.duration || 99) - 0.02, (phaseT.finish ?? frames[frames.length - 1].t) + 0.15);
            const list = frames.filter((f) => f.t >= tStart - 0.001 && f.t <= tEnd);
            const out = [{ t: tStart, x: ball.x, y: ball.y }];
            let prev = { x: ball.x * AW, y: ball.y * AH };
            for (let i = 0; i < list.length; i++) {
                const f = list[i];
                if (i % 4 === 0) say('Menyiapkan jejak stick… ' + Math.round((i / list.length) * 100) + '%');
                // Tiga frame berurutan: piksel yang berubah sebelum DAN sesudah = posisi benda pada saat t
                await vwSeek(f.t - 1 / 30);
                const a = grab();
                await vwSeek(f.t);
                const b = grab();
                await vwSeek(f.t + 1 / 30);
                const c3 = grab();
                const hx = (f.pose[15].x + f.pose[16].x) / 2 * AW, hy = (f.pose[15].y + f.pose[16].y) / 2 * AH;
                // Piksel bergerak dalam cincin sepanjang stick di sekitar tangan
                let cand = [];
                const R1 = L * 0.7, R2 = L * 1.2;
                const xa = Math.max(0, Math.floor(hx - R2)), xb = Math.min(AW - 1, Math.ceil(hx + R2));
                const ya = Math.max(0, Math.floor(hy - R2)), yb = Math.min(AH - 1, Math.ceil(hy + R2));
                for (let y = ya; y <= yb; y += 1) {
                    for (let x = xa; x <= xb; x += 1) {
                        const dd = Math.hypot(x - hx, y - hy);
                        if (dd < R1 || dd > R2) continue;
                        const k = (y * AW + x) * 4;
                        const d1 = Math.abs(a[k] - b[k]) + Math.abs(a[k + 1] - b[k + 1]) + Math.abs(a[k + 2] - b[k + 2]);
                        const d2 = Math.abs(c3[k] - b[k]) + Math.abs(c3[k + 1] - b[k + 1]) + Math.abs(c3[k + 2] - b[k + 2]);
                        if (d1 > 80 && d2 > 80) cand.push({ x, y, dd });
                    }
                }
                let pick = null;
                if (cand.length >= 3) {
                    cand.sort((p, q) => q.dd - p.dd);
                    const top = cand.slice(0, Math.max(3, Math.round(cand.length * 0.12)));
                    const cx = top.reduce((s2, q) => s2 + q.x, 0) / top.length, cy = top.reduce((s2, q) => s2 + q.y, 0) / top.length;
                    if (Math.hypot(cx - prev.x, cy - prev.y) < L * 1.1) pick = { x: cx, y: cy };
                }
                if (pick) { out.push({ t: f.t, x: pick.x / AW, y: pick.y / AH }); prev = pick; }
            }
            // Buang titik melompat (tidak searah dengan titik sebelum & sesudahnya)
            const clean = out.filter((q, i) => {
                if (i === 0 || i === out.length - 1) return true;
                const pv = out[i - 1], nx = out[i + 1];
                const ex = (pv.x + nx.x) / 2, ey = (pv.y + nx.y) / 2;
                const dev = Math.hypot((q.x - ex) * AW, (q.y - ey) * AH);
                const span = Math.hypot((pv.x - nx.x) * AW, (pv.y - nx.y) * AH);
                return dev < Math.max(L * 0.35, span * 0.9);
            });
            out.length = 0;
            out.push(...clean);
            // Haluskan (rata-rata 3 titik)
            const sm = out.map((q, i) => {
                const a2 = out[Math.max(0, i - 1)], c2 = out[Math.min(out.length - 1, i + 1)];
                return { t: q.t, x: (a2.x + q.x + c2.x) / 3, y: (a2.y + q.y + c2.y) / 3 };
            });
            club = sm.length >= Math.max(4, list.length * 0.3) ? sm : null;
            say(club ? 'Jejak stick siap. Putar video untuk melihat jalur kepala stick.' : 'Jejak stick belum terbaca dari video ini (stick kurang terlihat). Coba video dengan cahaya terang dan latar polos.');
        } catch (e) {
            club = null;
        } finally {
            clubBusy = false;
            draw();
        }
    }

    (async () => {
        try {
            if (vw.readyState < 2) await new Promise((res) => { vw.addEventListener('loadeddata', res, { once: true }); setTimeout(res, 8000); });
            await detectBall();
            await trackClub();
        } catch (e) { /* analisis tambahan gagal: Studio tetap jalan */ }
    })();

    draw();
}
