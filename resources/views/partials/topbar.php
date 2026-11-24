<header class="dash-topbar">
  <div class="topbar-left">
    <?php if (!empty($isAdmin)): ?>
    <button type="button" class="admin-nav-toggle" id="adminNavToggle" aria-controls="floatNav" aria-expanded="false" aria-label="Open navigation menu">
      <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <?php endif; ?>
    <a href="<?= route('home') ?>" class="topbar-brand-link" title="CJ Travels Home" style="display:inline-flex;align-items:center;text-decoration:none;margin-right:8px;">
      <img src="<?= asset('images/logo-transparent.png') ?>" alt="CJ Travels" style="height:28px;width:auto;object-fit:contain;" onerror="this.onerror=null;this.src='<?= asset('images/logo.png') ?>';" />
    </a>
    <div class="topbar-crumb">
      <i class="bi bi-house-door-fill"></i>
      <a href="<?= isset($isAdmin) && $isAdmin ? route('admin.dashboard') : route('customer.dashboard') ?>" style="color:var(--muted);text-decoration:none;">Dashboard</a>
      <span class="sep">/</span>
      <span class="current" id="crumbCurrent"><?= e($title ?? 'Overview') ?></span>
    </div>
  </div>
  <div class="topbar-right">
    <button type="button" class="icon-btn" id="notificationsBtn" aria-label="Notifications">
      <i class="bi bi-bell-fill"></i>
      <span class="dot" aria-hidden="true"></span>
    </button>
    <button type="button" class="icon-btn" id="helpBtn" aria-label="Help centre">
      <i class="bi bi-question-lg"></i>
    </button>
    <div class="topbar-user">
      <span class="avatar"><?= isset($isAdmin) && $isAdmin ? e($adminInitials ?? 'AD') : e($user->initials ?? 'CU') ?></span>
      <span><?= isset($isAdmin) && $isAdmin ? e($adminUser ?? 'Admin') : e($user->firstName ?? 'Customer') ?></span>
    </div>
  </div>
</header>

