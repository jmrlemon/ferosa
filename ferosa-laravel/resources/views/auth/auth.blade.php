<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Ferosa Landscaping – Garden & Landscaping</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --green: #22c55e;
  --green-dark: #16a34a;
}

html, body {
  height: 100%;
  font-family: 'Inter', sans-serif;
  overflow-x: hidden;
  /* Allow vertical scroll so keyboard doesn't cover inputs */
  overflow-y: auto;
  -webkit-text-size-adjust: 100%;
  text-size-adjust: 100%;
  /* Ensures focused input scrolls above keyboard with breathing room */
  scroll-padding-bottom: 120px;
}

/* ── Fullscreen background ── */
.scene-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  transform: scale(1.06);
  transition: transform 0.1s linear;
  will-change: transform;
  overflow: hidden;
}

.scene-overlay {
  position: fixed;
  inset: 0;
  background: linear-gradient(
    155deg,
    rgba(255,248,240,0.15) 0%,
    rgba(240,255,244,0.10) 40%,
    rgba(10,30,10,0.28) 100%
  );
  z-index: 1;
}

/* ── Brand bottom-left ── */
.brand {
  position: fixed;
  bottom: 38px;
  left: 42px;
  z-index: 20;
  animation: fadeUp 0.9s ease 0.3s both;
}
.brand-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 11px;
}
.brand-icon {
  width: 36px; height: 36px;
  background: var(--green);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 16px rgba(34,197,94,0.5);
  flex-shrink: 0;
}
.brand-name {
  font-family: 'Playfair Display', serif;
  font-size: 23px; font-weight: 700;
  color: white;
  text-shadow: 0 1px 10px rgba(0,0,0,0.35);
  letter-spacing: 0.5px;
}
.brand-slogan {
  font-size: 14.5px; font-weight: 600;
  color: rgba(255,255,255,0.95);
  text-shadow: 0 1px 10px rgba(0,0,0,0.4);
  max-width: 310px; line-height: 1.45;
  margin-bottom: 7px;
}
.brand-sub {
  font-size: 12px;
  color: rgba(255,255,255,0.65);
  text-shadow: 0 1px 8px rgba(0,0,0,0.4);
  max-width: 290px; line-height: 1.6;
}

/* ── Help badge ── */
.help-badge {
  position: fixed;
  bottom: 28px; right: 28px;
  z-index: 20;
  width: 34px; height: 34px;
  border-radius: 50%;
  background: rgba(17,24,39,0.65);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.18);
  color: white;
  font-size: 15px; font-weight: 600;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}

/* ── Scene wrapper ── */
.scene {
  position: relative;
  z-index: 5;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
}

/* ── Mobile overrides ── */
@media (max-width: 600px) {
  .brand { display: none; }
  .help-badge { bottom: 18px; right: 18px; }
  .scene {
    align-items: flex-start;
    padding: 24px 16px 100px;
    min-height: 100svh;
  }
  .form-card {
    padding: 32px 24px 28px;
    border-radius: 20px;
    max-width: 100%;
  }
  .form-title { font-size: 28px; }
}

/* ── Glass form card ── */
.form-card {
  width: 100%;
  max-width: 452px;
  background: rgba(255,255,255,0.74);
  backdrop-filter: blur(24px) saturate(200%);
  -webkit-backdrop-filter: blur(24px) saturate(200%);
  border: 1px solid rgba(255,255,255,0.6);
  border-radius: 28px;
  padding: 44px 40px 38px;
  box-shadow:
    0 10px 50px rgba(0,0,0,0.16),
    0 2px 14px rgba(0,0,0,0.07),
    inset 0 1px 0 rgba(255,255,255,0.85);
}

/* ── Page switch animations ── */
@keyframes fadeUp {
  from { opacity:0; transform:translateY(22px); }
  to   { opacity:1; transform:translateY(0); }
}
@keyframes fadeDown {
  from { opacity:1; transform:translateY(0); }
  to   { opacity:0; transform:translateY(-14px); }
}

.page { display:none; }
.page.active { display:flex; align-items:center; justify-content:center; }
.page.active .form-card { animation: fadeUp 0.55s cubic-bezier(0.22,1,0.36,1) both; }

