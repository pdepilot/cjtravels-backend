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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="<?= asset('images/logo-mark.png') ?>" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($title ?? 'Privacy Policy & Cookies — CJ Travels') ?></title>
  <meta name="description" content="Read the Privacy Policy and Cookie Notice for CJ Travels. Understand how we protect your personal travel data, comply with NDPR/GDPR, and handle cookie consent." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="<?= asset('css/cookie-banner.css') ?>" rel="stylesheet" />

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
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'DM Sans', sans-serif;
      font-weight: 400;
      color: var(--ink);
      background: var(--cream);
      line-height: 1.7;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 500;
      color: var(--navy);
      line-height: 1.2;
    }

    /* TOPBAR */
    .topbar {
      background: var(--navy);
      color: rgba(253, 251, 246, .75);
      font-size: 12px;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255, 255, 255, .08);
    }
    .topbar a { color: rgba(253, 251, 246, .75); text-decoration: none; transition: color .2s; }
    .topbar a:hover { color: var(--gold-soft); }

    /* HEADER */
    .site-header {
      background: var(--ivory);
      border-bottom: 1px solid var(--hairline-soft);
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: all .3s;
    }
    .site-header.scrolled {
      box-shadow: 0 10px 30px rgba(11, 30, 61, 0.08);
    }
    .header-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 0;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      text-decoration: none;
    }
    .brand-logo {
      height: 40px;
      width: auto;
      object-fit: contain;
    }
    .nav-links-desktop {
      display: flex;
      align-items: center;
      gap: 28px;
    }
    .nav-links-desktop a {
      color: var(--navy);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      letter-spacing: .02em;
      transition: color .2s;
    }
    .nav-links-desktop a:hover,
    .nav-links-desktop a.active {
      color: var(--gold);
    }

    /* HERO BANNER */
    .legal-hero {
      background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy) 60%, var(--navy-soft) 100%);
      color: var(--ivory);
      padding: 70px 0 60px;
      position: relative;
      border-bottom: 1px solid rgba(201, 165, 91, .3);
    }
    .legal-hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 80% 20%, rgba(201, 165, 91, 0.15) 0%, transparent 60%);
      pointer-events: none;
    }
    .eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .24em;
      text-transform: uppercase;
      color: var(--gold-soft);
      display: inline-block;
      margin-bottom: 12px;
    }
    .legal-hero h1 {
      color: var(--ivory);
      font-size: clamp(2.4rem, 4.5vw, 3.8rem);
      margin-bottom: 14px;
    }
    .legal-hero h1 em {
      font-style: italic;
      color: var(--gold-pale);
    }
    .legal-hero p {
      color: #d1d5db;
      font-size: 1.05rem;
      max-width: 680px;
      margin-bottom: 24px;
    }
    .legal-meta {
      display: flex;
      align-items: center;
      gap: 24px;
      flex-wrap: wrap;
      font-size: 13px;
      color: rgba(253, 251, 246, 0.7);
    }
    .legal-meta span {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .legal-meta i {
      color: var(--gold-soft);
    }

    /* MAIN CONTENT WRAPPER */
    .legal-section {
      padding: 60px 0 90px;
    }
    .legal-card {
      background: var(--ivory);
      border: 1px solid var(--hairline);
      border-radius: 18px;
      padding: 40px;
      box-shadow: 0 10px 40px rgba(11, 30, 61, 0.04);
      margin-bottom: 30px;
    }
    @media (max-width: 768px) {
      .legal-card { padding: 24px; }
    }

    /* SIDEBAR TOC */
    .toc-sticky {
      position: sticky;
      top: 100px;
      background: var(--ivory);
      border: 1px solid var(--hairline);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    .toc-title {
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .18em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 16px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--hairline-soft);
    }
    .toc-nav {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .toc-link {
      color: var(--ink-soft);
      font-size: 13.5px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all .2s;
      padding: 4px 0;
    }
    .toc-link:hover {
      color: var(--navy);
      transform: translateX(4px);
    }
    .toc-link i {
      font-size: 11px;
      color: var(--gold);
    }

    /* CALLOUT BOX */
    .callout-box {
      background: rgba(201, 165, 91, 0.08);
      border-left: 4px solid var(--gold);
      border-radius: 8px;
      padding: 18px 22px;
      margin: 24px 0;
      color: var(--navy);
      font-size: 0.95rem;
    }
    .callout-box strong {
      color: var(--navy-deep);
    }

    .cookie-action-box {
      background: linear-gradient(135deg, rgba(11, 30, 61, 0.95), rgba(6, 15, 33, 0.98));
      border: 1px solid var(--gold);
      border-radius: 14px;
      padding: 24px 28px;
      color: var(--ivory);
      margin: 26px 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }

    /* CONTENT STYLING */
    .legal-card h2 {
      font-size: 1.85rem;
      margin: 36px 0 16px 0;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--hairline-soft);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .legal-card h2:first-of-type {
      margin-top: 0;
    }
    .legal-card h2 .section-num {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      background: var(--navy);
      color: var(--gold-soft);
      border-radius: 50%;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
    }
    .legal-card h3 {
      font-size: 1.3rem;
      margin: 24px 0 10px 0;
      color: var(--navy-soft);
    }
    .legal-card p {
      color: var(--ink-soft);
      font-size: 0.98rem;
      margin-bottom: 16px;
    }
    .legal-card ul {
      color: var(--ink-soft);
      padding-left: 20px;
      margin-bottom: 20px;
    }
    .legal-card li {
      margin-bottom: 8px;
    }

    /* FOOTER */
    .site-footer {
      background: var(--navy-deep);
      color: rgba(253, 251, 246, 0.7);
      padding: 60px 0 30px;
      border-top: 1px solid rgba(201, 165, 91, 0.3);
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 40px;
      margin-bottom: 40px;
    }
    @media (max-width: 991px) {
      .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 575px) {
      .footer-grid { grid-template-columns: 1fr; }
    }
    .footer-heading {
      color: var(--ivory);
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.25rem;
      margin-bottom: 18px;
    }
    .footer-links {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .footer-links li { margin-bottom: 8px; }
    .footer-links a {
      color: rgba(253, 251, 246, 0.65);
      text-decoration: none;
      font-size: 13.5px;
      transition: color .2s;
    }
    .footer-links a:hover { color: var(--gold-soft); }
    .footer-bottom {
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding-top: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      font-size: 13px;
    }
    .footer-legal {
      display: flex;
      align-items: center;
      gap: 20px;
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .footer-legal a {
      color: rgba(253, 251, 246, 0.65);
      text-decoration: none;
      transition: color .2s;
    }
    .footer-legal a:hover { color: var(--gold-soft); }
  </style>
</head>

<body>

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="d-flex align-items-center gap-3">
        <span><i class="bi bi-geo-alt me-1 text-warning"></i> Owerri &amp; Lagos, Nigeria &bull; Worldwide Concierge</span>
        <span class="d-none d-md-inline">&bull;</span>
        <a href="tel:<?= e(cj_phone_tel()) ?>" class="d-none d-md-inline"><i class="bi bi-telephone me-1 text-warning"></i> <?= e(cj_phone_display()) ?></a>
      </div>
      <div>
        <a href="mailto:info@cjtravelsowerri.com"><i class="bi bi-envelope me-1 text-warning"></i> info@cjtravelsowerri.com</a>
      </div>
    </div>
  </div>

  <!-- HEADER -->
  <header class="site-header" id="siteHeader">
    <div class="container header-inner">
      <a class="brand" href="<?= route('home') ?>" aria-label="CJ Travels Home">
        <img src="<?= asset('images/cj-travels.jpg') ?>" alt="CJ Travels" class="brand-logo" />
      </a>
      <nav class="nav-links-desktop d-none d-lg-flex" aria-label="Main Navigation">
        <a href="<?= route('home') ?>">Home</a>
        <a href="<?= route('flights') ?>">Flights</a>
        <a href="<?= route('hotels') ?>">Hotels</a>
        <a href="<?= route('destinations') ?>">Destinations</a>
        <a href="<?= route('financing') ?>">Financing</a>
        <a href="<?= route('about') ?>">About</a>
        <a href="<?= route('contact') ?>">Contact</a>
      </nav>
      <div class="d-flex align-items-center gap-3">
        <a href="<?= route('contact') ?>#send-message" class="btn btn-sm btn-dark px-3 rounded-pill" style="background: var(--navy); border-color: var(--navy);">Book Now</a>
      </div>
    </div>
  </header>

  <!-- HERO -->
  <section class="legal-hero">
    <div class="container">
      <span class="eyebrow">Data Privacy &amp; Cookie Governance</span>
      <h1>Privacy <em>Policy</em> &amp; Cookie Notice</h1>
      <p>
        Your trust is the cornerstone of our travel agency. Learn how CJ Travels safeguards your personal information, booking data, passenger manifests, and cookie preferences in compliance with the Nigeria Data Protection Act (NDPA) and international standards.
      </p>
      <div class="legal-meta">
        <span><i class="bi bi-shield-lock-fill"></i> NDPA &amp; GDPR Aligned</span>
        <span><i class="bi bi-calendar3"></i> Last Updated: November 2026</span>
        <span><i class="bi bi-check-circle"></i> Complete Cookie Transparency</span>
      </div>
    </div>
  </section>

  <!-- CONTENT -->
  <section class="legal-section">
    <div class="container">
      <div class="row g-4">
        
        <!-- SIDEBAR TOC -->
        <div class="col-lg-4 col-xl-3">
          <div class="toc-sticky">
            <div class="toc-title">Table of Contents</div>
            <nav class="toc-nav">
              <a href="#priv-1" class="toc-link"><i class="bi bi-chevron-right"></i> 1. Overview &amp; Data Controller</a>
              <a href="#priv-2" class="toc-link"><i class="bi bi-chevron-right"></i> 2. Personal Data We Collect</a>
              <a href="#priv-3" class="toc-link"><i class="bi bi-chevron-right"></i> 3. How We Use Your Data</a>
              <a href="#priv-4" class="toc-link"><i class="bi bi-chevron-right"></i> 4. Lawful Bases for Processing</a>
              <a href="#priv-5" class="toc-link"><i class="bi bi-chevron-right"></i> 5. Third-Party Sharing</a>
              <a href="#priv-6" class="toc-link"><i class="bi bi-chevron-right"></i> 6. International Transfers</a>
              <a href="#priv-7" class="toc-link"><i class="bi bi-chevron-right"></i> 7. Retention &amp; Security</a>
              <a href="#priv-8" class="toc-link"><i class="bi bi-chevron-right"></i> 8. Your Data Rights</a>
              <a href="#priv-9" class="toc-link"><i class="bi bi-chevron-right"></i> 9. Cookie Policy &amp; Controls</a>
              <a href="#priv-10" class="toc-link"><i class="bi bi-chevron-right"></i> 10. Data Protection Officer</a>
            </nav>
            <div class="mt-4 pt-3 border-top">
              <button type="button" class="btn btn-warning btn-sm w-100 rounded-pill text-dark fw-bold mb-2 cj-open-cookie-settings" data-open-cookie-settings>
                <i class="bi bi-sliders me-1"></i> Cookie Settings
              </button>
              <a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                <i class="bi bi-printer me-1"></i> Print / Save PDF
              </a>
            </div>
          </div>
        </div>

        <!-- MAIN PRIVACY TEXT -->
        <div class="col-lg-8 col-xl-9">
          <div class="legal-card">
            
            <div class="callout-box">
              <strong>Your Privacy Commitment:</strong> We collect only the information necessary to reserve your flights, confirm luxury hotel stays, facilitate travel financing, and ensure flight security. We do not sell your personal data to data brokers. You hold continuous control over your optional cookies and marketing preferences.
            </div>

            <!-- SECTION 1 -->
            <section id="priv-1">
              <h2><span class="section-num">1</span> Overview &amp; Data Controller</h2>
              <p>
                This Privacy Policy explains how <strong>CJ Travels &amp; Tours Ltd</strong> ("CJ Travels", "we", "us", or "our") collects, processes, stores, and protects personal data obtained from visitors, registered clients, and travel financing applicants.
              </p>
              <p>
                CJ Travels operates as the primary <strong>Data Controller</strong> responsible for determining the purposes and means of processing your personal travel records under the Nigeria Data Protection Act 2023 (NDPA) and applicable international privacy directives.
              </p>
            </section>

            <!-- SECTION 2 -->
            <section id="priv-2">
              <h2><span class="section-num">2</span> Personal Data We Collect</h2>
              <p>
                Depending on the travel products or services you request, we collect the following categories of information:
              </p>
              <ul>
                <li><strong>Identity &amp; Contact Information:</strong> Full legal name, date of birth, gender, physical address, email address, and mobile phone number.</li>
                <li><strong>Travel Credentials &amp; Documents:</strong> Passport number, issuing country, nationality, passport expiration date, national identification number (NIN), and visa authorization details required for electronic passenger manifests (APIS).</li>
                <li><strong>Reservation &amp; Itinerary Data:</strong> Departure and arrival airports, flight numbers, seat selections, frequent flyer numbers, hotel room preferences, and dietary or special assistance requests.</li>
                <li><strong>Payment &amp; Transaction Details:</strong> Billing address, transaction reference codes, card brand, and masked account identifiers. Full payment card details are tokenized and processed directly by our PCI-DSS compliant payment gateways.</li>
                <li><strong>Financing Application Data:</strong> Employment status, monthly income range, bank verification details, and credit assessment references when applying for our "Pay Small Small" financing plans.</li>
                <li><strong>Device &amp; Telemetry Data:</strong> IP address, browser type, operating system, approximate geolocation, and cookie identifiers (as detailed in Section 9).</li>
              </ul>
            </section>

            <!-- SECTION 3 -->
            <section id="priv-3">
              <h2><span class="section-num">3</span> How We Use Your Data</h2>
              <p>We process your personal information strictly for legitimate commercial and travel operational purposes:</p>
              <ul>
                <li><strong>Issuing Tickets &amp; Vouchers:</strong> Confirming reservations with international airlines, hotels, and vehicle rental providers.</li>
                <li><strong>Customer Support &amp; Concierge:</strong> Sending flight schedule change alerts, boarding notifications, booking confirmations, and responding to emergency inquiries.</li>
                <li><strong>Travel Financing Processing:</strong> Reviewing creditworthiness, verifying applicant identity, setting up installment plans, and servicing loan repayments.</li>
                <li><strong>Security &amp; Fraud Prevention:</strong> Detecting fraudulent card transactions, verifying user identity, and preserving the integrity of user accounts.</li>
                <li><strong>Compliance &amp; Legal Duty:</strong> Adhering to mandatory civil aviation authority reporting, immigration declarations, and financial accounting standards.</li>
              </ul>
            </section>

            <!-- SECTION 4 -->
            <section id="priv-4">
              <h2><span class="section-num">4</span> Lawful Bases for Processing</h2>
              <p>Under data protection regulations, we process your personal data under the following legal bases:</p>
              <ul>
                <li><strong>Performance of a Contract:</strong> Processing necessary to fulfill your flight ticket, hotel stay, or travel package purchase.</li>
                <li><strong>Compliance with Legal Obligations:</strong> Mandated transmission of Advance Passenger Information (API/PNR) to border immigration and civil aviation authorities.</li>
                <li><strong>Legitimate Interests:</strong> Improving platform reliability, detecting unauthorized access, and analyzing booking engine performance.</li>
                <li><strong>Explicit Consent:</strong> Sending non-transactional marketing offers, personalized destination recommendations, or utilizing optional tracking cookies.</li>
              </ul>
            </section>

            <!-- SECTION 5 -->
            <section id="priv-5">
              <h2><span class="section-num">5</span> Third-Party Sharing &amp; Disclosures</h2>
              <p>We share your data only with verified parties required to execute your travel arrangements:</p>
              <ul>
                <li><strong>Airlines &amp; Global Distribution Systems (GDS):</strong> Such as Amadeus, Sabre, and commercial carriers for ticket issuance and passenger manifests.</li>
                <li><strong>Accommodations &amp; Tour Partners:</strong> Properties and local ground operators for check-in and room assignments.</li>
                <li><strong>Payment Processors:</strong> Central bank authorized payment gateways (e.g. Paystack, Flutterwave) for processing transactions.</li>
                <li><strong>Financing Partners:</strong> Licensed financial institutions partnering with CJ Travels to underwrite travel loans.</li>
                <li><strong>Border &amp; Government Agencies:</strong> Immigration, customs, and airport security agencies as required by statutory law.</li>
              </ul>
            </section>

            <!-- SECTION 6 -->
            <section id="priv-6">
              <h2><span class="section-num">6</span> International Data Transfers</h2>
              <p>
                International travel inherently involves transferring passenger details across borders to destination countries and international transit hubs. Where data is transferred outside Nigeria, we ensure adequate safeguards, such as Standard Contractual Clauses (SCCs) and adherence to international civil aviation treaties (ICAO/IATA standards).
              </p>
            </section>

            <!-- SECTION 7 -->
            <section id="priv-7">
              <h2><span class="section-num">7</span> Data Retention &amp; Security Measures</h2>
              <p>
                We retain your personal travel data only as long as necessary to fulfill booking contracts, satisfy tax and aviation record-keeping mandates (typically seven years for financial and ticketing audits), or resolve potential disputes.
              </p>
              <p>
                We employ industry-standard technical and organizational security protocols, including TLS 1.3 cryptographic transit encryption, strict database access controls, salted password hashing (Bcrypt/Argon2), and continuous network vulnerability monitoring.
              </p>
            </section>

            <!-- SECTION 8 -->
            <section id="priv-8">
              <h2><span class="section-num">8</span> Your Data Protection Rights</h2>
              <p>Under the NDPA and applicable global data protection regulations, you have enforceable legal rights:</p>
              <ul>
                <li><strong>Right of Access:</strong> Request a copy of the personal data we hold about you.</li>
                <li><strong>Right to Rectification:</strong> Request correction of inaccurate or incomplete travel profiles.</li>
                <li><strong>Right to Erasure ("Right to be Forgotten"):</strong> Request deletion of your records where retention is no longer legally mandated.</li>
                <li><strong>Right to Restrict or Object:</strong> Restrict or object to direct marketing communications and optional data analytics.</li>
                <li><strong>Right to Data Portability:</strong> Obtain your booking history in a structured, machine-readable format.</li>
                <li><strong>Right to Withdraw Consent:</strong> Withdraw consent for non-essential cookies and newsletters at any moment.</li>
              </ul>
            </section>

            <!-- SECTION 9 -->
            <section id="priv-9">
              <h2><span class="section-num">9</span> Cookie Policy &amp; Consent Management</h2>
              <p>
                Cookies are small encrypted text files deposited on your device when you visit our website. They enable session security, remember your recent flight itineraries, and help us analyze site speed.
              </p>

              <!-- INTERACTIVE PREFERENCE CONTROL CARD -->
              <div class="cookie-action-box">
                <div>
                  <h4 class="h5 mb-1 text-white"><i class="bi bi-shield-check text-warning me-2"></i>Manage Your Cookie Preferences</h4>
                  <p class="mb-0 text-white-50 small">Toggle optional analytics, functional memory, and tailored travel marketing cookies at any time.</p>
                </div>
                <button type="button" class="btn btn-warning rounded-pill px-4 py-2 text-dark fw-bold cj-open-cookie-settings" data-open-cookie-settings>
                  <i class="bi bi-sliders me-1"></i> Open Preferences
                </button>
              </div>

              <h3>Categories of Cookies We Utilize</h3>
              <ul>
                <li>
                  <strong>1. Strictly Necessary Cookies (Essential):</strong>
                  Required for user login sessions, CSRF token security, shopping cart integrity, and server load balancing. These cookies cannot be turned off as the core booking portal cannot function without them.
                </li>
                <li>
                  <strong>2. Functional &amp; Preference Cookies:</strong>
                  Remember your departure city, recent flight searches, preferred currency, and guest selections to simplify repeat bookings.
                </li>
                <li>
                  <strong>3. Analytics &amp; Performance Cookies:</strong>
                  Collect aggregated, anonymous statistics regarding page load times, popular travel routes, and booking funnel drop-offs so we can refine our technology.
                </li>
                <li>
                  <strong>4. Marketing &amp; Tailored Offer Cookies:</strong>
                  Allow CJ Travels and accredited airline/hotel partners to display seasonal discounts, flight fare drop alerts, and bespoke vacation recommendations aligned with your travel interests.
                </li>
              </ul>

              <p>
                You can also configure your web browser (Chrome, Safari, Edge, Firefox) to block or alert you about cookies, though certain portal features may function sub-optimally.
              </p>
            </section>

            <!-- SECTION 10 -->
            <section id="priv-10">
              <h2><span class="section-num">10</span> Data Protection Officer (DPO) &amp; Contact</h2>
              <p>
                If you have questions regarding this Privacy Policy, wish to exercise any of your data protection rights, or would like to submit a data inquiry, please contact our designated Data Protection Officer:
              </p>
              <div class="mt-4 p-4 rounded-3" style="background: rgba(11, 30, 61, 0.04); border: 1px solid var(--hairline);">
                <h4 class="h6 text-uppercase fw-bold text-dark mb-2">Office of the Data Protection Officer</h4>
                <p class="mb-1 text-muted"><strong>CJ Travels &amp; Tours Ltd</strong></p>
                <p class="mb-1 text-muted">Aviation House, Owerri &bull; Lagos Liaison Office, Nigeria</p>
                <p class="mb-1 text-muted">Email: <a href="mailto:privacy@cjtravelsowerri.com" class="text-navy text-decoration-none fw-medium">privacy@cjtravelsowerri.com</a> &bull; Phone: <?= e(cj_phone_display()) ?></p>
                <p class="mb-0 text-muted small mt-2">You also retain the right to lodge a complaint with the Nigeria Data Protection Commission (NDPC) at <a href="https://ndpc.gov.ng" target="_blank" rel="noopener" class="text-decoration-underline text-secondary">ndpc.gov.ng</a>.</p>
              </div>
            </section>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <a class="brand d-inline-block mb-3" href="<?= route('home') ?>">
            <img src="<?= asset('images/logo-white.png') ?>" alt="CJ Travels" class="brand-logo" style="filter: brightness(1.2);" />
          </a>
          <p class="small text-white-50">
            A luxury online travel agency delivering bespoke flights, curated hotel reservations, global destinations, and travel financing.
          </p>
        </div>
        <div>
          <h4 class="footer-heading">Services</h4>
          <ul class="footer-links">
            <li><a href="<?= route('flights') ?>">Flight Bookings</a></li>
            <li><a href="<?= route('hotels') ?>">Hotels &amp; Resorts</a></li>
            <li><a href="<?= route('destinations') ?>">Curated Destinations</a></li>
            <li><a href="<?= route('financing') ?>">Travel Financing</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-heading">Portals</h4>
          <ul class="footer-links">
            <li><a href="<?= route('contact') ?>">Contact</a></li>
            <li><a href="<?= route('admin.login') ?>">Admin Console</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-heading">Legal &amp; Privacy</h4>
          <ul class="footer-links">
            <li><a href="<?= route('privacy') ?>" class="text-warning">Privacy Policy</a></li>
            <li><a href="<?= route('terms') ?>">Terms of Use</a></li>
            <li><a href="javascript:void(0)" class="cj-open-cookie-settings" data-open-cookie-settings>Cookie Preferences</a></li>
            <li><a href="<?= route('contact') ?>">Support &amp; FAQ</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p class="mb-0">&copy; <?= date('Y') ?> CJ Travels &amp; Tours Ltd. All rights reserved.</p>
        <ul class="footer-legal">
          <li><a href="<?= route('privacy') ?>">Privacy Policy</a></li>
          <li><a href="<?= route('terms') ?>">Terms of Use</a></li>
          <li><a href="javascript:void(0)" class="cj-open-cookie-settings" data-open-cookie-settings>Cookie Settings</a></li>
          <li><a href="<?= route('contact') ?>">Help Center</a></li>
        </ul>
      </div>
    </div>
  </footer>

  <!-- COOKIE & PRIVACY MODAL PARTIAL -->
  <?php if (file_exists(__DIR__ . '/../partials/cookie-banner.php')): ?>
    <?php include __DIR__ . '/../partials/cookie-banner.php'; ?>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= asset('js/cookie-banner.js') ?>"></script>
  <script>
    var header = document.getElementById('siteHeader');
    if (header) {
      window.addEventListener('scroll', function() {
        header.classList.toggle('scrolled', window.scrollY > 10);
      }, { passive: true });
    }
  </script>
</body>
</html>

