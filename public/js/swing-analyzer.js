/*
 * Golf Analyzer - AI Swing Analysis di browser.
 *
 * Cara kerja:
 *  1. Video swing dipilih/direkam customer, diputar di <video> lokal (tidak di-upload).
 *  2. MediaPipe Pose Landmarker membaca 33 titik tubuh di setiap frame sampel.
 *  3. Dari posisi tangan dicari 5 tahapan swing: Setup, Backswing, Top, Impact, Follow Through.
 *  4. Dihitung sudut & ukuran: tekuk lutut, rotasi bahu, lengan depan, stabilitas kepala,
 *     tempo (rasio backswing : downswing), keseimbangan finish -> skor 0-100.
 *  5. Ringkasan + gambar kecil tiap tahapan dikirim ke server (POST /swing) lalu tampil di halaman hasil.
 */
const MP_VERSION = '0.10.14';
const MP_URL = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@' + MP_VERSION;
const MODEL_URL = 'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task';

const MAX_SIZE_MB = 150;
const MIN_SEC = 1.5;
const MAX_SEC = 20;

// Titik tubuh MediaPipe Pose
const L = { nose: 0, lSh: 11, rSh: 12, lEl: 13, rEl: 14, lWr: 15, rWr: 16, lHip: 23, rHip: 24, lKn: 25, rKn: 26, lAn: 27, rAn: 28 };
const BONES = [[11, 12], [11, 13], [13, 15], [12, 14], [14, 16], [11, 23], [12, 24], [23, 24], [23, 25], [25, 27], [24, 26], [26, 28]];

const root = document.querySelector('[data-swing-app]');
if (root) init(root);

