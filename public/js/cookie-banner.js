/**
 * CJ Travels — Luxury Cookie Consent & Privacy Preference Engine
 * Compliant with GDPR, NDPR, and modern privacy frameworks.
 */
(function (window, document) {
  'use strict';

  var STORAGE_KEY = 'cj_cookie_consent';
  var COOKIE_NAME = 'cj_cookie_consent';
  var CONSENT_VERSION = '1.0';

  var DEFAULT_CONSENT = {
    version: CONSENT_VERSION,
    timestamp: null,
    essential: true,
    functional: false,
    analytics: false,
    marketing: false
  };

  var CJCookieConsent = {
    state: null,

    /**
     * Initialize the cookie consent manager
     */
    init: function () {
      this.state = this.loadConsent();
      this.ensureDomElements();
      this.bindEvents();

      if (!this.hasConsented()) {
        // Show banner smoothly after page load
        var self = this;
        setTimeout(function () {
          self.showBanner();
        }, 700);
      } else {
        this.hideBanner();
        this.showTrigger();
      }
    },

    /**
     * Check if user has explicitly saved consent
     */
    hasConsented: function () {
      return this.state && !!this.state.timestamp;
    },

    /**
     * Get current consent object
     */
    getConsent: function () {
      return this.state || Object.assign({}, DEFAULT_CONSENT);
    },

    /**
     * Load consent from LocalStorage with cookie fallback
     */
    loadConsent: function () {
      try {
        var raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
          var parsed = JSON.parse(raw);
          if (parsed && parsed.version === CONSENT_VERSION) {
            return parsed;
          }
        }
      } catch (e) {
        // LocalStorage blocked or restricted
      }

      // Fallback: check document.cookie
      var cookieVal = this.getCookie(COOKIE_NAME);
      if (cookieVal) {
        try {
          var parsedCookie = JSON.parse(decodeURIComponent(cookieVal));
          if (parsedCookie && parsedCookie.version === CONSENT_VERSION) {
            return parsedCookie;
          }
        } catch (e) {}
      }

      return Object.assign({}, DEFAULT_CONSENT);
    },

    /**
     * Save consent to LocalStorage, Cookie and dispatch custom event
     */
    saveConsent: function (consent) {
      consent.version = CONSENT_VERSION;
      consent.timestamp = new Date().toISOString();
      consent.essential = true; // Always strictly necessary

      this.state = consent;

      // LocalStorage
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
      } catch (e) {}

      // First-party Cookie (1 year duration, SameSite=Lax)
      this.setCookie(COOKIE_NAME, encodeURIComponent(JSON.stringify(consent)), 365);

      // Dispatch global DOM event for other scripts/analytics to react
      try {
        var evt = new CustomEvent('cj:consent-updated', { detail: consent });
        window.dispatchEvent(evt);
      } catch (e) {}

      this.hideBanner();
      this.showTrigger();
    },

    /**
     * Accept All Cookies
     */
    acceptAll: function () {
      this.saveConsent({
        essential: true,
        functional: true,
        analytics: true,
        marketing: true
      });
      this.closeModal();
      this.showToast('All cookies accepted. Enjoy your journey!');
    },

    /**
     * Reject Non-Essential (Essential only)
     */
    rejectNonEssential: function () {
      this.saveConsent({
        essential: true,
        functional: false,
        analytics: false,
        marketing: false
      });
      this.closeModal();
      this.showToast('Essential cookies only enabled.');
    },

    /**
     * Save choices from Preferences Modal
     */
    savePreferencesFromModal: function () {
      var functional = document.getElementById('cjToggleFunctional') ? document.getElementById('cjToggleFunctional').checked : false;
      var analytics = document.getElementById('cjToggleAnalytics') ? document.getElementById('cjToggleAnalytics').checked : false;
      var marketing = document.getElementById('cjToggleMarketing') ? document.getElementById('cjToggleMarketing').checked : false;

      this.saveConsent({
        essential: true,
        functional: functional,
        analytics: analytics,
        marketing: marketing
      });
      this.closeModal();
      this.showToast('Your privacy preferences have been saved.');
    },

    /**
     * Show the main floating cookie banner
     */
    showBanner: function () {
      var banner = document.getElementById('cjCookieBanner');
      if (banner) {
        banner.classList.add('cj-visible');
        document.body.classList.add('cj-cookie-banner-active');
      }
    },

    /**
     * Hide the main floating cookie banner
     */
    hideBanner: function () {
      var banner = document.getElementById('cjCookieBanner');
      if (banner) {
        banner.classList.remove('cj-visible');
        document.body.classList.remove('cj-cookie-banner-active');
      }
    },

    /**
     * Show continuous floating badge trigger
     */
    showTrigger: function () {
      var trigger = document.getElementById('cjCookieTrigger');
      if (trigger) {
        trigger.style.display = 'inline-flex';
      }
    },

    /**
     * Open Preferences Modal
     */
    openPreferences: function () {
      var modal = document.getElementById('cjCookieModalBackdrop');
      if (!modal) {
        this.ensureDomElements();
        modal = document.getElementById('cjCookieModalBackdrop');
      }

      if (modal) {
        // Sync checkboxes with current consent
        var c = this.getConsent();
        var fToggle = document.getElementById('cjToggleFunctional');
        var aToggle = document.getElementById('cjToggleAnalytics');
        var mToggle = document.getElementById('cjToggleMarketing');

        if (fToggle) fToggle.checked = !!c.functional;
        if (aToggle) aToggle.checked = !!c.analytics;
        if (mToggle) mToggle.checked = !!c.marketing;

        modal.style.display = 'flex';
        modal.classList.add('cj-modal-visible');
        document.body.style.overflow = 'hidden';
      }
    },

    /**
     * Close Preferences Modal
     */
    closeModal: function () {
      var modal = document.getElementById('cjCookieModalBackdrop');
      if (modal) {
        modal.classList.remove('cj-modal-visible');
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    },

    /**
     * Temporary Toast Message
     */
    showToast: function (msg) {
      var toast = document.getElementById('cjCookieToast');
      var toastText = document.getElementById('cjCookieToastText');
      if (!toast) return;

      if (toastText) {
        toastText.textContent = msg;
      }
      toast.classList.add('cj-toast-visible');
      setTimeout(function () {
        toast.classList.remove('cj-toast-visible');
      }, 3500);
    },

    /**
     * Cookie Helpers
     */
    setCookie: function (name, value, days) {
      var expires = '';
      if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
      }
      document.cookie = name + '=' + (value || '') + expires + '; path=/; SameSite=Lax';
    },

    getCookie: function (name) {
      var nameEQ = name + '=';
      var ca = document.cookie.split(';');
      for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
      }
      return null;
    },

    /**
     * Ensure DOM elements (Banner, Modal, Badge, Toast) are present.
     * If not rendered by PHP, inject dynamically!
     */
    ensureDomElements: function () {
      if (document.getElementById('cjCookieBanner')) {
        return; // Already present in DOM
      }

      var wrapper = document.createElement('div');
      wrapper.id = 'cjCookieConsentContainer';
      wrapper.style.cssText = 'position:fixed;inset:auto auto 0 0;width:0;height:0;overflow:visible;pointer-events:none;z-index:99990;';
      wrapper.innerHTML = [
        '<!-- FLOATING COOKIE BANNER -->',
        '<div id="cjCookieBanner" class="cj-cookie-banner" role="region" aria-label="Cookie and Privacy Consent" style="pointer-events:auto;">',
        '  <div class="cj-cookie-header">',
        '    <div class="cj-cookie-badge"><i class="bi bi-shield-check"></i> <span>Privacy & Consent</span></div>',
        '    <button type="button" id="cjCloseBannerBtn" class="cj-cookie-dismiss" aria-label="Dismiss banner">&times;</button>',
        '  </div>',
        '  <h3 class="cj-cookie-title">Curating Your Voyage With Care</h3>',
        '  <p class="cj-cookie-text">',
        '    We use cookies to tailor your luxury travel experience, remember your flight and hotel preferences, and analyze site performance. By choosing <strong>"Accept All"</strong>, you consent to our use of cookies under our <a href="privacy.php">Privacy Policy</a> and <a href="terms.php">Terms of Use</a>.',
        '  </p>',
        '  <div class="cj-cookie-actions">',
        '    <button type="button" id="cjAcceptAllBtn" class="cj-btn-cookie cj-btn-cookie-primary"><i class="bi bi-check2-circle"></i> Accept All</button>',
        '    <button type="button" id="cjRejectBtn" class="cj-btn-cookie cj-btn-cookie-ghost">Essential Only</button>',
        '    <button type="button" id="cjPreferencesBtn" class="cj-btn-cookie cj-btn-cookie-text"><i class="bi bi-sliders me-1"></i> Preferences</button>',
        '  </div>',
        '</div>',

        '<!-- CONTINUOUS FLOATING BADGE -->',
        '<button type="button" id="cjCookieTrigger" class="cj-cookie-trigger" style="display:none;pointer-events:auto;" aria-label="Cookie and Privacy Settings">',
        '  <i class="bi bi-shield-lock-fill"></i>',
        '  <span>Cookie Settings</span>',
        '</button>',

        '<!-- PREFERENCES MODAL -->',
        '<div id="cjCookieModalBackdrop" class="cj-cookie-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="cjModalTitle" style="display:none;pointer-events:auto;">',
        '  <div class="cj-cookie-modal">',
        '    <div class="cj-modal-head">',
        '      <div>',
        '        <h2 id="cjModalTitle" class="cj-modal-head-title"><i class="bi bi-sliders"></i> Cookie & Privacy Preferences</h2>',
        '        <p class="cj-modal-head-sub">Customize how CJ Travels handles your data and personalizes your journey.</p>',
        '      </div>',
        '      <button type="button" id="cjModalCloseBtn" class="cj-modal-close" aria-label="Close preferences">&times;</button>',
        '    </div>',
        '    <div class="cj-modal-body">',
        '      <div class="cj-modal-intro">',
        '        <strong>Transparency First:</strong> Essential cookies are required to process reservations, secure accounts, and protect against fraud. Optional cookies help us enhance search tools and bring you personalized flight and hotel rates. You can change your choices at any time.',
        '      </div>',

        '      <!-- 1. STRICTLY NECESSARY -->',
        '      <div class="cj-cookie-group">',
        '        <div class="cj-group-head">',
        '          <div class="cj-group-info">',
        '            <h4 class="cj-group-title">Strictly Necessary Cookies</h4>',
        '            <span class="cj-status-badge cj-status-always"><i class="bi bi-lock-fill me-1"></i> Always Active</span>',
        '          </div>',
        '          <label class="cj-switch">',
        '            <input type="checkbox" checked disabled />',
        '            <span class="cj-slider"></span>',
        '          </label>',
        '        </div>',
        '        <p class="cj-group-desc">Essential for website operation, secure user logins, booking basket persistence, and CSRF security. Without these, the booking system cannot function.</p>',
        '        <div class="cj-group-cookies">Examples: <code>PHPSESSID</code>, <code>cj_session</code>, <code>csrf_token</code>, <code>cj_cookie_consent</code></div>',
        '      </div>',

        '      <!-- 2. FUNCTIONAL -->',
        '      <div class="cj-cookie-group">',
        '        <div class="cj-group-head">',
        '          <div class="cj-group-info">',
        '            <h4 class="cj-group-title">Functional & Experience Cookies</h4>',
        '          </div>',
        '          <label class="cj-switch">',
        '            <input type="checkbox" id="cjToggleFunctional" />',
        '            <span class="cj-slider"></span>',
        '          </label>',
        '        </div>',
        '        <p class="cj-group-desc">Remembers your departure airport, recent flight and hotel searches, currency choice, and guest count for effortless trip planning.</p>',
        '        <div class="cj-group-cookies">Examples: <code>cj_recent_search</code>, <code>cj_currency</code>, <code>cj_guest_pref</code></div>',
        '      </div>',

        '      <!-- 3. ANALYTICS -->',
        '      <div class="cj-cookie-group">',
        '        <div class="cj-group-head">',
        '          <div class="cj-group-info">',
        '            <h4 class="cj-group-title">Analytics & Performance Cookies</h4>',
        '          </div>',
        '          <label class="cj-switch">',
        '            <input type="checkbox" id="cjToggleAnalytics" />',
        '            <span class="cj-slider"></span>',
        '          </label>',
        '        </div>',
        '        <p class="cj-group-desc">Measures site speed, page navigation flow, and reservation drop-offs so we can continuously optimize booking speed and reliability. Data is aggregated and anonymized.</p>',
        '        <div class="cj-group-cookies">Examples: <code>cj_perf_metrics</code>, <code>_ga</code>, <code>_gid</code></div>',
        '      </div>',

        '      <!-- 4. MARKETING -->',
        '      <div class="cj-cookie-group">',
        '        <div class="cj-group-head">',
        '          <div class="cj-group-info">',
        '            <h4 class="cj-group-title">Marketing & Tailored Travel Offers</h4>',
        '          </div>',
        '          <label class="cj-switch">',
        '            <input type="checkbox" id="cjToggleMarketing" />',
        '            <span class="cj-slider"></span>',
        '          </label>',
        '        </div>',
        '        <p class="cj-group-desc">Enables tailored flight fare alerts, seasonal discount packages, and travel financing updates matching your travel interests across verified partner networks.</p>',
        '        <div class="cj-group-cookies">Examples: <code>cj_fare_alert</code>, <code>cj_campaign_ref</code></div>',
        '      </div>',
        '    </div>',

        '    <div class="cj-modal-foot">',
        '      <div class="cj-modal-foot-left">',
        '        <button type="button" id="cjModalRejectBtn" class="cj-btn-cookie cj-btn-cookie-ghost">Decline Optional</button>',
        '      </div>',
        '      <div class="cj-modal-foot-right">',
        '        <button type="button" id="cjModalSaveBtn" class="cj-btn-cookie cj-btn-cookie-ghost">Save Choices</button>',
        '        <button type="button" id="cjModalAcceptAllBtn" class="cj-btn-cookie cj-btn-cookie-primary"><i class="bi bi-check-all"></i> Accept All</button>',
        '      </div>',
        '    </div>',
        '  </div>',
        '</div>',

        '<!-- TOAST -->',
        '<div id="cjCookieToast" class="cj-cookie-toast" role="status" aria-live="polite">',
        '  <i class="bi bi-check-circle-fill"></i>',
        '  <span id="cjCookieToastText">Preferences saved successfully.</span>',
        '</div>'
      ].join('\n');

      document.body.appendChild(wrapper);
    },

    /**
     * Bind all DOM click & keyboard events
     */
    bindEvents: function () {
      var self = this;

      // Banner buttons
      var acceptBtn = document.getElementById('cjAcceptAllBtn');
      if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
          self.acceptAll();
        });
      }

      var rejectBtn = document.getElementById('cjRejectBtn');
      if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
          self.rejectNonEssential();
        });
      }

      var prefsBtn = document.getElementById('cjPreferencesBtn');
      if (prefsBtn) {
        prefsBtn.addEventListener('click', function () {
          self.openPreferences();
        });
      }

      var closeBanner = document.getElementById('cjCloseBannerBtn');
      if (closeBanner) {
        closeBanner.addEventListener('click', function () {
          self.hideBanner();
          self.showTrigger();
        });
      }

      // Floating trigger badge
      var trigger = document.getElementById('cjCookieTrigger');
      if (trigger) {
        trigger.addEventListener('click', function () {
          self.openPreferences();
        });
      }

      // Modal buttons
      var modalClose = document.getElementById('cjModalCloseBtn');
      if (modalClose) {
        modalClose.addEventListener('click', function () {
          self.closeModal();
        });
      }

      var modalSave = document.getElementById('cjModalSaveBtn');
      if (modalSave) {
        modalSave.addEventListener('click', function () {
          self.savePreferencesFromModal();
        });
      }

      var modalAcceptAll = document.getElementById('cjModalAcceptAllBtn');
      if (modalAcceptAll) {
        modalAcceptAll.addEventListener('click', function () {
          self.acceptAll();
        });
      }

      var modalReject = document.getElementById('cjModalRejectBtn');
      if (modalReject) {
        modalReject.addEventListener('click', function () {
          self.rejectNonEssential();
        });
      }

      // Backdrop click closes modal
      var backdrop = document.getElementById('cjCookieModalBackdrop');
      if (backdrop) {
        backdrop.addEventListener('click', function (e) {
          if (e.target === backdrop) {
            self.closeModal();
          }
        });
      }

      // Global links with data-open-cookie-settings or .cj-open-cookie-settings
      document.addEventListener('click', function (e) {
        var target = e.target.closest('[data-open-cookie-settings], .cj-open-cookie-settings, a[href="#cookie-settings"]');
        if (target) {
          e.preventDefault();
          self.openPreferences();
        }
      });

      // Escape key to close modal
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
          self.closeModal();
        }
      });
    }
  };

  // Expose globally
  window.CJCookieConsent = CJCookieConsent;

  // Auto-init once DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      CJCookieConsent.init();
    });
  } else {
    CJCookieConsent.init();
  }

})(window, document);

