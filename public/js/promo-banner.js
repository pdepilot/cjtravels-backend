/**
 * CJ Travels — World-Class Luxury Promo Banner & Registration System
 */
(function () {
  'use strict';

  var STORAGE_KEY_DISMISSED = 'cj_promo_dismissed_until';
  var STORAGE_KEY_CLAIMED   = 'cj_promo_claimed_voucher';

  var config = window.CJ_PROMO_CONFIG || {
    apiUrl: '/api/promo/register',
    registerUrl: '/register',
    flightsUrl: '/flights',
    hotelsUrl: '/hotels',
    defaultCode: 'CJLUXE50K',
    discountAmount: '₦50,000',
    delayMs: 2500
  };

  function $(id) {
    return document.getElementById(id);
  }

  /* -----------------------------------------------------------
     1. COUNTDOWN TIMER
     ----------------------------------------------------------- */
  function startCountdown() {
    var hoursEl = $('cjPromoHours');
    var minsEl  = $('cjPromoMins');
    var secsEl  = $('cjPromoSecs');
    if (!hoursEl || !minsEl || !secsEl) return;

    function tick() {
      var now = new Date();
      var endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
      var diff = Math.max(0, Math.floor((endOfDay.getTime() - now.getTime()) / 1000));

      var h = Math.floor(diff / 3600);
      var m = Math.floor((diff % 3600) / 60);
      var s = diff % 60;

      hoursEl.textContent = String(h).padStart(2, '0');
      minsEl.textContent  = String(m).padStart(2, '0');
      secsEl.textContent  = String(s).padStart(2, '0');
    }

    tick();
    setInterval(tick, 1000);
  }

  /* -----------------------------------------------------------
     2. MODAL DISPLAY & CONTROLS
     ----------------------------------------------------------- */
  var backdrop = $('cjPromoBackdrop');
  var card     = $('cjPromoCard');
  var trigger  = $('cjPromoTrigger');

  function openPromoModal() {
    if (!backdrop) return;
    backdrop.style.display = 'flex';
    // Trigger reflow for CSS animation
    void backdrop.offsetWidth;
    backdrop.classList.add('active');
    if (trigger) trigger.style.display = 'none';

    // If user already claimed previously, show their voucher certificate
    var claimed = getClaimedVoucher();
    if (claimed && $('cjPromoSuccessView')) {
      showSuccessView(claimed);
    }
  }

  function closePromoModal(setDismissed) {
    if (!backdrop) return;
    backdrop.classList.remove('active');

    if (setDismissed) {
      try {
        // Dismissed for 24 hours
        localStorage.setItem(STORAGE_KEY_DISMISSED, String(Date.now() + 86400000));
      } catch (e) {}
    }

    setTimeout(function () {
      backdrop.style.display = 'none';
      if (trigger) trigger.style.display = 'inline-flex';
    }, 400);
  }

  function isDismissed() {
    try {
      var until = parseInt(localStorage.getItem(STORAGE_KEY_DISMISSED), 10);
      return until && Date.now() < until;
    } catch (e) {
      return false;
    }
  }

  function getClaimedVoucher() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY_CLAIMED);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  /* -----------------------------------------------------------
     3. VIEW SWITCHING
     ----------------------------------------------------------- */
  var heroView    = $('cjPromoHeroView');
  var formView    = $('cjPromoFormView');
  var successView = $('cjPromoSuccessView');

  function showView(viewEl) {
    [heroView, formView, successView].forEach(function (v) {
      if (v) v.style.display = 'none';
    });
    if (viewEl) {
      viewEl.style.display = 'block';
      var card = document.getElementById('cjPromoCard');
      if (card) card.scrollTop = 0;
    }
  }

  function showSuccessView(promoData) {
    showView(successView);
    var codeEl = $('cjSuccessCode');
    var refEl  = $('cjSuccessRef');
    var textEl = $('cjSuccessPersonalizedText');

    if (codeEl) codeEl.textContent = promoData.code || config.defaultCode;
    if (refEl) refEl.textContent = promoData.ref || 'PRM-ACTIVE';
    if (textEl && promoData.name) {
      textEl.textContent = 'Welcome aboard, ' + promoData.name + '. Your ₦50,000 credit is ready to use on flights & hotels.';
    }
  }

  /* -----------------------------------------------------------
     4. COPY CODE INTERACTION
     ----------------------------------------------------------- */
  var copyBtn = $('cjCopyPromoBtn');
  var copyText = $('cjCopyText');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var code = ($('cjPromoCodeDisplay') && $('cjPromoCodeDisplay').textContent) || config.defaultCode;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(onCopied).catch(fallbackCopy);
      } else {
        fallbackCopy();
      }

      function onCopied() {
        if (copyText) copyText.textContent = 'Copied!';
        copyBtn.style.background = 'var(--cj-promo-gold)';
        copyBtn.style.color = '#050E1C';
        setTimeout(function () {
          if (copyText) copyText.textContent = 'Copy Code';
          copyBtn.style.background = '';
          copyBtn.style.color = '';
        }, 2500);
      }

      function fallbackCopy() {
        var input = document.createElement('input');
        input.value = code;
        document.body.appendChild(input);
        input.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(input);
        onCopied();
      }
    });
  }

  /* -----------------------------------------------------------
     5. FORM APPLICATION / REGISTRATION SUBMISSION
     ----------------------------------------------------------- */
  var openFormBtn   = $('cjOpenClaimFormBtn');
  var backToHeroBtn = $('cjBackToHeroBtn');
  var regForm       = $('cjPromoRegisterForm');
  var statusBox     = $('cjPromoStatus');
  var submitBtn     = $('cjSubmitPromoBtn');

  if (openFormBtn) {
    openFormBtn.addEventListener('click', function () {
      showView(formView);
      var nameInput = $('cjPromoFullName');
      if (nameInput) {
        setTimeout(function () { nameInput.focus(); }, 150);
      }
    });
  }

  if (backToHeroBtn) {
    backToHeroBtn.addEventListener('click', function () {
      showView(heroView);
    });
  }

  if (regForm) {
    regForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var nameInput  = $('cjPromoFullName');
      var emailInput = $('cjPromoEmail');
      var phoneInput = $('cjPromoPhone');
      var destInput  = $('cjPromoDestination');

      var errName  = $('cjErrName');
      var errEmail = $('cjErrEmail');

      var fullName = nameInput ? nameInput.value.trim() : '';
      var email    = emailInput ? emailInput.value.trim() : '';
      var phone    = phoneInput ? phoneInput.value.trim() : '';
      var dest     = destInput ? destInput.value : 'Dubai & UAE';

      var valid = true;

      // Validate name
      if (fullName.length < 2) {
        if (nameInput) nameInput.classList.add('is-invalid');
        if (errName) errName.classList.add('show');
        valid = false;
      } else {
        if (nameInput) nameInput.classList.remove('is-invalid');
        if (errName) errName.classList.remove('show');
      }

      // Validate email
      var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
      if (!emailPattern.test(email)) {
        if (emailInput) emailInput.classList.add('is-invalid');
        if (errEmail) errEmail.classList.add('show');
        valid = false;
      } else {
        if (emailInput) emailInput.classList.remove('is-invalid');
        if (errEmail) errEmail.classList.remove('show');
      }

      if (!valid) return;

      // Submit to backend
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Activating Voucher…';
      }
      if (statusBox) {
        statusBox.style.display = 'none';
        statusBox.className = 'cj-promo-status';
      }

      var basePath = window.location.pathname.replace(/\/+$/, '');
      var apiUrl = config.apiUrl;
      // Auto adjust for subfolder installs (e.g. /TravelBooking)
      if (apiUrl.indexOf('http') !== 0 && apiUrl.indexOf('/') === 0) {
        var prefix = basePath.replace(/\/(index|about|flights|hotels|financing|destinations|contact|login|register)(\.php)?$/i, '');
        if (prefix && prefix !== '/' && apiUrl.indexOf(prefix) !== 0) {
          apiUrl = prefix + apiUrl;
        }
      }

      fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          fullName: fullName,
          email: email,
          phone: phone,
          destination: dest,
          promoCode: config.defaultCode
        })
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-shield-check me-2"></i> Activate My ₦50,000 Promo';
          }

          if (res && res.success && res.promo) {
            try {
              localStorage.setItem(STORAGE_KEY_CLAIMED, JSON.stringify(res.promo));
            } catch (e) {}
            showSuccessView(res.promo);
            return;
          }

          if (statusBox) {
            statusBox.className = 'cj-promo-status error';
            statusBox.textContent = (res && (res.error || res.message)) || 'Unable to register for promo. Please try again.';
            statusBox.style.display = 'block';
          }
        })
        .catch(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-shield-check me-2"></i> Activate My ₦50,000 Promo';
          }
          if (statusBox) {
            statusBox.className = 'cj-promo-status error';
            statusBox.textContent = 'Could not connect to the server. Please check your connection.';
            statusBox.style.display = 'block';
          }
        });
    });
  }

  /* -----------------------------------------------------------
     6. EVENT BINDINGS
     ----------------------------------------------------------- */
  // Close / Cancel button
  var closeBtn = $('cjPromoCloseBtn');
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      closePromoModal(true);
    });
  }

  // Dismiss text button
  var dismissTextBtn = $('cjPromoDismissTextBtn');
  if (dismissTextBtn) {
    dismissTextBtn.addEventListener('click', function () {
      closePromoModal(true);
    });
  }

  // Success view done button
  var successDoneBtn = $('cjSuccessDoneBtn');
  if (successDoneBtn) {
    successDoneBtn.addEventListener('click', function () {
      closePromoModal(false);
    });
  }

  // Click outside card (on backdrop) closes
  if (backdrop) {
    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) {
        closePromoModal(true);
      }
    });
  }

  // Keyboard Escape key closes
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && backdrop && backdrop.classList.contains('active')) {
      closePromoModal(true);
    }
  });

  // Re-open trigger button
  if (trigger) {
    trigger.addEventListener('click', function () {
      openPromoModal();
    });
  }

  /* -----------------------------------------------------------
     7. INITIALIZATION & GRACEFUL POPUP
     ----------------------------------------------------------- */
  function initPromoBanner() {
    if (config.disabled) {
      if (backdrop) backdrop.style.display = 'none';
      if (trigger) trigger.style.display = 'none';
      return;
    }

    startCountdown();

    if (isDismissed()) {
      // User recently dismissed; don't auto popup, but provide trigger pill
      if (trigger) trigger.style.display = 'inline-flex';
    } else {
      // Graceful entrance after configured delay
      setTimeout(function () {
        openPromoModal();
      }, config.delayMs);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPromoBanner);
  } else {
    initPromoBanner();
  }
})();

