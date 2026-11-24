<?php $this->layout('layouts/admin', ['title' => 'Financial Reports — CJ Travels Admin', 'page' => 'reports']); ?>

<section class="view active" id="view-reports" data-view="reports">
          <div class="page-head">
            <div>
              <h1>Reports</h1>
              <p>Customers, bookings, payments, and financing totals from the live database. The figures refresh with the rest of the console.</p>
            </div>
            <div class="page-head-actions">
              <button type="button" class="btn btn-ghost" id="exportReportBtn">
                <i class="bi bi-download"></i> Export CSV
              </button>
            </div>
          </div>

          <div class="info-note">
            <i class="bi bi-arrow-repeat"></i>
            <div>
              <strong>Live snapshot.</strong> Paid revenue counts transactions marked Paid. The continent chart uses each booking’s destination.
              <span id="reportUpdated">Waiting for the latest records.</span>
            </div>
          </div>

          <div class="stat-grid">
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon"><i class="bi bi-people-fill"></i></span>
                <span class="stat-tag">Live</span>
              </div>
              <span class="stat-value" id="reportCustomers">0</span>
              <span class="stat-label">Customers</span>
            </div>
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon gold"><i class="bi bi-airplane-fill"></i></span>
                <span class="stat-tag">Live</span>
              </div>
              <span class="stat-value" id="reportBookings">0</span>
              <span class="stat-label">Total bookings</span>
            </div>
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon green"><i class="bi bi-cash-stack"></i></span>
                <span class="stat-tag">Live</span>
              </div>
              <span class="stat-value" id="reportRevenue">₦0</span>
              <span class="stat-label">Paid revenue</span>
            </div>
            <div class="stat-card">
              <div class="stat-top">
                <span class="stat-icon violet"><i class="bi bi-cash-coin"></i></span>
                <span class="stat-tag">Live</span>
              </div>
              <span class="stat-value" id="reportLoanValue">₦0</span>
              <span class="stat-label">Financing requested</span>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-lg-7">
              <div class="card-box h-100">
                <div class="card-box-head">
                  <h2><i class="bi bi-bar-chart-fill"></i> Monthly booking volume</h2>
                </div>
                <div class="card-box-body">
                  <div class="chart-legend">
                    <span><i class="swatch swatch-sky"></i> Flights</span>
                    <span><i class="swatch swatch-navy"></i> Hotels</span>
                  </div>
                  <div class="bar-chart" id="reportChart" role="img" aria-label="Bar chart of monthly bookings"></div>
                  <div class="bar-labels" id="reportChartLabels"></div>
                </div>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="card-box h-100">
                <div class="card-box-head">
                  <h2><i class="bi bi-globe-americas"></i> Bookings by continent</h2>
                </div>
                <div class="card-box-body">
                  <div class="donut-wrap">
                    <div class="donut" id="continentDonut" role="img" aria-label="Donut chart of bookings by continent">
                      <div class="donut-center">
                        <strong id="continentDonutTotal">0</strong>
                        <span>Bookings</span>
                      </div>
                    </div>
                    <div class="donut-legend" id="continentLegend"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card-box mt-3">
            <div class="card-box-head">
              <h2><i class="bi bi-credit-card-2-front-fill"></i> Payments by status</h2>
            </div>
            <div class="table-responsive">
              <table class="table-clean">
                <thead>
                  <tr>
                    <th scope="col">Status</th>
                    <th scope="col">Transactions</th>
                    <th scope="col">Amount</th>
                  </tr>
                </thead>
                <tbody id="reportPaymentsBody"></tbody>
              </table>
            </div>
          </div>
        </section>
