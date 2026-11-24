<?php
/**
 * CJ Travels — Cookie Consent Banner, Preferences Modal & Floating Badge Partial
 */
$privacyUrl = function_exists('route') ? route('privacy') : '/privacy';
$termsUrl = function_exists('route') ? route('terms') : '/terms';
?>
<!-- CJ TRAVELS COOKIE & PRIVACY CONSENT SYSTEM -->
<div id="cjCookieConsentContainer" style="position:fixed;inset:auto auto 0 0;width:0;height:0;overflow:visible;pointer-events:none;z-index:99990;">
  <!-- FLOATING COOKIE BANNER -->
  <aside id="cjCookieBanner" class="cj-cookie-banner" role="region" aria-label="Cookie and Privacy Consent" style="pointer-events:auto;">
    <div class="cj-cookie-header">
      <div class="cj-cookie-badge"><i class="bi bi-shield-check"></i> <span>Privacy &amp; Consent</span></div>
      <button type="button" id="cjCloseBannerBtn" class="cj-cookie-dismiss" aria-label="Dismiss cookie notice">&times;</button>
    </div>
    <h3 class="cj-cookie-title">Curating Your Voyage With Care</h3>
    <p class="cj-cookie-text">
      We use cookies to tailor your luxury travel experience, remember your flight and hotel preferences, and analyze site performance. By choosing <strong>"Accept All"</strong>, you consent to our use of cookies under our <a href="<?= htmlspecialchars($privacyUrl) ?>">Privacy Policy</a> and <a href="<?= htmlspecialchars($termsUrl) ?>">Terms of Use</a>.
    </p>
    <div class="cj-cookie-actions">
      <button type="button" id="cjAcceptAllBtn" class="cj-btn-cookie cj-btn-cookie-primary"><i class="bi bi-check2-circle"></i> Accept All</button>
      <button type="button" id="cjRejectBtn" class="cj-btn-cookie cj-btn-cookie-ghost">Essential Only</button>
      <button type="button" id="cjPreferencesBtn" class="cj-btn-cookie cj-btn-cookie-text"><i class="bi bi-sliders me-1"></i> Preferences</button>
    </div>
  </aside>

  <!-- CONTINUOUS FLOATING BADGE -->
  <button type="button" id="cjCookieTrigger" class="cj-cookie-trigger" style="display:none;pointer-events:auto;" aria-label="Cookie and Privacy Settings">
    <i class="bi bi-shield-lock-fill"></i>
    <span>Cookie Settings</span>
  </button>

  <!-- PREFERENCES MODAL -->
  <div id="cjCookieModalBackdrop" class="cj-cookie-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="cjModalTitle" style="display:none;pointer-events:auto;">
    <div class="cj-cookie-modal">
      <div class="cj-modal-head">
        <div>
          <h2 id="cjModalTitle" class="cj-modal-head-title"><i class="bi bi-sliders"></i> Cookie &amp; Privacy Preferences</h2>
          <p class="cj-modal-head-sub">Customize how CJ Travels handles your data and personalizes your journey.</p>
        </div>
        <button type="button" id="cjModalCloseBtn" class="cj-modal-close" aria-label="Close preferences">&times;</button>
      </div>

      <div class="cj-modal-body">
        <div class="cj-modal-intro">
          <strong>Transparency First:</strong> Essential cookies are required to process reservations, secure accounts, and protect against fraud. Optional cookies help us enhance search tools and bring you personalized flight and hotel rates. You can change your choices at any time.
        </div>

        <!-- 1. STRICTLY NECESSARY -->
        <div class="cj-cookie-group">
          <div class="cj-group-head">
            <div class="cj-group-info">
              <h4 class="cj-group-title">Strictly Necessary Cookies</h4>
              <span class="cj-status-badge cj-status-always"><i class="bi bi-lock-fill me-1"></i> Always Active</span>
            </div>
            <label class="cj-switch">
              <input type="checkbox" checked disabled />
              <span class="cj-slider"></span>
            </label>
          </div>
          <p class="cj-group-desc">Essential for website operation, secure user logins, booking basket persistence, and CSRF security. Without these, the booking system cannot function.</p>
          <div class="cj-group-cookies">Examples: <code>PHPSESSID</code>, <code>cj_session</code>, <code>csrf_token</code>, <code>cj_cookie_consent</code></div>
        </div>

        <!-- 2. FUNCTIONAL -->
        <div class="cj-cookie-group">
          <div class="cj-group-head">
            <div class="cj-group-info">
              <h4 class="cj-group-title">Functional &amp; Experience Cookies</h4>
            </div>
            <label class="cj-switch">
              <input type="checkbox" id="cjToggleFunctional" />
              <span class="cj-slider"></span>
            </label>
          </div>
          <p class="cj-group-desc">Remembers your departure airport, recent flight and hotel searches, currency choice, and guest count for effortless trip planning.</p>
          <div class="cj-group-cookies">Examples: <code>cj_recent_search</code>, <code>cj_currency</code>, <code>cj_guest_pref</code></div>
        </div>

        <!-- 3. ANALYTICS -->
        <div class="cj-cookie-group">
          <div class="cj-group-head">
            <div class="cj-group-info">
              <h4 class="cj-group-title">Analytics &amp; Performance Cookies</h4>
            </div>
            <label class="cj-switch">
              <input type="checkbox" id="cjToggleAnalytics" />
              <span class="cj-slider"></span>
            </label>
          </div>
          <p class="cj-group-desc">Measures site speed, page navigation flow, and reservation drop-offs so we can continuously optimize booking speed and reliability. Data is aggregated and anonymized.</p>
          <div class="cj-group-cookies">Examples: <code>cj_perf_metrics</code>, <code>_ga</code>, <code>_gid</code></div>
        </div>

        <!-- 4. MARKETING -->
        <div class="cj-cookie-group">
          <div class="cj-group-head">
            <div class="cj-group-info">
              <h4 class="cj-group-title">Marketing &amp; Tailored Travel Offers</h4>
            </div>
            <label class="cj-switch">
              <input type="checkbox" id="cjToggleMarketing" />
              <span class="cj-slider"></span>
            </label>
          </div>
          <p class="cj-group-desc">Enables tailored flight fare alerts, seasonal discount packages, and travel financing updates matching your travel interests across verified partner networks.</p>
          <div class="cj-group-cookies">Examples: <code>cj_fare_alert</code>, <code>cj_campaign_ref</code></div>
        </div>
      </div>

      <div class="cj-modal-foot">
        <div class="cj-modal-foot-left">
          <button type="button" id="cjModalRejectBtn" class="cj-btn-cookie cj-btn-cookie-ghost">Decline Optional</button>
        </div>
        <div class="cj-modal-foot-right">
          <button type="button" id="cjModalSaveBtn" class="cj-btn-cookie cj-btn-cookie-ghost">Save Choices</button>
          <button type="button" id="cjModalAcceptAllBtn" class="cj-btn-cookie cj-btn-cookie-primary"><i class="bi bi-check-all"></i> Accept All</button>
        </div>
      </div>
    </div>
  </div>

  <!-- SAVED TOAST -->
  <div id="cjCookieToast" class="cj-cookie-toast" role="status" aria-live="polite">
    <i class="bi bi-check-circle-fill"></i>
    <span id="cjCookieToastText">Preferences saved successfully.</span>
  </div>
</div>

