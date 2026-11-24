<?php $this->layout('layouts/customer', ['title' => 'My Hotels — Customer Dashboard — CJ Travels', 'page' => 'hotels']); ?>

<section class="view active" id="view-hotels" data-view="hotels" aria-labelledby="hotelsTitle">
          <div class="page-head">
            <div>
              <h1 id="hotelsTitle" class="h2 mb-1">
                My Hotels <span class="demo-pill"><i class="bi bi-building-fill"></i> Hotels</span>
              </h1>
              <p>View your upcoming and completed luxury hotel reservations and stay details.</p>
            </div>
            <div class="page-head-actions">
              <a href="<?= route('hotels') ?>" class="btn btn-primary">
                <i class="bi bi-search"></i> Book a hotel
              </a>
            </div>
          </div>

<?php
  $cCount = 0; $pCount = 0; $compCount = 0;
  foreach ($customerData['hotels'] ?? [] as $ht) {
      $st = $ht['status'] ?? '';
      if ($st === 'Confirmed') $cCount++;
      elseif ($st === 'Pending') $pCount++;
      elseif ($st === 'Completed') $compCount++;
  }
?>

          <!-- HOTELS SUMMARY STATS -->
          <div class="stat-grid stat-grid-3">
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon green"><i class="bi bi-check-circle-fill"></i></span>
                <span class="stat-tag">Confirmed</span>
              </div>
              <span class="stat-value"><?= $cCount ?></span>
              <span class="stat-label">Confirmed Stay</span>
              <div class="stat-foot">
                <i class="bi bi-building"></i> Reserved accommodation
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon gold"><i class="bi bi-clock-history"></i></span>
                <span class="stat-tag">Pending</span>
              </div>
              <span class="stat-value"><?= $pCount ?></span>
              <span class="stat-label">Awaiting Confirmation</span>
              <div class="stat-foot">
                <i class="bi bi-hourglass-split"></i> Partner processing
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon navy"><i class="bi bi-archive-fill"></i></span>
                <span class="stat-tag">Completed</span>
              </div>
              <span class="stat-value"><?= $compCount ?></span>
              <span class="stat-label">Completed Stays</span>
              <div class="stat-foot muted">
                <i class="bi bi-check2"></i> Past stays
              </div>
            </div>
          </div>

          <!-- HOTELS TABLE -->
          <div class="card-box">
            <div class="card-box-head">
              <h2><i class="bi bi-building"></i> Hotel Reservations</h2>
              <span class="badge-soft" id="hotelTableCount">3 records</span>
            </div>
            <div class="card-box-body p-0">
              <div class="table-responsive">
                <table class="table-clean">
                  <thead>
                    <tr>
                      <th>Reference</th>
                      <th>Property</th>
                      <th>Location</th>
                      <th>Stay</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="hotelsTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
