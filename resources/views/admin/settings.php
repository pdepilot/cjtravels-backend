<?php $this->layout('layouts/admin', ['title' => 'Console Settings — CJ Travels Admin', 'page' => 'settings']); ?>

<section class="view active" id="view-settings" data-view="settings">
  <div class="page-head mb-4">
    <div>
      <h1>Administrator Settings</h1>
      <p>Manage your administrator account credentials, login email, phone number, and security preferences.</p>
    </div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;border-radius:3px;padding:14px 18px;">
      <i class="bi bi-check-circle-fill me-3" style="font-size:1.3rem;color:#2e7d5b;"></i>
      <div>
        <strong>Success:</strong> <?= e($success) ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="background:#f7e6e2;border:1px solid #a3452e;color:#5a1f13;border-radius:3px;padding:14px 18px;">
      <i class="bi bi-exclamation-triangle-fill me-3" style="font-size:1.3rem;color:#a3452e;"></i>
      <div>
        <strong>Action required:</strong> <?= e($error) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- LEFT COLUMN: Forms -->
    <div class="col-lg-7">

      <!-- CARD 1: Update Login Details (Email, Phone, Name, Username) -->
      <div class="card-box mb-4">
        <div class="card-box-head">
          <h2><i class="bi bi-person-badge"></i> Administrator Login & Contact Details</h2>
        </div>
        <div class="card-box-body">
          <p class="text-muted mb-3" style="font-size:0.9rem;">
            Update your primary login identifiers. Once saved, you can log into the CJ Travels administrator console using <strong>either your email address or your phone number</strong>.
          </p>

          <form action="<?= route('admin.settings.profile') ?>" method="POST" id="profileSettingsForm">
            <?= csrf_field() ?>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="adminName"><strong>Full Name</strong> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="adminName" name="name" value="<?= e($admin->name ?? '') ?>" required />
              </div>

              <div class="col-md-6">
                <label class="form-label" for="adminUsername"><strong>Username</strong></label>
                <input type="text" class="form-control" id="adminUsername" name="username" value="<?= e($admin->username ?? '') ?>" placeholder="e.g. cjadmin" />
                <small class="text-muted" style="font-size:0.8rem;">Optional login identifier.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="adminEmail"><strong>Email Address (Login & Recovery)</strong> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text" style="background:#fff;border-color:var(--hairline);"><i class="bi bi-envelope"></i></span>
                  <input type="email" class="form-control" id="adminEmail" name="email" value="<?= e($admin->email ?? '') ?>" required placeholder="admin@cjtravels.com" />
                </div>
                <small class="text-muted" style="font-size:0.8rem;">Used to log in and receive password reset links.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="adminPhone"><strong>Phone Number (Login & SMS)</strong></label>
                <div class="input-group">
                  <span class="input-group-text" style="background:#fff;border-color:var(--hairline);"><i class="bi bi-telephone"></i></span>
                  <input type="tel" class="form-control" id="adminPhone" name="phone" value="<?= e($admin->phone ?? '') ?>" placeholder="+234 812 345 6789" />
                </div>
                <small class="text-muted" style="font-size:0.8rem;">Sign in directly using this phone number.</small>
              </div>

              <!-- Security Password Confirmation -->
              <div class="col-12 mt-3">
                <div style="background:var(--cream-soft);border:1px solid var(--hairline);border-radius:3px;padding:14px;">
                  <label class="form-label mb-1" for="current_password_profile" style="color:var(--navy);font-weight:600;">
                    <i class="bi bi-shield-lock-fill text-danger me-1"></i> Current Administrator Password <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <input type="password" class="form-control" id="current_password_profile" name="current_password" required placeholder="Enter your current password to authorize changes" />
                    <button class="btn btn-outline-secondary toggle-pwd-btn" type="button" data-target="current_password_profile" style="border-color:var(--hairline);background:#fff;" aria-label="Toggle password visibility">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                  <small class="text-muted" style="font-size:0.8rem;">For your protection, you must verify your existing password before updating sensitive login credentials.</small>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-navy" id="saveProfileBtn">
                <i class="bi bi-check2"></i> Save Login Details
              </button>
              <button type="reset" class="btn btn-ghost">
                Reset Form
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- CARD 2: Update Password -->
      <div class="card-box">
        <div class="card-box-head">
          <h2><i class="bi bi-shield-lock"></i> Change Administrator Password</h2>
        </div>
        <div class="card-box-body">
          <p class="text-muted mb-3" style="font-size:0.9rem;">
            Ensure your account is protected with a strong password containing at least 8 characters.
          </p>

          <form action="<?= route('admin.settings.password') ?>" method="POST" id="passwordSettingsForm">
            <?= csrf_field() ?>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="current_password_change"><strong>Current Password</strong> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" class="form-control" id="current_password_change" name="current_password" required placeholder="Enter current password" />
                  <button class="btn btn-outline-secondary toggle-pwd-btn" type="button" data-target="current_password_change" style="border-color:var(--hairline);background:#fff;" aria-label="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="new_password"><strong>New Password</strong> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required placeholder="Minimum 8 characters" />
                  <button class="btn btn-outline-secondary toggle-pwd-btn" type="button" data-target="new_password" style="border-color:var(--hairline);background:#fff;" aria-label="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="new_password_confirmation"><strong>Confirm New Password</strong> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" minlength="8" required placeholder="Re-enter new password" />
                  <button class="btn btn-outline-secondary toggle-pwd-btn" type="button" data-target="new_password_confirmation" style="border-color:var(--hairline);background:#fff;" aria-label="Toggle password visibility">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-navy" id="savePasswordBtn">
                <i class="bi bi-key-fill"></i> Update Password
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

    <!-- RIGHT COLUMN: Account & Environment Status -->
    <div class="col-lg-5">

      <!-- Active Account Card -->
      <div class="card-box mb-4">
        <div class="card-box-head">
          <h2><i class="bi bi-shield-check"></i> Active Administrator Account</h2>
        </div>
        <div class="card-box-body">
          <div class="d-flex align-items-center gap-3 pb-3 mb-3" style="border-bottom:1px solid var(--hairline-soft);">
            <div style="width:50px;height:50px;border-radius:50%;background:var(--navy);color:var(--cream);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;letter-spacing:1px;">
              <?= e($admin ? $admin->getInitials() : 'AD') ?>
            </div>
            <div>
              <div style="font-weight:600;font-size:1.05rem;color:var(--navy);"><?= e($admin->name ?? 'Administrator') ?></div>
              <div style="font-size:0.85rem;color:var(--muted);"><?= e($admin->role ?? 'Administrator') ?></div>
            </div>
          </div>

          <div class="detail-grid" style="margin:0;">
            <div class="detail-item">
              <span>Account Status</span>
              <strong><span class="status status-confirmed"><i class="bi bi-check-circle-fill"></i> <?= ucfirst(e($admin->status ?? 'active')) ?></span></strong>
            </div>
            <div class="detail-item">
              <span>Role Permissions</span>
              <strong><?= e($admin->role ?? 'Super Administrator') ?></strong>
            </div>
            <div class="detail-item">
              <span>Email Sign-In</span>
              <strong><?= e($admin->email ?? 'Not set') ?></strong>
            </div>
            <div class="detail-item">
              <span>Phone Sign-In</span>
              <strong><?= !empty($admin->phone) ? e($admin->phone) : '<span class="text-muted">Not registered</span>' ?></strong>
            </div>
            <div class="detail-item">
              <span>Username Sign-In</span>
              <strong><?= !empty($admin->username) ? e($admin->username) : '<span class="text-muted">Not set</span>' ?></strong>
            </div>
            <div class="detail-item">
              <span>Last Signed In</span>
              <strong><?= !empty($admin->last_login_at) ? date('M j, Y \a\t g:i A', strtotime($admin->last_login_at)) : 'Current session' ?></strong>
            </div>
            <div class="detail-item">
              <span>Last Known IP</span>
              <strong><?= e($admin->last_login_ip ?? '127.0.0.1') ?></strong>
            </div>
            <div class="detail-item">
              <span>Account ID</span>
              <strong>#ADM-<?= sprintf('%04d', $admin->id ?? 1) ?></strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Guidance Card -->
      <div class="card-box mb-4">
        <div class="card-box-head">
          <h2><i class="bi bi-info-circle"></i> Login Credentials Info</h2>
        </div>
        <div class="card-box-body">
          <div class="setting-row">
            <div class="setting-row-text">
              <strong>Dual Identifier Sign-In</strong>
              <span>You can log in at <code>/admin/login</code> with either your email address or your registered phone number.</span>
            </div>
            <span class="status status-confirmed"><i class="bi bi-check-lg"></i> Enabled</span>
          </div>
          <div class="setting-row">
            <div class="setting-row-text">
              <strong>Password Protection</strong>
              <span>Passwords are hashed using PHP's native <code>bcrypt</code> algorithm with automatic cost adjustment.</span>
            </div>
            <span class="status status-confirmed"><i class="bi bi-lock-fill"></i> Active</span>
          </div>
          <div class="setting-row">
            <div class="setting-row-text">
              <strong>CSRF Protection</strong>
              <span>All settings and credential modification requests are guarded with cryptographic session tokens.</span>
            </div>
            <span class="status status-confirmed"><i class="bi bi-shield-check"></i> Verified</span>
          </div>
        </div>
      </div>

      <!-- Environment Card -->
      <div class="card-box">
        <div class="card-box-head">
          <h2><i class="bi bi-hdd-network"></i> Backend Environment</h2>
        </div>
        <div class="card-box-body">
          <div class="detail-grid" style="margin:0;">
            <div class="detail-item">
              <span>Database Engine</span>
              <strong>MySQL 8.0+ / MariaDB</strong>
            </div>
            <div class="detail-item">
              <span>Database Name</span>
              <strong>travelbookings</strong>
            </div>
            <div class="detail-item">
              <span>Host / Port</span>
              <strong>127.0.0.1:3306</strong>
            </div>
            <div class="detail-item">
              <span>Server Environment</span>
              <strong>Apache 2.4 (XAMPP)</strong>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
  // Password Visibility Toggle
  document.querySelectorAll('.toggle-pwd-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var targetId = this.getAttribute('data-target');
      var input = document.getElementById(targetId);
      var icon = this.querySelector('i');
      if (!input) return;

      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    });
  });
</script>
