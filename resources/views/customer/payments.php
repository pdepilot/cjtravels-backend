<?php $this->layout('layouts/customer', ['title' => 'Payments — Customer Dashboard — CJ Travels', 'page' => 'payments']); ?>

<section class="view active" id="view-payments" data-view="payments" aria-labelledby="paymentsTitle">
          <div class="page-head">
            <div>
              <h1 id="paymentsTitle" class="h2 mb-1">
                Payments & Invoices <span class="demo-pill"><i class="bi bi-credit-card-2-front-fill"></i> Invoices</span>
              </h1>
              <p>Review transaction records, receipts and payment methods for your bookings.</p>
            </div>
          </div>

<?php
  $settledTotal = 0;
  $settledCount = 0;
  $pendingTotal = 0;
  foreach ($customerData['payments'] ?? [] as $pm) {
      if (($pm['status'] ?? '') === 'Paid') {
          $settledTotal += (float)($pm['amount'] ?? 0);
          $settledCount++;
      } elseif (($pm['status'] ?? '') === 'Pending') {
          $pendingTotal += (float)($pm['amount'] ?? 0);
      }
  }
?>

          <!-- PAYMENTS STATS -->
          <div class="stat-grid stat-grid-3">
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon green"><i class="bi bi-check-circle-fill"></i></span>
                <span class="stat-tag">Settled</span>
              </div>
              <span class="stat-value">₦<?= number_format($settledTotal) ?></span>
              <span class="stat-label">Total Settled</span>
              <div class="stat-foot">
                <i class="bi bi-receipt"></i> <?= $settledCount ?> transactions paid
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon gold"><i class="bi bi-clock-history"></i></span>
                <span class="stat-tag">Pending</span>
              </div>
              <span class="stat-value">₦<?= number_format($pendingTotal) ?></span>
              <span class="stat-label">Pending Invoices</span>
              <div class="stat-foot">
                <i class="bi bi-hourglass-split"></i> Awaiting gateway settlement
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon navy"><i class="bi bi-credit-card"></i></span>
                <span class="stat-tag">Records</span>
              </div>
              <span class="stat-value" style="font-size:1.5rem;padding-top:8px;"><?= count($customerData['payments'] ?? []) ?> Records</span>
              <span class="stat-label">Total Transactions</span>
              <div class="stat-foot">
                <i class="bi bi-shield-lock-fill"></i> Live MySQL verified
              </div>
            </div>
          </div>

          <!-- PAYMENTS TABLE -->
          <div class="card-box">
            <div class="card-box-head">
              <h2><i class="bi bi-receipt"></i> Payment Records</h2>
              <span class="badge-soft" id="paymentTableCount">4 records</span>
            </div>
            <div class="card-box-body p-0">
              <div class="table-responsive">
                <table class="table-clean">
                  <thead>
                    <tr>
                      <th>Reference</th>
                      <th>Related booking</th>
                      <th>Method</th>
                      <th>Date</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="paymentsTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
