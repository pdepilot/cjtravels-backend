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

  <div class="card-box mb-4" id="public-phone">
    <div class="card-box-head">
      <h2><i class="bi bi-whatsapp" style="color:#25D366;"></i> Public phone and WhatsApp</h2>
    </div>
    <div class="card-box-body">
      <p class="text-muted mb-3" style="font-size:0.92rem;">
        This number is shown on the website and opens WhatsApp when a customer searches for a flight or hotel.
        The current WhatsApp link uses <strong><?= e($contactPhone['whatsapp'] ?? '') ?></strong>.
      </p>
      <form action="<?= route('admin.settings.contact-phone') ?>" method="POST" class="row g-3 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-6">
          <label class="form-label" for="contactPhone"><strong>Phone number</strong></label>
          <input type="tel" class="form-control" id="contactPhone" name="contact_phone" value="<?= e($contactPhone['display'] ?? '') ?>" required placeholder="08135196772" />
          <small class="text-muted" style="font-size:0.8rem;">Use the local number, for example 08135196772. A leading 0 is converted for WhatsApp.</small>
        </div>
        <div class="col-md-auto">
          <button type="submit" class="btn btn-primary">Save phone number</button>
        </div>
      </form>
    </div>
  </div>

  <!-- PROMOTIONAL FLIER & CAMPAIGN MANAGER -->
  <div class="card-box mb-4" id="promo-settings">
    <div class="card-box-head d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h2><i class="bi bi-megaphone-fill" style="color:var(--gold);"></i> Promotional Flier &amp; Campaign Manager</h2>
      <div>
        <?php if (!empty($promoCampaign['is_active'])): ?>
          <span class="status status-confirmed"><i class="bi bi-broadcast"></i> Campaign Live &amp; Active</span>
        <?php else: ?>
          <span class="status" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;"><i class="bi bi-pause-circle"></i> Campaign Paused</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-box-body">
      <p class="text-muted mb-4" style="font-size:0.92rem;">
        Upload a promotional banner or flier and customize the real-time campaign details displayed to travelers inside the luxury modal popup on the public website.
      </p>

      <form action="<?= route('admin.settings.promo') ?>" method="POST" enctype="multipart/form-data" id="promoCampaignForm">
        <?= csrf_field() ?>

        <div class="row g-4">
          <!-- LEFT SUB-COLUMN: Flier Image Upload & Live Preview -->
          <div class="col-lg-5">
            <div style="background:var(--cream-soft);border:1px solid var(--hairline);border-radius:6px;padding:18px;">
              <label class="form-label mb-2 d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-image text-navy me-1"></i> Promotional Flier Image</strong>
                <small class="text-muted">JPG, PNG, WEBP, GIF (Max 10MB)</small>
              </label>

              <!-- Image Display / Preview Area -->
              <div id="flierPreviewBox" style="position:relative;background:#050E1C;border:1px dashed var(--hairline);border-radius:6px;min-height:200px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:14px;">
                <?php if (!empty($promoCampaign['flier_image'])): ?>
                  <img id="activeFlierImg" src="<?= asset($promoCampaign['flier_image']) ?>" alt="Active Promotional Flier" style="width:100%;max-height:260px;object-fit:cover;display:block;" />
                  <div style="position:absolute;top:10px;left:10px;background:rgba(11,30,61,0.88);backdrop-filter:blur(4px);color:#E0C58A;border:1px solid rgba(201,165,91,0.5);font-size:0.75rem;font-weight:700;padding:3px 9px;border-radius:100px;">
                    <i class="bi bi-check2-circle"></i> Live Flier
                  </div>
                  <a href="<?= asset($promoCampaign['flier_image']) ?>" target="_blank" style="position:absolute;bottom:10px;right:10px;background:rgba(0,0,0,0.7);color:#fff;font-size:0.75rem;padding:4px 10px;border-radius:4px;text-decoration:none;" title="Open original size in new tab">
                    <i class="bi bi-arrows-fullscreen"></i> View Full
                  </a>
                <?php else: ?>
                  <div id="noFlierPlaceholder" class="text-center p-4 text-muted">
                    <i class="bi bi-image" style="font-size:2.8rem;color:var(--hairline);display:block;margin-bottom:8px;"></i>
                    <span style="font-size:0.9rem;font-weight:500;">No promotional flier uploaded yet</span>
                    <p style="font-size:0.8rem;margin:4px 0 0;color:var(--muted);">Select an image below to feature your promotional poster.</p>
                  </div>
                <?php endif; ?>
                <!-- Live Client-side Preview Container -->
                <div id="newFilePreviewWrap" style="display:none;position:absolute;inset:0;background:#050E1C;align-items:center;justify-content:center;">
                  <img id="newFilePreviewImg" src="" alt="New Flier Preview" style="width:100%;height:100%;object-fit:contain;display:block;" />
                  <div style="position:absolute;top:10px;left:10px;background:rgba(46,125,91,0.9);color:#fff;font-size:0.75rem;font-weight:600;padding:3px 9px;border-radius:100px;">
                    <i class="bi bi-eye"></i> Pending Upload Preview
                  </div>
                </div>
              </div>

              <!-- File Input -->
              <div class="mb-3">
                <input type="file" class="form-control" id="flier_image" name="flier_image" accept="image/jpeg,image/png,image/webp,image/gif" />
                <div class="form-text" style="font-size:0.8rem;">
                  Recommended aspect ratio: 16:9 landscape or 4:5 portrait. Uploading a new image will replace the current one.
                </div>
              </div>

              <?php if (!empty($promoCampaign['flier_image'])): ?>
                <!-- Checkbox to Remove Current Flier -->
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="remove_flier" value="1" id="removeFlierCheckbox" />
                  <label class="form-check-label text-danger" for="removeFlierCheckbox" style="font-size:0.85rem;font-weight:600;">
                    <i class="bi bi-trash3"></i> Remove active flier (revert to typography-only modal)
                  </label>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- RIGHT SUB-COLUMN: Promotional Details & Controls -->
          <div class="col-lg-7">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="promoTitle"><strong>Campaign Title / Headline</strong> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="promoTitle" name="title" value="<?= e($promoCampaign['title'] ?? '') ?>" required placeholder="e.g. Unlock ₦50,000 Off Your Next Luxury Getaway" />
                <small class="text-muted" style="font-size:0.8rem;">Shown prominently in the modal header and public promotions.</small>
              </div>

              <div class="col-12">
                <label class="form-label" for="promoSubtitle"><strong>Campaign Subtitle / Tagline</strong></label>
                <textarea class="form-control" id="promoSubtitle" name="subtitle" rows="2" placeholder="e.g. Fly gracefully to London, Dubai, Paris, or explore paradise in Zanzibar..."><?= e($promoCampaign['subtitle'] ?? '') ?></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="promoCode"><strong>Voucher / Promo Code</strong> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text" style="background:#fff;border-color:var(--hairline);font-weight:700;color:var(--gold);"><i class="bi bi-tag-fill"></i></span>
                  <input type="text" class="form-control" id="promoCode" name="promo_code" value="<?= e($promoCampaign['promo_code'] ?? 'CJLUXE50K') ?>" required style="text-transform:uppercase;font-family:var(--font-mono);font-weight:700;letter-spacing:1px;" />
                </div>
                <small class="text-muted" style="font-size:0.8rem;">Code applied automatically at checkout.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="discountAmount"><strong>Discount Value Display</strong> <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text" style="background:#fff;border-color:var(--hairline);"><i class="bi bi-cash-stack"></i></span>
                  <input type="text" class="form-control" id="discountAmount" name="discount_amount" value="<?= e($promoCampaign['discount_amount'] ?? '₦50,000') ?>" required placeholder="e.g. ₦50,000 or 25% OFF" />
                </div>
                <small class="text-muted" style="font-size:0.8rem;">Displayed on the certificate badge and buttons.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="promoStatusSelect"><strong>Campaign Display Status</strong></label>
                <select class="form-select" id="promoStatusSelect" name="is_active">
                  <option value="1" <?= (!empty($promoCampaign['is_active']) ? 'selected' : '') ?>>Active — Auto-Popup to Visitors</option>
                  <option value="0" <?= (empty($promoCampaign['is_active']) ? 'selected' : '') ?>>Paused — Hide Popup from Public</option>
                </select>
                <small class="text-muted" style="font-size:0.8rem;">Toggle offer availability anytime without deleting data.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label"><strong>Public Preview</strong></label>
                <div class="d-flex align-items-center h-100 pb-2">
                  <a href="<?= route('home') ?>" target="_blank" class="btn btn-outline-secondary w-100" style="border-color:var(--hairline);background:#fff;color:var(--navy);font-weight:600;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Test on Website
                  </a>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 mt-4 pt-2" style="border-top:1px solid var(--hairline-soft);">
              <button type="submit" class="btn btn-navy" id="savePromoCampaignBtn">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Save &amp; Publish Promotional Flier
              </button>
              <button type="reset" class="btn btn-ghost" id="resetPromoFormBtn">
                Reset Changes
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

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
              <strong><?= !empty($admin->last_login_ip) ? e($admin->last_login_ip) : 'Not recorded' ?></strong>
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
              <strong><?= e($environment['engine'] ?? '') ?></strong>
            </div>
            <div class="detail-item">
              <span>Database Name</span>
              <strong><?= e($environment['name'] ?? '') ?></strong>
            </div>
            <div class="detail-item">
              <span>Host / Port</span>
              <strong><?= e($environment['host'] ?? '') ?></strong>
            </div>
            <div class="detail-item">
              <span>Server Environment</span>
              <strong><?= e($environment['server'] ?? '') ?></strong>
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

  // Promotional Flier Image Instant Preview
  var flierInput = document.getElementById('flier_image');
  var newPreviewWrap = document.getElementById('newFilePreviewWrap');
  var newPreviewImg = document.getElementById('newFilePreviewImg');
  var resetPromoBtn = document.getElementById('resetPromoFormBtn');

  if (flierInput && newPreviewWrap && newPreviewImg) {
    flierInput.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        var file = this.files[0];
        if (file.size > 10 * 1024 * 1024) {
          alert('Warning: The selected image is larger than 10MB (' + (file.size / (1024 * 1024)).toFixed(1) + 'MB). Please choose an image under 10MB.');
          this.value = '';
          newPreviewWrap.style.display = 'none';
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          newPreviewImg.src = e.target.result;
          newPreviewWrap.style.display = 'flex';
        };
        reader.readAsDataURL(file);
      } else {
        newPreviewWrap.style.display = 'none';
      }
    });

    if (resetPromoBtn) {
      resetPromoBtn.addEventListener('click', function () {
        setTimeout(function () {
          newPreviewWrap.style.display = 'none';
        }, 50);
      });
    }
  }
</script>
