<?php $this->layout('layouts/admin', ['title' => 'Email center — CJ Travels Admin', 'page' => 'emails']); ?>

<section class="view active" id="view-emails" data-view="emails">
  <div class="page-head mb-4">
    <div>
      <h1>Email center</h1>
      <p>Send a message to a customer. Every email includes the CJ Travels logo and office address.</p>
    </div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success mb-4" role="alert"><?= e($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="card-box mb-4">
    <div class="card-box-head">
      <h2><i class="bi bi-send"></i> Write an email</h2>
    </div>
    <div class="card-box-body">
      <form action="<?= route('admin.emails.send') ?>" method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-6">
          <label class="form-label" for="customerId"><strong>Customer</strong></label>
          <select class="form-select" id="customerId" name="customer_id">
            <option value="">Choose a customer, or type an email below</option>
            <?php foreach ($customers as $customer): ?>
              <option value="<?= (int)$customer['id'] ?>"><?= e($customer['name']) ?> — <?= e($customer['email']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="emailTo"><strong>Email address</strong></label>
          <input type="email" class="form-control" id="emailTo" name="email" placeholder="name@example.com" />
        </div>
        <div class="col-12">
          <label class="form-label" for="emailSubject"><strong>Subject</strong></label>
          <input type="text" class="form-control" id="emailSubject" name="subject" required maxlength="180" />
        </div>
        <div class="col-12">
          <label class="form-label" for="emailMessage"><strong>Message</strong></label>
          <textarea class="form-control" id="emailMessage" name="message" rows="7" required placeholder="Write the message. The logo and Lagos office address are added automatically."></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Send email</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card-box mb-4" id="mail-settings">
    <div class="card-box-head">
      <h2><i class="bi bi-gear"></i> Mailbox</h2>
    </div>
    <div class="card-box-body">
      <p class="text-muted mb-3" style="font-size:0.92rem;">
        Use the mailbox that should send customer emails, for example the host for info@cjtravelsowerri.com.
        Financing confirmations and loan approvals use these same settings.
      </p>
      <form action="<?= route('admin.emails.settings') ?>" method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-6">
          <label class="form-label" for="fromName"><strong>From name</strong></label>
          <input type="text" class="form-control" id="fromName" name="from_name" value="<?= e($mailSettings['from_name'] ?? 'CJ Travels') ?>" required />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="fromEmail"><strong>From email</strong></label>
          <input type="email" class="form-control" id="fromEmail" name="from_email" value="<?= e($mailSettings['from_email'] ?? 'info@cjtravelsowerri.com') ?>" required />
        </div>
        <div class="col-md-4">
          <label class="form-label" for="mailHost"><strong>Mail host</strong></label>
          <input type="text" class="form-control" id="mailHost" name="host" value="<?= e($mailSettings['host'] ?? '') ?>" placeholder="smtp.example.com" />
        </div>
        <div class="col-md-2">
          <label class="form-label" for="mailPort"><strong>Port</strong></label>
          <input type="number" class="form-control" id="mailPort" name="port" value="<?= e($mailSettings['port'] ?? '587') ?>" min="1" max="65535" />
        </div>
        <div class="col-md-3">
          <label class="form-label" for="mailEncryption"><strong>Security</strong></label>
          <select class="form-select" id="mailEncryption" name="encryption">
            <?php $encryption = $mailSettings['encryption'] ?? 'tls'; ?>
            <option value="tls" <?= $encryption === 'tls' ? 'selected' : '' ?>>TLS</option>
            <option value="ssl" <?= $encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
            <option value="none" <?= $encryption === 'none' ? 'selected' : '' ?>>None</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="mailUsername"><strong>Username</strong></label>
          <input type="text" class="form-control" id="mailUsername" name="username" value="<?= e($mailSettings['username'] ?? '') ?>" autocomplete="off" />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="mailPassword"><strong>Password</strong></label>
          <input type="password" class="form-control" id="mailPassword" name="password" value="" autocomplete="new-password" placeholder="<?= !empty($mailSettings['has_password']) ? 'Saved. Leave blank to keep it.' : 'Mailbox password' ?>" />
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Save mailbox</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-head">
      <h2>Sent mail</h2>
    </div>
    <div class="table-responsive">
      <table class="table-clean">
        <thead>
          <tr>
            <th>When</th>
            <th>To</th>
            <th>Subject</th>
            <th>Type</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sentEmails)): ?>
            <tr class="row-empty">
              <td class="cell-empty" colspan="5">No emails have been sent yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($sentEmails as $sent): ?>
              <tr>
                <td data-label="When"><?= e(!empty($sent['created_at']) ? date('M j, Y g:i A', strtotime($sent['created_at'])) : '') ?></td>
                <td data-label="To">
                  <span class="cell-strong"><?= e($sent['recipient_name'] ?? '') ?></span>
                  <span class="cell-sub"><?= e($sent['recipient_email'] ?? '') ?></span>
                </td>
                <td data-label="Subject"><?= e($sent['subject'] ?? '') ?></td>
                <td data-label="Type"><?= e($sent['kind'] ?? '') ?><?= !empty($sent['related_ref']) ? ' · ' . e($sent['related_ref']) : '' ?></td>
                <td data-label="Status">
                  <?= e($sent['status'] ?? '') ?>
                  <?php if (!empty($sent['error_message'])): ?>
                    <span class="cell-sub"><?= e($sent['error_message']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
