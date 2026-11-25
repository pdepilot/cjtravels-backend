<?php
if (!function_exists('route')) {
    $bootstrap = __DIR__ . '/bootstrap/app.php';
    if (!file_exists($bootstrap)) {
        $bootstrap = __DIR__ . '/../bootstrap/app.php';
    }
    if (!file_exists($bootstrap)) {
        $bootstrap = __DIR__ . '/../../bootstrap/app.php';
    }
    if (file_exists($bootstrap)) {
        require_once $bootstrap;
    }
}
$base = function_exists('base_subfolder') ? base_subfolder() : '/TravelBooking';
$popularRoutes = $routes ?? \App\Services\RealtimeDataService::getPopularBusRoutes();
$whatsappNumber = function_exists('cj_whatsapp_number') ? cj_whatsapp_number() : '2348135196772';
$phoneDisplay = function_exists('cj_phone_display') ? cj_phone_display() : '08135196772';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="<?= asset('images/logo-mark.png') ?>" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($title ?? 'Luxury Road Transport & Bus Tickets — CJ Travels') ?></title>
  <meta name="description" content="Book interstate luxury bus tickets and road transport across Nigeria. Select departure and return dates, compare routes, and send your booking directly via WhatsApp." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="<?= asset('css/cookie-banner.css') ?>" rel="stylesheet" />
  <link href="<?= asset('css/promo-banner.css') ?>" rel="stylesheet" />

  <style>
    :root {
      --cream: #f5f0e6;
      --cream-soft: #faf6ed;
      --ivory: #fdfbf6;
      --navy: #0b1e3d;
      --navy-soft: #1e3657;
      --navy-deep: #060f21;
      --gold: #a8842c;
      --gold-soft: #c9a55b;
      --gold-pale: #e8d9b3;
      --ink: #1a1a1a;
      --ink-soft: #4a4741;
      --muted: #8a8378;
      --hairline: #d9d1c0;
      --hairline-soft: #e8e1d1;
      --green: #2e7d5b;
      --whatsapp: #25D366;
      --whatsapp-dark: #128C7E;
      --red: #a3452e;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'DM Sans', sans-serif;
      font-weight: 400;
      color: var(--ink);
      background: var(--cream);
      line-height: 1.65;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 400;
      color: var(--navy);
      margin: 0;
      line-height: 1.15;
    }
    p { margin: 0; }
    a { color: inherit; text-decoration: none; }
    img { max-width: 100%; display: block; }

    .container { max-width: 1200px; padding-left: 24px; padding-right: 24px; margin: 0 auto; }

    /* =======================================================
       SITE HEADER
       ======================================================= */
    .site-header {
      position: sticky;
      top: 0;
      background: rgba(245, 240, 230, .94);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--hairline);
      z-index: 1000;
      transition: background-color .3s ease;
    }
    .header-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 78px;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }
    .brand-logo {
      height: 38px;
      width: auto;
      object-fit: contain;
    }
    .nav-center {
      display: flex;
      align-items: center;
      gap: 4px;
      flex: 1;
      justify-content: center;
    }
    .nav-center a {
      padding: 10px 16px;
      font-size: 12px;
      font-weight: 500;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--ink-soft);
      border-bottom: 1px solid transparent;
      transition: all .25s ease;
      white-space: nowrap;
    }
    .nav-center a:hover { color: var(--navy); }
    .nav-center a.active {
      color: var(--navy);
      border-bottom-color: var(--gold);
    }
    .nav-actions {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-shrink: 0;
    }
    .btn-nav-cta {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 11px 22px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .2em;
      text-transform: uppercase;
      background: var(--navy);
      color: var(--ivory);
      border: 0;
      cursor: pointer;
      transition: background-color .25s ease;
    }
    .btn-nav-cta:hover { background: var(--navy-soft); color: var(--ivory); }

    .navbar-toggler {
      display: none;
      width: 44px;
      height: 44px;
      border: 1px solid var(--hairline);
      background: transparent;
      color: var(--navy);
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    @media (max-width: 991.98px) {
      .nav-center, .nav-actions { display: none; }
      .navbar-toggler { display: inline-flex; }
    }

    /* Mobile Drawer */
    .mobile-nav-overlay {
      position: fixed;
      inset: 0;
      background: rgba(6, 15, 33, .55);
      backdrop-filter: blur(3px);
      z-index: 1049;
      opacity: 0;
      pointer-events: none;
      transition: opacity .3s ease;
    }
    .mobile-nav-overlay.show { opacity: 1; pointer-events: auto; }
    .mobile-nav {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: min(360px, 88vw);
      background: var(--cream);
      z-index: 1050;
      transform: translateX(100%);
      transition: transform .4s cubic-bezier(.4, 0, .2, 1);
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }
    .mobile-nav.open { transform: translateX(0); }
    .mobile-nav-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 22px 24px;
      border-bottom: 1px solid var(--hairline-soft);
    }
    .mobile-nav-close {
      width: 40px; height: 40px;
      border: 1px solid var(--hairline);
      background: transparent;
      color: var(--navy);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
    .mobile-nav-links {
      display: flex;
      flex-direction: column;
      padding: 24px;
      gap: 4px;
    }
    .mobile-nav-links a {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 14px;
      font-size: 13px;
      font-weight: 500;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--navy);
      border-radius: 2px;
      transition: background-color .2s ease;
    }
    .mobile-nav-links a.active, .mobile-nav-links a:hover {
      background: var(--cream-soft);
      color: var(--gold);
    }
    .mobile-nav-foot {
      padding: 24px;
      margin-top: auto;
      border-top: 1px solid var(--hairline-soft);
    }

    /* =======================================================
       HERO BANNER
       ======================================================= */
    .bus-hero {
      position: relative;
      background: radial-gradient(circle at 50% 0%, #152e59 0%, #08152b 50%, #030813 100%);
      color: #fdfbf6;
      padding: clamp(60px, 9vw, 100px) 0 clamp(80px, 11vw, 120px);
      overflow: hidden;
      text-align: center;
    }
    .bus-hero::before {
      content: "";
      position: absolute;
      top: -150px;
      left: 50%;
      transform: translateX(-50%);
      width: clamp(500px, 75vw, 1100px);
      height: 450px;
      background: radial-gradient(ellipse, rgba(201, 165, 91, 0.22) 0%, rgba(37, 211, 102, 0.12) 40%, transparent 75%);
      filter: blur(80px);
      border-radius: 50%;
      pointer-events: none;
    }
    .hero-badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 7px 18px;
      background: rgba(253, 251, 246, 0.08);
      border: 1px solid rgba(201, 165, 91, 0.4);
      border-radius: 100px;
      backdrop-filter: blur(12px);
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .22em;
      text-transform: uppercase;
      color: #e8d9b3;
      margin-bottom: 22px;
    }
    .hero-badge-pill .pulse-dot {
      width: 8px; height: 8px;
      background: #25D366;
      border-radius: 50%;
      box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.35);
    }
    .bus-hero h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.4rem, 6.5vw, 5rem);
      line-height: 1.05;
      font-weight: 300;
      color: #fdfbf6;
      margin-bottom: 20px;
      letter-spacing: -.02em;
    }
    .bus-hero h1 em {
      font-style: italic;
      background: linear-gradient(135deg, #f7eccf 0%, #c9a55b 50%, #e8d9b3 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .bus-hero p.hero-sub {
      font-size: clamp(15px, 1.3vw, 17.5px);
      color: rgba(253, 251, 246, 0.8);
      max-width: 68ch;
      margin: 0 auto 30px;
      line-height: 1.7;
      font-weight: 300;
    }
    .quick-routes-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 24px;
    }
    .quick-route-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      background: rgba(253, 251, 246, 0.08);
      border: 1px solid rgba(253, 251, 246, 0.18);
      color: #fdfbf6;
      border-radius: 100px;
      font-size: 12px;
      cursor: pointer;
      transition: all .25s ease;
      backdrop-filter: blur(8px);
    }
    .quick-route-chip:hover {
      background: rgba(201, 165, 91, 0.25);
      border-color: #c9a55b;
      color: #fff;
      transform: translateY(-2px);
    }

    /* =======================================================
       SEARCH & BOOKING TERMINAL
       ======================================================= */
    .bus-terminal-wrap {
      position: relative;
      margin-top: -55px;
      z-index: 20;
      padding: 0 16px;
      margin-bottom: 80px;
    }
    .bus-booking-card {
      max-width: 1100px;
      margin: 0 auto;
      background: #fdfbf6;
      border: 1px solid rgba(201, 165, 91, 0.35);
      border-radius: 8px;
      padding: clamp(24px, 4vw, 42px);
      box-shadow: 0 24px 64px rgba(6, 15, 33, 0.16), 0 4px 16px rgba(6, 15, 33, 0.06);
      position: relative;
    }
    .bus-booking-card::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, #a8842c 0%, #25D366 50%, #a8842c 100%);
      border-radius: 8px 8px 0 0;
    }

    /* Trip Type Pill Switch */
    .trip-type-toggle {
      display: inline-flex;
      align-items: center;
      background: var(--cream-soft);
      border: 1px solid var(--hairline);
      border-radius: 100px;
      padding: 4px;
      margin-bottom: 24px;
    }
    .trip-type-btn {
      padding: 7px 18px;
      border-radius: 100px;
      border: 0;
      background: transparent;
      color: var(--ink-soft);
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      cursor: pointer;
      transition: all .25s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .trip-type-btn.active {
      background: var(--navy);
      color: #fff;
      box-shadow: 0 3px 10px rgba(11, 30, 61, 0.2);
    }

    /* Form Fields */
    .field-label {
      font-size: 11.5px;
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--navy);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .field-label i { color: var(--gold); font-size: 13px; }
    .form-control, .form-select {
      height: 52px;
      border: 1px solid var(--hairline);
      background: var(--cream-soft);
      border-radius: 4px;
      font-size: 14px;
      color: var(--navy);
      padding: 0 14px;
      transition: all .25s ease;
    }
    .form-control:focus, .form-select:focus {
      background: #fff;
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(201, 165, 91, 0.15);
      outline: none;
    }
    textarea.form-control {
      height: auto;
      padding: 12px 14px;
    }

    /* City Swap Button */
    .swap-btn-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 52px;
    }
    .btn-swap {
      width: 44px; height: 44px;
      border-radius: 50%;
      border: 1px solid var(--hairline);
      background: #fff;
      color: var(--gold);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 16px;
      transition: all .25s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .btn-swap:hover {
      background: var(--navy);
      color: #fff;
      border-color: var(--navy);
      transform: rotate(180deg);
    }

    /* Fare Estimation Banner */
    .fare-estimation-card {
      background: linear-gradient(135deg, rgba(11, 30, 61, 0.04) 0%, rgba(37, 211, 102, 0.08) 100%);
      border: 1px solid rgba(37, 211, 102, 0.35);
      border-radius: 6px;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin: 24px 0;
    }
    .fare-meta strong {
      display: block;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: var(--navy);
    }
    .fare-meta span {
      font-size: 13px;
      color: var(--muted);
    }
    .fare-val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px;
      font-weight: 700;
      color: var(--navy);
    }
    .fare-val em {
      font-style: normal;
      color: var(--green);
    }

    /* Submit CTA */
    .btn-whatsapp-submit {
      width: 100%;
      padding: 16px 28px;
      background: #25D366;
      color: #fff;
      border: 0;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      cursor: pointer;
      transition: all .25s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      box-shadow: 0 6px 20px rgba(37, 211, 102, 0.35);
    }
    .btn-whatsapp-submit:hover {
      background: #1eb956;
      box-shadow: 0 8px 25px rgba(37, 211, 102, 0.45);
      transform: translateY(-2px);
    }
    .btn-whatsapp-submit i { font-size: 20px; }

    /* =======================================================
       POPULAR ROUTES GRID
       ======================================================= */
    .routes-section {
      padding: 40px 0 80px;
    }
    .section-head {
      text-align: center;
      margin-bottom: 48px;
    }
    .section-head .eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .24em;
      text-transform: uppercase;
      color: var(--gold);
      display: block;
      margin-bottom: 10px;
    }
    .section-head h2 {
      font-size: clamp(2rem, 4vw, 3.2rem);
      margin-bottom: 14px;
    }
    .section-head p {
      max-width: 60ch;
      margin: 0 auto;
      color: var(--muted);
    }

    .route-card {
      background: var(--ivory);
      border: 1px solid var(--hairline);
      border-radius: 6px;
      padding: 22px;
      transition: all .3s cubic-bezier(0.2, 0.8, 0.2, 1);
      display: flex;
      flex-direction: column;
      height: 100%;
      position: relative;
    }
    .route-card:hover {
      border-color: var(--gold);
      transform: translateY(-4px);
      box-shadow: 0 14px 34px rgba(11, 30, 61, 0.08);
    }
    .route-badge {
      position: absolute;
      top: 14px;
      right: 14px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
      padding: 3px 8px;
      background: rgba(201, 165, 91, 0.15);
      color: var(--gold);
      border-radius: 100px;
    }
    .route-cities {
      font-family: 'Cormorant Garamond', serif;
      font-size: 22px;
      font-weight: 600;
      color: var(--navy);
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }
    .route-terminals {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 16px;
      line-height: 1.4;
    }
    .route-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 14px;
      border-top: 1px solid var(--hairline-soft);
      margin-top: auto;
    }
    .route-fare {
      font-size: 18px;
      font-weight: 700;
      color: var(--navy);
    }
    .route-fare small {
      font-size: 11px;
      font-weight: 400;
      color: var(--muted);
      display: block;
    }
    .btn-select-route {
      padding: 6px 14px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
      background: var(--navy);
      color: #fff;
      border: 0;
      border-radius: 3px;
      cursor: pointer;
      transition: all .2s ease;
    }
    .btn-select-route:hover {
      background: var(--gold);
      color: #fff;
    }

    /* =======================================================
       THE CJ ADVANTAGE
       ======================================================= */
    .advantage-box {
      background: var(--navy);
      color: #fdfbf6;
      border-radius: 8px;
      padding: clamp(36px, 6vw, 64px);
      margin-bottom: 80px;
      position: relative;
      overflow: hidden;
    }
    .advantage-box h2 {
      color: #fdfbf6;
      margin-bottom: 18px;
      font-size: clamp(2rem, 3.5vw, 3rem);
    }
    .advantage-box h2 em {
      color: var(--gold-soft);
      font-style: italic;
    }
    .adv-feat {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 24px;
    }
    .adv-icon {
      width: 44px; height: 44px;
      border-radius: 50%;
      background: rgba(201, 165, 91, 0.16);
      border: 1px solid rgba(201, 165, 91, 0.4);
      color: var(--gold-soft);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }
    .adv-text strong {
      display: block;
      font-size: 15px;
      color: #fdfbf6;
      margin-bottom: 4px;
    }
    .adv-text p {
      font-size: 13px;
      color: rgba(253, 251, 246, 0.72);
      line-height: 1.55;
    }

    /* =======================================================
       SUCCESS MODAL
       ======================================================= */
    .bus-modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(6, 15, 33, 0.8);
      backdrop-filter: blur(8px);
      z-index: 10000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .bus-modal-backdrop.show { display: flex; }
    .bus-modal-card {
      background: #fdfbf6;
      border: 1px solid var(--gold);
      border-radius: 8px;
      width: 100%;
      max-width: 580px;
      padding: 34px;
      position: relative;
      box-shadow: 0 30px 80px rgba(0,0,0,0.5);
      animation: modalPop 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes modalPop {
      from { transform: scale(0.92) translateY(20px); opacity: 0; }
      to { transform: scale(1) translateY(0); opacity: 1; }
    }
    .bus-modal-close {
      position: absolute;
      top: 16px;
      right: 16px;
      width: 34px; height: 34px;
      border: 1px solid var(--hairline);
      background: transparent;
      border-radius: 50%;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--navy);
    }
    .bus-ticket-pass {
      background: var(--cream-soft);
      border: 1px dashed var(--hairline);
      border-radius: 6px;
      padding: 18px;
      margin: 20px 0;
    }
    .pass-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      padding: 4px 0;
      border-bottom: 1px solid var(--hairline-soft);
    }
    .pass-row:last-child { border-bottom: 0; font-weight: 700; font-size: 14px; }
  </style>
