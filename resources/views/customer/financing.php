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
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerFinancingModal">
                <i class="bi bi-plus-lg"></i> New financing enquiry
              </button>
              <a href="<?= route('financing') ?>" class="btn btn-outline-secondary ms-2" target="_blank">
                <i class="bi bi-calculator"></i> Public Calculator
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

          <!-- FINANCING APPLICATIONS TABLE -->
          <div class="card-box">
            <div class="card-box-head">
              <h2><i class="bi bi-cash-coin"></i> Financing Enquiries</h2>
              <span class="badge-soft" id="financingTableCount"><?= $totalFinancingCount ?> records</span>
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

        <!-- NEW FINANCING ENQUIRY MODAL -->
        <div class="modal fade" id="customerFinancingModal" tabindex="-1" aria-labelledby="customerFinancingModalTitle" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="customerFinancingModalTitle">
                  <i class="bi bi-cash-coin text-warning me-2"></i> Submit Travel Financing Enquiry
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <form id="customerFinancingForm">
                <div class="modal-body">
                  <div class="alert alert-light border d-flex gap-2 align-items-center mb-3">
                    <i class="bi bi-info-circle text-primary fs-5"></i>
                    <small class="text-muted">
                      Apply with ease using your profile. Indicative calculations are based on a 15% annual illustrative rate. Financing is assessed by independent licensed lending partners.
                    </small>
                  </div>

                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="custFinAmount">Estimated Travel Cost (₦)</label>
                      <div class="input-group">
                        <span class="input-group-text">₦</span>
                        <input type="number" id="custFinAmount" class="form-control" min="100000" max="10000000" step="50000" value="750000" required />
                      </div>
                      <div class="form-text">Between ₦100,000 and ₦10,000,000</div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="custFinPeriod">Repayment Period</label>
                      <select id="custFinPeriod" class="form-select" required>
                        <option value="3 months">3 months</option>
                        <option value="6 months" selected>6 months</option>
                        <option value="9 months">9 months</option>
                        <option value="12 months">12 months</option>
                      </select>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="custFinPurpose">Purpose of Travel</label>
                      <select id="custFinPurpose" class="form-select" required>
                        <option value="Leisure / holiday" selected>Leisure / holiday</option>
                        <option value="Business travel">Business travel</option>
                        <option value="Visiting family">Visiting family</option>
                        <option value="Education">Education</option>
                        <option value="Medical travel">Medical travel</option>
                        <option value="Pilgrimage">Pilgrimage</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fw-semibold" for="custFinApplicant">Applicant</label>
                      <input type="text" id="custFinApplicant" class="form-control bg-light" value="<?= e($customerData['customer']['name'] ?? 'Account Holder') ?> (<?= e($customerData['customer']['email'] ?? '') ?>)" readonly />
                    </div>

                    <div class="col-12">
                      <label class="form-label fw-semibold" for="custFinNotes">Trip Notes / Destination details (optional)</label>
                      <textarea id="custFinNotes" class="form-control" rows="2" placeholder="e.g. Flight to London Heathrow with British Airways in December"></textarea>
                    </div>
                  </div>

                  <!-- Live estimate box -->
                  <div class="p-3 mt-3 rounded-3" style="background:#0b1e3d; color:#faf6ed;">
                    <div class="row text-center">
                      <div class="col-6 border-end border-secondary">
                        <div class="text-uppercase small opacity-75">Estimated Monthly</div>
                        <div class="fs-4 fw-bold text-warning" id="custFinMonthly">₦130,812 / mo</div>
                      </div>
                      <div class="col-6">
                        <div class="text-uppercase small opacity-75">Total Repayable (Est.)</div>
                        <div class="fs-4 fw-bold" id="custFinTotal">₦784,872</div>
                      </div>
                    </div>
                  </div>

                  <div id="customerFinancingStatus" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary" id="submitCustFinBtn">
                    <i class="bi bi-send me-1"></i> Submit Financing Enquiry
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
