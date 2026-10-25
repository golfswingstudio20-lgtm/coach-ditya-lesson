@push('head')
<style>
    .sw [hidden] { display: none !important; }
    .sw { display: grid; gap: 14px; }
    .fw-ic-inline { width: 14px; height: 14px; vertical-align: -2px; }
    .sw-head { display: flex; align-items: center; gap: 10px; }
    .sw-back { width: 44px; height: 44px; flex: 0 0 44px; display: grid; place-items: center; border-radius: 14px; border: 2.5px solid #14141A; background: #DDF3E4; color: #14141A; box-shadow: 2px 2px 0 #14141A; }
    .sw-back svg { width: 20px; height: 20px; }
    .sw-head h1 { flex: 1; font-size: 26px; line-height: 1; }
    .sw-card { padding: 16px; display: grid; gap: 10px; }
    .sw-card h2 { font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 20px; letter-spacing: .01em; }
    .sw-card p { font-size: 14.5px; line-height: 1.45; color: #14141A; font-weight: 500; }
    .sw-hero { display: flex; gap: 12px; align-items: center; }
    .sw-hero .ic { width: 64px; height: 64px; flex: 0 0 64px; display: grid; place-items: center; border-radius: 18px; border: 2.5px solid #14141A; background: #22A04B; color: #fff; }
    .sw-hero .ic svg { width: 32px; height: 32px; }
    .sw-list { display: grid; gap: 8px; }
    .sw-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 2px solid rgba(20, 20, 26, .2); border-radius: 16px; background: #EAF8EF; color: #14141A; }
    .sw-item .num { width: 30px; height: 30px; flex: 0 0 30px; display: grid; place-items: center; border-radius: 50%; border: 2px solid #14141A; background: #fff; font-weight: 700; font-size: 14px; }
    .sw-item .num svg { width: 16px; height: 16px; }
    .sw-item strong { display: block; font-size: 15px; }
    .sw-item small { display: block; color: #14141A; font-size: 13px; line-height: 1.35; }
    .sw-score { width: 54px; height: 54px; flex: 0 0 54px; display: grid; place-items: center; border-radius: 50%; border: 4px solid #22A04B; background: #fff; font-family: "Lilita One", system-ui, sans-serif; font-size: 19px; }
    .sw-score.mid { border-color: #E9A23B; }
    .sw-score.low { border-color: #E8453C; }
    .sw-tag { display: inline-block; padding: 3px 9px; border-radius: 99px; border: 2px solid #14141A; font-size: 12px; font-weight: 700; background: #BFEBCB; }
    .sw-tag.mid { background: #FFE2A8; }
    .sw-tag.low { background: #FFC7C2; }
    .sw-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .sw-stat { padding: 12px 14px; text-align: center; }
    .sw-stat b { display: block; font-family: "Lilita One", system-ui, sans-serif; font-weight: 400; font-size: 30px; }
    .sw-stat span { font-size: 13px; font-weight: 600; color: #14141A; }
    .sw-empty { text-align: center; color: #14141A; font-size: 14px; padding: 6px 0; }
    .sw-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    @media (min-width: 821px) { .fw-main.narrow { width: min(560px, 100% - 48px); } }
</style>
@endpush