function init(app) {
    const $ = (s) => app.querySelector(s);
    const el = {
        file: $('#sw-file'), record: $('#sw-record'), video: $('#sw-video'), preview: $('#sw-preview'),
        empty: $('#sw-empty'), view: $('#sw-view'), hand: $('#sw-hand'), start: $('#sw-start'),
        stepUpload: $('#sw-step-upload'), stepProcess: $('#sw-step-process'), pct: $('#sw-pct'),
        ring: $('#sw-ring'), status: $('#sw-status'), error: $('#sw-error'), steps: app.querySelectorAll('[data-step]'),
        checks: app.querySelectorAll('[data-chk]'), fileInfo: $('#sw-fileinfo'), canvas: $('#sw-canvas'),
        user: $('#sw-user'),
    };
    const storeUrl = app.getAttribute('data-store-url');
    const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    let objectUrl = null;

    function showError(msg) {
        el.error.textContent = msg;
        el.error.hidden = !msg;
    }

    function setStep(n) {
        el.steps.forEach((s) => s.classList.toggle('on', Number(s.getAttribute('data-step')) <= n));
    }

    function setProgress(p, text, chk) {
        const v = Math.max(0, Math.min(100, Math.round(p)));
        el.pct.textContent = v + '%';
        el.ring.style.setProperty('--p', v);
        if (text) el.status.textContent = text;
        if (chk) el.checks.forEach((c) => {
            const n = Number(c.getAttribute('data-chk'));
            c.classList.toggle('done', n < chk);
            c.classList.toggle('now', n === chk);
        });
    }

    async function onPick(input) {
        showError('');
        const f = input.files && input.files[0];
        if (!f) return;
        if (!/^video\//.test(f.type)) return showError('File harus berupa video (MP4, MOV, atau WEBM).');
        if (f.size > MAX_SIZE_MB * 1024 * 1024) return showError('Ukuran video maksimal ' + MAX_SIZE_MB + ' MB.');

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(f);
        el.video.src = objectUrl;
        el.video.muted = true;
        el.video.playsInline = true;

        try {
            await once(el.video, 'loadedmetadata', 15000);
        } catch (e) {
            return showError('Video tidak bisa dibuka di browser ini. Coba format MP4.');
        }

        const d = el.video.duration;
        if (!isFinite(d) || d < MIN_SEC) return showError('Video terlalu pendek. Rekam satu swing penuh (minimal 2 detik).');
        if (d > MAX_SEC) return showError('Video terlalu panjang (' + d.toFixed(0) + ' detik). Potong menjadi maksimal ' + MAX_SEC + ' detik, cukup satu swing.');

        el.empty.hidden = true;
        el.preview.hidden = false;
        el.fileInfo.textContent = f.name + ' · ' + (f.size / 1048576).toFixed(1) + ' MB · ' + d.toFixed(1) + ' detik';
        el.start.disabled = false;
    }

    el.file.addEventListener('change', () => onPick(el.file));
    if (el.record) el.record.addEventListener('change', () => onPick(el.record));

    el.start.addEventListener('click', async () => {
        showError('');
        el.start.disabled = true;
        el.stepUpload.hidden = true;
        el.stepProcess.hidden = false;
        setStep(2);
        window.scrollTo({ top: 0, behavior: 'smooth' });

        try {
            const view = el.view.value;
            const hand = el.hand.value;

            setProgress(3, 'Menyiapkan AI pendeteksi pose…', 1);
            const landmarker = await loadLandmarker();

            setProgress(12, 'Mendeteksi pose tubuh di setiap frame…', 1);
            const frames = await sampleFrames(el.video, landmarker, (p) => setProgress(12 + p * 66, null, p > 0.5 ? 2 : 1));

            setProgress(80, 'Menghitung sudut dan posisi…', 3);
            const W = el.video.videoWidth, H = el.video.videoHeight;
            const result = analyse(frames, W, H, view, hand);

            setProgress(88, 'Menyiapkan hasil analisis…', 4);
            for (const ph of result.phases) {
                ph.image = await snapshot(el.video, el.canvas, frames[ph.index]);
                delete ph.index;
            }

            setProgress(95, 'Menyimpan hasil…', 4);
            const body = {
                camera_view: view, handedness: hand, score: result.score, duration_ms: Math.round(el.video.duration * 1000),
                frames: frames.length, insight: result.insight, metrics: result.metrics, phases: result.phases, tips: result.tips,
            };
            // Admin: analisis untuk customer yang dipilih (kosong = untuk coach sendiri)
            if (el.user) body.user_id = el.user.value ? Number(el.user.value) : null;
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.redirect) throw new Error(json.message || 'Hasil gagal disimpan (kode ' + res.status + '). Coba lagi.');

            setProgress(100, 'Selesai! Membuka hasil…', 5);
            window.location.href = json.redirect;
        } catch (e) {
            console.error(e);
            el.stepProcess.hidden = true;
            el.stepUpload.hidden = false;
            el.start.disabled = false;
            setStep(1);
            showError(e && e.message ? e.message : 'Analisis gagal. Coba video lain.');
        }
    });
}

/* ---------------- MediaPipe ---------------- */

let landmarkerPromise = null;

function loadLandmarker() {
    if (landmarkerPromise) return landmarkerPromise;
    landmarkerPromise = (async () => {
        let vision;
        try {
            vision = await import(MP_URL);
        } catch (e) {
            throw new Error('AI tidak bisa dimuat. Periksa koneksi internet lalu coba lagi.');
        }
        const fileset = await vision.FilesetResolver.forVisionTasks(MP_URL + '/wasm');
        const make = (delegate) => vision.PoseLandmarker.createFromOptions(fileset, {
            baseOptions: { modelAssetPath: MODEL_URL, delegate },
            runningMode: 'VIDEO',
            numPoses: 1,
            minPoseDetectionConfidence: 0.5,
            minPosePresenceConfidence: 0.5,
            minTrackingConfidence: 0.5,
        });
        try {
            return await make('GPU');
        } catch (e) {
            return await make('CPU');
        }
    })();
    landmarkerPromise.catch(() => { landmarkerPromise = null; });
    return landmarkerPromise;
}

