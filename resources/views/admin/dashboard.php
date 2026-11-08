<?php $this->layout('layouts/admin', ['title' => 'Dashboard — CJ Travels Admin', 'page' => 'dashboard']); ?>

<style>
  .live-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-family: 'DM Sans', sans-serif;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .12em;
    text-transform: uppercase;
    padding: 4px 10px;
    background: rgba(46, 125, 91, 0.12);
    border: 1px solid rgba(46, 125, 91, 0.3);
    color: var(--green);
    border-radius: 20px;
    vertical-align: middle;
    margin-left: 8px;
  }
  .live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--green);
    box-shadow: 0 0 0 3px rgba(46, 125, 91, 0.25);
    display: inline-block;
    animation: livePulse 1.8s infinite;
  }
  @keyframes livePulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.8); }
  }
  .spin-icon {
    display: inline-block;
    animation: spinAnim 0.75s linear infinite;
  }
  @keyframes spinAnim {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
</style>

<section class="view active" id="view-dashboard" data-view="dashboard">
  <div class="page-head">
    <div>
      <h1>Admin dashboard <span class="live-pill" id="liveFeedPill"><span class="live-dot"></span> Live MySQL Feed</span></h1>
      <p>Real-time overview of customers, bookings and platform revenue &bull; Last synchronized: <span id="liveTimestamp" style="font-weight:600;color:var(--navy);"><?= date('g:i:s A') ?></span></p>
    </div>
    <div class="page-head-actions">
      <button type="button" class="btn btn-ghost" id="btnRefreshLive" title="Poll MySQL database now">
        <i class="bi bi-arrow-clockwise" id="refreshIcon"></i> Refresh Data
      </button>
      <a href="<?= route('admin.reports') ?>" class="btn btn-ghost">
        <i class="bi bi-bar-chart-line"></i> View reports
      </a>
      <button type="button" class="btn btn-gold" id="addDestinationBtnTop">
        <i class="bi bi-plus-lg"></i> Add destination
      </button>
    </div>
  </div>

  <div class="info-note">
    <i class="bi bi-shield-check"></i>
    <div>
      <strong>Live MySQL Database Connected.</strong> All metrics, flight reservations, hotel inventory, financing applications, and transaction volumes shown below are queried directly in real time from the <code>travelbookings</code> MySQL database.
    </div>
  </div>

  <!-- KPI CARDS -->
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-top">
        <span class="stat-icon"><i class="bi bi-people-fill"></i></span>
        <span class="stat-tag">All time</span>
      </div>
      <span class="stat-value" id="statCustomers"><?= (int)($stats['customers'] ?? 0) ?></span>
      <span class="stat-label">Total customers</span>
      <span class="stat-foot"><i class="bi bi-person-check-fill"></i> <span id="statActiveCustomers"><?= (int)($stats['activeCustomers'] ?? 0) ?></span> active accounts</span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span class="stat-icon gold"><i class="bi bi-airplane-fill"></i></span>
        <span class="stat-tag">Bookings</span>
      </div>
      <span class="stat-value" id="statFlights"><?= (int)($stats['flights'] ?? 0) ?></span>
      <span class="stat-label">Flight bookings</span>
      <span class="stat-foot"><i class="bi bi-clock-history"></i> <span id="statPendingFlights"><?= (int)($stats['pendingFlights'] ?? 0) ?></span> pending</span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span class="stat-icon navy"><i class="bi bi-building-fill"></i></span>
        <span class="stat-tag">Reservations</span>
      </div>
      <span class="stat-value" id="statHotels"><?= (int)($stats['hotels'] ?? 0) ?></span>
      <span class="stat-label">Hotel reservations</span>
      <span class="stat-foot"><i class="bi bi-clock-history"></i> <span id="statPendingHotels"><?= (int)($stats['pendingHotels'] ?? 0) ?></span> pending</span>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <span class="stat-icon violet"><i class="bi bi-cash-coin"></i></span>
        <span class="stat-tag">Applications</span>
      </div>
      <span class="stat-value" id="statLoans"><?= (int)($stats['loans'] ?? 0) ?></span>
      <span class="stat-label">Financing applications</span>
      <span class="stat-foot muted"><i class="bi bi-shield-check"></i> In review &amp; active</span>
    </div>
  </div>

  <!-- ROW 1: BOOKING ACTIVITY & STATUS OVERVIEW -->
  <div class="row g-3 mb-3">
    <div class="col-lg-7">
      <div class="card-box h-100">
        <div class="card-box-head">
          <h2><i class="bi bi-bar-chart-fill"></i> Booking activity</h2>
          <span class="head-sub">Live monthly distribution</span>
        </div>
        <div class="card-box-body">
          <div class="chart-legend">
            <span><i class="swatch swatch-sky"></i> Flights</span>
            <span><i class="swatch swatch-navy"></i> Hotels</span>
          </div>
          <div class="bar-chart" id="bookingChart" role="img" aria-label="Bar chart of monthly flight and hotel bookings"></div>
          <div class="bar-labels" id="bookingChartLabels"></div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card-box h-100">
        <div class="card-box-head">
          <h2><i class="bi bi-pie-chart-fill"></i> Booking status overview</h2>
        </div>
        <div class="card-box-body">
          <div class="donut-wrap">
            <div class="donut" id="statusDonut" role="img" aria-label="Donut chart of booking statuses">
              <div class="donut-center">
                <strong id="statusDonutTotal"><?= (int)($totalBookings ?? 0) ?></strong>
                <span>Bookings</span>
              </div>
            </div>
            <div class="donut-legend" id="statusLegend"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ROW 2: RECENT ACTIVITY & TRANSACTION OVERVIEW -->
  <div class="row g-3 mb-3">
    <div class="col-lg-5">
      <div class="card-box h-100">
        <div class="card-box-head">
          <h2><i class="bi bi-activity"></i> Recent activity</h2>
        </div>
        <div class="card-box-body">
          <ul class="activity-list" id="activityList">
            <?php if (!empty($activity)): ?>
              <?php foreach ($activity as $item): ?>
                <li class="activity-item">
                  <span class="activity-icon <?= htmlspecialchars($item['iconClass'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                  </span>
                  <div class="activity-body">
                    <strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($item['meta'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <span class="activity-time"><?= htmlspecialchars($item['time'], ENT_QUOTES, 'UTF-8') ?></span>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="text-muted p-3">No activity recorded yet.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card-box h-100">
        <div class="card-box-head">
          <h2><i class="bi bi-wallet2"></i> Transaction overview</h2>
          <a href="<?= route('admin.transactions') ?>" class="btn-link-soft">
            All transactions <i class="bi bi-arrow-right"></i>
          </a>
        </div>
        <div class="card-box-body">
          <div class="row g-3 text-center mb-3">
            <div class="col-4">
              <span class="stat-value" style="font-size:1.5rem;" id="txnTotal"><?= htmlspecialchars($stats['formattedTxnTotal'] ?? '₦0', ENT_QUOTES, 'UTF-8') ?></span>
              <span class="stat-label">Total value</span>
            </div>
            <div class="col-4">
              <span class="stat-value" style="font-size:1.5rem;color:var(--green);" id="txnPaid"><?= htmlspecialchars($stats['formattedTxnPaid'] ?? '₦0', ENT_QUOTES, 'UTF-8') ?></span>
              <span class="stat-label">Paid</span>
            </div>
            <div class="col-4">
              <span class="stat-value" style="font-size:1.5rem;color:var(--gold);" id="txnPending"><?= htmlspecialchars($stats['formattedTxnPending'] ?? '₦0', ENT_QUOTES, 'UTF-8') ?></span>
              <span class="stat-label">Pending</span>
            </div>
          </div>
          <div class="chart-legend mb-0">
            <span><i class="swatch swatch-green"></i> Paid</span>
            <span><i class="swatch swatch-gold"></i> Pending</span>
            <span><i class="swatch swatch-violet"></i> Refunded</span>
            <span><i class="swatch swatch-red"></i> Failed</span>
          </div>
          <div class="bar-chart" style="height:120px;" id="txnChart"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ROW 3: RECENT BOOKINGS TABLE -->
  <div class="card-box">
    <div class="card-box-head">
      <h2><i class="bi bi-clock-history"></i> Recent bookings</h2>
      <div class="d-flex gap-2">
        <a href="<?= route('admin.flights') ?>" class="btn-link-soft">Flights</a>
        <a href="<?= route('admin.hotels') ?>" class="btn-link-soft">Hotels</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table-clean">
        <thead>
          <tr>
            <th scope="col">Reference</th>
            <th scope="col">Customer</th>
            <th scope="col">Booking</th>
            <th scope="col">Travel date</th>
            <th scope="col">Amount</th>
            <th scope="col">Status</th>
            <th scope="col" class="text-end">Action</th>
          </tr>
        </thead>
        <tbody id="recentBookingsBody">
          <?php if (!empty($recentBookings)): ?>
            <?php foreach ($recentBookings as $row): ?>
              <tr>
                <td><strong><?= htmlspecialchars($row['ref'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td><?= htmlspecialchars($row['customer'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span class="booking-type-pill <?= $row['type'] === 'flight' ? 'flight' : 'hotel' ?>">
                    <i class="bi <?= $row['type'] === 'flight' ? 'bi-airplane-fill' : 'bi-building-fill' ?>"></i>
                  </span>
                  <?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td><?= htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><strong><?= htmlspecialchars($row['formattedAmount'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td>
                  <span class="status-pill status-<?= strtolower(str_replace(' ', '', $row['status'])) ?>">
                    <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td class="text-end">
                  <button type="button" class="btn btn-ghost btn-xs js-view" data-kind="<?= htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') ?>" data-id="<?= htmlspecialchars($row['ref'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No recent bookings recorded.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- REAL-TIME DATA EMBED & LIVE POLLING SCRIPT -->
<script id="serverDashboardData" type="application/json">
<?= json_encode([
    'stats' => $stats ?? [],
    'statusCounts' => $statusCounts ?? [],
    'totalBookings' => $totalBookings ?? 0,
    'bookingChart' => $bookingChart ?? [],
    'recentBookings' => $recentBookings ?? [],
    'activity' => $activity ?? [],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var statsApiUrl = '<?= route("admin.api.dashboard-stats") ?>';
  var refreshBtn = document.getElementById('btnRefreshLive');
  var refreshIcon = document.getElementById('refreshIcon');
  var timestampEl = document.getElementById('liveTimestamp');

  // Load initial server-rendered data
  var serverDataEl = document.getElementById('serverDashboardData');
  if (serverDataEl) {
    try {
      var initialData = JSON.parse(serverDataEl.textContent);
      applyLiveCharts(initialData);
    } catch (e) {
      console.warn('Initial chart parse error:', e);
    }
  }

  // Polling function
  function pollLiveStats(isManual) {
    if (isManual && refreshIcon) {
      refreshIcon.classList.add('spin-icon');
    }

    fetch(statsApiUrl, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    })
    .then(function (payload) {
      if (payload && payload.success && payload.data) {
        updateDashboardDOM(payload.data);
        if (timestampEl && payload.formattedTime) {
          timestampEl.textContent = payload.formattedTime;
        }
      }
    })
    .catch(function (err) {
      console.warn('Real-time poll error:', err);
    })
    .finally(function () {
      if (isManual && refreshIcon) {
        setTimeout(function () {
          refreshIcon.classList.remove('spin-icon');
        }, 500);
      }
    });
  }

  // Update DOM with fresh database metrics
  function updateDashboardDOM(d) {
    if (!d || !d.stats) return;

    // 1. KPI cards
    var el;
    if ((el = document.getElementById('statCustomers'))) el.textContent = d.stats.customers;
    if ((el = document.getElementById('statActiveCustomers'))) el.textContent = d.stats.activeCustomers;
    if ((el = document.getElementById('statFlights'))) el.textContent = d.stats.flights;
    if ((el = document.getElementById('statPendingFlights'))) el.textContent = d.stats.pendingFlights;
    if ((el = document.getElementById('statHotels'))) el.textContent = d.stats.hotels;
    if ((el = document.getElementById('statPendingHotels'))) el.textContent = d.stats.pendingHotels;
    if ((el = document.getElementById('statLoans'))) el.textContent = d.stats.loans;

    // 2. Transaction values
    if ((el = document.getElementById('txnTotal'))) el.textContent = d.stats.formattedTxnTotal;
    if ((el = document.getElementById('txnPaid'))) el.textContent = d.stats.formattedTxnPaid;
    if ((el = document.getElementById('txnPending'))) el.textContent = d.stats.formattedTxnPending;

    // 3. Status Donut total
    if ((el = document.getElementById('statusDonutTotal'))) el.textContent = d.totalBookings;

    // 4. Re-render Charts
    applyLiveCharts(d);

    // 5. Update Recent Bookings Table
    if (d.recentBookings && d.recentBookings.length > 0) {
      var tbody = document.getElementById('recentBookingsBody');
      if (tbody) {
        var html = '';
        d.recentBookings.forEach(function (row) {
          var typeClass = (row.type === 'flight') ? 'flight' : 'hotel';
          var iconClass = (row.type === 'flight') ? 'bi-airplane-fill' : 'bi-building-fill';
          var stClass = 'status-' + (row.status || '').toLowerCase().replace(/\s+/g, '');

          html += '<tr>' +
            '<td><strong>' + escapeHTML(row.ref) + '</strong></td>' +
            '<td>' + escapeHTML(row.customer) + '</td>' +
            '<td><span class="booking-type-pill ' + typeClass + '"><i class="bi ' + iconClass + '"></i></span> ' + escapeHTML(row.title) + '</td>' +
            '<td>' + escapeHTML(row.date) + '</td>' +
            '<td><strong>' + escapeHTML(row.formattedAmount) + '</strong></td>' +
            '<td><span class="status-pill ' + stClass + '">' + escapeHTML(row.status) + '</span></td>' +
            '<td class="text-end"><button type="button" class="btn btn-ghost btn-xs js-view" data-kind="' + escapeHTML(row.type) + '" data-id="' + escapeHTML(row.ref) + '"><i class="bi bi-eye"></i> View</button></td>' +
            '</tr>';
        });
        tbody.innerHTML = html;
      }
    }
  }

  // Render Charts based on live data
  function applyLiveCharts(data) {
    if (!data) return;

    // A. Booking Activity Bars
    var chartEl = document.getElementById('bookingChart');
    var labelsEl = document.getElementById('bookingChartLabels');
    if (chartEl && data.bookingChart && data.bookingChart.length > 0) {
      var maxVal = 1;
      data.bookingChart.forEach(function (m) {
        if (m.flights > maxVal) maxVal = m.flights;
        if (m.hotels > maxVal) maxVal = m.hotels;
      });

      var barsHtml = '';
      var labelsHtml = '';
      data.bookingChart.forEach(function (m) {
        var fH = Math.max(6, Math.round((m.flights / maxVal) * 100));
        var hH = Math.max(6, Math.round((m.hotels / maxVal) * 100));

        barsHtml += '<div class="bar-col">' +
          '<div class="bar-pair">' +
            '<div class="bar bar-sky" style="height:' + fH + '%;" title="' + m.flights + ' Flights in ' + m.month + '"></div>' +
            '<div class="bar bar-navy" style="height:' + hH + '%;" title="' + m.hotels + ' Hotels in ' + m.month + '"></div>' +
          '</div>' +
        '</div>';

        labelsHtml += '<span>' + m.month + '</span>';
      });

      chartEl.innerHTML = barsHtml;
      if (labelsEl) labelsEl.innerHTML = labelsHtml;
    }

    // B. Status Donut
    var donutEl = document.getElementById('statusDonut');
    var legendEl = document.getElementById('statusLegend');
    if (donutEl && data.statusCounts) {
      var total = Math.max(1, data.totalBookings || 1);
      var confirmed = data.statusCounts.Confirmed || 0;
      var pending = data.statusCounts.Pending || 0;
      var completed = data.statusCounts.Completed || 0;
      var cancelled = data.statusCounts.Cancelled || 0;

      var pConf = (confirmed / total) * 100;
      var pPend = (pending / total) * 100;
      var pComp = (completed / total) * 100;
      var pCanc = (cancelled / total) * 100;

      // conic-gradient
      var g1 = pConf;
      var g2 = g1 + pPend;
      var g3 = g2 + pComp;

      donutEl.style.background = 'conic-gradient(' +
        'var(--green) 0% ' + g1 + '%, ' +
        'var(--gold) ' + g1 + '% ' + g2 + '%, ' +
        'var(--navy-soft) ' + g2 + '% ' + g3 + '%, ' +
        'var(--error) ' + g3 + '% 100%)';

      if (legendEl) {
        legendEl.innerHTML =
          '<div class="legend-item"><span class="legend-dot" style="background:var(--green)"></span><span>Confirmed (' + confirmed + ')</span></div>' +
          '<div class="legend-item"><span class="legend-dot" style="background:var(--gold)"></span><span>Pending (' + pending + ')</span></div>' +
          '<div class="legend-item"><span class="legend-dot" style="background:var(--navy-soft)"></span><span>Completed (' + completed + ')</span></div>' +
          '<div class="legend-item"><span class="legend-dot" style="background:var(--error)"></span><span>Cancelled (' + cancelled + ')</span></div>';
      }
    }

    // C. Transaction Bar Breakdown
    var txnChart = document.getElementById('txnChart');
    if (txnChart && data.stats) {
      var maxTxn = Math.max(1, data.stats.txnTotal || 1);
      var pPaid = Math.max(4, Math.round((data.stats.txnPaid / maxTxn) * 100));
      var pPending = Math.max(4, Math.round((data.stats.txnPending / maxTxn) * 100));
      var pRefunded = Math.max(2, Math.round((data.stats.txnRefunded / maxTxn) * 100));
      var pFailed = Math.max(2, Math.round((data.stats.txnFailed / maxTxn) * 100));

      txnChart.innerHTML = '<div class="bar-col w-100 d-flex gap-2 align-items-end" style="height:120px;">' +
        '<div class="bar bar-green flex-fill" style="height:' + pPaid + '%;" title="Paid: ' + data.stats.formattedTxnPaid + '"></div>' +
        '<div class="bar bar-gold flex-fill" style="height:' + pPending + '%;" title="Pending: ' + data.stats.formattedTxnPending + '"></div>' +
        '<div class="bar bar-violet flex-fill" style="height:' + pRefunded + '%;" title="Refunded"></div>' +
        '<div class="bar bar-red flex-fill" style="height:' + pFailed + '%;" title="Failed"></div>' +
      '</div>';
    }
  }

  function escapeHTML(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function (m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
  }

  // Refresh button click
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      pollLiveStats(true);
    });
  }

  // Auto-poll every 15 seconds
  setInterval(function () {
    pollLiveStats(false);
  }, 15000);
});
</script>