</head>

<body>
  <!-- SITE HEADER / NAVBAR -->
  <header class="site-header" id="siteHeader">
    <div class="container header-inner">
      <a class="brand" href="<?= route('home') ?>" aria-label="CJ Travels home">
        <img src="<?= asset('images/logo-transparent.png') ?>" alt="CJ Travels" class="brand-logo" onerror="this.onerror=null;this.src='<?= asset('images/logo.png') ?>';" />
      </a>

      <nav class="nav-center" aria-label="Main navigation">
        <a href="<?= route('home') ?>">Home</a>
        <a href="<?= route('flights') ?>">Flights</a>
        <a href="<?= route('hotels') ?>">Hotels</a>
        <a href="<?= route('bus') ?>" class="active">Bus Tickets</a>
        <a href="<?= route('destinations') ?>">Destinations</a>
        <a href="<?= route('financing') ?>">Financing</a>
        <a href="<?= route('about') ?>">About</a>
      </nav>

      <div class="nav-actions">
        <a href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank" class="btn-nav-cta" style="background:#25D366;">
          <i class="bi bi-whatsapp"></i> WhatsApp Help
        </a>
      </div>

      <button class="navbar-toggler" type="button" id="mobileToggle" aria-controls="mobileNav" aria-expanded="false" aria-label="Open menu">
        <i class="bi bi-list"></i>
      </button>
    </div>
  </header>

  <!-- MOBILE DRAWER -->
  <div class="mobile-nav-overlay" id="mobileOverlay"></div>
  <aside class="mobile-nav" id="mobileNav" aria-label="Mobile navigation">
    <div class="mobile-nav-head">
      <a class="brand" href="<?= route('home') ?>" aria-label="CJ Travels home">
        <img src="<?= asset('images/logo-transparent.png') ?>" alt="CJ Travels" class="brand-logo" onerror="this.onerror=null;this.src='<?= asset('images/logo.png') ?>';" />
      </a>
      <button class="mobile-nav-close" id="mobileClose" type="button" aria-label="Close menu">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="mobile-nav-links">
      <a href="<?= route('home') ?>"><i class="bi bi-house-door"></i> Home</a>
      <a href="<?= route('flights') ?>"><i class="bi bi-airplane"></i> Flights</a>
      <a href="<?= route('hotels') ?>"><i class="bi bi-building"></i> Hotels</a>
      <a href="<?= route('bus') ?>" class="active"><i class="bi bi-bus-front"></i> Bus Tickets</a>
      <a href="<?= route('destinations') ?>"><i class="bi bi-compass"></i> Destinations</a>
      <a href="<?= route('financing') ?>"><i class="bi bi-cash-coin"></i> Financing</a>
      <a href="<?= route('about') ?>"><i class="bi bi-info-circle"></i> About</a>
      <a href="<?= route('contact') ?>"><i class="bi bi-envelope"></i> Contact</a>
    </div>
    <div class="mobile-nav-foot">
      <a href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank" class="btn-nav-cta w-100 text-center" style="background:#25D366;justify-content:center;">
        <i class="bi bi-whatsapp"></i> Chat on WhatsApp
      </a>
    </div>
  </aside>

  <!-- HERO SECTION -->
  <section class="bus-hero">
    <div class="container">
      <div class="hero-badge-pill">
        <span class="pulse-dot"></span> Executive Road Transport &amp; Interstate Bus Tickets
      </div>
      <h1>Travel by Road in <em>First-Class Comfort</em></h1>
      <p class="hero-sub">
        Select your departure and return travel dates across Nigeria's leading luxury coach terminals. Every reservation enquiry is automatically sent to our dedicated WhatsApp travel concierge for instant seat verification.
      </p>

      <div class="quick-routes-bar">
        <span style="font-size:11px;letter-spacing:.15em;text-transform:uppercase;color:var(--gold-soft);font-weight:600;margin-right:6px;">POPULAR:</span>
        <button type="button" class="quick-route-chip" data-from="Lagos" data-to="Abuja"><i class="bi bi-geo-alt"></i> Lagos → Abuja</button>
        <button type="button" class="quick-route-chip" data-from="Lagos" data-to="Port Harcourt"><i class="bi bi-geo-alt"></i> Lagos → Port Harcourt</button>
        <button type="button" class="quick-route-chip" data-from="Lagos" data-to="Benin City"><i class="bi bi-geo-alt"></i> Lagos → Benin</button>
        <button type="button" class="quick-route-chip" data-from="Lagos" data-to="Enugu"><i class="bi bi-geo-alt"></i> Lagos → Enugu</button>
        <button type="button" class="quick-route-chip" data-from="Abuja" data-to="Kano"><i class="bi bi-geo-alt"></i> Abuja → Kano</button>
      </div>
    </div>
  </section>

  <!-- INTERACTIVE BOOKING TERMINAL -->
  <section class="bus-terminal-wrap">
    <div class="container">
      <div class="bus-booking-card" id="busBookingCard">
        
        <form id="busBookingForm" novalidate>
          <?= csrf_field() ?>

          <!-- TRIP TYPE TOGGLE -->
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div class="trip-type-toggle" role="radiogroup" aria-label="Trip type">
              <button type="button" class="trip-type-btn active" data-type="one-way">
                <i class="bi bi-arrow-right"></i> One-Way
              </button>
              <button type="button" class="trip-type-btn" data-type="round-trip">
                <i class="bi bi-arrow-left-right"></i> Round-Trip (Two-Way)
              </button>
            </div>
            <input type="hidden" name="trip_type" id="tripTypeInput" value="one-way" />

            <div style="font-size:12px;color:var(--muted);">
              <i class="bi bi-shield-check text-success me-1"></i> Official Operator Partner Fares
            </div>
          </div>

          <!-- ROUTE SELECTION (FROM / SWAP / TO) -->
          <div class="row g-3 align-items-end mb-3">
            <div class="col-lg-5 col-md-5">
              <label class="field-label" for="busFrom">
                <span><i class="bi bi-geo-alt-fill"></i> Departure City / Terminal</span>
              </label>
              <select class="form-select" id="busFrom" name="departure_city" required>
                <option value="">Select departure terminal...</option>
                <option value="Lagos (Jibowu / Yaba Terminal)" selected>Lagos (Jibowu / Yaba Terminal)</option>
                <option value="Lagos (Ajah / Lekki Terminal)">Lagos (Ajah / Lekki Terminal)</option>
                <option value="Lagos (Maza-Maza Terminal)">Lagos (Maza-Maza Terminal)</option>
                <option value="Abuja (Utako Central Terminal)">Abuja (Utako Central Terminal)</option>
                <option value="Port Harcourt (Waterlines Terminal)">Port Harcourt (Waterlines Terminal)</option>
                <option value="Benin City (Uselu / Akpakpava)">Benin City (Uselu / Akpakpava)</option>
                <option value="Enugu (Holy Ghost Terminal)">Enugu (Holy Ghost Terminal)</option>
                <option value="Owerri (Control Post Terminal)">Owerri (Control Post Terminal)</option>
                <option value="Ibadan (Iwo Road / Challenge)">Ibadan (Iwo Road / Challenge)</option>
                <option value="Asaba / Onitsha (Summit Rd / Upper Iweka)">Asaba / Onitsha (Summit Rd)</option>
                <option value="Kaduna (Kawo / Central Station)">Kaduna (Kawo / Central Station)</option>
                <option value="Kano (Sabon Gari / Naibawa)">Kano (Sabon Gari / Naibawa)</option>
                <option value="Jos (Terminus / British America)">Jos (Terminus / British America)</option>
                <option value="Calabar (Murtala Mohammed Hwy)">Calabar (Murtala Mohammed Hwy)</option>
                <option value="Uyo (Itam Peace Park)">Uyo (Itam Peace Park)</option>
                <option value="Warri (Effurun Roundabout)">Warri (Effurun Roundabout)</option>
              </select>
            </div>

            <div class="col-lg-2 col-md-2 text-center">
              <div class="swap-btn-wrap">
                <button type="button" class="btn-swap" id="btnSwapCities" title="Swap departure and destination" aria-label="Swap departure and destination">
                  <i class="bi bi-arrow-left-right"></i>
                </button>
              </div>
            </div>

            <div class="col-lg-5 col-md-5">
              <label class="field-label" for="busTo">
                <span><i class="bi bi-pin-map-fill"></i> Destination City / Terminal</span>
              </label>
              <select class="form-select" id="busTo" name="destination_city" required>
                <option value="">Select destination terminal...</option>
                <option value="Abuja (Utako Central Terminal)" selected>Abuja (Utako Central Terminal)</option>
                <option value="Lagos (Jibowu / Yaba Terminal)">Lagos (Jibowu / Yaba Terminal)</option>
                <option value="Lagos (Ajah / Lekki Terminal)">Lagos (Ajah / Lekki Terminal)</option>
                <option value="Port Harcourt (Waterlines Terminal)">Port Harcourt (Waterlines Terminal)</option>
                <option value="Benin City (Uselu / Akpakpava)">Benin City (Uselu / Akpakpava)</option>
                <option value="Enugu (Holy Ghost Terminal)">Enugu (Holy Ghost Terminal)</option>
                <option value="Owerri (Control Post Terminal)">Owerri (Control Post Terminal)</option>
                <option value="Ibadan (Iwo Road / Challenge)">Ibadan (Iwo Road / Challenge)</option>
                <option value="Asaba / Onitsha (Summit Rd / Upper Iweka)">Asaba / Onitsha (Summit Rd)</option>
                <option value="Kaduna (Kawo / Central Station)">Kaduna (Kawo / Central Station)</option>
                <option value="Kano (Sabon Gari / Naibawa)">Kano (Sabon Gari / Naibawa)</option>
                <option value="Jos (Terminus / British America)">Jos (Terminus / British America)</option>
                <option value="Calabar (Murtala Mohammed Hwy)">Calabar (Murtala Mohammed Hwy)</option>
                <option value="Uyo (Itam Peace Park)">Uyo (Itam Peace Park)</option>
                <option value="Warri (Effurun Roundabout)">Warri (Effurun Roundabout)</option>
              </select>
            </div>
          </div>

          <!-- DATES & TIMING -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="field-label" for="busDepartDate">
                <span><i class="bi bi-calendar3"></i> Departure Date</span>
                <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control" id="busDepartDate" name="departure_date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" />
            </div>

            <div class="col-md-4" id="returnDateWrap" style="display:none;">
              <label class="field-label" for="busReturnDate">
                <span><i class="bi bi-calendar2-check"></i> Return Date</span>
                <span class="text-danger" id="returnRequiredStar">*</span>
              </label>
              <input type="date" class="form-control" id="busReturnDate" name="return_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+5 days')) ?>" />
            </div>

            <div class="col-md-4" id="departTimeCol">
              <label class="field-label" for="busDepartTime">
                <span><i class="bi bi-clock-history"></i> Preferred Departure Time</span>
              </label>
              <select class="form-select" id="busDepartTime" name="departure_time">
                <option value="Morning (06:30 - 08:00 AM)" selected>Morning (06:30 - 08:00 AM)</option>
                <option value="Mid-Day (11:00 AM - 01:00 PM)">Mid-Day (11:00 AM - 01:00 PM)</option>
                <option value="Night Express (07:00 - 09:00 PM)">Night Express (07:00 - 09:00 PM)</option>
              </select>
            </div>

            <div class="col-md-4" id="seatsCol">
              <label class="field-label" for="busPassengers">
                <span><i class="bi bi-people"></i> Seats / Passengers</span>
              </label>
              <select class="form-select" id="busPassengers" name="passengers">
                <option value="1" selected>1 Seat</option>
                <option value="2">2 Seats</option>
                <option value="3">3 Seats</option>
                <option value="4">4 Seats</option>
                <option value="5">5 Seats</option>
                <option value="6">6 Seats (Family/Group)</option>
                <option value="10">10+ Seats (Full Coach Charter)</option>
              </select>
            </div>
          </div>

          <!-- COACH CATEGORY & OPERATOR -->
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="field-label" for="busCoachClass">
                <span><i class="bi bi-stars"></i> Coach Service Tier</span>
              </label>
              <select class="form-select" id="busCoachClass" name="coach_class">
                <option value="Executive Luxury AC Coach" selected>Executive Luxury AC Coach (Spacious Recliner, USB, AC)</option>
                <option value="VIP First-Class Coach">VIP First-Class Coach (Ultra Legroom, Hostess, Snacks)</option>
                <option value="Standard Comfort AC Bus">Standard Comfort AC Bus (Economical Interstate Express)</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="field-label" for="busOperator">
                <span><i class="bi bi-shield-check"></i> Preferred Bus Operator</span>
              </label>
              <select class="form-select" id="busOperator" name="preferred_operator">
                <option value="Any Verified Luxury Partner" selected>Any Verified Luxury Partner (Best Rate &amp; Schedule)</option>
                <option value="GIGM - God is Good Motors">GIGM (God is Good Motors)</option>
                <option value="ABC Transport Plc">ABC Transport Plc</option>
                <option value="Chisco Transport">Chisco Transport</option>
                <option value="GUO Transport">GUO Transport</option>
                <option value="Young Shall Grow Motors">Young Shall Grow Motors</option>
                <option value="Peace Mass Transit">Peace Mass Transit</option>
              </select>
            </div>
          </div>

          <!-- PASSENGER CONTACT DETAILS -->
          <div class="p-3 mb-4" style="background:var(--cream-soft);border:1px solid var(--hairline);border-radius:6px;">
            <div style="font-weight:600;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:var(--navy);margin-bottom:12px;">
              <i class="bi bi-person-badge text-gold me-1"></i> Passenger &amp; WhatsApp Notification Contact
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="field-label" for="busName">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="busName" name="customer_name" placeholder="e.g. Somtochukwu Okafor" required />
              </div>
              <div class="col-md-4">
                <label class="field-label" for="busPhone">WhatsApp Phone Number <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="busPhone" name="customer_phone" placeholder="e.g. 08123456789" required />
              </div>
              <div class="col-md-4">
                <label class="field-label" for="busEmail">Email Address (Optional)</label>
                <input type="email" class="form-control" id="busEmail" name="customer_email" placeholder="e.g. somto@example.com" />
              </div>
              <div class="col-12">
                <label class="field-label" for="busNotes">Luggage Notes or Seat Preferences (Optional)</label>
                <input type="text" class="form-control" id="busNotes" name="notes" placeholder="e.g. 2 large luggage bags, window seat preference, traveling with infant" />
              </div>
            </div>
          </div>

          <!-- LIVE FARE ESTIMATION CARD -->
          <div class="fare-estimation-card" id="fareEstimateBox">
            <div class="fare-meta">
              <strong>Estimated Journey Fare</strong>
              <span id="fareMetaDescription">Lagos → Abuja · 1 Seat · Executive Luxury AC Coach</span>
            </div>
            <div class="fare-val">
              <span id="fareAmountDisplay">₦40,000</span>
              <small style="font-size:12px;font-family:'DM Sans',sans-serif;font-weight:400;color:var(--muted);display:block;text-align:right;">Estimated terminal tariff</small>
            </div>
          </div>

          <div id="bookingErrorAlert" class="alert alert-danger" style="display:none;margin-bottom:16px;"></div>

          <!-- PRIMARY SUBMISSION BUTTON -->
          <button type="submit" class="btn-whatsapp-submit" id="btnSubmitBooking">
            <i class="bi bi-whatsapp"></i> Reserve &amp; Send Enquiry via WhatsApp
          </button>

          <p class="text-center text-muted mt-2" style="font-size:11.5px;">
            <i class="bi bi-lock-fill text-gold me-1"></i> Submitting immediately saves your reservation reference in our database and opens WhatsApp pre-filled with all travel details.
          </p>
        </form>

      </div>
    </div>
  </section>

  <!-- POPULAR ROUTES SHOWCASE -->
  <section class="routes-section">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">CURATED NETWORKS</span>
        <h2>Popular Executive Coach Routes</h2>
        <p>Verified daily schedules across Nigeria's major commercial and administrative gateways.</p>
      </div>

      <div class="row g-4">
        <?php foreach ($popularRoutes as $route): ?>
          <div class="col-lg-4 col-md-6">
            <div class="route-card">
              <span class="route-badge"><?= e($route['badge']) ?></span>
              <div class="route-cities">
                <span><?= e($route['from']) ?></span>
                <i class="bi bi-arrow-right" style="font-size:14px;color:var(--gold);"></i>
                <span><?= e($route['to']) ?></span>
              </div>
              <div class="route-terminals">
                <div><i class="bi bi-geo-alt me-1 text-gold"></i> <strong>From:</strong> <?= e($route['from_terminal']) ?></div>
                <div><i class="bi bi-pin-map me-1 text-gold"></i> <strong>To:</strong> <?= e($route['to_terminal']) ?></div>
                <div class="mt-1"><i class="bi bi-clock me-1 text-muted"></i> Approx. <?= e($route['duration']) ?></div>
              </div>

              <div class="route-meta">
                <div class="route-fare">
                  <?= e($route['fare_display']) ?>
                  <small>Per passenger</small>
                </div>
                <button type="button" class="btn-select-route" 
                        data-from="<?= e($route['from']) ?>" 
                        data-to="<?= e($route['to']) ?>"
                        data-fare="<?= e($route['fare']) ?>">
                  Select Route
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- THE CJ TRAVELS ROAD ADVANTAGE -->
  <section class="container mb-5">
    <div class="advantage-box">
      <div class="row g-4 align-items-center">
        <div class="col-lg-6">
          <span style="font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:var(--gold-soft);font-weight:600;">THE CJ VOYAGE STANDARD</span>
          <h2>The Refined Way to Travel by <em>Road Transport</em></h2>
          <p style="color:rgba(253,251,246,0.8);font-size:15px;line-height:1.7;margin-bottom:20px;">
            We eliminate terminal queues, unreliable operators, and ambiguous departure times. CJ Travels curates only verified luxury transport partners with vetted safety protocols.
          </p>
          <a href="https://wa.me/<?= e($whatsappNumber) ?>" target="_blank" class="btn btn-navy" style="background:#25D366;border-color:#25D366;color:#fff;font-weight:600;padding:12px 24px;">
            <i class="bi bi-whatsapp me-2"></i> Speak with Road Concierge
          </a>
        </div>

        <div class="col-lg-6">
          <div class="adv-feat">
            <div class="adv-icon"><i class="bi bi-shield-check"></i></div>
            <div class="adv-text">
              <strong>Verified Captains &amp; Speed Governed Fleet</strong>
              <p>Every coach operates with real-time GPS tracking, professional licensed captains, and safety compliance.</p>
            </div>
          </div>

          <div class="adv-feat">
            <div class="adv-icon"><i class="bi bi-wifi"></i></div>
            <div class="adv-text">
              <strong>Executive Amenities &amp; Climate Control</strong>
              <p>Chilled air-conditioning, individual USB charging ports, reclining legroom, and onboard entertainment.</p>
            </div>
          </div>

          <div class="adv-feat">
            <div class="adv-icon"><i class="bi bi-whatsapp"></i></div>
            <div class="adv-text">
              <strong>Instant WhatsApp Seat Confirmation</strong>
              <p>Your itinerary is delivered right to your phone with terminal directions, boarding reference, and live status.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONFIRMATION & WHATSAPP MODAL POPUP -->
  <div class="bus-modal-backdrop" id="busSuccessModal" role="dialog" aria-modal="true" aria-labelledby="modalRefTitle">
    <div class="bus-modal-card">
      <button type="button" class="bus-modal-close" id="btnCloseSuccessModal" aria-label="Close modal">
        <i class="bi bi-x-lg"></i>
      </button>

      <div class="text-center">
        <div style="width:56px;height:56px;border-radius:50%;background:#e8f7ee;color:#25D366;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:14px;">
          <i class="bi bi-check2-circle"></i>
        </div>
        <h3 id="modalRefTitle" style="font-size:26px;color:var(--navy);margin-bottom:6px;">Booking Enquiry Saved!</h3>
        <p class="text-muted" style="font-size:13.5px;">Your bus ticket reservation reference has been registered in our system.</p>
      </div>

      <div class="bus-ticket-pass">
        <div class="pass-row">
          <span>Booking Reference</span>
          <strong id="modalPassRef" style="color:var(--navy);font-family:monospace;font-size:14px;">CJBUS-XXXXXX</strong>
        </div>
        <div class="pass-row">
          <span>Passenger Name</span>
          <strong id="modalPassName">—</strong>
        </div>
        <div class="pass-row">
          <span>Route</span>
          <strong id="modalPassRoute">—</strong>
        </div>
        <div class="pass-row">
          <span>Departure Date</span>
          <strong id="modalPassDepart">—</strong>
        </div>
        <div class="pass-row" id="modalPassReturnRow" style="display:none;">
          <span>Return Date</span>
          <strong id="modalPassReturn">—</strong>
        </div>
        <div class="pass-row">
          <span>Coach Class / Seats</span>
          <strong id="modalPassClass">—</strong>
        </div>
        <div class="pass-row" style="color:var(--navy);">
          <span>Estimated Total</span>
          <strong id="modalPassFare" style="color:var(--green);font-size:16px;">₦0.00</strong>
        </div>
      </div>

      <div class="d-grid gap-2">
        <a href="#" id="modalWhatsappLink" target="_blank" class="btn-whatsapp-submit" style="text-decoration:none;">
          <i class="bi bi-whatsapp"></i> Continue to WhatsApp Concierge
        </a>
        <button type="button" class="btn btn-outline-secondary" id="btnDoneBrowsing" style="height:48px;font-size:13px;text-transform:uppercase;letter-spacing:.08em;">
          Done &amp; Return to Page
        </button>
      </div>

      <p class="text-center text-muted mt-3 mb-0" style="font-size:11.5px;">
        If WhatsApp did not launch automatically, click the button above to start the chat.
      </p>
    </div>
  </div>

  <!-- SITE FOOTER -->
  <?php
  $footerPath = __DIR__ . '/../partials/footer.php';
  if (file_exists($footerPath)) {
      include $footerPath;
  }
  ?>

  <!-- COOKIE & PROMO MODALS -->
  <?php
  $cookiePath = __DIR__ . '/../partials/cookie-banner.php';
  if (file_exists($cookiePath)) {
      include $cookiePath;
  }
  $promoPath = __DIR__ . '/../partials/promo-modal.php';
  if (file_exists($promoPath)) {
      include $promoPath;
  }
  ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= asset('js/cookie-banner.js') ?>"></script>
  <script src="<?= asset('js/promo-banner.js') ?>"></script>

  <script>
    (function () {
      'use strict';

      // 1. Mobile navigation drawer toggle
      var mobileToggle = document.getElementById('mobileToggle');
      var mobileClose  = document.getElementById('mobileClose');
      var mobileNav    = document.getElementById('mobileNav');
      var mobileOverlay= document.getElementById('mobileOverlay');

      function openNav() {
        if (mobileNav) mobileNav.classList.add('open');
        if (mobileOverlay) mobileOverlay.classList.add('show');
      }
      function closeNav() {
        if (mobileNav) mobileNav.classList.remove('open');
        if (mobileOverlay) mobileOverlay.classList.remove('show');
      }
      if (mobileToggle) mobileToggle.addEventListener('click', openNav);
      if (mobileClose) mobileClose.addEventListener('click', closeNav);
      if (mobileOverlay) mobileOverlay.addEventListener('click', closeNav);

      // 2. Trip Type Switch (One-Way vs. Round-Trip)
      var tripTypeBtns = document.querySelectorAll('.trip-type-btn');
      var tripTypeInput = document.getElementById('tripTypeInput');
      var returnWrap = document.getElementById('returnDateWrap');
      var returnInput = document.getElementById('busReturnDate');
      var departTimeCol = document.getElementById('departTimeCol');
      var seatsCol = document.getElementById('seatsCol');

      tripTypeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          tripTypeBtns.forEach(function (b) { b.classList.remove('active'); });
          this.classList.add('active');
          var type = this.getAttribute('data-type');
          tripTypeInput.value = type;

          if (type === 'round-trip') {
            returnWrap.style.display = 'block';
            returnInput.setAttribute('required', 'required');
            departTimeCol.className = 'col-md-6';
            seatsCol.className = 'col-md-6';
          } else {
            returnWrap.style.display = 'none';
            returnInput.removeAttribute('required');
            departTimeCol.className = 'col-md-4';
            seatsCol.className = 'col-md-4';
          }
          calculateFare();
        });
      });

      // 3. Swap Departure and Destination Cities
      var btnSwap = document.getElementById('btnSwapCities');
      var selectFrom = document.getElementById('busFrom');
      var selectTo = document.getElementById('busTo');

      if (btnSwap && selectFrom && selectTo) {
        btnSwap.addEventListener('click', function () {
          var tempVal = selectFrom.value;
          selectFrom.value = selectTo.value;
          selectTo.value = tempVal;
          calculateFare();
        });
      }

      // 4. Date Syncing
      var departDateInput = document.getElementById('busDepartDate');
      if (departDateInput && returnInput) {
        departDateInput.addEventListener('change', function () {
          returnInput.min = this.value;
          if (returnInput.value && returnInput.value < this.value) {
            returnInput.value = this.value;
          }
          calculateFare();
        });
      }

      // 5. Live Fare Calculation
      var classSelect = document.getElementById('busCoachClass');
      var passengerSelect = document.getElementById('busPassengers');
      var fareAmountDisplay = document.getElementById('fareAmountDisplay');
      var fareMetaDescription = document.getElementById('fareMetaDescription');

      var routeMatrix = [
        { from: 'lagos', to: 'abuja', fare: 32000 },
        { from: 'lagos', to: 'port harcourt', fare: 28000 },
        { from: 'lagos', to: 'benin', fare: 18000 },
        { from: 'lagos', to: 'enugu', fare: 26000 },
        { from: 'lagos', to: 'owerri', fare: 26000 },
        { from: 'lagos', to: 'ibadan', fare: 10000 },
        { from: 'abuja', to: 'kaduna', fare: 8500 },
        { from: 'abuja', to: 'kano', fare: 16000 },
        { from: 'abuja', to: 'jos', fare: 12000 },
        { from: 'abuja', to: 'port harcourt', fare: 30000 },
        { from: 'lagos', to: 'asaba', fare: 24000 },
        { from: 'lagos', to: 'calabar', fare: 32000 }
      ];

      function calculateFare() {
        var fromVal = (selectFrom.value || '').toLowerCase();
        var toVal = (selectTo.value || '').toLowerCase();
        var isRound = (tripTypeInput.value === 'round-trip');
        var passengers = parseInt(passengerSelect.value, 10) || 1;
        var coach = classSelect.value;

        var base = 22000;
        for (var i = 0; i < routeMatrix.length; i++) {
          var item = routeMatrix[i];
          if ((fromVal.indexOf(item.from) !== -1 && toVal.indexOf(item.to) !== -1) ||
              (fromVal.indexOf(item.to) !== -1 && toVal.indexOf(item.from) !== -1)) {
            base = item.fare;
            break;
          }
        }

        var classMultiplier = 1.25;
        if (coach.indexOf('VIP') !== -1) classMultiplier = 1.50;
        else if (coach.indexOf('Standard') !== -1) classMultiplier = 1.00;

        var tripMultiplier = isRound ? 1.95 : 1.00;
        var total = Math.round(base * classMultiplier * tripMultiplier * passengers);

        if (fareAmountDisplay) {
          fareAmountDisplay.textContent = '₦' + total.toLocaleString('en-US');
        }
        if (fareMetaDescription) {
          var fromCity = selectFrom.value.split('(')[0].trim() || 'Origin';
          var toCity = selectTo.value.split('(')[0].trim() || 'Destination';
          fareMetaDescription.textContent = fromCity + ' → ' + toCity + ' · ' + passengers + ' Seat' + (passengers > 1 ? 's' : '') + ' · ' + (isRound ? 'Round-Trip' : 'One-Way');
        }
        return total;
      }

      if (selectFrom) selectFrom.addEventListener('change', calculateFare);
      if (selectTo) selectTo.addEventListener('change', calculateFare);
      if (classSelect) classSelect.addEventListener('change', calculateFare);
      if (passengerSelect) passengerSelect.addEventListener('change', calculateFare);

      // Read URL query parameters to prefill from homepage or quick links
      var urlParams = new URLSearchParams(window.location.search);
      var qFrom = urlParams.get('from');
      var qTo = urlParams.get('to');
      var qDepart = urlParams.get('depart');
      var qReturn = urlParams.get('return');
      var qSeats = urlParams.get('seats');

      if (qFrom) {
        for (var i = 0; i < selectFrom.options.length; i++) {
          if (selectFrom.options[i].value.toLowerCase().indexOf(qFrom.toLowerCase()) !== -1) {
            selectFrom.selectedIndex = i;
            break;
          }
        }
      }
      if (qTo) {
        for (var j = 0; j < selectTo.options.length; j++) {
          if (selectTo.options[j].value.toLowerCase().indexOf(qTo.toLowerCase()) !== -1) {
            selectTo.selectedIndex = j;
            break;
          }
        }
      }
      if (qDepart && departDateInput) {
        departDateInput.value = qDepart;
      }
      if (qReturn && returnInput) {
        var roundBtn = document.querySelector('.trip-type-btn[data-type="round-trip"]');
        if (roundBtn) roundBtn.click();
        returnInput.value = qReturn;
      }
      if (qSeats && passengerSelect) {
        passengerSelect.value = qSeats;
      }

      calculateFare();

      // 6. "Select Route" button handlers from popular routes cards
      document.querySelectorAll('.btn-select-route').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var from = btn.getAttribute('data-from').toLowerCase();
          var to = btn.getAttribute('data-to').toLowerCase();

          // Match options in selectFrom and selectTo
          for (var i = 0; i < selectFrom.options.length; i++) {
            if (selectFrom.options[i].value.toLowerCase().indexOf(from) !== -1) {
              selectFrom.selectedIndex = i;
              break;
            }
          }
          for (var j = 0; j < selectTo.options.length; j++) {
            if (selectTo.options[j].value.toLowerCase().indexOf(to) !== -1) {
              selectTo.selectedIndex = j;
              break;
            }
          }

          calculateFare();
          var card = document.getElementById('busBookingCard');
          if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });
      });

      // Quick route chips
      document.querySelectorAll('.quick-route-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
          var from = chip.getAttribute('data-from').toLowerCase();
          var to = chip.getAttribute('data-to').toLowerCase();

          for (var i = 0; i < selectFrom.options.length; i++) {
            if (selectFrom.options[i].value.toLowerCase().indexOf(from) !== -1) {
              selectFrom.selectedIndex = i;
              break;
            }
          }
          for (var j = 0; j < selectTo.options.length; j++) {
            if (selectTo.options[j].value.toLowerCase().indexOf(to) !== -1) {
              selectTo.selectedIndex = j;
              break;
            }
          }

          calculateFare();
          var card = document.getElementById('busBookingCard');
          if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });
      });

      // 7. Form Submission via AJAX to /api/bus/enquire & Launch WhatsApp
      var form = document.getElementById('busBookingForm');
      var errorAlert = document.getElementById('bookingErrorAlert');
      var submitBtn = document.getElementById('btnSubmitBooking');
      var successModal = document.getElementById('busSuccessModal');
      var closeSuccessModal = document.getElementById('btnCloseSuccessModal');
      var btnDoneBrowsing = document.getElementById('btnDoneBrowsing');
      var modalWhatsappLink = document.getElementById('modalWhatsappLink');

      function hideModal() {
        if (successModal) successModal.classList.remove('show');
      }
      if (closeSuccessModal) closeSuccessModal.addEventListener('click', hideModal);
      if (btnDoneBrowsing) btnDoneBrowsing.addEventListener('click', hideModal);

      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          if (errorAlert) {
            errorAlert.style.display = 'none';
            errorAlert.textContent = '';
          }

          var name = (document.getElementById('busName').value || '').trim();
          var phone = (document.getElementById('busPhone').value || '').trim();
          var from = selectFrom.value;
          var to = selectTo.value;
          var departDate = departDateInput.value;

          if (!name) {
            showError('Please enter your full name.');
            return;
          }
          if (!phone || phone.replace(/\D/g, '').length < 8) {
            showError('Please enter a valid phone number so we can reach you on WhatsApp.');
            return;
          }
          if (!from || !to) {
            showError('Please select both departure and destination terminals.');
            return;
          }
          if (from === to) {
            showError('Departure and destination cannot be the same city.');
            return;
          }
          if (!departDate) {
            showError('Please choose a departure date.');
            return;
          }

          var isRound = (tripTypeInput.value === 'round-trip');
          if (isRound) {
            var retDate = returnInput.value;
            if (!retDate) {
              showError('Please choose a return date for round-trip travel.');
              return;
            }
            if (retDate < departDate) {
              showError('Return date cannot be earlier than departure date.');
              return;
            }
          }

          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving & Opening WhatsApp...';

          var formData = new FormData(form);
          var payload = {};
          formData.forEach(function (value, key) {
            payload[key] = value;
          });

          var apiUrl = '<?= function_exists('route') ? route('api.bus.enquire') : '/api/bus/enquire' ?>';

          fetch(apiUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
          })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-whatsapp"></i> Reserve &amp; Send Enquiry via WhatsApp';

            if (!data.success) {
              showError(data.error || 'Failed to submit booking. Please try again or chat with us directly.');
              return;
            }

            // Fill modal pass info
            var b = data.booking || {};
            document.getElementById('modalPassRef').textContent = b.ref || 'CJBUS-CONFIRMED';
            document.getElementById('modalPassName').textContent = b.customer_name || name;
            document.getElementById('modalPassRoute').textContent = (b.departure_city || from).split('(')[0] + ' → ' + (b.destination_city || to).split('(')[0];
            document.getElementById('modalPassDepart').textContent = b.formattedDepartDate || b.departure_date;
            
            var modalReturnRow = document.getElementById('modalPassReturnRow');
            if (b.return_date && modalReturnRow) {
              modalReturnRow.style.display = 'flex';
              document.getElementById('modalPassReturn').textContent = b.formattedReturnDate || b.return_date;
            } else if (modalReturnRow) {
              modalReturnRow.style.display = 'none';
            }

            document.getElementById('modalPassClass').textContent = (b.coach_class || 'Executive Coach') + ' (' + (b.passengers || 1) + ' seat' + ((b.passengers || 1) > 1 ? 's' : '') + ')';
            document.getElementById('modalPassFare').textContent = b.formattedFare || ('₦' + (b.estimated_fare || 0).toLocaleString());

            if (modalWhatsappLink && data.whatsappUrl) {
              modalWhatsappLink.href = data.whatsappUrl;
            }

            // Show confirmation modal
            if (successModal) {
              successModal.classList.add('show');
            }

            // Open WhatsApp directly in new window/tab
            if (data.whatsappUrl) {
              window.open(data.whatsappUrl, '_blank');
            }
          })
          .catch(function (err) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-whatsapp"></i> Reserve &amp; Send Enquiry via WhatsApp';
            showError('Network error. Please try again or click the WhatsApp help button.');
          });
        });
      }

      function showError(msg) {
        if (errorAlert) {
          errorAlert.textContent = msg;
          errorAlert.style.display = 'block';
          errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          alert(msg);
        }
      }

    })();
  </script>
</body>
</html>