async function sampleFrames(video, landmarker, onProgress) {
    const dur = video.duration;
    const fps = dur <= 6 ? 24 : dur <= 12 ? 16 : 12;
    const total = Math.min(260, Math.max(20, Math.floor(dur * fps)));
    const step = dur / total;
    const frames = [];
    let lastTs = -1;

    video.pause();
    for (let i = 0; i < total; i++) {
        const t = Math.min(dur - 0.001, i * step);
        await seek(video, t);
        let ts = Math.round(t * 1000);
        if (ts <= lastTs) ts = lastTs + 1;
        lastTs = ts;

        let pose = null;
        try {
            const r = landmarker.detectForVideo(video, ts);
            if (r && r.landmarks && r.landmarks[0]) {
                pose = r.landmarks[0].map((p) => ({ x: p.x, y: p.y, v: p.visibility == null ? 1 : p.visibility }));
            }
        } catch (e) { pose = null; }

        frames.push({ t, pose });
        if (i % 3 === 0) onProgress((i + 1) / total);
    }
    onProgress(1);
    return frames;
}

/* ---------------- Analisis ---------------- */

function analyse(frames, W, H, view, hand) {
    const valid = frames.map((f, i) => (f.pose ? i : -1)).filter((i) => i >= 0);
    if (valid.length < Math.max(12, frames.length * 0.55)) {
        throw new Error('Pose tubuh kurang terdeteksi. Pastikan seluruh badan (kepala sampai kaki) terlihat, cahaya cukup, dan kamera diam.');
    }

    const P = (i, k) => { const p = frames[i].pose[k]; return { x: p.x * W, y: p.y * H, v: p.v }; };
    const mid = (a, b) => ({ x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 });
    const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
    const ang = (a, b, c) => {
        const v1 = { x: a.x - b.x, y: a.y - b.y }, v2 = { x: c.x - b.x, y: c.y - b.y };
        const cos = (v1.x * v2.x + v1.y * v2.y) / ((Math.hypot(v1.x, v1.y) * Math.hypot(v2.x, v2.y)) || 1);
        return Math.acos(Math.max(-1, Math.min(1, cos))) * 180 / Math.PI;
    };
    const tiltFromVertical = (low, high) => Math.abs(Math.atan2(high.x - low.x, low.y - high.y) * 180 / Math.PI);
    const clamp = (v, lo = 30, hi = 100) => Math.round(Math.max(lo, Math.min(hi, v)));

    const lead = hand === 'left'
        ? { sh: L.rSh, el: L.rEl, wr: L.rWr, an: L.rAn }
        : { sh: L.lSh, el: L.lEl, wr: L.lWr, an: L.lAn };

    // Tinggi tangan (y kecil = tangan tinggi), dihaluskan
    const raw = valid.map((i) => (P(i, L.lWr).y + P(i, L.rWr).y) / 2);
    const hy = raw.map((_, k) => {
        let s = 0, n = 0;
        for (let j = Math.max(0, k - 2); j <= Math.min(raw.length - 1, k + 2); j++) { s += raw[j]; n++; }
        return s / n;
    });

    const head = valid.slice(0, Math.max(3, Math.round(valid.length * 0.15))).map((_, k) => hy[k]).sort((a, b) => a - b);
    const baseline = head[Math.floor(head.length / 2)];
    const minY = Math.min(...hy);
    const range = baseline - minY;

    const a0 = valid[0];
    const bodyH = Math.abs(mid(P(a0, L.lAn), P(a0, L.rAn)).y - P(a0, L.nose).y) || H * 0.6;
    if (range < bodyH * 0.25) {
        throw new Error('Gerakan swing tidak terdeteksi. Rekam satu swing penuh dari posisi siap sampai finish.');
    }

    const thr = baseline - 0.55 * range;
    const findFrom = (from, test) => { for (let k = from; k < hy.length; k++) if (test(hy[k])) return k; return -1; };
    const argBetween = (from, to, better) => { let best = from; for (let k = from; k <= to; k++) if (better(hy[k], hy[best])) best = k; return best; };

    const k1 = findFrom(0, (v) => v < thr);
    const k2 = k1 >= 0 ? findFrom(k1, (v) => v > thr) : -1;
    if (k1 < 0 || k2 < 0) {
        throw new Error('Tahapan swing tidak lengkap. Pastikan video berisi backswing, pukulan, dan follow through.');
    }
    const kTop = argBetween(k1, k2 - 1, (a, b) => a < b);
    let k3 = findFrom(k2, (v) => v < thr);
    if (k3 < 0) k3 = hy.length - 1;
    const kImpact = argBetween(k2, k3, (a, b) => a > b);
    const kFinish = k3 < hy.length - 1 ? argBetween(k3, hy.length - 1, (a, b) => a < b) : hy.length - 1;

    let kStart = 0;
    for (let k = kTop; k >= 0; k--) { if (hy[k] > baseline - 0.1 * range) { kStart = k; break; } }
    const kBack = Math.round((kStart + kTop) / 2);

    const fi = (k) => valid[k];
    const iSet = fi(kStart), iBack = fi(kBack), iTop = fi(kTop), iImp = fi(kImpact), iFin = fi(kFinish);
    const t = (i) => frames[i].t;

    // 1. Postur awal: tekuk lutut (+ condong badan bila dari samping)
    const knee = (ang(P(iSet, L.lHip), P(iSet, L.lKn), P(iSet, L.lAn)) + ang(P(iSet, L.rHip), P(iSet, L.rKn), P(iSet, L.rAn))) / 2;
    let posture = knee >= 148 && knee <= 173 ? 96 : 96 - (knee < 148 ? (148 - knee) : (knee - 173)) * 3;
    let spine = null;
    if (view === 'down_the_line') {
        spine = tiltFromVertical(mid(P(iSet, L.lHip), P(iSet, L.rHip)), mid(P(iSet, L.lSh), P(iSet, L.rSh)));
        const sp = spine >= 25 && spine <= 45 ? 96 : 96 - (spine < 25 ? 25 - spine : spine - 45) * 2.5;
        posture = (posture + sp) / 2;
    }
    posture = clamp(posture);

    // 2. Rotasi bahu (perkiraan dari perubahan lebar bahu di layar)
    const wSet = dist(P(iSet, L.lSh), P(iSet, L.rSh)) || 1;
    const wTop = dist(P(iTop, L.lSh), P(iTop, L.rSh)) || 1;
    const turn = view === 'down_the_line'
        ? Math.acos(Math.min(1, wSet / wTop)) * 180 / Math.PI
        : Math.acos(Math.min(1, wTop / wSet)) * 180 / Math.PI;
    const rotation = clamp(turn / 75 * 100);

    // 3. Lengan depan di puncak backswing
    const arm = ang(P(iTop, lead.sh), P(iTop, lead.el), P(iTop, lead.wr));
    const armScore = clamp(100 - Math.max(0, 165 - arm) * 2.2);

    // 4. Stabilitas kepala dari setup sampai impact
    const nose0 = P(iSet, L.nose);
    let maxMove = 0;
    for (let k = kStart; k <= kImpact; k++) maxMove = Math.max(maxMove, dist(P(fi(k), L.nose), nose0));
    const headMove = maxMove / wSet;
    const headScore = clamp(100 - Math.max(0, headMove - 0.08) * 180);

    // 5. Tempo backswing : downswing (ideal sekitar 3 : 1)
    const back = Math.max(0.05, t(iTop) - t(iSet));
    const down = Math.max(0.03, t(iImp) - t(iTop));
    const tempo = back / down;
    const tempoScore = clamp(100 - Math.abs(tempo - 3) * 22);

    // 6. Keseimbangan di finish
    let balance;
    if (view === 'down_the_line') {
        const fTilt = tiltFromVertical(mid(P(iFin, L.lHip), P(iFin, L.rHip)), mid(P(iFin, L.lSh), P(iFin, L.rSh)));
        balance = clamp(100 - Math.max(0, fTilt - 15) * 2.5);
    } else {
        const stance = Math.abs(P(iSet, L.lAn).x - P(iSet, L.rAn).x) || wSet;
        const hipF = mid(P(iFin, L.lHip), P(iFin, L.rHip));
        const rel = Math.abs(hipF.x - P(iFin, lead.an).x) / stance;
        balance = clamp(100 - Math.max(0, rel - 0.2) * 160);
    }

    const score = Math.round(posture * 0.18 + rotation * 0.2 + armScore * 0.15 + headScore * 0.17 + tempoScore * 0.15 + balance * 0.15);

    const band = (s, good, mid2, low) => (s >= 80 ? good : s >= 65 ? mid2 : low);
    const metrics = [
        { key: 'posture', label: 'Postur Awal', score: posture, value: Math.round(knee), unit: '°',
          note: band(posture, 'Lutut sedikit ditekuk, posisi siap sudah stabil.', 'Postur cukup, perhatikan tekukan lutut.', knee > 173 ? 'Lutut terlalu lurus saat setup.' : 'Lutut terlalu ditekuk saat setup.') },
        { key: 'rotation', label: 'Rotasi Bahu', score: rotation, value: Math.round(turn), unit: '°',
          note: band(rotation, 'Rotasi bahu penuh saat backswing.', 'Rotasi bahu masih bisa ditambah.', 'Rotasi bahu kurang maksimal.') },
        { key: 'arm', label: 'Lengan Depan', score: armScore, value: Math.round(arm), unit: '°',
          note: band(armScore, 'Lengan depan lurus di puncak backswing.', 'Lengan depan sedikit menekuk.', 'Lengan depan terlalu menekuk di puncak.') },
        { key: 'head', label: 'Stabilitas Kepala', score: headScore, value: Math.round(headMove * 100), unit: '%',
          note: band(headScore, 'Kepala stabil sampai impact.', 'Kepala sedikit bergeser.', 'Kepala banyak bergerak saat swing.') },
        { key: 'tempo', label: 'Tempo', score: tempoScore, value: Math.round(tempo * 10) / 10, unit: ':1',
          note: band(tempoScore, 'Tempo backswing dan downswing seimbang.', 'Tempo cukup, sedikit terburu-buru.', tempo < 3 ? 'Backswing terlalu cepat dibanding downswing.' : 'Downswing terlalu lambat.') },
        { key: 'balance', label: 'Keseimbangan', score: balance, value: null, unit: null,
          note: band(balance, 'Finish seimbang di kaki depan.', 'Finish cukup seimbang.', 'Berat badan belum pindah ke kaki depan saat finish.') },
    ];
    const byKey = Object.fromEntries(metrics.map((m) => [m.key, m]));

    const phases = [
        { key: 'setup', name: 'Setup', index: iSet, score: posture, note: byKey.posture.note },
        { key: 'backswing', name: 'Backswing', index: iBack, score: rotation, note: byKey.rotation.note },
        { key: 'top', name: 'Top of Backswing', index: iTop, score: clamp(armScore * 0.6 + rotation * 0.4), note: byKey.arm.note },
        { key: 'impact', name: 'Downswing & Impact', index: iImp, score: clamp(headScore * 0.5 + tempoScore * 0.5), note: headScore < tempoScore ? byKey.head.note : byKey.tempo.note },
        { key: 'finish', name: 'Follow Through', index: iFin, score: balance, note: byKey.balance.note },
    ];

    const DRILLS = {
        posture: ['Latihan Postur Setup', 'Tekuk lutut sedikit, condongkan badan dari pinggul, punggung tetap lurus. Latih di depan cermin 10 kali sebelum memukul.'],
        rotation: ['Latihan Rotasi Bahu', 'Silangkan stik di dada, putar bahu sampai punggung menghadap target. Lakukan 3 x 15 repetisi.'],
        arm: ['Drill Lengan Lurus', 'Ayunan setengah dengan fokus lengan depan tetap lurus sampai puncak backswing. Lakukan 2 x 10 ayunan.'],
        head: ['Drill Kepala Diam', 'Swing pelan sambil menjaga pandangan ke bola. Minta teman memegang stik di dekat kepala sebagai patokan.'],
        tempo: ['Drill Tempo 1-2-3', 'Gunakan metronom: hitungan 1-2 untuk backswing, 3 untuk downswing. Ulangi 20 ayunan.'],
        balance: ['Latihan Keseimbangan Finish', 'Tahan posisi finish 3 detik dengan berat di kaki depan dan tumit belakang terangkat.'],
    };
    const weak = metrics.filter((m) => m.score < 80).sort((a, b) => a.score - b.score);
    const tips = (weak.length ? weak : metrics.slice().sort((a, b) => a.score - b.score).slice(0, 2))
        .slice(0, 4)
        .map((m) => ({ title: DRILLS[m.key][0], text: DRILLS[m.key][1] }));

    const worst = metrics.slice().sort((a, b) => a.score - b.score)[0];
    const insight = score >= 80
        ? 'Swing kamu sudah bagus! Fokus utama berikutnya: ' + worst.label.toLowerCase() + '. ' + worst.note
        : 'Fokus perbaikan utama: ' + worst.label.toLowerCase() + '. ' + worst.note;

    return {
        score,
        insight,
        tips,
        phases,
        metrics: metrics.map((m) => ({ ...m, value: m.value })),
        extra: { spine },
    };
}