/* ── Form header ── */
.form-header { text-align:center; margin-bottom:26px; }
.form-title {
  font-family: 'Playfair Display', serif;
  font-size: 36px; font-weight: 700;
  color: #111827; line-height: 1.15;
  margin-bottom: 7px;
}
.form-subtitle { font-size: 14px; color: #4b5563; }

/* ── Fields ── */
.field { margin-bottom:13px; }
.field-label {
  display:block; font-size:12.5px; font-weight:600;
  color:#374151; margin-bottom:6px; letter-spacing:0.2px;
}
.field-label-row {
  display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;
}
.forgot-link {
  font-size:12.5px; color:var(--green-dark); font-weight:600;
  cursor:pointer; text-decoration:none;
}
.forgot-link:hover { text-decoration:underline; }

.input-wrap { position:relative; display:flex; align-items:center; }
.input-icon {
  position:absolute; left:13px; color:#9ca3af;
  display:flex; align-items:center; pointer-events:none;
}
.input-eye {
  position:absolute; right:13px; color:#9ca3af;
  cursor:pointer; display:flex; align-items:center;
}
.field input {
  width:100%; padding:12px 13px 12px 38px;
  border:1.5px solid rgba(0,0,0,0.1);
  border-radius:12px;
  background:rgba(255,255,255,0.88);
  font-family:'Inter',sans-serif;
  /* 16px minimum: prevents Android IME batch-composition (backward typing) bug */
  font-size:16px; color:#111827; outline:none;
  direction: ltr;
  unicode-bidi: plaintext;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.field input::placeholder { color:#9ca3af; }
.field input:focus {
  border-color:var(--green);
  background:white;
  box-shadow:0 0 0 3px rgba(34,197,94,0.14);
}
.has-eye input { padding-right:38px; }

/* ── Account type ── */
.acct-label {
  display:block; font-size:12.5px; font-weight:600;
  color:#374151; margin-bottom:8px;
}
.acct-row { display:flex; gap:10px; margin-bottom:20px; }
.acct-btn {
  flex:1; padding:13px 10px;
  border:1.5px solid rgba(0,0,0,0.1);
  border-radius:13px;
  background:rgba(255,255,255,0.88);
  text-align:left; cursor:pointer;
  transition:all 0.2s; font-family:'Inter',sans-serif;
}
.acct-btn .aname { font-size:14px; font-weight:600; color:#111827; display:block; margin-bottom:2px; }
.acct-btn .adesc { font-size:11.5px; color:#6b7280; }
.acct-btn.selected { border-color:var(--green); background:rgba(220,252,231,0.72); }
.acct-btn.selected .aname { color:var(--green-dark); }

/* ── CTA ── */
.cta-btn {
  width:100%; padding:15px;
  background:var(--green); color:white; border:none;
  border-radius:14px; font-family:'Inter',sans-serif;
  font-size:15px; font-weight:700;
  cursor:pointer; letter-spacing:0.2px;
  display:flex; align-items:center; justify-content:center; gap:8px;
  box-shadow:0 4px 20px rgba(34,197,94,0.45);
  transition:all 0.2s; margin-bottom:17px;
}
.cta-btn:hover {
  background:var(--green-dark); transform:translateY(-1px);
  box-shadow:0 6px 24px rgba(34,197,94,0.52);
}

/* ── Bottom link ── */
.bottom-link { text-align:center; font-size:13.5px; color:#4b5563; }
.bottom-link a {
  color:var(--green-dark); font-weight:700; cursor:pointer; text-decoration:none;
}
.bottom-link a:hover { text-decoration:underline; }

/* ── Toast notifications ── */
#toast-container {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}
.toast {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-radius: 14px;
  font-family: 'Figtree', sans-serif;
  font-size: 13.5px;
  font-weight: 500;
  color: white;
  backdrop-filter: blur(16px);
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);
  pointer-events: auto;
  min-width: 280px;
  max-width: 360px;
  animation: toastIn 0.4s cubic-bezier(0.22,1,0.36,1) both;
}
.toast.success { background: rgba(22,163,74,0.92); border: 1px solid rgba(255,255,255,0.2); }
.toast.error   { background: rgba(220,38,38,0.92);  border: 1px solid rgba(255,255,255,0.15); }
.toast.info    { background: rgba(37,99,235,0.92);  border: 1px solid rgba(255,255,255,0.15); }
.toast.fadeout { animation: toastOut 0.35s ease forwards; }
@keyframes toastIn  { from { opacity:0; transform:translateX(30px); } to { opacity:1; transform:translateX(0); } }
@keyframes toastOut { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(30px); } }

/* ── Loading spinner on button ── */
.cta-btn.loading { opacity: 0.75; pointer-events: none; cursor: not-allowed; }
.spinner {
  width: 16px; height: 16px;
  border: 2.5px solid rgba(255,255,255,0.35);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Session banner ── */
#session-banner {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  background: rgba(22,163,74,0.95);
  backdrop-filter: blur(12px);
  color: white; text-align: center;
  padding: 14px 20px;
  font-family: 'Figtree', sans-serif;
  font-size: 14px; font-weight: 500;
  display: none;
  gap: 12px; align-items: center; justify-content: center;
  flex-wrap: wrap;
}
#session-banner.show { display: flex; }
#session-banner a {
  color: #bbf7d0; font-weight: 700; cursor: pointer;
  text-decoration: underline;
}
</style>

  <script>
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });
  </script>
</head>
<body>


<!-- ── Toast notification container ── -->
<div id="toast-container"></div>

<!-- ── Already-logged-in session banner ── -->
<div id="session-banner">
  <span id="session-msg">You're already signed in.</span>
  <a onclick="handleSignOut()">Sign out</a>
  <a href="ferosa-home.html">Go to Dashboard →</a>
</div>

<div class="scene-bg" id="sceneBg">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" style="width:100%;height:100%;display:block;">
  <defs>
    <radialGradient id="sky" cx="50%" cy="40%" r="70%">
      <stop offset="0%" stop-color="#c8e6c9"/>
      <stop offset="40%" stop-color="#81c784"/>
      <stop offset="100%" stop-color="#1b5e20"/>
    </radialGradient>
    <radialGradient id="bgGlow" cx="55%" cy="45%" r="50%">
      <stop offset="0%" stop-color="#f8bbd0" stop-opacity="0.6"/>
      <stop offset="100%" stop-color="#f8bbd0" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="petalGrad1" cx="40%" cy="30%" r="70%">
      <stop offset="0%" stop-color="#fce4ec"/>
      <stop offset="50%" stop-color="#f48fb1"/>
      <stop offset="100%" stop-color="#c2185b"/>
    </radialGradient>
    <radialGradient id="petalGrad2" cx="40%" cy="30%" r="70%">
      <stop offset="0%" stop-color="#fce4ec"/>
      <stop offset="50%" stop-color="#f06292"/>
      <stop offset="100%" stop-color="#ad1457"/>
    </radialGradient>
    <radialGradient id="petalGrad3" cx="50%" cy="20%" r="80%">
      <stop offset="0%" stop-color="#fce4ec"/>
      <stop offset="60%" stop-color="#ec407a"/>
      <stop offset="100%" stop-color="#880e4f"/>
    </radialGradient>
    <radialGradient id="centerGrad" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#fff9c4"/>
      <stop offset="60%" stop-color="#f9a825"/>
      <stop offset="100%" stop-color="#e65100"/>
    </radialGradient>
    <filter id="blur1"><feGaussianBlur stdDeviation="18"/></filter>
    <filter id="blur2"><feGaussianBlur stdDeviation="8"/></filter>
    <filter id="softShadow"><feDropShadow dx="0" dy="4" stdDeviation="12" flood-color="#880e4f" flood-opacity="0.3"/></filter>
  </defs>

  <!-- Deep green background -->
  <rect width="1440" height="900" fill="url(#sky)"/>

  <!-- Background bokeh blobs -->
  <circle cx="120" cy="80" r="90" fill="#4caf50" opacity="0.25" filter="url(#blur1)"/>
  <circle cx="1350" cy="120" r="110" fill="#2e7d32" opacity="0.3" filter="url(#blur1)"/>
  <circle cx="200" cy="800" r="130" fill="#388e3c" opacity="0.28" filter="url(#blur1)"/>
  <circle cx="1300" cy="780" r="100" fill="#1b5e20" opacity="0.35" filter="url(#blur1)"/>
  <circle cx="700" cy="50" r="80" fill="#66bb6a" opacity="0.2" filter="url(#blur1)"/>
  <circle cx="900" cy="860" r="120" fill="#2e7d32" opacity="0.3" filter="url(#blur1)"/>

  <!-- Far background leaves (large, blurred) -->
  <ellipse cx="100" cy="300" rx="180" ry="60" fill="#2e7d32" opacity="0.5" transform="rotate(-35 100 300)" filter="url(#blur2)"/>
  <ellipse cx="1380" cy="250" rx="200" ry="65" fill="#1b5e20" opacity="0.55" transform="rotate(40 1380 250)" filter="url(#blur2)"/>
  <ellipse cx="50" cy="650" rx="160" ry="55" fill="#388e3c" opacity="0.45" transform="rotate(-50 50 650)" filter="url(#blur2)"/>
  <ellipse cx="1420" cy="700" rx="170" ry="60" fill="#2e7d32" opacity="0.5" transform="rotate(45 1420 700)" filter="url(#blur2)"/>
  <ellipse cx="600" cy="880" rx="200" ry="70" fill="#1b5e20" opacity="0.4" transform="rotate(-10 600 880)" filter="url(#blur1)"/>
  <ellipse cx="880" cy="30" rx="180" ry="58" fill="#388e3c" opacity="0.35" transform="rotate(15 880 30)" filter="url(#blur2)"/>

  <!-- Mid background stems & leaves -->
  <line x1="720" y1="900" x2="720" y2="380" stroke="#2e7d32" stroke-width="14" stroke-linecap="round" opacity="0.7"/>
  <line x1="720" y1="700" x2="550" y2="550" stroke="#388e3c" stroke-width="10" stroke-linecap="round" opacity="0.6"/>
  <line x1="720" y1="620" x2="900" y2="500" stroke="#388e3c" stroke-width="10" stroke-linecap="round" opacity="0.6"/>
  <line x1="720" y1="780" x2="400" y2="700" stroke="#2e7d32" stroke-width="8" stroke-linecap="round" opacity="0.5"/>
  <line x1="720" y1="750" x2="1020" y2="660" stroke="#2e7d32" stroke-width="8" stroke-linecap="round" opacity="0.5"/>

  <!-- Leaves mid -->
  <ellipse cx="490" cy="510" rx="110" ry="38" fill="#33691e" transform="rotate(-38 490 510)"/>
  <ellipse cx="940" cy="470" rx="105" ry="36" fill="#33691e" transform="rotate(32 940 470)"/>
  <ellipse cx="340" cy="675" rx="90" ry="30" fill="#388e3c" transform="rotate(-52 340 675)"/>
  <ellipse cx="1060" cy="635" rx="95" ry="32" fill="#388e3c" transform="rotate(48 1060 635)"/>
  <!-- Leaf veins -->
  <line x1="430" y1="488" x2="555" y2="535" stroke="#4caf50" stroke-width="2" opacity="0.5"/>
  <line x1="885" y1="445" x2="998" y2="498" stroke="#4caf50" stroke-width="2" opacity="0.5"/>

  <!-- Small buds on stems -->
  <ellipse cx="560" cy="430" rx="14" ry="26" fill="#f48fb1" transform="rotate(-25 560 430)"/>
  <ellipse cx="890" cy="400" rx="14" ry="26" fill="#ec407a" transform="rotate(20 890 400)"/>
  <ellipse cx="460" cy="360" rx="11" ry="20" fill="#f48fb1" transform="rotate(-15 460 360)"/>
  <ellipse cx="990" cy="350" rx="11" ry="20" fill="#f06292" transform="rotate(18 990 350)"/>
  <ellipse cx="650" cy="310" rx="10" ry="18" fill="#fce4ec" transform="rotate(-8 650 310)"/>
  <ellipse cx="800" cy="300" rx="10" ry="18" fill="#fce4ec" transform="rotate(10 800 300)"/>

  <!-- Pink glow behind main flower -->
  <circle cx="720" cy="440" r="260" fill="url(#bgGlow)" filter="url(#blur1)"/>

  <!-- MAIN FLOWER — centered large bloom -->
  <g transform="translate(720,440)" filter="url(#softShadow)">
    <!-- 5 petals radiating out -->
    <!-- Petal 1: top -->
    <ellipse rx="72" ry="155" fill="url(#petalGrad1)" transform="rotate(0) translate(0,-90)" opacity="0.97"/>
    <!-- Petal 2: upper right -->
    <ellipse rx="72" ry="155" fill="url(#petalGrad2)" transform="rotate(72) translate(0,-90)" opacity="0.95"/>
    <!-- Petal 3: lower right -->
    <ellipse rx="72" ry="155" fill="url(#petalGrad3)" transform="rotate(144) translate(0,-90)" opacity="0.95"/>
    <!-- Petal 4: lower left -->
    <ellipse rx="72" ry="155" fill="url(#petalGrad2)" transform="rotate(216) translate(0,-90)" opacity="0.95"/>
    <!-- Petal 5: upper left -->
    <ellipse rx="72" ry="155" fill="url(#petalGrad1)" transform="rotate(288) translate(0,-90)" opacity="0.97"/>

    <!-- Petal highlight streaks -->
    <line x1="0" y1="-180" x2="0" y2="-30" stroke="rgba(255,255,255,0.35)" stroke-width="3" stroke-linecap="round" transform="rotate(0)"/>
    <line x1="0" y1="-180" x2="0" y2="-30" stroke="rgba(255,255,255,0.25)" stroke-width="2" stroke-linecap="round" transform="rotate(72)"/>
    <line x1="0" y1="-180" x2="0" y2="-30" stroke="rgba(255,255,255,0.3)" stroke-width="2.5" stroke-linecap="round" transform="rotate(144)"/>
    <line x1="0" y1="-180" x2="0" y2="-30" stroke="rgba(255,255,255,0.25)" stroke-width="2" stroke-linecap="round" transform="rotate(216)"/>
    <line x1="0" y1="-180" x2="0" y2="-30" stroke="rgba(255,255,255,0.3)" stroke-width="2.5" stroke-linecap="round" transform="rotate(288)"/>

    <!-- Flower center -->
    <circle r="38" fill="#fff176"/>
    <circle r="28" fill="url(#centerGrad)"/>
    <circle r="18" fill="#ffcc02"/>
    <!-- Stamens -->
    <circle cx="0" cy="-22" r="4.5" fill="#fff"/>
    <circle cx="20" cy="-10" r="4.5" fill="#fff"/>
    <circle cx="13" cy="17" r="4.5" fill="#fff"/>
    <circle cx="-13" cy="17" r="4.5" fill="#fff"/>
    <circle cx="-20" cy="-10" r="4.5" fill="#fff"/>
    <!-- Stamen dots -->
    <circle cx="0" cy="-22" r="2.5" fill="#f57f17"/>
    <circle cx="20" cy="-10" r="2.5" fill="#f57f17"/>
    <circle cx="13" cy="17" r="2.5" fill="#f57f17"/>
    <circle cx="-13" cy="17" r="2.5" fill="#f57f17"/>
    <circle cx="-20" cy="-10" r="2.5" fill="#f57f17"/>
  </g>

  <!-- Foreground leaf overlaps (adds depth) -->
  <ellipse cx="150" cy="500" rx="200" ry="70" fill="#1b5e20" opacity="0.7" transform="rotate(-30 150 500)"/>
  <ellipse cx="1310" cy="480" rx="190" ry="68" fill="#1b5e20" opacity="0.65" transform="rotate(28 1310 480)"/>
  <ellipse cx="0" cy="750" rx="220" ry="75" fill="#2e7d32" opacity="0.6" transform="rotate(-45 0 750)"/>
  <ellipse cx="1450" cy="750" rx="210" ry="72" fill="#2e7d32" opacity="0.6" transform="rotate(42 1450 750)"/>

  <!-- Bokeh light spots (foreground) -->
  <circle cx="250" cy="180" r="22" fill="rgba(255,255,255,0.12)" filter="url(#blur2)"/>
  <circle cx="1180" cy="210" r="28" fill="rgba(255,255,255,0.1)" filter="url(#blur2)"/>
  <circle cx="400" cy="820" r="18" fill="rgba(255,255,255,0.1)" filter="url(#blur2)"/>
  <circle cx="1100" cy="800" r="24" fill="rgba(255,255,255,0.1)" filter="url(#blur2)"/>
  <circle cx="80" cy="400" r="16" fill="rgba(255,200,150,0.15)" filter="url(#blur2)"/>
  <circle cx="1380" cy="380" r="20" fill="rgba(255,200,150,0.12)" filter="url(#blur2)"/>
</svg>
</div>
<div class="scene-overlay"></div>

<div class="brand">
  <div class="brand-logo">
    <div class="brand-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
        <path d="M12 3C12 3 7 6.5 7 12c0 2.76 1.34 5.22 3.4 6.74.38-.48.93-.74 1.6-.74s1.22.26 1.6.74C15.66 17.22 17 14.76 17 12c0-5.5-5-9-5-9z" fill="white"/>
        <path d="M12 3c0 0 0 4.5 3 7.5" stroke="rgba(255,255,255,0.45)" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </div>
    <span class="brand-name">Ferosa Landscaping</span>
  </div>
  <p class="brand-slogan">Transform your outdoor space into a paradise</p>
  <p class="brand-sub">Join thousands of homeowners who trust Ferosa Landscaping for their garden and landscaping needs.</p>
</div>

<div class="help-badge">?</div>

<div class="scene">

  <!-- LOGIN -->
  <div class="page {{ ($active ?? 'login') === 'signup' ? '' : 'active' }}" id="page-login">
    <div class="form-card">
      <div class="form-header">
        <h1 class="form-title">Welcome Back</h1>
        <p class="form-subtitle">Sign in to your Ferosa Landscaping account</p>
      </div>

      <div class="field">
        <label class="field-label">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
          <input id="login-email" type="text" inputmode="email" placeholder="you@example.com" dir="ltr" autocomplete="email" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>
      </div>

      <div class="field">
        <div class="field-label-row">
          <label class="field-label" style="margin:0">Password</label>
          <a class="forgot-link" onclick="handleForgotPassword()">Forgot password?</a>
        </div>
        <div class="input-wrap has-eye">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input id="login-password" type="password" placeholder="••••••••" dir="ltr" autocomplete="current-password">
          <span class="input-eye" onclick="togglePw(this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
      </div>

      <button id="login-btn" class="cta-btn" style="margin-top:8px" onclick="handleLogin()">
        Sign In
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>

      <p class="bottom-link">Don't have an account? <a onclick="switchTo('signup')">Sign up</a></p>
    </div>
  </div>

  <!-- SIGNUP -->
  <div class="page {{ ($active ?? 'login') === 'signup' ? 'active' : '' }}" id="page-signup">
    <div class="form-card">
      <div class="form-header">
        <h1 class="form-title">Create Account</h1>
        <p class="form-subtitle">Join Ferosa Landscaping and start designing your dream garden</p>
      </div>

      <div class="field">
        <label class="field-label">Full Name</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input id="signup-name" type="text" placeholder="John Doe" dir="ltr" autocomplete="name" autocorrect="off" autocapitalize="words" spellcheck="false">
        </div>
      </div>

      <div class="field">
        <label class="field-label">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
          <input id="signup-email" type="text" inputmode="email" placeholder="you@example.com" dir="ltr" autocomplete="email" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>
      </div>

      <div class="field">
        <label class="field-label">Mobile Number</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></span>
          <input id="signup-phone" type="text" inputmode="tel" placeholder="+63 912 345 6789" dir="ltr" autocomplete="tel" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>
      </div>

      <div class="field">
        <label class="field-label">Password</label>
        <div class="input-wrap has-eye">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input id="signup-password" type="password" placeholder="••••••••" dir="ltr" autocomplete="new-password">
          <span class="input-eye" onclick="togglePw(this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
      </div>

      <div class="field">
        <label class="field-label">Confirm Password</label>
        <div class="input-wrap has-eye">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input id="signup-password-confirm" type="password" placeholder="••••••••" dir="ltr" autocomplete="new-password">
          <span class="input-eye" onclick="togglePw(this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
      </div>

      <button id="signup-btn" class="cta-btn" onclick="handleSignup()">
        Create Account
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>

      <p class="bottom-link">Already have an account? <a onclick="switchTo('login')">Sign in</a></p>
    </div>
  </div>

  <!-- FORGOT PASSWORD: Enter Phone -->
  <div class="page" id="page-forgot">
    <div class="form-card">
      <div class="form-header">
        <h1 class="form-title">Forgot Password</h1>
        <p class="form-subtitle">Enter your registered mobile number to receive a verification code.</p>
      </div>
      <div class="field">
        <label class="field-label">Mobile Number</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg></span>
          <input id="forgot-phone" type="text" inputmode="tel" placeholder="+63 912 345 6789" dir="ltr" autocomplete="tel" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>
      </div>
      <button id="forgot-btn" class="cta-btn" onclick="sendOtp()">
        Send Verification Code
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>
      <p class="bottom-link"><a onclick="switchTo('login')">Back to Sign In</a></p>
    </div>
  </div>

  <!-- FORGOT PASSWORD: Enter OTP -->
  <div class="page" id="page-otp">
    <div class="form-card">
      <div class="form-header">
        <h1 class="form-title">Enter Verification Code</h1>
        <p class="form-subtitle">We sent a 6-digit code to your mobile number. It expires in 10 minutes.</p>
      </div>
      <div class="field">
        <label class="field-label">6-Digit Code</label>
        <div class="input-wrap">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input id="otp-code" type="text" inputmode="numeric" maxlength="6" placeholder="123456" dir="ltr" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>
      </div>
      <button id="otp-btn" class="cta-btn" onclick="verifyOtp()">
        Verify Code
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>
      <p class="bottom-link"><a onclick="switchTo('forgot')">Resend code</a> &nbsp;·&nbsp; <a onclick="switchTo('login')">Cancel</a></p>
    </div>
  </div>

  <!-- FORGOT PASSWORD: New Password -->
  <div class="page" id="page-newpassword">
    <div class="form-card">
      <div class="form-header">
        <h1 class="form-title">Set New Password</h1>
        <p class="form-subtitle">Choose a strong password for your account.</p>
      </div>
      <div class="field">
        <label class="field-label">New Password</label>
        <div class="input-wrap has-eye">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input id="new-password" type="password" placeholder="••••••••" dir="ltr" autocomplete="new-password">
          <span class="input-eye" onclick="togglePw(this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
      </div>
      <div class="field">
        <label class="field-label">Confirm New Password</label>
        <div class="input-wrap has-eye">
          <span class="input-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input id="new-password-confirm" type="password" placeholder="••••••••" dir="ltr" autocomplete="new-password">
          <span class="input-eye" onclick="togglePw(this)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
      </div>
      <button id="newpw-btn" class="cta-btn" onclick="resetPassword()">
        Reset Password
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>
      <p class="bottom-link"><a onclick="switchTo('login')">Cancel</a></p>
    </div>
  </div>

</div><!-- /scene -->

<script>
function showToast(message, type = 'info', duration = 4500) {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  const icons = {
    success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
    error:   '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    info:    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  };
  toast.innerHTML = `${icons[type] || icons.info}<span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('fadeout');
    toast.addEventListener('animationend', () => toast.remove());
  }, duration);
}

function setLoading(btnId, loading, originalHTML) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  if (loading) {
    btn._originalHTML = btn.innerHTML;
    btn.classList.add('loading');
    btn.innerHTML = '<div class="spinner"></div> Processing...';
  } else {
    btn.classList.remove('loading');
    btn.innerHTML = originalHTML || btn._originalHTML || btn.innerHTML;
  }
}

function switchTo(target) {
  const fromPage = document.querySelector('.page.active');
  const toPage = document.getElementById('page-' + target);
  if (!fromPage || fromPage === toPage) return;
  const card = fromPage.querySelector('.form-card');
  if (card) card.style.animation = 'fadeDown 0.28s ease forwards';
  setTimeout(() => {
    fromPage.classList.remove('active');
    toPage.classList.add('active');
    const newCard = toPage.querySelector('.form-card');
    if (newCard) {
      newCard.style.animation = 'none';
      requestAnimationFrame(() => {
        newCard.style.animation = 'fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both';
      });
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, 260);
}

function togglePw(eye) {
  const input = eye.previousElementSibling;
  input.type = input.type === 'password' ? 'text' : 'password';
}

function selectAcct(btn) {
  btn.closest('.acct-row').querySelectorAll('.acct-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}

// ── Android WebView: fix backward/RTL typing ──────────────────────────────
// On focus, move cursor to end of any existing value (prevents IME inserting at pos 0)
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input').forEach(input => {
    input.addEventListener('focus', function () {
      // Force LTR direction at the DOM level
      this.setAttribute('dir', 'ltr');
      // Move cursor to end
      const len = this.value.length;
      try {
        this.setSelectionRange(len, len);
      } catch (e) { /* password fields may throw */ }
      // Scroll the focused field into view above the keyboard
      // Delay slightly to let the keyboard animation finish first
      const el = this;
      setTimeout(() => {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 320);
    });
  });
});

document.addEventListener('mousemove', e => {
  const bg = document.getElementById('sceneBg');
  if (!bg) return;
  const x = (e.clientX / window.innerWidth - 0.5) * 12;
  const y = (e.clientY / window.innerHeight - 0.5) * 12;
  bg.style.transform = `scale(1.06) translate(${x}px,${y}px)`;
});

async function handleLogin() {
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  if (!email) return showToast('Please enter your email address.', 'error');
  if (!password) return showToast('Please enter your password.', 'error');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showToast('Please enter a valid email address.', 'error');

  setLoading('login-btn', true);
  try {
    const form = new URLSearchParams();
    form.append('email', email);
    form.append('password', password);
    const res = await fetch('{{ route('login.submit') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: form.toString(),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (data.errors) {
        const messages = Object.values(data.errors).flat();
        messages.forEach(msg => showToast(msg, 'error'));
        return;
      }
      throw new Error(data.message || 'Sign in failed');
    }
    window.location.href = data.redirectUrl || '{{ route('home') }}';
  } catch (err) {
    showToast(err.message || 'Sign in failed. Please try again.', 'error');
  } finally {
    setLoading('login-btn', false);
  }
}

async function handleSignup() {
  const fullName = document.getElementById('signup-name').value.trim();
  const email = document.getElementById('signup-email').value.trim();
  const phone = document.getElementById('signup-phone').value.trim();
  const password = document.getElementById('signup-password').value;
  const passwordConfirm = document.getElementById('signup-password-confirm').value;

  if (!fullName) return showToast('Please enter your full name.', 'error');
  if (!email) return showToast('Please enter your email address.', 'error');
  if (!phone) return showToast('Please enter your mobile number.', 'error');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showToast('Please enter a valid email address.', 'error');
  if (!password) return showToast('Please create a password.', 'error');
  if (password.length < 8) return showToast('Password must be at least 8 characters.', 'error');
  if (password !== passwordConfirm) return showToast('Passwords do not match.', 'error');

  setLoading('signup-btn', true);
  try {
    const form = new URLSearchParams();
    form.append('name', fullName);
    form.append('email', email);
    form.append('phone_number', phone);
    form.append('password', password);
    form.append('password_confirmation', passwordConfirm);
    const res = await fetch('{{ route('register.submit') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: form.toString(),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (data.errors) {
        const messages = Object.values(data.errors).flat();
        messages.forEach(msg => showToast(msg, 'error'));
        return;
      }
      throw new Error(data.message || 'Account creation failed');
    }
    window.location.href = data.redirectUrl || '{{ route('home') }}';
  } catch (err) {
    showToast(err.message || 'Account creation failed. Please try again.', 'error');
  } finally {
    setLoading('signup-btn', false);
  }
}

function handleForgotPassword() {
  switchTo('forgot');
}

// ── OTP state ────────────────────────────────────────────
let _otpPhone = '';
let _otpCode  = '';

async function sendOtp() {
  const phone = document.getElementById('forgot-phone').value.trim();
  if (!phone) return showToast('Please enter your mobile number.', 'error');

  setLoading('forgot-btn', true);
  try {
    const form = new URLSearchParams();
    form.append('phone_number', phone);
    const res = await fetch('{{ route('forgot.send-otp') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: form.toString(),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Failed to send OTP.'];
      messages.forEach(m => showToast(m, 'error'));
      return;
    }
    _otpPhone = phone;
    showToast(data.message || 'OTP sent!', 'success');
    switchTo('otp');
  } catch (err) {
    showToast('Could not send OTP. Please try again.', 'error');
  } finally {
    setLoading('forgot-btn', false);
  }
}

async function verifyOtp() {
  const code = document.getElementById('otp-code').value.trim();
  if (!code || code.length !== 6) return showToast('Please enter the 6-digit code.', 'error');

  setLoading('otp-btn', true);
  try {
    const form = new URLSearchParams();
    form.append('phone_number', _otpPhone);
    form.append('otp', code);
    const res = await fetch('{{ route('forgot.verify-otp') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: form.toString(),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Invalid OTP.'];
      messages.forEach(m => showToast(m, 'error'));
      return;
    }
    _otpCode = code;
    switchTo('newpassword');
  } catch (err) {
    showToast('Verification failed. Please try again.', 'error');
  } finally {
    setLoading('otp-btn', false);
  }
}

async function resetPassword() {
  const password = document.getElementById('new-password').value;
  const passwordConfirm = document.getElementById('new-password-confirm').value;
  if (!password) return showToast('Please enter a new password.', 'error');
  if (password.length < 8) return showToast('Password must be at least 8 characters.', 'error');
  if (password !== passwordConfirm) return showToast('Passwords do not match.', 'error');

  setLoading('newpw-btn', true);
  try {
    const form = new URLSearchParams();
    form.append('phone_number', _otpPhone);
    form.append('otp', _otpCode);
    form.append('password', password);
    form.append('password_confirmation', passwordConfirm);
    const res = await fetch('{{ route('forgot.reset') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: form.toString(),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const messages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Reset failed.'];
      messages.forEach(m => showToast(m, 'error'));
      return;
    }
    showToast('Password reset successfully! Please sign in.', 'success', 4000);
    _otpPhone = ''; _otpCode = '';
    setTimeout(() => switchTo('login'), 1500);
  } catch (err) {
    showToast('Could not reset password. Please try again.', 'error');
  } finally {
    setLoading('newpw-btn', false);
  }
}

function handleSignOut() {
  showToast('Please sign out from the dashboard.', 'info');
}

document.addEventListener('keydown', e => {
  if (e.key !== 'Enter') return;
  const loginPage = document.getElementById('page-login');
  const signupPage = document.getElementById('page-signup');
  if (loginPage && loginPage.classList.contains('active')) handleLogin();
  if (signupPage && signupPage.classList.contains('active')) handleSignup();
});
</script>
</body>
</html>
