<?php $this->layout('layouts/customer', ['title' => 'Travel Financing — Customer Dashboard — CJ Travels', 'page' => 'financing']); ?>

<section class="view active" id="view-financing" data-view="financing" aria-labelledby="financingTitle">
          <div class="page-head">
            <div>
              <h1 id="financingTitle" class="h2 mb-1">
                Travel Financing <span class="demo-pill"><i class="bi bi-cash-coin"></i> Financing</span>
              </h1>
              <p>Track your travel loan enquiries, repayment schedules, and partner financing status.</p>
            </div>
            <div class="page-head-actions">
              <a href="../contact.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New financing enquiry
              </a>
            </div>
          </div>

          <!-- INDEPENDENT LENDING DISCLOSURE -->
          <div class="info-note">
            <i class="bi bi-shield-exclamation"></i>
            <div>
              <strong>Independent Central Bank Licensed Lending Partners</strong><br />
              Travel Financing enquiries are assessed and administered exclusively by independent licensed financial institutions. CJ Travels acts solely as a discovery platform and does not grant loans, set interest rates, or make credit decisions.
            </div>
          </div>

<?php
  $inReviewAmount = 0;
  $approvedAmount = 0;
  foreach ($customerData['financing'] ?? [] as $fin) {
      if (($fin['status'] ?? '') === 'In review') {
          $inReviewAmount += (float)($fin['amount'] ?? 0);
      } elseif (($fin['status'] ?? '') === 'Approved') {
          $approvedAmount += (float)($fin['amount'] ?? 0);
      }
  }
  $totalFinancingCount = count($customerData['financing'] ?? []);
?>

          <!-- FINANCING SUMMARY STATS -->
          <div class="stat-grid stat-grid-3">
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon gold"><i class="bi bi-hourglass-split"></i></span>
                <span class="stat-tag">In Review</span>
              </div>
              <span class="stat-value">₦<?= number_format($inReviewAmount) ?></span>
              <span class="stat-label">Pending Partner Review</span>
              <div class="stat-foot">
                <i class="bi bi-clock-history"></i> Active workflow review
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon green"><i class="bi bi-check-circle-fill"></i></span>
                <span class="stat-tag">Approved</span>
              </div>
              <span class="stat-value">₦<?= number_format($approvedAmount) ?></span>
              <span class="stat-label">Partner Approvals</span>
              <div class="stat-foot">
                <i class="bi bi-shield-check"></i> Verified partner credit
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon navy"><i class="bi bi-file-earmark-text"></i></span>
                <span class="stat-tag">Total Enquiries</span>
              </div>
              <span class="stat-value"><?= $totalFinancingCount ?></span>
              <span class="stat-label">Total Applications</span>
              <div class="stat-foot">
                <i class="bi bi-folder-check"></i> In your account
              </div>
            </div>
          </div>
                <i class="bi bi-shield-check"></i> Based on travel history
              </div>
            </div>
          </div>

          <!-- FINANCING APPLICATIONS TABLE -->
          <div class="card-box">
            <div class="card-box-head">
              <h2><i class="bi bi-cash-coin"></i> Financing Enquiries</h2>
              <span class="badge-soft" id="financingTableCount">2 records</span>
            </div>
            <div class="card-box-body p-0">
              <div class="table-responsive">
                <table class="table-clean">
                  <thead>
                    <tr>
                      <th>Reference</th>
                      <th>Amount requested</th>
                      <th>Period</th>
                      <th>Purpose</th>
                      <th>Submitted</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="financingTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
