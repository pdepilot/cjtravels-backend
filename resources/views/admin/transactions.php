<?php $this->layout('layouts/admin', ['title' => 'Transactions — CJ Travels Admin', 'page' => 'transactions']); ?>

<section class="view active" id="view-transactions" data-view="transactions">
          <div class="page-head">
            <div>
              <h1>Transactions &amp; Payments</h1>
              <p>Real-time payment logs, gateway records and client settlements synced with MySQL.</p>
            </div>
          </div>
          <div class="card-box">
            <div class="toolbar">
              <div class="search-box">
                <i class="bi bi-search"></i>
                <label class="visually-hidden" for="txnSearch">Search transactions</label>
                <input type="search" id="txnSearch" placeholder="Search reference, customer or method…" />
              </div>
              <label class="visually-hidden" for="txnStatusFilter">Filter by status</label>
              <select class="filter-select" id="txnStatusFilter">
                <option value="">All statuses</option>
                <option value="Paid">Paid</option>
                <option value="Pending">Pending</option>
                <option value="Refunded">Refunded</option>
                <option value="Failed">Failed</option>
              </select>
              <span class="toolbar-count"><strong id="txnCount">0</strong> records</span>
            </div>
            <div class="table-responsive">
              <table class="table-clean">
                <thead>
                  <tr>
                    <th scope="col">Reference</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Related booking</th>
                    <th scope="col">Method</th>
                    <th scope="col">Date</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody id="transactionsBody"></tbody>
              </table>
            </div>
          </div>
        </section>
