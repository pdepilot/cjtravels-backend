<!-- FLOATING SIDE NAV -->
<nav class="float-nav" id="floatNav" aria-label="Admin navigation">
  <a class="float-brand" href="<?= route('home') ?>" aria-label="CJ Travels home">
        <img src="<?= asset('images/logo-mark.png') ?>" alt="CJ Travels" style="width:28px;height:28px;object-fit:contain;flex-shrink:0;" onerror="this.onerror=null;this.src='<?= asset('images/logo.png') ?>';" />
        <span class="float-btn-label">CJ Travels Admin</span>
      </a>
  <div class="float-nav-divider" aria-hidden="true"></div>
  <a class="float-btn <?= routeIs('admin.dashboard') ? 'active' : '' ?>" href="<?= route('admin.dashboard') ?>" <?= routeIs('admin.dashboard') ? 'aria-current="page"' : '' ?> aria-label="Dashboard">
    <i class="bi bi-grid-1x2-fill"></i>
    <span class="float-btn-label">Dashboard</span>
  </a>
  <a class="float-btn <?= routeIs('admin.customers') ? 'active' : '' ?>" href="<?= route('admin.customers') ?>" <?= routeIs('admin.customers') ? 'aria-current="page"' : '' ?> aria-label="Customers">
    <i class="bi bi-people-fill"></i>
    <span class="float-btn-label">Customers</span>
    <span class="float-badge" id="badgeCustomers"><?= (int)($liveCounts['customers'] ?? 0) ?></span>
  </a>
  <a class="float-btn <?= routeIs('admin.flights') ? 'active' : '' ?>" href="<?= route('admin.flights') ?>" <?= routeIs('admin.flights') ? 'aria-current="page"' : '' ?> aria-label="Flights">
    <i class="bi bi-airplane-fill"></i>
    <span class="float-btn-label">Flights</span>
    <span class="float-badge" id="badgeFlights"><?= (int)($liveCounts['flights'] ?? 0) ?></span>
  </a>
  <a class="float-btn <?= routeIs('admin.hotels') ? 'active' : '' ?>" href="<?= route('admin.hotels') ?>" <?= routeIs('admin.hotels') ? 'aria-current="page"' : '' ?> aria-label="Hotels">
    <i class="bi bi-building-fill"></i>
    <span class="float-btn-label">Hotels</span>
    <span class="float-badge" id="badgeHotels"><?= (int)($liveCounts['hotels'] ?? 0) ?></span>
  </a>
  <a class="float-btn <?= routeIs('admin.loans') ? 'active' : '' ?>" href="<?= route('admin.loans') ?>" <?= routeIs('admin.loans') ? 'aria-current="page"' : '' ?> aria-label="Travel loans">
    <i class="bi bi-cash-coin"></i>
    <span class="float-btn-label">Travel Loans</span>
    <span class="float-badge" id="badgeLoans"><?= (int)($liveCounts['loans'] ?? 0) ?></span>
  </a>
  <div class="float-nav-divider" aria-hidden="true"></div>
  <a class="float-btn <?= routeIs('admin.destinations') ? 'active' : '' ?>" href="<?= route('admin.destinations') ?>" <?= routeIs('admin.destinations') ? 'aria-current="page"' : '' ?> aria-label="Destinations">
    <i class="bi bi-compass-fill"></i>
    <span class="float-btn-label">Destinations</span>
    <span class="float-badge" id="badgeDestinations"><?= (int)($liveCounts['destinations'] ?? 0) ?></span>
  </a>
  <a class="float-btn <?= routeIs('admin.transactions') ? 'active' : '' ?>" href="<?= route('admin.transactions') ?>" <?= routeIs('admin.transactions') ? 'aria-current="page"' : '' ?> aria-label="Transactions">
    <i class="bi bi-credit-card-2-front-fill"></i>
    <span class="float-btn-label">Transactions</span>
    <span class="float-badge" id="badgeTransactions"><?= (int)($liveCounts['transactions'] ?? 0) ?></span>
  </a>
  <a class="float-btn <?= routeIs('admin.api') ? 'active' : '' ?>" href="<?= route('admin.api') ?>" <?= routeIs('admin.api') ? 'aria-current="page"' : '' ?> aria-label="API integrations">
    <i class="bi bi-cpu-fill"></i>
    <span class="float-btn-label">API Integrations</span>
    <span class="float-badge" id="badgeApi"><?= (int)($liveCounts['api'] ?? 0) ?></span>
  </a>
  <a class="float-btn <?= routeIs('admin.emails') ? 'active' : '' ?>" href="<?= route('admin.emails') ?>" <?= routeIs('admin.emails') ? 'aria-current="page"' : '' ?> aria-label="Email">
    <i class="bi bi-envelope-paper-fill"></i>
    <span class="float-btn-label">Email</span>
  </a>
  <a class="float-btn <?= routeIs('admin.messages') ? 'active' : '' ?>" href="<?= route('admin.messages') ?>" <?= routeIs('admin.messages') ? 'aria-current="page"' : '' ?> aria-label="Messages">
    <i class="bi bi-envelope-fill"></i>
    <span class="float-btn-label">Messages</span>
    <span class="float-badge" id="badgeMessages"><?= (int)($liveCounts['messages'] ?? 0) ?></span>
  </a>
  <a class="float-btn <?= routeIs('admin.reports') ? 'active' : '' ?>" href="<?= route('admin.reports') ?>" <?= routeIs('admin.reports') ? 'aria-current="page"' : '' ?> aria-label="Reports">
    <i class="bi bi-bar-chart-fill"></i>
    <span class="float-btn-label">Reports</span>
  </a>
  <div class="float-nav-divider" aria-hidden="true"></div>
  <a class="float-btn <?= routeIs('admin.settings') ? 'active' : '' ?>" href="<?= route('admin.settings') ?>" <?= routeIs('admin.settings') ? 'aria-current="page"' : '' ?> aria-label="Settings">
    <i class="bi bi-gear-fill"></i>
    <span class="float-btn-label">Settings</span>
  </a>
  <button type="button" class="float-btn logout" data-bs-toggle="modal" data-bs-target="#logoutModal" aria-label="Logout">
    <i class="bi bi-box-arrow-right"></i>
    <span class="float-btn-label">Logout</span>
  </button>
  <a class="float-btn" href="<?= route('home') ?>" aria-label="Website Home" title="Back to Website Home">
    <i class="bi bi-house-door"></i>
    <span class="float-btn-label">Public Website</span>
  </a>
</nav>

