<?php $this->layout('layouts/admin', ['title' => 'API Integrations — CJ Travels Admin', 'page' => 'api']); ?>

<section class="view active" id="view-api" data-view="api">
          <div class="page-head">
            <div>
              <h1>API Integrations</h1>
              <p>Saved flight and hotel providers refresh with the rest of the console. Test connection checks the provider address from the server.</p>
            </div>
            <div class="page-head-actions">
              <button type="button" class="btn btn-ghost" id="apiRefreshBtn">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
            </div>
          </div>

          <div class="info-note">
            <i class="bi bi-shield-lock"></i>
            <div>
              <strong>Keys stay on the server.</strong> After a provider is saved, this page shows only the
              last four characters. Connection tests check whether the address responds and do not send the key or secret.
            </div>
          </div>

          <div class="api-grid">
            <div class="api-panel">
              <div class="api-panel-head">
                <h2>
                  <i class="bi bi-airplane-fill"></i> Flight API providers
                  <span class="api-panel-count" id="flightProviderCount">0 providers</span>
                </h2>
                <button type="button" class="btn btn-navy btn-sm" data-api-add="Flight">
                  <i class="bi bi-plus-lg"></i> Add flight provider
                </button>
              </div>
              <div class="api-panel-body">
                <div class="api-table-wrap">
                  <table class="api-table" aria-label="Flight API providers">
                    <thead>
                      <tr>
                        <th scope="col">Provider</th>
                        <th scope="col">Service</th>
                        <th scope="col">Environment</th>
                        <th scope="col">Status</th>
                        <th scope="col">Last test</th>
                        <th scope="col">Active</th>
                        <th scope="col" class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="flightProvidersBody"></tbody>
                  </table>
                </div>
                <div class="api-empty" id="flightEmpty">
                  <i class="bi bi-plug-fill"></i>
                  <p>No flight API providers yet.</p>
                </div>
              </div>
            </div>

            <div class="api-panel">
              <div class="api-panel-head">
                <h2>
                  <i class="bi bi-building-fill"></i> Hotel API providers
                  <span class="api-panel-count" id="hotelProviderCount">0 providers</span>
                </h2>
                <button type="button" class="btn btn-navy btn-sm" data-api-add="Hotel">
                  <i class="bi bi-plus-lg"></i> Add hotel provider
                </button>
              </div>
              <div class="api-panel-body">
                <div class="api-table-wrap">
                  <table class="api-table" aria-label="Hotel API providers">
                    <thead>
                      <tr>
                        <th scope="col">Provider</th>
                        <th scope="col">Service</th>
                        <th scope="col">Environment</th>
                        <th scope="col">Status</th>
                        <th scope="col">Last test</th>
                        <th scope="col">Active</th>
                        <th scope="col" class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="hotelProvidersBody"></tbody>
                  </table>
                </div>
                <div class="api-empty" id="hotelEmpty">
                  <i class="bi bi-plug-fill"></i>
                  <p>No hotel API providers yet.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="card-box">
            <div class="card-box-head">
              <h2><i class="bi bi-journal-text"></i> API activity logs</h2>
              <span class="head-sub" id="apiLogCount">0 events</span>
            </div>
            <div class="table-responsive">
              <table class="api-log-table" aria-label="API activity logs">
                <thead>
                  <tr>
                    <th scope="col">Date &amp; time</th>
                    <th scope="col">Provider</th>
                    <th scope="col">Service</th>
                    <th scope="col">Activity</th>
                    <th scope="col">Status</th>
                  </tr>
                </thead>
                <tbody id="apiLogBody"></tbody>
              </table>
            </div>
            <div class="api-empty" id="apiLogEmpty">
              <i class="bi bi-journal"></i>
              <p>No API activity yet.</p>
            </div>
          </div>
        </section>
