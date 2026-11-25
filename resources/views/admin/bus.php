<?php $this->layout('layouts/admin', ['title' => 'Bus Tickets & Road Transport — CJ Travels Admin', 'page' => 'bus']); ?>

<section class="view active" id="view-bus" data-view="bus">
  <div class="page-head mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h1>Bus Tickets &amp; Road Transport</h1>
      <p>Intercity bus ticket enquiries, passenger reservations, departure dates, and direct WhatsApp contact.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= route('bus') ?>" target="_blank" class="btn btn-outline-secondary" style="background:#fff;border-color:var(--hairline);color:var(--navy);font-weight:600;">
        <i class="bi bi-box-arrow-up-right me-1"></i> Public Bus Booking Page
      </a>
      <a href="https://wa.me/<?= e(cj_whatsapp_number()) ?>" target="_blank" class="btn btn-success" style="background:#25D366;border-color:#25D366;font-weight:600;">
        <i class="bi bi-whatsapp me-1"></i> WhatsApp Desk
      </a>
    </div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success mb-4" role="alert"><?= e($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <!-- KPI SUMMARY CARDS -->
  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
      <div class="card-box p-3 d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:8px;background:rgba(11,30,61,0.08);color:var(--navy);display:flex;align-items:center;justify-content:center;font-size:22px;">
          <i class="bi bi-bus-front-fill"></i>
        </div>
        <div>
          <span class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;font-weight:600;">Total Enquiries</span>
          <h3 style="font-size:24px;margin:0;font-weight:700;color:var(--navy);"><?= (int)($counts['total'] ?? 0) ?></h3>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6">
      <div class="card-box p-3 d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:8px;background:rgba(168,132,44,0.12);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:22px;">
          <i class="bi bi-hourglass-split"></i>
        </div>
        <div>
          <span class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;font-weight:600;">Pending Action</span>
          <h3 style="font-size:24px;margin:0;font-weight:700;color:var(--gold);"><?= (int)($counts['pending'] ?? 0) ?></h3>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6">
      <div class="card-box p-3 d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:8px;background:rgba(46,125,91,0.12);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:22px;">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <div>
          <span class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;font-weight:600;">Confirmed</span>
          <h3 style="font-size:24px;margin:0;font-weight:700;color:var(--green);"><?= (int)($counts['confirmed'] ?? 0) ?></h3>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-sm-6">
      <div class="card-box p-3 d-flex align-items-center gap-3">
        <div style="width:48px;height:48px;border-radius:8px;background:rgba(37,211,102,0.12);color:#25D366;display:flex;align-items:center;justify-content:center;font-size:22px;">
          <i class="bi bi-whatsapp"></i>
        </div>
        <div>
          <span class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;font-weight:600;">WhatsApp Desk</span>
          <h3 style="font-size:18px;margin:0;font-weight:700;color:#184a34;"><?= e(cj_phone_display()) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- BOOKINGS TABLE -->
  <div class="card-box">
    <div class="card-box-head d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h2><i class="bi bi-ticket-perforated-fill text-gold me-2"></i> Recent Road Transport Enquiries</h2>

      <!-- STATUS FILTER -->
      <div class="d-flex gap-2">
        <a href="<?= route('admin.bus') ?>" class="btn btn-sm <?= empty($currentStatus) ? 'btn-navy' : 'btn-ghost' ?>">All</a>
        <a href="<?= route('admin.bus') ?>?status=Pending" class="btn btn-sm <?= ($currentStatus === 'Pending') ? 'btn-navy' : 'btn-ghost' ?>">Pending</a>
        <a href="<?= route('admin.bus') ?>?status=Confirmed" class="btn btn-sm <?= ($currentStatus === 'Confirmed') ? 'btn-navy' : 'btn-ghost' ?>">Confirmed</a>
        <a href="<?= route('admin.bus') ?>?status=Processing" class="btn btn-sm <?= ($currentStatus === 'Processing') ? 'btn-navy' : 'btn-ghost' ?>">Processing</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table-clean">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Passenger</th>
            <th>Route &amp; Travel Dates</th>
            <th>Coach &amp; Seats</th>
            <th>Est. Fare</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bookings)): ?>
            <tr class="row-empty">
              <td class="cell-empty" colspan="7">No bus bookings recorded yet. New enquiries will appear here in real time.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($bookings as $b): ?>
              <?php
                // Clean phone digits for WhatsApp link to passenger
                $cleanPhone = preg_replace('/\D+/', '', (string)$b['customer_phone']);
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '234' . substr($cleanPhone, 1);
                } elseif (!str_starts_with($cleanPhone, '234')) {
                    $cleanPhone = '234' . $cleanPhone;
                }
                $custWhatsApp = "https://wa.me/{$cleanPhone}?text=" . rawurlencode("Hello " . $b['customer_name'] . ", regarding your CJ Travels bus reservation (Ref: " . $b['ref'] . " for " . $b['departure_city'] . " to " . $b['destination_city'] . "), your seat availability is verified.");
              ?>
              <tr id="bus-row-<?= (int)$b['id'] ?>">
                <td data-label="Reference">
                  <span class="cell-mono font-weight-bold" style="color:var(--navy);font-weight:700;"><?= e($b['ref']) ?></span>
                  <div style="font-size:11px;color:var(--muted);"><?= date('M j, Y g:i A', strtotime($b['created_at'])) ?></div>
                </td>

                <td data-label="Passenger">
                  <span class="cell-strong"><?= e($b['customer_name']) ?></span>
                  <div class="cell-sub"><i class="bi bi-telephone me-1"></i><?= e($b['customer_phone']) ?></div>
                  <?php if (!empty($b['customer_email'])): ?>
                    <div class="cell-sub"><i class="bi bi-envelope me-1"></i><?= e($b['customer_email']) ?></div>
                  <?php endif; ?>
                </td>

                <td data-label="Route & Travel Dates">
                  <div style="font-weight:600;color:var(--navy);font-size:13.5px;">
                    <?= e(explode('(', $b['departure_city'])[0]) ?>
                    <i class="bi bi-arrow-right text-gold mx-1"></i>
                    <?= e(explode('(', $b['destination_city'])[0]) ?>
                  </div>

                  <div style="font-size:12px;color:var(--ink-soft);margin-top:2px;">
                    <i class="bi bi-calendar-event me-1 text-gold"></i>
                    <strong>Depart:</strong> <?= date('D, j M Y', strtotime($b['departure_date'])) ?>
                    <span class="badge bg-light text-dark ms-1" style="border:1px solid #ddd;"><?= e($b['departure_time']) ?></span>
                  </div>

                  <?php if (!empty($b['return_date'])): ?>
                    <div style="font-size:12px;color:var(--green);margin-top:2px;">
                      <i class="bi bi-calendar-check me-1"></i>
                      <strong>Return:</strong> <?= date('D, j M Y', strtotime($b['return_date'])) ?>
                      <span class="badge bg-success-subtle text-success ms-1">Round-Trip</span>
                    </div>
                  <?php else: ?>
                    <span class="badge bg-light text-muted" style="border:1px solid #eee;font-size:10px;">One-Way</span>
                  <?php endif; ?>
                </td>

                <td data-label="Coach & Seats">
                  <div style="font-weight:600;font-size:13px;color:var(--navy);"><?= e($b['coach_class']) ?></div>
                  <div style="font-size:12px;color:var(--muted);"><?= (int)$b['passengers'] ?> Passenger<?= ((int)$b['passengers'] > 1 ? 's' : '') ?></div>
                  <small class="text-muted d-block" style="font-size:11px;">Operator: <?= e($b['preferred_operator']) ?></small>
                </td>

                <td data-label="Est. Fare">
                  <span style="font-weight:700;color:var(--navy);font-size:14px;"><?= e($b['formatted_fare']) ?></span>
                </td>

                <td data-label="Status">
                  <select class="form-select form-select-sm status-select" data-id="<?= (int)$b['id'] ?>" style="width:130px;font-size:12px;font-weight:600;">
                    <option value="Pending" <?= ($b['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="Confirmed" <?= ($b['status'] === 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                    <option value="Processing" <?= ($b['status'] === 'Processing') ? 'selected' : '' ?>>Processing</option>
                    <option value="Cancelled" <?= ($b['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                </td>

                <td class="text-end" data-label="Actions">
                  <div class="d-flex justify-content-end gap-1">
                    <a href="<?= e($custWhatsApp) ?>" target="_blank" class="btn btn-sm btn-success" title="Chat with passenger on WhatsApp" style="background:#25D366;border-color:#25D366;padding:4px 8px;">
                      <i class="bi bi-whatsapp"></i> Chat
                    </a>
                    <?php if (!empty($b['notes'])): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary view-notes-btn" title="View special requests" data-notes="<?= e($b['notes']) ?>" style="padding:4px 8px;">
                        <i class="bi bi-chat-left-text"></i>
                      </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-bus-btn" data-id="<?= (int)$b['id'] ?>" data-ref="<?= e($b['ref']) ?>" title="Delete enquiry" style="padding:4px 8px;">
                      <i class="bi bi-trash3"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- NOTES MODAL -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notesModalLabel"><i class="bi bi-info-circle text-gold me-2"></i> Special Luggage &amp; Travel Notes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="notesModalBody" style="font-size:14px;color:var(--navy);"></p>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = '<?= csrf_token() ?>';

    // 1. Status Update via AJAX
    document.querySelectorAll('.status-select').forEach(function (select) {
      select.addEventListener('focus', function () {
        this.setAttribute('data-previous', this.value);
      });
      select.addEventListener('change', function () {
        var id = this.getAttribute('data-id');
        var newStatus = this.value;
        var previous = this.getAttribute('data-previous') || newStatus;
        var originalColor = this.style.borderColor;
        var selectEl = this;

        fetch('<?= route('admin.api.bus.status') ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({ id: id, status: newStatus, _token: csrfToken })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            selectEl.setAttribute('data-previous', newStatus);
            selectEl.style.borderColor = '#2e7d5b';
            setTimeout(function () { selectEl.style.borderColor = originalColor; }, 1200);
          } else {
            selectEl.value = previous;
            alert(data.error || 'Failed to update status.');
          }
        })
        .catch(function () {
          selectEl.value = previous;
          alert('Network error while updating status.');
        });
      });
    });

    // 2. Delete Enquiry
    document.querySelectorAll('.delete-bus-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = this.getAttribute('data-id');
        var ref = this.getAttribute('data-ref');

        if (btn.disabled) {
          return;
        }
        if (!confirm('Are you sure you want to delete bus booking enquiry ' + ref + '?')) {
          return;
        }
        btn.disabled = true;

        fetch('<?= route('admin.api.bus.delete') ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({ id: id, _token: csrfToken })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            var row = document.getElementById('bus-row-' + id);
            if (row) {
              row.style.transition = 'all 0.3s ease';
              row.style.opacity = '0';
              setTimeout(function () { row.remove(); }, 300);
            }
          } else {
            btn.disabled = false;
            alert(data.error || 'That booking could not be removed.');
          }
        })
        .catch(function () {
          btn.disabled = false;
          alert('Network error while deleting.');
        });
      });
    });

    // 3. View Notes Modal
    var notesModalEl = document.getElementById('notesModal');
    var notesBody = document.getElementById('notesModalBody');
    var notesModal = notesModalEl ? new bootstrap.Modal(notesModalEl) : null;

    document.querySelectorAll('.view-notes-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var notes = this.getAttribute('data-notes');
        if (notesBody) notesBody.textContent = notes || 'No special requests provided.';
        if (notesModal) notesModal.show();
      });
    });
  });
</script>
