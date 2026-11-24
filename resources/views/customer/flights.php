<?php $this->layout('layouts/customer', ['title' => 'My Flights — Customer Dashboard — CJ Travels', 'page' => 'flights']); ?>

<section class="view active" id="view-flights" data-view="flights" aria-labelledby="flightsTitle">
          <div class="page-head">
            <div>
              <h1 id="flightsTitle" class="h2 mb-1">
                My Flights <span class="demo-pill"><i class="bi bi-airplane-fill"></i> Flights</span>
              </h1>
              <p>Manage and review your confirmed, pending and completed flight bookings.</p>
            </div>
            <div class="page-head-actions">
              <a href="<?= route('flights') ?>" class="btn btn-primary">
                <i class="bi bi-search"></i> Book a new flight
              </a>
            </div>
          </div>

<?php
  $cCount = 0; $pCount = 0; $compCount = 0;
  foreach ($customerData['flights'] ?? [] as $fl) {
      $st = $fl['status'] ?? '';
      if ($st === 'Confirmed') $cCount++;
      elseif ($st === 'Pending') $pCount++;
      elseif ($st === 'Completed') $compCount++;
  }
?>

          <!-- FLIGHTS SUMMARY STATS -->
          <div class="stat-grid stat-grid-3">
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon green"><i class="bi bi-check-circle-fill"></i></span>
                <span class="stat-tag">Confirmed</span>
              </div>
              <span class="stat-value"><?= $cCount ?></span>
              <span class="stat-label">Confirmed Bookings</span>
              <div class="stat-foot">
                <i class="bi bi-airplane"></i> Verified scheduled tickets
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
                <i class="bi bi-hourglass-split"></i> Processing payment or airline
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon navy"><i class="bi bi-archive-fill"></i></span>
                <span class="stat-tag">Completed</span>
              </div>
              <span class="stat-value"><?= $compCount ?></span>
              <span class="stat-label">Past Journeys</span>
              <div class="stat-foot muted">
                <i class="bi bi-check2"></i> Flown flights
              </div>
            </div>
          </div>

          <!-- FLIGHTS TABLE -->
          <div class="card-box">
            <div class="card-box-head">
              <h2><i class="bi bi-airplane-engines"></i> Flight Reservations</h2>
              <span class="badge-soft" id="flightTableCount">4 records</span>
            </div>
            <div class="card-box-body p-0">
              <div class="table-responsive">
                <table class="table-clean">
                  <thead>
                    <tr>
                      <th>Reference</th>
                      <th>Airline & flight</th>
                      <th>Route</th>
                      <th>Departure</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="flightsTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
