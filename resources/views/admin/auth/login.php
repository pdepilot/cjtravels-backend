<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="/images/logo-mark.png" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($title ?? 'Admin Sign In — CJ Travels') ?></title>
  <meta name="description" content="Secure administrative access for CJ Travels travel management portal." />
  <meta name="robots" content="noindex, nofollow" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

  <style>
    :root {
      --cream: #f5f0e6;
      --cream-soft: #faf6ed;
      --ivory: #fdfbf6;
      --navy: #0b1e3d;
      --navy-dark: #071326;
      --navy-card: #0e2447;
      --navy-soft: #1e3657;
      --gold: #a8842c;
      --gold-soft: #c9a55b;
      --gold-glow: rgba(201, 165, 91, 0.25);
      --ink: #1a1a1a;
      --muted: #8a8378;
      --hairline: rgba(201, 165, 91, 0.2);
      --error: #e05243;
      --error-bg: rgba(224, 82, 67, 0.12);
      --success: #2e9e6b;
      --success-bg: rgba(46, 158, 107, 0.12);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }

    body {
      font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      font-weight: 400;
      color: var(--ivory);
      background-color: var(--navy-dark);
      background-image: 
        radial-gradient(circle at 15% 20%, rgba(30, 54, 87, 0.5) 0%, transparent 40%),
        radial-gradient(circle at 85% 80%, rgba(168, 132, 44, 0.12) 0%, transparent 40%),
        linear-gradient(180deg, #071326 0%, #0b1e3d 100%);
      background-attachment: fixed;
      line-height: 1.5;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    .skip-link {
      position: absolute;
      left: 1rem;
      top: -60px;
      z-index: 100;
      background: var(--gold);
      color: var(--navy);
      padding: .6rem 1.2rem;
      font-size: 13px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      transition: top .25s ease;
      text-decoration: none;
    }
    .skip-link:focus { top: 1rem; }

    .admin-auth-shell {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      position: relative;
    }

    .auth-container {
      width: 100%;
      max-width: 440px;
      position: relative;
      z-index: 2;
    }

    /* BRAND HEADER */
    .brand-header {
      text-align: center;
      margin-bottom: 28px;
    }

    .brand-logo-link {
      display: inline-block;
      margin-bottom: 16px;
      transition: transform .25s ease;
    }
    .brand-logo-link:hover { transform: scale(1.03); }

    .brand-logo {
      height: 48px;
      width: auto;
      object-fit: contain;
    }

    .brand-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 5px 14px;
      background: rgba(201, 165, 91, 0.12);
      border: 1px solid rgba(201, 165, 91, 0.3);
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .18em;
      text-transform: uppercase;
      color: var(--gold-soft);
      margin-bottom: 12px;
    }

    .brand-badge i { font-size: 13px; color: var(--gold-soft); }

    h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 34px;
      font-weight: 400;
      letter-spacing: .02em;
      color: var(--ivory);
      margin-bottom: 6px;
    }

    h1 em {
      font-style: italic;
      color: var(--gold-soft);
    }

    .subtitle {
      font-size: 13.5px;
      color: rgba(253, 251, 246, 0.65);
      font-weight: 300;
    }

    /* CARD */
    .auth-card {
      background: rgba(14, 36, 71, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--hairline);
      border-radius: 12px;
      padding: clamp(28px, 6vw, 36px);
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35), 0 0 40px rgba(168, 132, 44, 0.05);
    }

    /* ALERTS */
    .alert-banner {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 13px 16px;
      border-radius: 8px;
      font-size: 13px;
      line-height: 1.45;
      margin-bottom: 22px;
    }

    .alert-banner.error {
      background: var(--error-bg);
      border: 1px solid rgba(224, 82, 67, 0.35);
      color: #ff9185;
    }

    .alert-banner.success {
      background: var(--success-bg);
      border: 1px solid rgba(46, 158, 107, 0.35);
      color: #79e3b1;
    }

    .alert-banner i { font-size: 16px; margin-top: 1px; flex-shrink: 0; }

    /* FORM ELEMENTS */
    .field {
      margin-bottom: 20px;
      position: relative;
    }

    .field label {
      display: block;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .15em;
      text-transform: uppercase;
      color: rgba(253, 251, 246, 0.7);
      margin-bottom: 8px;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i.prefix-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(253, 251, 246, 0.35);
      font-size: 15px;
      pointer-events: none;
      transition: color .2s ease;
    }

    .field input[type="text"],
    .field input[type="email"],
    .field input[type="password"] {
      width: 100%;
      height: 48px;
      padding: 0 16px 0 42px;
      background: rgba(7, 19, 38, 0.7);
      border: 1px solid rgba(201, 165, 91, 0.25);
      border-radius: 8px;
      font-size: 14px;
      color: var(--ivory);
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .field input[type="password"] {
      padding-right: 44px;
    }

    .field input:focus {
      border-color: var(--gold-soft);
      box-shadow: 0 0 0 3px var(--gold-glow);
      background: rgba(7, 19, 38, 0.95);
    }

    .field input:focus + i.prefix-icon,
    .field input:focus ~ i.prefix-icon {
      color: var(--gold-soft);
    }

    .pw-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(253, 251, 246, 0.45);
      font-size: 16px;
      cursor: pointer;
      padding: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color .2s ease;
    }
    .pw-toggle:hover { color: var(--gold-soft); }

    .options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
      font-size: 13px;
    }

    .checkbox-wrap {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      user-select: none;
      color: rgba(253, 251, 246, 0.8);
    }

    .checkbox-wrap input[type="checkbox"] {
      accent-color: var(--gold);
      width: 16px;
      height: 16px;
      cursor: pointer;
    }

    .link-gold {
      color: var(--gold-soft);
      text-decoration: none;
      font-size: 12.5px;
      transition: color .2s ease, text-decoration .2s ease;
    }
    .link-gold:hover {
      color: var(--ivory);
      text-decoration: underline;
    }

    /* SUBMIT BUTTON */
    .btn-submit {
      width: 100%;
      height: 50px;
      background: linear-gradient(135deg, #c9a55b 0%, #a8842c 100%);
      border: none;
      border-radius: 8px;
      color: var(--navy-dark);
      font-size: 13.5px;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
      box-shadow: 0 8px 20px rgba(168, 132, 44, 0.25);
    }

    .btn-submit:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 10px 24px rgba(168, 132, 44, 0.35);
      filter: brightness(1.05);
    }

    .btn-submit:active:not(:disabled) {
      transform: translateY(0);
    }

    .btn-submit:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
    }

    .btn-submit .spinner {
      display: none;
      width: 18px;
      height: 18px;
      border: 2px solid rgba(7, 19, 38, 0.3);
      border-top-color: var(--navy-dark);
      border-radius: 50%;
      animation: spin .7s linear infinite;
    }

    .btn-submit.loading .spinner { display: inline-block; }
    .btn-submit.loading .btn-text-default { display: none; }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* FOOTER / RETURN LINK */
    .auth-footer {
      text-align: center;
      margin-top: 24px;
    }

    .return-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: rgba(253, 251, 246, 0.55);
      font-size: 12.5px;
      text-decoration: none;
      transition: color .2s ease;
    }
    .return-link:hover {
      color: var(--gold-soft);
    }

    .security-notice {
      text-align: center;
      margin-top: 18px;
      font-size: 11px;
      color: rgba(253, 251, 246, 0.4);
      letter-spacing: .05em;
    }
  </style>
