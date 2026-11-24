<?php $this->layout('layouts/admin', ['title' => 'Contact messages — CJ Travels Admin', 'page' => 'messages']); ?>

<section class="view active" id="view-messages" data-view="messages">
  <div class="page-head mb-4">
    <div>
      <h1>Contact messages</h1>
      <p>Booking requests and enquiries sent from the public contact page.</p>
    </div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success mb-4" role="alert"><?= e($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="card-box">
    <div class="card-box-head">
      <h2>Inbox</h2>
    </div>
    <div class="table-responsive">
      <table class="table-clean">
        <thead>
          <tr>
            <th>Reference</th>
            <th>From</th>
            <th>Department</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Received</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($messages)): ?>
            <tr class="row-empty">
              <td class="cell-empty" colspan="7">No contact messages yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($messages as $message): ?>
              <tr>
                <td data-label="Reference"><span class="cell-mono"><?= e($message['ref'] ?? '') ?></span></td>
                <td data-label="From">
                  <span class="cell-strong"><?= e($message['name'] ?? '') ?></span>
                  <span class="cell-sub"><?= e($message['email'] ?? '') ?></span>
                  <?php if (!empty($message['phone'])): ?>
                    <span class="cell-sub"><?= e($message['phone']) ?></span>
                  <?php endif; ?>
                </td>
                <td data-label="Department"><?= e($message['department'] ?? '') ?></td>
                <td data-label="Subject"><?= e($message['subject'] ?? '') ?></td>
                <td data-label="Message"><?= e($message['message'] ?? '') ?></td>
                <td data-label="Received"><?= e(!empty($message['created_at']) ? date('M j, Y g:i A', strtotime($message['created_at'])) : '') ?></td>
                <td class="text-end" data-label="">
                  <form method="POST" action="<?= route('admin.messages.delete') ?>" onsubmit="return confirm('Delete this message?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ref" value="<?= e($message['ref'] ?? '') ?>" />
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
