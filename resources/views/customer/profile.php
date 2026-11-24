<?php $this->layout('layouts/customer', ['title' => 'My Profile — Customer Dashboard — CJ Travels', 'page' => 'profile']); ?>

<section class="view active" id="view-profile" data-view="profile" aria-labelledby="profileTitle">
          <div class="page-head">
            <div>
              <h1 id="profileTitle" class="h2 mb-1">
                My Profile <span class="demo-pill"><i class="bi bi-person-fill"></i> Account</span>
              </h1>
              <p>Manage your contact information, passport details and CJ Travels loyalty tier.</p>
            </div>
          </div>

          <!-- PROFILE HERO BANNER -->
          <div class="profile-card">
            <span class="avatar-lg"><?= e($user->initials ?? '') ?></span>
            <div class="profile-meta">
              <h2><?= e($user->name ?? '') ?></h2>
              <span class="email"><?= e($user->email ?? '') ?></span>
              <div class="profile-tags">
                <span class="profile-tag gold"><i class="bi bi-award-fill"></i> <?= e($user->tier ?? '') ?></span>
                <span class="profile-tag"><i class="bi bi-calendar-check"></i> Member since <?= e($user->memberSince ?? '') ?></span>
                <span class="profile-tag"><i class="bi bi-geo-alt-fill"></i> <?= e(trim(($user->city ?? '') . ', ' . ($user->country ?? ''), ' ,')) ?></span>
              </div>
            </div>
          </div>

          <!-- PERSONAL INFORMATION -->
          <div class="card-box mb-4">
            <div class="card-box-head">
              <h2><i class="bi bi-person-badge"></i> Personal Information</h2>
            </div>
            <div class="card-box-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="profFirstName">First name</label>
                  <input type="text" class="form-control" id="profFirstName" value="<?= e($user->firstName ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profLastName">Last name</label>
                  <input type="text" class="form-control" id="profLastName" value="<?= e($user->lastName ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profEmail">Email address</label>
                  <input type="email" class="form-control" id="profEmail" value="<?= e($user->email ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profPhone">Phone number</label>
                  <input type="tel" class="form-control" id="profPhone" value="<?= e($user->phone ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profCountry">Country of residence</label>
                  <input type="text" class="form-control" id="profCountry" value="<?= e($user->country ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profCity">City</label>
                  <input type="text" class="form-control" id="profCity" value="<?= e($user->city ?? '') ?>" />
                </div>
              </div>
              <div class="mt-4 d-flex align-items-center gap-3 flex-wrap">
                <button type="button" class="btn btn-primary" id="saveProfileBtn">
                  Save personal information
                </button>
                <span id="profileSaveStatus" class="text-muted" style="font-size:0.9rem;"></span>
              </div>
            </div>
          </div>

          <!-- PASSPORTS & TRAVEL DOCUMENTS -->
          <div class="card-box">
            <div class="card-box-head">
              <h2><i class="bi bi-pass"></i> Travel Documents & Passports</h2>
            </div>
            <div class="card-box-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="profPassport">Passport number</label>
                  <input type="text" class="form-control cell-mono" id="profPassport" value="<?= e($user->passport ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profIssuing">Issuing country</label>
                  <input type="text" class="form-control" id="profIssuing" value="<?= e($user->issuingCountry ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profExpiry">Expiration date</label>
                  <input type="date" class="form-control" id="profExpiry" value="<?= e($user->passportExpiry ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="profFrequentFlyer">Frequent flyer number</label>
                  <input type="text" class="form-control cell-mono" id="profFrequentFlyer" value="<?= e($user->frequentFlyer ?? '') ?>" placeholder="e.g. BA-12345678" />
                </div>
              </div>
              <div class="mt-4 d-flex align-items-center gap-3 flex-wrap">
                <button type="button" class="btn btn-primary" id="saveDocumentsBtn">
                  Update travel documents
                </button>
                <span id="documentsSaveStatus" class="text-muted" style="font-size:0.9rem;"></span>
              </div>
            </div>
          </div>
        </section>