</head>

<body>
  <a href="#adminLoginForm" class="skip-link">Skip to sign in form</a>

  <div class="admin-auth-shell">
    <div class="auth-container">

      <!-- BRAND HEADER -->
      <header class="brand-header">
        <a href="<?= route('home') ?>" class="brand-logo-link" title="CJ Travels Home">
          <img src="<?= asset('images/logo-white.png') ?>" alt="CJ Travels" class="brand-logo" onerror="this.onerror=null;this.src='<?= asset('images/logo.png') ?>';" />
        </a>
        <div>
          <span class="brand-badge">
            <i class="bi bi-shield-lock-fill"></i>
            Internal Portal
          </span>
        </div>
        <h1>Administrator <em>Console</em></h1>
        <p class="subtitle">Enter your verified credentials to access administrative systems</p>
      </header>

      <!-- AUTH CARD -->
      <main class="auth-card">

        <!-- STATUS ALERTS -->
        <?php if (!empty($error)): ?>
          <div class="alert-banner error" role="alert" id="serverError">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert-banner success" role="status" id="serverSuccess">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= e($success) ?></div>
          </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form id="adminLoginForm" method="POST" action="<?= route('admin.login') ?>" autocomplete="on" novalidate>
          <?= csrf_field() ?>

          <div class="field">
            <label for="identifier">Email address or Username</label>
            <div class="input-wrap">
              <input type="text"
                     id="identifier"
                     name="identifier"
                     placeholder="admin@cjtravels.com or username"
                     value="<?= e($identifier ?? '') ?>"
                     autocomplete="username"
                     autofocus
                     required
                     aria-required="true" />
              <i class="bi bi-person-fill prefix-icon"></i>
            </div>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
              <input type="password"
                     id="password"
                     name="password"
                     placeholder="Enter your password"
                     autocomplete="current-password"
                     required
                     aria-required="true" />
              <i class="bi bi-lock-fill prefix-icon"></i>
              <button type="button" class="pw-toggle" id="togglePassword" aria-label="Show password" aria-pressed="false" tabindex="-1">
                <i class="bi bi-eye" id="toggleIcon"></i>
              </button>
            </div>
          </div>

          <div class="options-row">
            <label class="checkbox-wrap">
              <input type="checkbox" name="remember" value="1" id="rememberMe" />
              <span>Remember me</span>
            </label>
            <a href="<?= route('admin.forgot-password') ?>" class="link-gold">Forgot password?</a>
          </div>

          <button type="submit" class="btn-submit" id="submitBtn">
            <span class="spinner" aria-hidden="true"></span>
            <span class="btn-text-default">
              Sign In to Console <i class="bi bi-arrow-right"></i>
            </span>
            <span class="btn-text-loading" style="display:none;">Authenticating...</span>
          </button>
        </form>

        <p class="security-notice">
          <i class="bi bi-shield-check"></i> 256-bit encrypted authentication &bull; Activity monitored
        </p>

      </main>

      <!-- FOOTER -->
      <footer class="auth-footer">
        <a href="<?= route('home') ?>" class="return-link">
          <i class="bi bi-arrow-left"></i> Return to CJ Travels Website
        </a>
      </footer>

    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('adminLoginForm');
      var submitBtn = document.getElementById('submitBtn');
      var passwordInput = document.getElementById('password');
      var toggleBtn = document.getElementById('togglePassword');
      var toggleIcon = document.getElementById('toggleIcon');
      var identifierInput = document.getElementById('identifier');

      // 1. Password show/hide toggle
      if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
          var isPassword = passwordInput.type === 'password';
          passwordInput.type = isPassword ? 'text' : 'password';
          toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
          toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
          toggleBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
          passwordInput.focus();
        });
      }

      // 2. Prevent duplicate submissions & show loading feedback
      if (form && submitBtn) {
        form.addEventListener('submit', function (e) {
          var idVal = identifierInput.value.trim();
          var pwVal = passwordInput.value;

          if (!idVal || !pwVal) {
            e.preventDefault();
            if (!idVal) identifierInput.focus();
            else passwordInput.focus();
            return;
          }

          // Disable button and show spinner
          submitBtn.classList.add('loading');
          submitBtn.disabled = true;
          var textDefault = submitBtn.querySelector('.btn-text-default');
          var textLoading = submitBtn.querySelector('.btn-text-loading');
          if (textDefault) textDefault.style.display = 'none';
          if (textLoading) textLoading.style.display = 'inline-block';
        });
      }
    });
  </script>
</body>
</html>

