<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="/images/logo-mark.png" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($title ?? 'Password Recovery — CJ Travels Admin') ?></title>
  <meta name="robots" content="noindex, nofollow" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

  <style>
    :root {
      --ivory: #fdfbf6;
      --navy: #0b1e3d;
      --navy-dark: #071326;
      --gold: #a8842c;
      --gold-soft: #c9a55b;
      --gold-glow: rgba(201, 165, 91, 0.25);
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
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    .admin-auth-shell {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    .auth-container {
      width: 100%;
      max-width: 440px;
    }

    .brand-header {
      text-align: center;
      margin-bottom: 28px;
    }

    .brand-logo {
      height: 48px;
      width: auto;
      object-fit: contain;
      margin-bottom: 16px;
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

    h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 32px;
      font-weight: 400;
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

    .auth-card {
      background: rgba(14, 36, 71, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--hairline);
      border-radius: 12px;
      padding: clamp(28px, 6vw, 36px);
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
    }

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

    .dev-box {
      background: rgba(201, 165, 91, 0.1);
      border: 1px dashed var(--gold-soft);
      border-radius: 8px;
      padding: 14px;
      margin-bottom: 20px;
      font-size: 12px;
    }

    .dev-box strong { color: var(--gold-soft); display: block; margin-bottom: 6px; }
    .dev-box a { color: var(--ivory); word-break: break-all; text-decoration: underline; }

    .field {
      margin-bottom: 20px;
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
    }

    .field input[type="email"] {
      width: 100%;
      height: 48px;
      padding: 0 16px 0 42px;
      background: rgba(7, 19, 38, 0.7);
      border: 1px solid rgba(201, 165, 91, 0.25);
      border-radius: 8px;
      font-size: 14px;
      color: var(--ivory);
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease;
    }

    .field input:focus {
      border-color: var(--gold-soft);
      box-shadow: 0 0 0 3px var(--gold-glow);
    }

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
      transition: transform .2s ease, box-shadow .2s ease;
      box-shadow: 0 8px 20px rgba(168, 132, 44, 0.25);
    }

    .btn-submit:hover:not(:disabled) {
      transform: translateY(-1px);
      filter: brightness(1.05);
    }

    .btn-submit:disabled { opacity: 0.65; cursor: not-allowed; }

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

    .auth-footer {
      text-align: center;
      margin-top: 24px;
    }

    .return-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: rgba(253, 251, 246, 0.65);
      font-size: 13px;
      text-decoration: none;
      transition: color .2s ease;
    }
    .return-link:hover { color: var(--gold-soft); }
  </style>
</head>

<body>
  <div class="admin-auth-shell">
    <div class="auth-container">

      <header class="brand-header">
        <a href="<?= route('home') ?>">
          <img src="<?= asset('images/logo-white.png') ?>" alt="CJ Travels" class="brand-logo" onerror="this.onerror=null;this.src='<?= asset('images/logo.png') ?>';" />
        </a>
        <div>
          <span class="brand-badge">
            <i class="bi bi-key-fill"></i>
            Account Recovery
          </span>
        </div>
        <h1>Reset <em>Password</em></h1>
        <p class="subtitle">Enter your registered administrator email to receive reset instructions</p>
      </header>

      <main class="auth-card">

        <?php if (!empty($error)): ?>
          <div class="alert-banner error" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert-banner success" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= e($success) ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($devResetLink)): ?>
          <div class="dev-box">
            <strong><i class="bi bi-info-circle-fill"></i> Security Note (SMTP not configured):</strong>
            Because outbound SMTP email is not configured in this environment, your reset link was generated and logged to <code>storage/logs/mail.log</code>. For local testing, follow this link:
            <div style="margin-top:8px;">
              <a href="<?= e($devResetLink) ?>">Click here to Reset Password &rarr;</a>
            </div>
          </div>
        <?php endif; ?>

        <form id="forgotForm" method="POST" action="<?= route('admin.forgot-password') ?>" novalidate>
          <?= csrf_field() ?>

          <div class="field">
            <label for="email">Administrator Email</label>
            <div class="input-wrap">
              <input type="email"
                     id="email"
                     name="email"
                     placeholder="admin@cjtravels.com"
                     autocomplete="email"
                     autofocus
                     required />
              <i class="bi bi-envelope-fill prefix-icon"></i>
            </div>
          </div>

          <button type="submit" class="btn-submit" id="submitBtn">
            <span class="spinner" aria-hidden="true"></span>
            <span class="btn-text-default">Send Reset Instructions <i class="bi bi-arrow-right"></i></span>
            <span class="btn-text-loading" style="display:none;">Generating Token...</span>
          </button>
        </form>

      </main>

      <footer class="auth-footer">
        <a href="<?= route('admin.login') ?>" class="return-link">
          <i class="bi bi-arrow-left"></i> Back to Administrator Sign In
        </a>
      </footer>

    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('forgotForm');
      var submitBtn = document.getElementById('submitBtn');
      var emailInput = document.getElementById('email');

      if (form && submitBtn) {
        form.addEventListener('submit', function (e) {
          if (!emailInput.value.trim()) {
            e.preventDefault();
            emailInput.focus();
            return;
          }
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

