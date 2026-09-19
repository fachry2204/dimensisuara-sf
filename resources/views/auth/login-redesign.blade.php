<style>
.sf-login{--accent:#ff793c;min-height:100vh;display:grid;grid-template-columns:59% 41%;color:#f8f9fc;background:#0d121b;font-family:'Noto Sans',Arial,sans-serif;letter-spacing:0}
.sf-login *{box-sizing:border-box}.sf-login a{color:var(--accent);text-decoration:none}.sf-login a:hover{text-decoration:underline}
.sf-story{position:relative;min-height:100vh;display:flex;align-items:center;background:#21142b url('{{ asset('build/images/login-studio.png') }}') center/cover no-repeat;isolation:isolate}
.sf-story::before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(9,7,19,.45),rgba(9,7,19,.06) 75%)}
.sf-story__content{width:min(760px,100%);padding:72px clamp(36px,6vw,98px);transform:translateY(45px)}
.sf-story__brand{margin:0 0 24px;color:#fff;font-size:16px;font-weight:750;letter-spacing:.21em}
.sf-story h1{max-width:680px;margin:0;color:#fff;font-size:clamp(43px,3.45vw,66px);line-height:1.08;font-weight:800;letter-spacing:-.045em}
.sf-story__intro{max-width:570px;margin:22px 0 42px;color:#f0eaf4;font-size:clamp(17px,1.36vw,23px);line-height:1.5}
.sf-benefits{display:grid;gap:24px;padding:0;margin:0;list-style:none}.sf-benefits li{display:flex;align-items:center;gap:20px;color:#fff;font-size:clamp(16px,1.15vw,20px);font-weight:650}
.sf-benefits__icon{flex:0 0 54px;width:54px;height:54px;display:grid;place-items:center;color:#ff8a48;border:2px solid var(--accent);border-radius:14px;background:rgba(17,12,27,.56)}
.sf-benefits__icon svg{width:27px;height:27px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.sf-story__footer{margin:76px 0 0;color:#e7dfea;font-size:11px;line-height:1.7;letter-spacing:.28em}.sf-story__footer::before{content:'';display:block;width:50px;height:4px;margin-bottom:16px;border-radius:4px;background:var(--accent)}
.sf-form-side{display:flex;min-height:100vh;align-items:center;justify-content:center;background:#0d121b;padding:64px clamp(32px,5.4vw,100px)}.sf-form-panel{width:min(505px,100%);transform:translateY(-24px)}
.sf-logo{display:inline-block;margin-bottom:64px}.sf-logo img{display:block;width:242px;max-width:100%;height:auto}
.sf-form-panel h2{margin:0 0 12px;color:#fff;font-size:clamp(30px,2.4vw,43px);line-height:1.16;font-weight:800;letter-spacing:-.035em}
.sf-form-panel__intro{margin:0 0 44px;color:#bfc4cd;font-size:18px;line-height:1.5}
.sf-field{margin-bottom:24px}.sf-field label{display:block;margin-bottom:10px;color:#f6f6f9;font-size:17px;font-weight:600}.sf-input-wrap{position:relative}
.sf-input{display:block;width:100%;height:58px;padding:0 18px;border:1px solid #536071;border-radius:10px;outline:0;color:#fff;background:#1b2430;font:inherit;font-size:17px;transition:border-color .18s,box-shadow .18s}
.sf-input::placeholder{color:#9ba4b2!important;opacity:1!important}.sf-input:focus{border-color:#fca83a;box-shadow:0 0 0 3px rgba(255,155,46,.24)}.sf-input.is-invalid{border-color:#ff6b74}.sf-password-input{padding-right:62px}
.sf-password-toggle{position:absolute;right:0;top:0;display:grid;place-items:center;width:58px;height:58px;padding:0;border:0;color:#c8ced8;background:transparent;cursor:pointer}.sf-password-toggle:hover{color:#fff}
.sf-password-toggle svg{width:23px;height:23px;stroke:currentColor;stroke-width:1.8;fill:none;stroke-linecap:round;stroke-linejoin:round}.sf-error{display:block;margin-top:8px;color:#ff9da3;font-size:14px}
.sf-options{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:26px 0 28px;font-size:16px}.sf-remember{display:inline-flex;align-items:center;gap:12px;cursor:pointer}.sf-remember input{width:23px;height:23px;margin:0;accent-color:var(--accent);cursor:pointer}
.sf-submit{display:block;width:100%;min-height:62px;border:0;border-radius:10px;color:#fff;background:linear-gradient(100deg,#ff4738,#ff913a);font:inherit;font-size:18px;font-weight:750;cursor:pointer;transition:filter .18s,transform .18s}.sf-submit:hover{filter:brightness(1.08)}.sf-submit:active{transform:translateY(1px)}
.sf-submit:focus-visible,.sf-login a:focus-visible,.sf-password-toggle:focus-visible{outline:3px solid #ffb264;outline-offset:3px}
.sf-login-register{display:flex;align-items:center;gap:20px;margin:44px 0 0;color:#d8dbe2;font-size:16px;white-space:nowrap}.sf-login-register::before,.sf-login-register::after{content:'';flex:1;height:1px;background:#46505d}
@media(max-width:1199px){.sf-login{grid-template-columns:52% 48%}.sf-story__content{padding:56px 42px}.sf-form-side{padding:48px 42px}.sf-story h1{font-size:clamp(39px,4vw,52px)}}
@media(max-width:760px){.sf-login{display:flex;flex-direction:column}.sf-story{min-height:auto}.sf-story__content{padding:38px 28px 36px;transform:none}.sf-story__brand{margin-bottom:14px;font-size:12px}.sf-story h1{max-width:520px;font-size:clamp(31px,8vw,44px)}.sf-story__intro{margin:14px 0 0;font-size:16px}.sf-benefits,.sf-story__footer{display:none}.sf-form-side{min-height:auto;padding:38px 28px 58px}.sf-form-panel{transform:none}.sf-logo{margin-bottom:36px}.sf-logo img{width:190px}.sf-form-panel h2{font-size:30px}.sf-form-panel__intro{margin-bottom:32px;font-size:16px}.sf-options,.sf-login-register{font-size:14px}}
@media(max-width:380px){.sf-options{align-items:flex-start;flex-direction:column}.sf-login-register{gap:9px;font-size:12px}}
@media(max-width:760px){.sf-story__content{padding:19px 28px}.sf-story__brand,.sf-story__intro{display:none}.sf-story h1{font-size:29px;line-height:1.1}.sf-form-side{padding:28px 28px 32px}.sf-logo{margin-bottom:24px}.sf-form-panel__intro{margin-bottom:24px}.sf-login-register{margin-top:28px}}
</style>
<main class="sf-login">
 <section class="sf-story" aria-label="Tentang SoundFresh">
  <div class="sf-story__content">
   <p class="sf-story__brand">SOUNDFRESH</p>
   <h1>Musikmu,<br>siap didengar dunia.</h1>
   <p class="sf-story__intro">Kelola rilis, pantau progres, dan jangkau pendengar dari satu tempat.</p>
   <ul class="sf-benefits">
    <li><span class="sf-benefits__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/></svg></span>Kelola rilismu</li>
    <li><span class="sf-benefits__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V13m5 7V8m5 12V4m5 16V10"/></svg></span>Pantau status distribusi</li>
    <li><span class="sf-benefits__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20v-2a7 7 0 0 1 14 0v2M4 8a2 2 0 0 0 0 4m16-4a2 2 0 0 1 0 4"/></svg></span>Terhubung dengan audiens</li>
   </ul>
   <p class="sf-story__footer">MUSIK LEBIH JAUH<br>PENJALANAN LEBIH BERARTI</p>
  </div>
 </section>
 <section class="sf-form-side" aria-labelledby="login-heading">
  <div data-vue-login="{{ json_encode([
      'action' => route('login'), 'csrf' => csrf_token(), 'oldEmail' => old('email', ''),
      'oldRemember' => (bool) old('remember'), 'emailError' => $errors->first('email'),
      'passwordError' => $errors->first('password'),
      'forgotUrl' => Route::has('password.request') ? route('password.request') : null,
      'registerUrl' => Route::has('register') ? route('register') : null,
      'homeUrl' => url('/'), 'logoUrl' => asset('build/images/logo-soundfresh-full-white.webp'),
  ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"></div>
 </section>
</main>