/* ---------------- Gambar tahapan ---------------- */

async function snapshot(video, canvas, frame) {
    try {
        await seek(video, frame.t);
        const w = 360;
        const h = Math.round(w * (video.videoHeight / video.videoWidth));
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);
        if (frame.pose) {
            ctx.lineCap = 'round';
            ctx.lineWidth = 4;
            ctx.strokeStyle = '#22A04B';
            BONES.forEach(([a, b]) => {
                const p = frame.pose[a], q = frame.pose[b];
                if (p.v < 0.3 || q.v < 0.3) return;
                ctx.beginPath(); ctx.moveTo(p.x * w, p.y * h); ctx.lineTo(q.x * w, q.y * h); ctx.stroke();
            });
            ctx.fillStyle = '#FFFFFF';
            ctx.strokeStyle = '#14141A';
            ctx.lineWidth = 2;
            [0, 11, 12, 13, 14, 15, 16, 23, 24, 25, 26, 27, 28].forEach((k) => {
                const p = frame.pose[k];
                if (p.v < 0.3) return;
                ctx.beginPath(); ctx.arc(p.x * w, p.y * h, 4.5, 0, Math.PI * 2); ctx.fill(); ctx.stroke();
            });
        }
        return canvas.toDataURL('image/jpeg', 0.72);
    } catch (e) {
        return null;
    }
}

/* ---------------- Util ---------------- */

function once(target, evt, timeout) {
    return new Promise((resolve, reject) => {
        let timer = null;
        const done = (ok) => () => { clearTimeout(timer); target.removeEventListener(evt, onOk); target.removeEventListener('error', onErr); ok ? resolve() : reject(new Error(evt)); };
        const onOk = done(true), onErr = done(false);
        target.addEventListener(evt, onOk, { once: true });
        target.addEventListener('error', onErr, { once: true });
        if (timeout) timer = setTimeout(onErr, timeout);
        if (evt === 'loadedmetadata' && target.readyState >= 1) onOk();
    });
}

function seek(video, t) {
    return new Promise((resolve) => {
        if (Math.abs(video.currentTime - t) < 0.0005 && video.readyState >= 2) return resolve();
        const done = () => { video.removeEventListener('seeked', done); resolve(); };
        video.addEventListener('seeked', done);
        video.currentTime = t;
        setTimeout(done, 1500);
    });
}
