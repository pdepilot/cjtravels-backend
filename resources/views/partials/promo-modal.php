<?php
/**
 * CJ Travels — World-Class Luxury Promo Banner & Registration Modal Partial
 */
$registerUrl = function_exists('route') ? route('register') : '/register';
$flightsUrl  = function_exists('route') ? route('flights') : '/flights';
$hotelsUrl   = function_exists('route') ? route('hotels') : '/hotels';
$promoApiUrl = function_exists('route') ? route('api.promo.register') : '/api/promo/register';

$promoCampaign = class_exists(\App\Services\RealtimeDataService::class)
    ? \App\Services\RealtimeDataService::getActivePromoCampaign()
    : [
        'title' => 'Unlock ₦50,000 Off Your Next Luxury Getaway',
        'subtitle' => 'Fly gracefully to London, Dubai, Paris, or explore paradise in Zanzibar. Register today to claim your limited-edition CJ Travels promotion credit.',
        'promo_code' => 'CJLUXE50K',
        'discount_amount' => '₦50,000',
        'flier_image' => null,
        'is_active' => 1,
    ];

$campaignTitle    = !empty($promoCampaign['title']) ? $promoCampaign['title'] : 'Unlock ₦50,000 Off Your Next Luxury Getaway';
$campaignSubtitle = !empty($promoCampaign['subtitle']) ? $promoCampaign['subtitle'] : 'Fly gracefully to London, Dubai, Paris, or explore paradise in Zanzibar.';
$promoCode        = !empty($promoCampaign['promo_code']) ? strtoupper($promoCampaign['promo_code']) : 'CJLUXE50K';
$discountAmount   = !empty($promoCampaign['discount_amount']) ? $promoCampaign['discount_amount'] : '₦50,000';
$flierImage       = !empty($promoCampaign['flier_image']) ? $promoCampaign['flier_image'] : null;
$isActive         = !empty($promoCampaign['is_active']);
$flierUrl         = $flierImage ? (function_exists('asset') ? asset($flierImage) : '/' . ltrim($flierImage, '/')) : null;
?>
<!-- CJ TRAVELS LUXURY PROMO BANNER SYSTEM -->
<div id="cjPromoContainer" style="position:fixed;inset:0;width:0;height:0;overflow:visible;pointer-events:none;z-index:99995;">

  <!-- FLOATING PROMO TRIGGER BADGE (WHEN BANNER IS MINIMIZED/CLOSED) -->
  <button type="button" id="cjPromoTrigger" class="cj-promo-trigger" style="display:none;pointer-events:auto;" aria-label="Open Special Travel Promotion">
    <span class="cj-promo-trigger-pulse"></span>
    <i class="bi bi-gift-fill"></i>
    <span class="cj-promo-trigger-label"><?= htmlspecialchars($discountAmount) ?> Travel Promo</span>
  </button>

  <!-- LUXURY PROMO MODAL BACKDROP -->
  <div id="cjPromoBackdrop" class="cj-promo-backdrop" role="dialog" aria-modal="true" aria-labelledby="cjPromoMainTitle" style="display:none;pointer-events:auto;">
    <div class="cj-promo-card <?= $flierUrl ? 'has-flier' : '' ?>" id="cjPromoCard">

      <!-- AMBIENT GLOW ACCENTS -->
      <div class="cj-promo-glow-top" aria-hidden="true"></div>
      <div class="cj-promo-glow-bottom" aria-hidden="true"></div>

      <!-- CLOSE / CANCEL BUTTON -->
      <button type="button" id="cjPromoCloseBtn" class="cj-promo-close" aria-label="Close promotion window" title="Close">
        <i class="bi bi-x-lg"></i>
      </button>

      <!-- CARD HEADER: EXCLUSIVE TAG & COUNTDOWN -->
      <div class="cj-promo-head">
        <div class="cj-promo-pill">
          <i class="bi bi-stars"></i>
          <span>EXCLUSIVE VOYAGE PRIVILEGE</span>
        </div>
        <div class="cj-promo-countdown" id="cjPromoCountdown" title="Offer validity countdown">
          <i class="bi bi-stopwatch"></i>
          <span class="cj-countdown-label">ENDS IN</span>
          <span class="cj-countdown-digits">
            <strong id="cjPromoHours">23</strong>h :
            <strong id="cjPromoMins">59</strong>m :
            <strong id="cjPromoSecs">45</strong>s
          </span>
        </div>
      </div>

      <!-- VIEW 1: HERO OFFER & APPLICATION ENTRY -->
      <div id="cjPromoHeroView" class="cj-promo-view active">
        <div class="cj-promo-hero-body">

          <?php if ($flierUrl): ?>
            <!-- UPLOADED PROMOTIONAL FLIER POSTER -->
            <div class="cj-promo-flier-wrap">
              <a href="<?= htmlspecialchars($flierUrl) ?>" target="_blank" class="cj-promo-flier-link" title="Click to view full promotional flier">
                <img src="<?= htmlspecialchars($flierUrl) ?>" alt="<?= htmlspecialchars($campaignTitle) ?> Flier" class="cj-promo-flier-img" loading="eager" />
                <div class="cj-promo-flier-overlay">
                  <span class="cj-promo-flier-badge"><i class="bi bi-stars"></i> Official Campaign Flier</span>
                  <span class="cj-promo-flier-zoom"><i class="bi bi-arrows-fullscreen"></i> View Poster</span>
                </div>
              </a>
            </div>
          <?php endif; ?>

          <h2 id="cjPromoMainTitle" class="cj-promo-title">
            <?= htmlspecialchars($campaignTitle) ?>
          </h2>
          <p class="cj-promo-sub">
            <?= htmlspecialchars($campaignSubtitle) ?>
          </p>

          <!-- VALUE PROPOSITION HIGHLIGHTS -->
          <div class="cj-promo-features">
            <div class="cj-promo-feat">
              <span class="cj-feat-icon"><i class="bi bi-airplane-engines-fill"></i></span>
              <div class="cj-feat-text">
                <strong><?= htmlspecialchars($discountAmount) ?> Credit</strong>
                <small>Valid on all international &amp; domestic routes</small>
              </div>
            </div>
            <div class="cj-promo-feat">
              <span class="cj-feat-icon"><i class="bi bi-building-fill-check"></i></span>
              <div class="cj-feat-text">
                <strong>25% Off 5-Star Stays</strong>
                <small>Partner hotels &amp; beachfront resorts</small>
              </div>
            </div>
            <div class="cj-promo-feat">
              <span class="cj-feat-icon"><i class="bi bi-credit-card-2-front-fill"></i></span>
              <div class="cj-feat-text">
                <strong>Pay Small Small</strong>
                <small>Combine with 3–12 months financing</small>
              </div>
            </div>
          </div>

          <!-- PROMO VOUCHER CODE BOX -->
          <div class="cj-promo-voucher-box">
            <div class="cj-voucher-left">
              <span class="cj-voucher-tag">PROMO CODE</span>
              <span class="cj-voucher-code" id="cjPromoCodeDisplay"><?= htmlspecialchars($promoCode) ?></span>
            </div>
            <button type="button" id="cjCopyPromoBtn" class="cj-voucher-copy" title="Copy Promo Code to Clipboard">
              <i class="bi bi-clipboard"></i> <span id="cjCopyText">Copy Code</span>
            </button>
          </div>

          <!-- PRIMARY ACTIONS -->
          <div class="cj-promo-actions">
            <button type="button" id="cjOpenClaimFormBtn" class="cj-promo-btn cj-promo-btn-primary">
              <i class="bi bi-gift-fill me-2"></i> Register &amp; Apply for Promo
            </button>
            <a href="<?= htmlspecialchars($registerUrl) ?>?promo=<?= urlencode($promoCode) ?>" class="cj-promo-btn cj-promo-btn-secondary" id="cjDirectRegisterBtn">
              <i class="bi bi-person-check me-2"></i> Create Account with Promo
            </a>
          </div>

          <div class="cj-promo-foot">
            <button type="button" id="cjPromoDismissTextBtn" class="cj-promo-dismiss-link">
              No thanks, I will pay full fare
            </button>
            <span class="cj-promo-terms-note">
              *T&amp;Cs apply. Valid for registered members booking eligible flights and partner luxury accommodations.
            </span>
          </div>
        </div>
      </div>

      <!-- VIEW 2: INSTANT REGISTRATION / APPLICATION FORM -->
      <div id="cjPromoFormView" class="cj-promo-view" style="display:none;">
        <div class="cj-promo-form-body">
          <div class="cj-form-header">
            <span class="cj-form-step">STEP 1 OF 1</span>
            <h3 class="cj-form-title">Claim &amp; Activate Your Promo Pass</h3>
            <p class="cj-form-sub">Enter your details below. Your <?= htmlspecialchars($discountAmount) ?> credit will be registered and activated immediately.</p>
          </div>

          <form id="cjPromoRegisterForm" novalidate>
            <div class="cj-form-group">
              <label for="cjPromoFullName" class="cj-promo-label">Full Name</label>
              <div class="cj-input-wrapper">
                <i class="bi bi-person cj-input-icon"></i>
                <input type="text" id="cjPromoFullName" class="cj-promo-input" placeholder="e.g. Somtochukwu Okafor" autocomplete="name" required />
              </div>
              <div class="cj-promo-error" id="cjErrName">Please enter your full name.</div>
            </div>

            <div class="cj-form-group">
              <label for="cjPromoEmail" class="cj-promo-label">Email Address</label>
              <div class="cj-input-wrapper">
                <i class="bi bi-envelope cj-input-icon"></i>
                <input type="email" id="cjPromoEmail" class="cj-promo-input" placeholder="e.g. somto@example.com" autocomplete="email" required />
              </div>
              <div class="cj-promo-error" id="cjErrEmail">Please enter a valid email address.</div>
            </div>

            <div class="cj-form-row">
              <div class="cj-form-group cj-col-half">
                <label for="cjPromoPhone" class="cj-promo-label">Phone Number (Optional)</label>
                <div class="cj-input-wrapper">
                  <i class="bi bi-telephone cj-input-icon"></i>
                  <input type="tel" id="cjPromoPhone" class="cj-promo-input" placeholder="+234 812 345 6789" autocomplete="tel" />
                </div>
              </div>

              <div class="cj-form-group cj-col-half">
                <label for="cjPromoDestination" class="cj-promo-label">Dream Destination</label>
                <div class="cj-input-wrapper">
                  <i class="bi bi-geo-alt cj-input-icon"></i>
                  <select id="cjPromoDestination" class="cj-promo-select">
                    <option value="London & Europe">London &amp; Europe</option>
                    <option value="Dubai & UAE" selected>Dubai &amp; UAE</option>
                    <option value="Paris & France">Paris &amp; France</option>
                    <option value="Maldives & Islands">Maldives &amp; Islands</option>
                    <option value="USA & Canada">USA &amp; Canada</option>
                    <option value="Domestic & Africa">Domestic &amp; Africa</option>
                  </select>
                </div>
              </div>
            </div>

            <div id="cjPromoStatus" class="cj-promo-status" role="alert" style="display:none;"></div>

            <div class="cj-form-actions">
              <button type="submit" id="cjSubmitPromoBtn" class="cj-promo-btn cj-promo-btn-primary">
                <i class="bi bi-shield-check me-2"></i> Activate My <?= htmlspecialchars($discountAmount) ?> Promo
              </button>
              <button type="button" id="cjBackToHeroBtn" class="cj-promo-btn cj-promo-btn-ghost">
                <i class="bi bi-arrow-left me-1"></i> Back to Offer
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- VIEW 3: SUCCESS CERTIFICATE / PROMO ACTIVATED -->
      <div id="cjPromoSuccessView" class="cj-promo-view" style="display:none;">
        <div class="cj-promo-success-body text-center">
          <div class="cj-success-badge-wrap">
            <span class="cj-success-badge-icon"><i class="bi bi-check2-circle"></i></span>
          </div>

          <h3 class="cj-success-title">Your <?= htmlspecialchars($discountAmount) ?> Promo Is Activated!</h3>
          <p class="cj-success-sub" id="cjSuccessPersonalizedText">
            Welcome aboard. Your exclusive travel voucher is ready for booking flights and boutique hotels.
          </p>

          <div class="cj-voucher-certificate">
            <div class="cj-cert-top">
              <span>CJ TRAVELS · LUXURY VOYAGE PASS</span>
              <span class="badge bg-gold">STATUS: ACTIVE</span>
            </div>
            <div class="cj-cert-body">
              <div class="cj-cert-val"><?= htmlspecialchars($discountAmount) ?> OFF</div>
              <div class="cj-cert-code">CODE: <strong id="cjSuccessCode"><?= htmlspecialchars($promoCode) ?></strong></div>
            </div>
            <div class="cj-cert-foot">
              <span>Reference: <strong id="cjSuccessRef">PRM-ACTIVE</strong></span>
              <span>Valid for 30 days</span>
            </div>
          </div>

          <div class="cj-promo-actions">
            <a href="<?= htmlspecialchars($flightsUrl) ?>?promo=<?= urlencode($promoCode) ?>" class="cj-promo-btn cj-promo-btn-primary">
              <i class="bi bi-airplane-engines me-2"></i> Book Flights with Voucher
            </a>
            <a href="<?= htmlspecialchars($hotelsUrl) ?>?promo=<?= urlencode($promoCode) ?>" class="cj-promo-btn cj-promo-btn-secondary">
              <i class="bi bi-building me-2"></i> Explore 5-Star Hotels
            </a>
          </div>

          <div class="mt-3">
            <button type="button" id="cjSuccessDoneBtn" class="cj-promo-dismiss-link">
              Done &amp; Continue Browsing
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  window.CJ_PROMO_CONFIG = {
    apiUrl: "<?= htmlspecialchars($promoApiUrl) ?>",
    registerUrl: "<?= htmlspecialchars($registerUrl) ?>",
    flightsUrl: "<?= htmlspecialchars($flightsUrl) ?>",
    hotelsUrl: "<?= htmlspecialchars($hotelsUrl) ?>",
    defaultCode: <?= json_encode($promoCode) ?>,
    discountAmount: <?= json_encode($discountAmount) ?>,
    disabled: <?= $isActive ? 'false' : 'true' ?>,
    hasFlier: <?= $flierUrl ? 'true' : 'false' ?>,
    delayMs: 2500
  };
</script>

