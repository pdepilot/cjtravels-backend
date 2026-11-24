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
  <title><?= e($title ?? 'Terms of Use — CJ Travels') ?></title>
  <meta name="description" content="Read the Terms of Use and Booking Conditions for CJ Travels. Transparent policies for flights, luxury hotels, curated destinations, and travel financing." />

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
      <span class="eyebrow">Legal Framework &amp; Client Agreement</span>
      <h1>Terms of <em>Use</em> &amp; Booking Conditions</h1>
      <p>
        Please review our standard terms of service. By booking flights, luxury accommodations, curated excursions, or applying for travel financing through CJ Travels, you enter into a legally binding agreement.
      </p>
      <div class="legal-meta">
        <span><i class="bi bi-shield-check"></i> Standard Operating Terms</span>
        <span><i class="bi bi-calendar3"></i> Last Updated: November 2026</span>
        <span><i class="bi bi-geo-alt"></i> Governed by Nigerian Aviation &amp; Commercial Law</span>
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
              <a href="#sec-1" class="toc-link"><i class="bi bi-chevron-right"></i> 1. Introduction &amp; Acceptance</a>
              <a href="#sec-2" class="toc-link"><i class="bi bi-chevron-right"></i> 2. Agency Role &amp; Scope</a>
              <a href="#sec-3" class="toc-link"><i class="bi bi-chevron-right"></i> 3. User Accounts &amp; Security</a>
              <a href="#sec-4" class="toc-link"><i class="bi bi-chevron-right"></i> 4. Booking, Fares &amp; Payment</a>
              <a href="#sec-5" class="toc-link"><i class="bi bi-chevron-right"></i> 5. Cancellations &amp; Refunds</a>
              <a href="#sec-6" class="toc-link"><i class="bi bi-chevron-right"></i> 6. Passports, Visas &amp; Health</a>
              <a href="#sec-7" class="toc-link"><i class="bi bi-chevron-right"></i> 7. Travel Financing (Loans)</a>
              <a href="#sec-8" class="toc-link"><i class="bi bi-chevron-right"></i> 8. Limitation of Liability</a>
              <a href="#sec-9" class="toc-link"><i class="bi bi-chevron-right"></i> 9. Intellectual Property</a>
              <a href="#sec-10" class="toc-link"><i class="bi bi-chevron-right"></i> 10. Governing Law &amp; Contact</a>
            </nav>
            <div class="mt-4 pt-3 border-top">
              <a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                <i class="bi bi-printer me-1"></i> Print / Save PDF
              </a>
            </div>
          </div>
        </div>

        <!-- MAIN TERMS TEXT -->
        <div class="col-lg-8 col-xl-9">
          <div class="legal-card">
            
            <div class="callout-box">
              <strong>Summary in Plain English:</strong> CJ Travels acts as an authorized travel booking agent and financing facilitator. All airline tickets and hotel reservations are subject to carrier and property tariffs. Passports must have at least 6 months validity. Financing agreements require timely repayments according to agreed installment schedules.
            </div>

            <!-- SECTION 1 -->
            <section id="sec-1">
              <h2><span class="section-num">1</span> Introduction &amp; Acceptance</h2>
              <p>
                Welcome to <strong>CJ Travels</strong> (operated by CJ Travels &amp; Tours Ltd, registered under the laws of the Federal Republic of Nigeria). These Terms of Use ("Terms") govern your access to and utilization of our website, mobile interfaces, booking engines, and concierge services.
              </p>
              <p>
                By accessing, browsing, submitting inquiries, or finalizing reservations through this platform, you acknowledge that you have read, understood, and agree to be legally bound by these Terms, together with our <a href="<?= route('privacy') ?>" class="text-decoration-underline text-warning-emphasis">Privacy Policy</a> and cookie notices. If you do not accept these Terms in full, you must discontinue using our services immediately.
              </p>
            </section>

            <!-- SECTION 2 -->
            <section id="sec-2">
              <h2><span class="section-num">2</span> Agency Role &amp; Service Scope</h2>
              <p>
                CJ Travels operates as an intermediary travel agent connecting customers with third-party service providers, including commercial airlines, hotel operators, charter companies, ground transportation providers, and licensed credit lenders.
              </p>
              <ul>
                <li><strong>Carrier Precedence:</strong> When you purchase an airline ticket through CJ Travels, your carriage contract is formed directly with the respective airline and is subject to that airline’s Conditions of Carriage and IATA regulations.</li>
                <li><strong>Accommodations:</strong> Hotel bookings are governed by individual property policies regarding check-in times, incidentals, damage deposits, and cancellation deadlines.</li>
                <li><strong>Curated Experiences:</strong> Sightseeing tours, destination packages, and private transfers are coordinated in partnership with thoroughly vetted local ground operators.</li>
              </ul>
            </section>

            <!-- SECTION 3 -->
            <section id="sec-3">
              <h2><span class="section-num">3</span> User Accounts, Identity &amp; Security</h2>
              <p>
                To access client dashboards, manage reservations, or request travel financing, you may be required to register a customer account. You agree to provide accurate, truthful, and complete details at all times.
              </p>
              <ul>
                <li>You are solely responsible for maintaining the confidentiality of your account credentials, login passwords, and authentication tokens.</li>
                <li>You agree to notify CJ Travels immediately upon discovering any unauthorized access or security breach involving your account.</li>
                <li>Passenger names supplied during reservation must match the traveler’s passport or government photo ID exactly. CJ Travels is not responsible for denial of boarding resulting from passenger name discrepancies.</li>
              </ul>
            </section>

            <!-- SECTION 4 -->
            <section id="sec-4">
              <h2><span class="section-num">4</span> Booking, Pricing, Currency &amp; Payment</h2>
              <p>
                All fares, hotel room rates, package prices, and taxes displayed on our platform are subject to real-time availability and carrier price modifications until the transaction is successfully completed and an electronic ticket (e-ticket) or hotel voucher is issued.
              </p>
              <ul>
                <li><strong>Currency:</strong> Quoted prices are displayed in Nigerian Naira (NGN), United States Dollars (USD), or other selected currencies. Where conversions apply, the exchange rate utilized at the precise moment of settlement will apply.</li>
                <li><strong>Payment Methods:</strong> We accept secured electronic card payments (Mastercard, Visa, Verve), direct bank transfers, and verified corporate invoicing. All online payment data is encrypted and processed via PCI-DSS compliant payment gateways.</li>
                <li><strong>Price Adjustments:</strong> Airlines reserve the statutory right to re-quote or re-issue un-ticketed flight segments in the event of fuel surcharge hikes, airport tax changes, or currency devaluations occurring prior to ticket issuance.</li>
              </ul>
            </section>

            <!-- SECTION 5 -->
            <section id="sec-5">
              <h2><span class="section-num">5</span> Cancellations, Amendments &amp; Refunds</h2>
              <p>
                Cancellation, date change, and refund terms vary widely depending on the fare class and hotel rate booked.
              </p>
              <ul>
                <li><strong>Non-Refundable Fares:</strong> Highly discounted economy fares and non-flexible hotel promotional rates are strictly non-refundable and non-transferable under carrier rules.</li>
                <li><strong>Refund Processing:</strong> Where an airline or property permits a refund, CJ Travels will submit the application on your behalf. Carrier refund approval periods typically range between 14 to 45 business days. Approved refunds are disbursed back to the original funding source minus airline administrative fees and CJ Travels agency processing charges.</li>
                <li><strong>Flight Schedule Changes &amp; Delays:</strong> Airlines hold unilateral authority to alter flight schedules, equipment, or routing. In cases of involuntary cancellation, we will assist you with rebooking or submitting involuntary refund claims per IATA guidelines.</li>
              </ul>
            </section>

            <!-- SECTION 6 -->
            <section id="sec-6">
              <h2><span class="section-num">6</span> Passports, Visas &amp; Health Regulations</h2>
              <p>
                It is the traveler’s personal and legal responsibility to ensure that all travel documents are up to date and comply with transit and immigration requirements.
              </p>
              <ul>
                <li><strong>Passport Validity:</strong> Most sovereign nations require passports to remain valid for at least six (6) months beyond your scheduled date of return.</li>
                <li><strong>Visas &amp; Transit Permits:</strong> Certain flight routes with intermediate technical or refueling stops necessitate airport transit visas. CJ Travels provides general guidance, but the ultimate duty to procure valid entry visas rests with the traveler.</li>
                <li><strong>Health &amp; Vaccinations:</strong> Compliance with vaccination protocols (e.g., Yellow Fever, COVID-19, Meningitis) and medical declarations is compulsory where required by destination authorities.</li>
              </ul>
            </section>

            <!-- SECTION 7 -->
            <section id="sec-7">
              <h2><span class="section-num">7</span> Travel Financing &amp; Pay Small Small (Loans)</h2>
              <p>
                Our "Pay Small Small" travel financing products allow qualifying travelers to spread the cost of their flights and holidays across structured monthly installments.
              </p>
              <ul>
                <li><strong>Credit Assessment:</strong> Travel financing is provided in conjunction with licensed non-bank financial institutions. Approval is contingent on credit evaluation, income verification, and identity screening.</li>
                <li><strong>Repayment Default:</strong> Failure to meet scheduled repayment deadlines may result in immediate cancellation of pending flight itineraries, forfeiture of down-payments, suspension of travel privileges, and reporting to authorized credit rating agencies.</li>
                <li><strong>Interest &amp; Fees:</strong> Applicable interest rates, platform processing fees, and late repayment surcharges are explicitly detailed prior to contract signature.</li>
              </ul>
            </section>

            <!-- SECTION 8 -->
            <section id="sec-8">
              <h2><span class="section-num">8</span> Limitation of Liability &amp; Force Majeure</h2>
              <p>
                To the fullest extent permissible by applicable law, CJ Travels shall not be held liable for any direct, indirect, punitive, or consequential damages resulting from:
              </p>
              <ul>
                <li>Acts of God, adverse weather conditions, natural disasters, epidemics, pandemics, or civil unrest (Force Majeure events).</li>
                <li>Airline strikes, technical groundings, mechanical delays, luggage loss, or pilot schedule timeouts.</li>
                <li>Denial of boarding by airlines or refusal of entry by border immigration officials.</li>
                <li>Personal injury or property loss occurring on hotel premises or during third-party tours. We strongly advise all clients to procure comprehensive travel and medical insurance prior to departure.</li>
              </ul>
            </section>

            <!-- SECTION 9 -->
            <section id="sec-9">
              <h2><span class="section-num">9</span> Intellectual Property &amp; Acceptable Use</h2>
              <p>
                All trademarks, trade names, imagery, branding logos, text, design systems, algorithms, and software code underpinning CJ Travels are the exclusive property of CJ Travels &amp; Tours Ltd or its licensors.
              </p>
              <p>
                You may not scrape, harvest, reverse engineer, or deploy automated bots against our flight inventory or search interfaces without our prior written consent.
              </p>
            </section>

            <!-- SECTION 10 -->
            <section id="sec-10">
              <h2><span class="section-num">10</span> Governing Law, Dispute Resolution &amp; Contact</h2>
              <p>
                These Terms shall be governed by and construed in accordance with the laws of the Federal Republic of Nigeria. Any controversy or dispute arising out of or related to these Terms shall first be submitted to good-faith mutual mediation. Where mediation fails within 30 calendar days, the dispute shall be resolved through arbitration in Nigeria under the Arbitration and Mediation Act.
              </p>
              <div class="mt-4 p-4 rounded-3" style="background: rgba(11, 30, 61, 0.04); border: 1px solid var(--hairline);">
                <h4 class="h6 text-uppercase fw-bold text-dark mb-2">Legal Compliance &amp; Client Inquiries</h4>
                <p class="mb-1 text-muted"><strong>CJ Travels &amp; Tours Ltd</strong></p>
                <p class="mb-1 text-muted">Aviation House, Owerri &bull; Lagos Liaison Office, Nigeria</p>
                <p class="mb-1 text-muted">Email: <a href="mailto:legal@cjtravelsowerri.com" class="text-navy text-decoration-none fw-medium">legal@cjtravelsowerri.com</a> &bull; Phone: <?= e(cj_phone_display()) ?></p>
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
            <li><a href="<?= route('terms') ?>" class="text-warning">Terms of Use</a></li>
            <li><a href="<?= route('privacy') ?>">Privacy Policy</a></li>
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

