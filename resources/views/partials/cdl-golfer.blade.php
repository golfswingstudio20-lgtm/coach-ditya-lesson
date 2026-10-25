{{-- Animasi pegolf (swing, bola masuk lubang, senyum). Dipakai di halaman masuk/daftar. --}}
@once
<style>
.cdl-golfer{display:block;width:100%;max-width:330px;height:auto}
/* Ayunan golf dari depan, target (lubang) di kanan layar. Siklus 4 detik.
   Lengan: maks ~100 derajat (tangan setinggi bahu). Pergelangan menekuk ~70 derajat sehingga stik naik di atas bahu.
   Badan memutar dan berat pindah: backswing ke kaki kiri layar, finish ke kaki kanan layar, tumit belakang terangkat. */
@keyframes cdl-arms{
  0%{transform:rotate(0deg);animation-timing-function:cubic-bezier(.45,0,.55,1)}
  8%{transform:rotate(-3deg);animation-timing-function:cubic-bezier(.35,0,.35,1)}
  36%{transform:rotate(98deg);animation-timing-function:ease-in-out}
  42%{transform:rotate(102deg);animation-timing-function:cubic-bezier(.55,0,.85,.35)}
  50%{transform:rotate(0deg);animation-timing-function:cubic-bezier(.15,.6,.35,1)}
  62%{transform:rotate(-105deg);animation-timing-function:ease-out}
  85%{transform:rotate(-110deg);animation-timing-function:cubic-bezier(.45,0,.55,1)}
  100%{transform:rotate(0deg)}
}
@keyframes cdl-wrist{
  0%,8%{transform:rotate(0deg);animation-timing-function:cubic-bezier(.4,0,.4,1)}
  22%{transform:rotate(25deg);animation-timing-function:ease-in-out}
  36%,42%{transform:rotate(72deg);animation-timing-function:linear}
  47%{transform:rotate(45deg);animation-timing-function:cubic-bezier(.6,0,1,.6)}
  50%{transform:rotate(0deg);animation-timing-function:cubic-bezier(.1,.6,.3,1)}
  62%{transform:rotate(-55deg);animation-timing-function:ease-out}
  85%{transform:rotate(-62deg);animation-timing-function:cubic-bezier(.45,0,.55,1)}
  100%{transform:rotate(0deg)}
}
@keyframes cdl-upper{
  0%,8%{transform:translateX(0) rotate(0deg);animation-timing-function:cubic-bezier(.4,0,.3,1)}
  36%,42%{transform:translateX(-7px) rotate(5deg);animation-timing-function:cubic-bezier(.55,0,.85,.35)}
  50%{transform:translateX(3px) rotate(-2deg);animation-timing-function:cubic-bezier(.15,.6,.35,1)}
  62%,85%{transform:translateX(11px) rotate(-10deg);animation-timing-function:cubic-bezier(.45,0,.55,1)}
  100%{transform:translateX(0) rotate(0deg)}
}
@keyframes cdl-head{
  0%,52%{transform:rotate(0deg)}
  64%,85%{transform:rotate(-12deg)}
  100%{transform:rotate(0deg)}
}
/* Kaki: telapak tetap menapak, kaki condong (skew) persis sejauh pinggul bergeser, jadi selalu menempel */
@keyframes cdl-hips{
  0%{transform:translateX(0)}
  10%{transform:translateX(-0.5px)}
  38%{transform:translateX(-4px)}
  50%{transform:translateX(2px)}
  68%{transform:translateX(9.5px)}
  84%{transform:translateX(10px)}
  100%{transform:translateX(0)}
}
@keyframes cdl-legs{
  0%{transform:skewX(0deg) scaleY(1)}
  10%{transform:skewX(0.5deg) scaleY(.995)}
  38%{transform:skewX(3.8deg) scaleY(.985)}
  50%{transform:skewX(-1.9deg) scaleY(.975)}
  68%{transform:skewX(-9deg) scaleY(1)}
  84%{transform:skewX(-9.5deg) scaleY(1.005)}
  100%{transform:skewX(0deg) scaleY(1)}
}
@keyframes cdl-rear-shoe{0%,50%{transform:rotate(0deg)}72%{transform:rotate(-16deg)}84%{transform:rotate(-18deg)}100%{transform:rotate(0deg)}}
@keyframes cdl-face-on{0%,78%{opacity:1}81%,95%{opacity:0}98%,100%{opacity:1}}
@keyframes cdl-face-joy{0%,78%{opacity:0;transform:scale(.6)}82%{opacity:1;transform:scale(1.12)}85%,95%{opacity:1;transform:scale(1)}98%,100%{opacity:0;transform:scale(1)}}
@keyframes cdl-ball-x{0%,50%{transform:translateX(0);opacity:1}78%{transform:translateX(110px);opacity:1}82%,100%{transform:translateX(110px);opacity:0}}
@keyframes cdl-ball-y{0%,50%{transform:translateY(0);animation-timing-function:cubic-bezier(.2,.7,.4,1)}63%{transform:translateY(-122px);animation-timing-function:cubic-bezier(.6,0,.8,.3)}76%{transform:translateY(2px);animation-timing-function:ease-in}80%,100%{transform:translateY(9px) scale(.5)}}
@keyframes cdl-shadow{0%,50%{transform:scale(1);opacity:.35}63%{transform:scale(.4);opacity:.12}76%{transform:scale(1);opacity:.35}80%,100%{transform:scale(1);opacity:0}}
@keyframes cdl-win{0%,78%{transform:scale(0);opacity:0}84%{transform:scale(1.25) rotate(20deg);opacity:1}96%{transform:scale(1) rotate(40deg);opacity:1}100%{transform:scale(.6);opacity:0}}
@keyframes cdl-flag{0%,100%{transform:skewY(0deg) scaleX(1)}50%{transform:skewY(-7deg) scaleX(.94)}}
@keyframes cdl-pole{0%,77%,100%{transform:rotate(0deg)}81%{transform:rotate(-6deg)}85%{transform:rotate(4deg)}89%{transform:rotate(-2deg)}}
@keyframes cdl-spark{0%,100%{transform:scale(.75) rotate(0deg);opacity:.45}50%{transform:scale(1.15) rotate(20deg);opacity:1}}
@keyframes cdl-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.cdl-arms{transform-box:view-box;transform-origin:150px 138px;animation:cdl-arms 4s infinite}
.cdl-wrist{transform-box:view-box;transform-origin:150px 178px;animation:cdl-wrist 4s infinite}
.cdl-upper{transform-box:view-box;transform-origin:150px 200px;animation:cdl-upper 4s infinite}
.cdl-head{transform-box:view-box;transform-origin:150px 116px;animation:cdl-head 4s ease-in-out infinite}
.cdl-hips{transform-box:view-box;animation:cdl-hips 4s cubic-bezier(.45,0,.55,1) infinite}
.cdl-rear-leg,.cdl-lead-leg{transform-box:view-box;transform-origin:150px 250px;animation:cdl-legs 4s cubic-bezier(.45,0,.55,1) infinite}
.cdl-rear-shoe{transform-box:view-box;transform-origin:132px 256px;animation:cdl-rear-shoe 4s cubic-bezier(.45,0,.55,1) infinite}
.cdl-face-on{animation:cdl-face-on 4s linear infinite}
.cdl-face-joy{transform-box:view-box;transform-origin:150px 104px;animation:cdl-face-joy 4s ease-out infinite}
.cdl-ball-x{transform-box:view-box;animation:cdl-ball-x 4s linear infinite}
.cdl-ball-y{transform-box:fill-box;transform-origin:center;animation:cdl-ball-y 4s infinite}
.cdl-shadow{transform-box:fill-box;transform-origin:center;animation:cdl-shadow 4s ease-out infinite}
.cdl-win{transform-box:fill-box;transform-origin:center;animation:cdl-win 4s ease-out infinite}
.cdl-pole{transform-box:view-box;transform-origin:282px 254px;animation:cdl-pole 4s ease-in-out infinite}
.cdl-flag{transform-box:view-box;transform-origin:284px 134px;animation:cdl-flag 1.8s ease-in-out infinite}
.cdl-spark{transform-box:fill-box;transform-origin:center;animation:cdl-spark 1.8s ease-in-out infinite}
.cdl-float{animation:cdl-float 4s ease-in-out infinite}
@media (prefers-reduced-motion: reduce){.cdl-arms,.cdl-wrist,.cdl-upper,.cdl-head,.cdl-hips,.cdl-rear-leg,.cdl-rear-shoe,.cdl-lead-leg,.cdl-ball-x,.cdl-ball-y,.cdl-shadow,.cdl-win,.cdl-pole,.cdl-flag,.cdl-spark,.cdl-float{animation:none}}
</style>
@endonce
<svg class="cdl-golfer" width="330" height="300" viewBox="0 0 330 300" role="img" aria-label="Ilustrasi pegolf memukul bola hingga masuk ke lubang">
      <circle cx="165" cy="150" r="140" fill="#FFFFFF" fill-opacity="0.22"></circle>
      <g class="cdl-spark"><path d="M40 60l5 12 12 5-12 5-5 12-5-12-12-5 12-5z" fill="#FFFFFF" stroke="#14141A" stroke-width="2.5" stroke-linejoin="round"></path></g>
      <g class="cdl-spark" style="animation-delay: 0.4s"><path d="M80 40q4-6 8 0t8 0M200 30l6 6M206 30l-6 6M60 120q3 6-2 10M250 140l8-3" fill="none" stroke="#14141A" stroke-width="3" stroke-linecap="round"></path></g>
      <g class="cdl-spark" style="animation-delay: 0.9s"><path d="M236 46l3.5 8 8 3.5-8 3.5-3.5 8-3.5-8-8-3.5 8-3.5z" fill="#22A04B" stroke="#14141A" stroke-width="2.5" stroke-linejoin="round"></path></g>
      <g class="cdl-float"><path d="M232 98q8-10 18-4q10-8 20 2q10 0 8 10h-50q-6-6 4-8z" fill="#FFFFFF" stroke="#14141A" stroke-width="2.5" stroke-linejoin="round"></path></g>
      <ellipse cx="165" cy="258" rx="152" ry="22" fill="#3BB55A" stroke="#14141A" stroke-width="3"></ellipse>
      <ellipse cx="282" cy="254" rx="12" ry="4" fill="#14141A"></ellipse>
      <g class="cdl-pole">
        <line x1="282" y1="132" x2="282" y2="252" stroke="#14141A" stroke-width="4" stroke-linecap="round"></line>
        <g class="cdl-flag"><path d="M284 134l30 13-30 13z" fill="#E8453C" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path></g>
      </g>
      <g class="cdl-win"><path d="M282 214l5 11 11 5-11 5-5 11-5-11-11-5 11-5z" fill="#FFE08A" stroke="#14141A" stroke-width="2.5" stroke-linejoin="round"></path></g>
      <g class="cdl-win" style="animation-delay: .08s"><path d="M262 230l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#FFFFFF" stroke="#14141A" stroke-width="2" stroke-linejoin="round"></path></g>
      <g class="cdl-win" style="animation-delay: .14s"><path d="M302 228l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#22A04B" stroke="#14141A" stroke-width="2" stroke-linejoin="round"></path></g>
      <g class="cdl-rear-leg">
        <path d="M128 190L112 250h22l16-46z" fill="#4A4F5C" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path>
        </g>
      <g class="cdl-rear-shoe"><path d="M100 246h32v10h-36z" fill="#FFFFFF" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path></g>
      <g class="cdl-lead-leg">
        <path d="M172 190l16 60h-22l-16-46z" fill="#4A4F5C" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path>
        </g>
      <path d="M168 246h32l4 10h-36z" fill="#FFFFFF" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path>
      <g class="cdl-hips"><path d="M124 188h52l-3 18h-46z" fill="#4A4F5C" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path><path d="M126 192h48" stroke="#14141A" stroke-width="3"></path></g>
      <g class="cdl-upper">
        <path d="M120 134c0-10 10-16 30-16s30 6 30 16l-4 60h-52z" fill="#1D2B44" stroke="#14141A" stroke-width="3.5" stroke-linejoin="round"></path>
        <path d="M128 140q4 26 2 50" fill="none" stroke="#2E4266" stroke-width="3" stroke-linecap="round"></path>
        <path d="M141 119l9 13 9-13" fill="#14223A" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path>
        <path d="M150 132v14" stroke="#14141A" stroke-width="2.5"></path>
        <g class="cdl-head">
          <ellipse cx="126" cy="98" rx="5" ry="7" fill="#F2C49B" stroke="#14141A" stroke-width="3"></ellipse>
          <ellipse cx="174" cy="98" rx="5" ry="7" fill="#F2C49B" stroke="#14141A" stroke-width="3"></ellipse>
          <circle cx="150" cy="94" r="24" fill="#F2C49B" stroke="#14141A" stroke-width="3.5"></circle>
          <path d="M127 88q-2 10 3 16l3-14zM173 88q2 10-3 16l-3-14z" fill="#7A4A2A" stroke="#14141A" stroke-width="2.5" stroke-linejoin="round"></path>
          <path d="M137 95q5-3 10 0M153 95q5-3 10 0" fill="none" stroke="#5A3418" stroke-width="3" stroke-linecap="round"></path>
          <path d="M150 101q2 4-1 6" fill="none" stroke="#14141A" stroke-width="2" stroke-linecap="round"></path>
          <g class="cdl-face-on">
            <ellipse cx="142" cy="101" rx="2.6" ry="3" fill="#14141A"></ellipse>
            <ellipse cx="158" cy="101" rx="2.6" ry="3" fill="#14141A"></ellipse>
            <path d="M144 109q6 5 12 0" fill="none" stroke="#14141A" stroke-width="2.5" stroke-linecap="round"></path>
          </g>
          <g class="cdl-face-joy">
            <path d="M137 102q5-6 10 0M153 102q5-6 10 0" fill="none" stroke="#14141A" stroke-width="2.8" stroke-linecap="round"></path>
            <path d="M140 106q10 14 20 0z" fill="#7A1F1F" stroke="#14141A" stroke-width="2.5" stroke-linejoin="round"></path>
            <path d="M142 107q8 4 16 0l-1 2.5q-7 3-14 0z" fill="#FFFFFF"></path>
          </g>
          <ellipse cx="136" cy="106" rx="4" ry="2.5" fill="#F28B7D" fill-opacity="0.6"></ellipse>
          <ellipse cx="164" cy="106" rx="4" ry="2.5" fill="#F28B7D" fill-opacity="0.6"></ellipse>
          <path d="M126 88c0-16 11-26 24-26s24 10 24 26z" fill="#1A2740" stroke="#14141A" stroke-width="3.5" stroke-linejoin="round"></path>
          <path d="M138 66q-6 8-6 20M162 66q6 8 6 20" fill="none" stroke="#2E4266" stroke-width="2.5" stroke-linecap="round"></path>
          <circle cx="150" cy="63" r="3" fill="#1A2740" stroke="#14141A" stroke-width="2"></circle>
          <path d="M145 80l4-7 4 7 4-4" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M122 88q28 9 56 0q4 6-2 9q-26 8-52 0q-6-3-2-9z" fill="#111B2E" stroke="#14141A" stroke-width="3.5" stroke-linejoin="round"></path>
        </g>
        <g class="cdl-arms">
          <g class="cdl-wrist">
            <line x1="150" y1="184" x2="152" y2="244" stroke="#14141A" stroke-width="4" stroke-linecap="round"></line>
            <path d="M146 240h18q4 0 3 5l-1 5h-21z" fill="#9AA3AF" stroke="#14141A" stroke-width="3" stroke-linejoin="round"></path>
          </g>
          <path d="M136 134q2 26 13 42" fill="none" stroke="#14141A" stroke-width="14" stroke-linecap="round"></path>
          <path d="M136 134q2 26 13 42" fill="none" stroke="#F2C49B" stroke-width="8" stroke-linecap="round"></path>
          <path d="M164 134q-2 26-13 42" fill="none" stroke="#14141A" stroke-width="14" stroke-linecap="round"></path>
          <path d="M164 134q-2 26-13 42" fill="none" stroke="#F2C49B" stroke-width="8" stroke-linecap="round"></path>
          <path d="M136 132q0 8 1 14M164 132q0 8-1 14" fill="none" stroke="#14141A" stroke-width="18" stroke-linecap="round"></path>
          <path d="M136 132q0 8 1 14M164 132q0 8-1 14" fill="none" stroke="#1D2B44" stroke-width="12" stroke-linecap="round"></path>
          <circle cx="150" cy="178" r="9" fill="#22232A" stroke="#14141A" stroke-width="3"></circle>
          <path d="M143 176h14" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"></path>
        </g>
      </g>
      <g class="cdl-ball-x">
        <ellipse class="cdl-shadow" cx="172" cy="257" rx="7" ry="2" fill="#14141A"></ellipse>
        <g class="cdl-ball-y"><circle cx="172" cy="251" r="6" fill="#FFFFFF" stroke="#14141A" stroke-width="2.5"></circle></g>
      </g>
    </svg>
