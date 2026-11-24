<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="<?= asset('images/logo-mark.png') ?>" />
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($title ?? 'Dashboard — CJ Travels Admin') ?></title>
  <meta name="description" content="<?= e($description ?? 'CJ Travels administration dashboard.') ?>" />
  <meta name="robots" content="noindex, nofollow" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="<?= asset('css/admin.css') ?>?v=nav3" rel="stylesheet" />

  <script>document.documentElement.classList.add('js');</script>
</head>

<body data-page="<?= e($page ?? 'dashboard') ?>">
  <a href="#adminMain" class="skip-link">Skip to dashboard content</a>

  <div class="dash-shell">

    <?php $this->include('partials/admin-sidebar'); ?>

    <div class="admin-main" id="adminMain">

      <?php $this->include('partials/topbar', ['isAdmin' => true, 'title' => $title ?? 'Dashboard']); ?>

      <div class="admin-content">
        <?= $content ?>
      </div>

      <footer class="admin-footer">
        <p class="mb-0">
          &copy; <span id="year"><?= date('Y') ?></span> CJ Travels admin console.
        </p>
        <ul>
          <li><a href="<?= route('home') ?>">Public site</a></li>
          <li><a href="<?= route('terms') ?>">Terms</a></li>
          <li><a href="<?= route('privacy') ?>">Privacy</a></li>
          <li><a href="<?= route('contact') ?>">Support</a></li>
        </ul>
      </footer>

    </div>
  </div>

  <!-- MODALS -->
  <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detailModalTitle">
            <i class="bi bi-file-earmark-text"></i> Record details
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="detailModalBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalTitle">
            <i class="bi bi-pencil-square"></i> Edit record
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-note" style="margin-bottom:1rem;background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;">
            <i class="bi bi-shield-check" style="color:#2e7d5b;"></i>
            <div>Changes are saved directly to the CJ Travels MySQL database in real time.</div>
          </div>
          <form id="editForm" novalidate>
            <div id="editFields" class="row g-3"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-navy btn-sm" id="saveEditBtn">
            <i class="bi bi-check2"></i> Save changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalTitle">
            <i class="bi bi-trash3"></i> Delete record
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p style="font-size:14px;color:var(--ink-soft);margin-bottom:16px;line-height:1.7;">
            Delete <strong id="deleteRecordLabel">this record</strong>, or cancel to leave it unchanged.
          </p>
          <p class="modal-note mb-0" style="color:var(--error);">
            <i class="bi bi-exclamation-triangle"></i>
            This will permanently remove the record from the MySQL database.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger-soft btn-sm" id="confirmDeleteBtn">
            <i class="bi bi-trash3"></i> Delete record
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="destinationModal" tabindex="-1" aria-labelledby="destinationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="destinationModalTitle">
            <i class="bi bi-globe-americas"></i> Add destination
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-note" style="margin-bottom:1rem;background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;">
            <i class="bi bi-shield-check" style="color:#2e7d5b;"></i>
            <div>The new destination will be stored directly into the CJ Travels MySQL database.</div>
          </div>
          <form id="destinationForm" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="destCity">City</label>
                <input type="text" class="form-control" id="destCity" placeholder="e.g. Lisbon" required />
                <div class="invalid-feedback">Please enter a city name.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="destCountry">Country</label>
                <input type="text" class="form-control" id="destCountry" placeholder="e.g. Portugal" required />
                <div class="invalid-feedback">Please enter a country name.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="destContinent">Continent</label>
                <select class="form-select" id="destContinent" required>
                  <option value="" selected disabled>Select a continent</option>
                  <option value="Africa">Africa</option>
                  <option value="Europe">Europe</option>
                  <option value="Asia">Asia</option>
                  <option value="Middle East">Middle East</option>
                  <option value="Americas">Americas</option>
                  <option value="Oceania">Oceania</option>
                </select>
                <div class="invalid-feedback">Please select a continent.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="destCategories">Travel styles</label>
                <select class="form-select" id="destCategories" multiple size="4" aria-describedby="destCategoriesHelp">
                  <option value="Beaches">Beaches</option>
                  <option value="Adventure">Adventure</option>
                  <option value="Culture">Culture</option>
                  <option value="Luxury">Luxury</option>
                  <option value="City Breaks">City Breaks</option>
                </select>
                <div class="form-text" id="destCategoriesHelp" style="font-size:11px;color:var(--muted);margin-top:6px;">
                  Hold Ctrl / Cmd to select more than one.
                </div>
              </div>
              <div class="col-12">
                <label class="form-label" for="destImage">Image URL (optional)</label>
                <input type="url" class="form-control" id="destImage" placeholder="https://images.unsplash.com/…" />
                <div class="form-text" style="font-size:11px;color:var(--muted);margin-top:6px;">Leave blank to use a neutral gradient placeholder.</div>
              </div>
              <div class="col-12">
                <label class="form-label" for="destBlurb">Short description</label>
                <input type="text" class="form-control" id="destBlurb" placeholder="One line that captures the destination" />
              </div>
            </div>
            <div class="toast-inline mt-3" id="destinationToast"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-gold btn-sm" id="saveDestinationBtn">
            <i class="bi bi-plus-lg"></i> Add destination
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="flightModal" tabindex="-1" aria-labelledby="flightModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="flightModalTitle">
            <i class="bi bi-airplane-fill"></i> Add flight
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-note" style="margin-bottom:1rem;background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;">
            <i class="bi bi-shield-check" style="color:#2e7d5b;"></i>
            <div>Confirmed flights are offered on the public flight search. Pending flights stay in this admin list.</div>
          </div>
          <form id="flightForm" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="flightCustomer">Customer</label>
                <select class="form-select" id="flightCustomer"></select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="flightAirline">Airline</label>
                <input type="text" class="form-control" id="flightAirline" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="flightNumber">Flight number</label>
                <input type="text" class="form-control" id="flightNumber" placeholder="BA 074" />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="flightCabin">Cabin</label>
                <select class="form-select" id="flightCabin">
                  <option>Economy</option>
                  <option>Premium Economy</option>
                  <option>Business</option>
                  <option>First Class</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="flightFrom">From</label>
                <input type="text" class="form-control" id="flightFrom" placeholder="Lagos (LOS)" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="flightTo">To</label>
                <input type="text" class="form-control" id="flightTo" placeholder="London (LHR)" required />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="flightDate">Departure date</label>
                <input type="date" class="form-control" id="flightDate" required />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="flightTime">Departure time</label>
                <input type="time" class="form-control" id="flightTime" value="09:00" />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="flightArrival">Arrival time</label>
                <input type="time" class="form-control" id="flightArrival" value="16:00" />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="flightDuration">Duration</label>
                <input type="text" class="form-control" id="flightDuration" value="7h 00m" />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="flightStops">Stops</label>
                <select class="form-select" id="flightStops">
                  <option>Direct</option>
                  <option>1 stop</option>
                  <option>2 stops</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="flightAmount">Amount (₦)</label>
                <input type="number" class="form-control" id="flightAmount" min="1" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="flightStatus">Status</label>
                <select class="form-select" id="flightStatus">
                  <option value="Confirmed" selected>Confirmed</option>
                  <option value="Pending">Pending</option>
                  <option value="Completed">Completed</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>
            </div>
            <div class="toast-inline mt-3" id="flightToast"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-gold btn-sm" id="saveFlightBtn">
            <i class="bi bi-plus-lg"></i> Add flight
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="hotelModal" tabindex="-1" aria-labelledby="hotelModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="hotelModalTitle">
            <i class="bi bi-building-fill"></i> Add hotel
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-note" style="margin-bottom:1rem;background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;">
            <i class="bi bi-shield-check" style="color:#2e7d5b;"></i>
            <div>Confirmed hotels are offered on the public hotel search. Pending stays stay in this admin list.</div>
          </div>
          <form id="hotelForm" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="hotelCustomer">Customer</label>
                <select class="form-select" id="hotelCustomer"></select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="hotelProperty">Property</label>
                <input type="text" class="form-control" id="hotelProperty" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="hotelLocation">Location</label>
                <input type="text" class="form-control" id="hotelLocation" placeholder="Ikoyi, Lagos" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="hotelRoomType">Room type</label>
                <input type="text" class="form-control" id="hotelRoomType" value="Deluxe Room" />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="hotelCheckIn">Check-in</label>
                <input type="date" class="form-control" id="hotelCheckIn" required />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="hotelCheckOut">Check-out</label>
                <input type="date" class="form-control" id="hotelCheckOut" required />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="hotelGuests">Guests</label>
                <select class="form-select" id="hotelGuests">
                  <option value="1">1 guest</option>
                  <option value="2" selected>2 guests</option>
                  <option value="3">3 guests</option>
                  <option value="4">4 guests</option>
                  <option value="6">6 guests</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="hotelAmount">Total amount (₦)</label>
                <input type="number" class="form-control" id="hotelAmount" min="1" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="hotelStatus">Status</label>
                <select class="form-select" id="hotelStatus">
                  <option value="Confirmed" selected>Confirmed</option>
                  <option value="Pending">Pending</option>
                  <option value="Completed">Completed</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>
            </div>
            <div class="toast-inline mt-3" id="hotelToast"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-gold btn-sm" id="saveHotelBtn">
            <i class="bi bi-plus-lg"></i> Add hotel
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="loanModal" tabindex="-1" aria-labelledby="loanModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loanModalTitle">
            <i class="bi bi-cash-coin"></i> Add financing enquiry
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-note" style="margin-bottom:1rem;background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;">
            <i class="bi bi-shield-check" style="color:#2e7d5b;"></i>
            <div>This records an enquiry only. CJ Travels does not approve or decline financing.</div>
          </div>
          <form id="loanForm" novalidate>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="loanCustomer">Customer</label>
                <select class="form-select" id="loanCustomer"></select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="loanAmount">Amount (₦)</label>
                <input type="number" class="form-control" id="loanAmount" min="1" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="loanPeriod">Repayment period</label>
                <select class="form-select" id="loanPeriod">
                  <option>3 months</option>
                  <option selected>6 months</option>
                  <option>9 months</option>
                  <option>12 months</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="loanPurpose">Purpose of travel</label>
                <input type="text" class="form-control" id="loanPurpose" required />
              </div>
              <div class="col-12">
                <label class="form-label" for="loanNote">Note</label>
                <input type="text" class="form-control" id="loanNote" />
              </div>
              <div class="col-12">
                <label class="form-label" for="loanStatus">Status</label>
                <select class="form-select" id="loanStatus">
                  <option value="In review" selected>In review</option>
                  <option value="Awaiting documents">Awaiting documents</option>
                  <option value="Referred to partner">Referred to partner</option>
                  <option value="Draft">Draft</option>
                  <option value="Approved">Approved</option>
                </select>
              </div>
            </div>
            <div class="toast-inline mt-3" id="loanToast"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-gold btn-sm" id="saveLoanBtn">
            <i class="bi bi-plus-lg"></i> Add enquiry
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="transactionModalTitle">
            <i class="bi bi-credit-card-2-front-fill"></i> Add transaction
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-note" style="margin-bottom:1rem;background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;">
            <i class="bi bi-shield-check" style="color:#2e7d5b;"></i>
            <div>The payment is saved to MySQL and shows on the selected customer’s Payments page.</div>
          </div>
          <form id="transactionForm" novalidate>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="txnCustomer">Customer</label>
                <select class="form-select" id="txnCustomer" required></select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="txnAmount">Amount (₦)</label>
                <input type="number" class="form-control" id="txnAmount" min="1" step="0.01" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="txnMethod">Payment method</label>
                <select class="form-select" id="txnMethod">
                  <option value="Card" selected>Card</option>
                  <option value="Paystack / Card">Paystack / Card</option>
                  <option value="Bank Transfer">Bank Transfer</option>
                  <option value="Cash">Cash</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="txnDate">Date</label>
                <input type="date" class="form-control" id="txnDate" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="txnStatus">Status</label>
                <select class="form-select" id="txnStatus">
                  <option value="Paid" selected>Paid</option>
                  <option value="Pending">Pending</option>
                  <option value="Refunded">Refunded</option>
                  <option value="Failed">Failed</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="txnRelated">Related booking reference</label>
                <input type="text" class="form-control" id="txnRelated" placeholder="Optional, for example TBF-2201" />
              </div>
            </div>
            <div class="toast-inline mt-3" id="txnToast"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-gold btn-sm" id="saveTransactionBtn">
            <i class="bi bi-plus-lg"></i> Add transaction
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="customerModalTitle">
            <i class="bi bi-person-plus-fill"></i> Add customer
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-note" style="margin-bottom:1rem;background:#e8f3ed;border:1px solid #2e7d5b;color:#184a34;">
            <i class="bi bi-shield-check" style="color:#2e7d5b;"></i>
            <div>The new customer is saved to MySQL and becomes available to the customer portal.</div>
          </div>
          <form id="customerForm" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="custFirst">First name</label>
                <input type="text" class="form-control" id="custFirst" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="custLast">Last name</label>
                <input type="text" class="form-control" id="custLast" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="custEmail">Email</label>
                <input type="email" class="form-control" id="custEmail" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="custPhone">Phone</label>
                <input type="text" class="form-control" id="custPhone" />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="custCountry">Country</label>
                <input type="text" class="form-control" id="custCountry" value="Nigeria" />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="custCity">City</label>
                <input type="text" class="form-control" id="custCity" value="Lagos" />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="custTier">Membership</label>
                <select class="form-select" id="custTier">
                  <option value="Standard member">Standard member</option>
                  <option value="Silver member">Silver member</option>
                  <option value="Gold member" selected>Gold member</option>
                  <option value="Platinum member">Platinum member</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="custStatus">Status</label>
                <select class="form-select" id="custStatus">
                  <option value="Active" selected>Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="toast-inline mt-3" id="customerToast"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-gold btn-sm" id="saveCustomerBtn">
            <i class="bi bi-plus-lg"></i> Add customer
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="apiProviderModal" tabindex="-1" aria-labelledby="apiProviderModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="apiProviderModalTitle">
            <i class="bi bi-plug-fill"></i> Add API provider
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="api-security-note" style="margin-bottom:16px;">
            <i class="bi bi-shield-lock"></i>
            <div>
              <strong>Keys stay on the server.</strong> After you save, this page shows only the last
              four characters. Leave a field blank while editing to keep the current key or secret.
            </div>
          </div>
          <form id="apiProviderForm" novalidate>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="apiName">Provider name</label>
                <input type="text" class="form-control" id="apiName" placeholder="e.g. Amadeus" required />
                <div class="invalid-feedback">Please enter a provider name.</div>
              </div>
              <div class="col-12">
                <label class="form-label" for="apiBaseUrl">API base URL</label>
                <input type="url" class="form-control" id="apiBaseUrl" placeholder="https://api.example.com/v1" required />
                <div class="invalid-feedback">Please enter a valid HTTPS URL.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="apiKey">API key</label>
                <div class="cred-row">
                  <input type="password" class="form-control" id="apiKey" placeholder="Enter API key" autocomplete="new-password" required />
                  <button type="button" class="cred-toggle" data-target="apiKey" aria-label="Show API key" aria-pressed="false">
                    <i class="bi bi-eye-fill"></i>
                  </button>
                </div>
                <div class="cred-hint" id="apiKeyHint" hidden>
                  <i class="bi bi-info-circle"></i>
                  <span>Leave blank to keep the existing key.</span>
                </div>
                <div class="invalid-feedback">Please enter an API key.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="apiSecret">API secret <span style="font-weight:500;text-transform:none;letter-spacing:0;color:var(--muted);">(where applicable)</span></label>
                <div class="cred-row">
                  <input type="password" class="form-control" id="apiSecret" placeholder="Enter API secret" autocomplete="new-password" />
                  <button type="button" class="cred-toggle" data-target="apiSecret" aria-label="Show API secret" aria-pressed="false">
                    <i class="bi bi-eye-fill"></i>
                  </button>
                </div>
                <div class="cred-hint" id="apiSecretHint" hidden>
                  <i class="bi bi-info-circle"></i>
                  <span>Leave blank to keep the existing secret.</span>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="apiEnv">Environment</label>
                <select class="form-select" id="apiEnv" required>
                  <option value="Sandbox" selected>Sandbox</option>
                  <option value="Production">Production</option>
                </select>
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch m-0 pb-2">
                  <input class="form-check-input" type="checkbox" id="apiEnabled" checked />
                  <label class="form-check-label" for="apiEnabled" style="font-size:12px;margin-left:8px;">Enable integration</label>
                </div>
              </div>
            </div>
            <div class="toast-inline mt-3" id="apiToast"></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-navy btn-sm" id="apiSaveBtn">
            <i class="bi bi-check2"></i> Save provider
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="apiDeleteModal" tabindex="-1" aria-labelledby="apiDeleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="apiDeleteModalTitle">
            <i class="bi bi-trash3"></i> Remove API provider
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p style="font-size:14px;color:var(--ink-soft);margin-bottom:16px;line-height:1.7;">
            Remove <strong id="apiDeleteName">this provider</strong>, or cancel to keep it.
          </p>
          <p class="modal-note mb-0" style="color:var(--error);">
            <i class="bi bi-exclamation-triangle"></i>
            This deletes the provider and its saved key from the database.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger-soft btn-sm" id="apiDeleteConfirm">
            <i class="bi bi-trash3"></i> Remove provider
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADMIN LOGOUT MODAL -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title h5 mb-0" id="logoutModalTitle">Confirm Sign Out</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0">Are you sure you want to sign out of the administrator console?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
          <form method="POST" action="<?= route('admin.logout') ?>" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Sign Out</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    window.CJ_ADMIN_DATA = <?= json_encode($realtimeData ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.CJ_CSRF_TOKEN = "<?= csrf_token() ?>";
    window.CJ_BASE_URL = "<?= rtrim(url(''), '/') ?>";
    window.CJ_API_URLS = {
      liveData: "<?= route('admin.api.live-data') ?>",
      updateRecord: "<?= route('admin.api.records.update') ?>",
      deleteRecord: "<?= route('admin.api.records.delete') ?>",
      createDestination: "<?= route('admin.api.destinations.create') ?>",
      createCustomer: "<?= route('admin.api.customers.create') ?>",
      createFlight: "<?= route('admin.api.flights.create') ?>",
      createHotel: "<?= route('admin.api.hotels.create') ?>",
      createLoan: "<?= route('admin.api.loans.create') ?>",
      createTransaction: "<?= route('admin.api.transactions.create') ?>",
      saveProvider: "<?= route('admin.api.providers.save') ?>",
      deleteProvider: "<?= route('admin.api.providers.delete') ?>",
      testProvider: "<?= route('admin.api.providers.test') ?>",
      exportReport: "<?= route('admin.reports.export') ?>"
    };
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= asset('js/admin.js') ?>"></script>
  <script src="<?= asset('js/admin-nav.js') ?>?v=nav3"></script>
</body>
</html>

