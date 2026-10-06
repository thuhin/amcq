<?php defined('BASEPATH') OR exit('No direct script access allowed');
$v = function ($field, $default = '') { return e(set_value($field, $default)); };
$err = function ($field) { return form_error($field, '<span class="field__error">', '</span>'); };
$tel = preg_replace('/[^\d+]/', '', CONTACT_PHONE);
?>
<section class="page-band">
  <div class="amcq-container contact-band">
    <div class="page-band__title">
      <span class="tile tile--blue"><?= icon('chat') ?></span>
      <div>
        <h1>Contact Us</h1>
        <p class="contact-band__bn">আমরা সাহায্য করতে প্রস্তুত</p>
        <p>Questions about practice, payments or the competition? Send us a message and our team will get back to you.</p>
      </div>
    </div>
    <div class="contact-band__art" aria-hidden="true">
      <span class="round round--blue"><?= icon('mail') ?></span>
      <span class="round round--green"><?= icon('phone') ?></span>
      <span class="round round--amber"><?= icon('map-pin') ?></span>
      <span class="round round--purple"><?= icon('chat') ?></span>
    </div>
  </div>
</section>

<section class="amcq-container contact-grid">
  <div class="contact-info">
    <ul class="info-cards">
      <li class="info-card"><span class="sq sq--blue"><?= icon('map-pin') ?></span><div><small>Office Address</small><address><?= e(CONTACT_ADDRESS) ?></address></div></li>
      <li class="info-card"><span class="sq sq--green"><?= icon('phone') ?></span><div><small>Phone</small><a href="tel:<?= e($tel) ?>"><?= e(CONTACT_PHONE) ?></a></div></li>
      <li class="info-card"><span class="sq sq--purple"><?= icon('mail') ?></span><div><small>Email</small><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></div></li>
      <li class="info-card"><span class="sq sq--amber"><?= icon('clock') ?></span><div><small>Office Hours</small><span><?= e(CONTACT_HOURS) ?></span></div></li>
    </ul>
    <!-- Decorative map, drawn in CSS: no third-party embed, nothing to load. -->
    <div class="map-card" role="img" aria-label="Office location: <?= e(CONTACT_ADDRESS) ?>">
      <span class="map-card__road map-card__road--a"></span><span class="map-card__road map-card__road--b"></span>
      <span class="map-card__road map-card__road--c"></span><span class="map-card__lake"></span>
      <span class="map-card__pin"><?= icon('map-pin') ?></span>
      <span class="map-card__label">AcademicMCQ · Dhaka</span>
      <?php if (CONTACT_MAP_URL): ?><a class="amcq-btn amcq-btn--light amcq-btn--sm map-card__btn" href="<?= e(CONTACT_MAP_URL) ?>" target="_blank" rel="noopener">Open in Maps <?= icon('arrow-right') ?></a><?php endif; ?>
    </div>
  </div>

  <div class="card contact-form-card" id="sent">
    <?php if ($ref): ?>
      <div class="sent">
        <span class="sent__check"><?= icon('check') ?></span>
        <h2>Message received</h2>
        <p>Thank you for writing to us. Keep this reference number in case you need to follow up:</p>
        <p class="sent__ref"><?= e($ref) ?></p>
        <p class="amcq-muted">We read every message and reply by phone or email as soon as we can.</p>
        <a class="amcq-btn amcq-btn--secondary" href="<?= site_url('contact') ?>">Send another message</a>
      </div>
    <?php else: ?>
      <h2><?= icon('send') ?> Send us a message</h2>
      <?php if ($error): ?><p class="amcq-flash amcq-flash--warning" role="alert"><?= e($error) ?></p><?php endif; ?>
      <?= form_open('contact#sent', array('class' => 'contact-form', 'novalidate' => TRUE)) ?>
        <div class="form-2col">
          <label class="field"><span class="field__label">Your name</span>
            <input class="field__input" type="text" name="name" autocomplete="name" required value="<?= $v('name', $user ? $user['name'] : '') ?>"
                   aria-invalid="<?= form_error('name') ? 'true' : 'false' ?>"><?= $err('name') ?></label>
          <label class="field"><span class="field__label">Mobile number</span>
            <input class="field__input" type="tel" name="phone" inputmode="numeric" autocomplete="tel" placeholder="01XXXXXXXXX" value="<?= $v('phone', $user ? $user['phone'] : '') ?>"
                   aria-invalid="<?= form_error('phone') ? 'true' : 'false' ?>"><?= $err('phone') ?></label>
        </div>
        <label class="field"><span class="field__label">Email <span class="amcq-muted">(optional if you gave a mobile number)</span></span>
          <input class="field__input" type="email" name="email" autocomplete="email" placeholder="you@example.com" value="<?= $v('email') ?>"
                 aria-invalid="<?= form_error('email') ? 'true' : 'false' ?>"><?= $err('email') ?></label>
        <fieldset class="field topic-field">
          <legend class="field__label">What is it about?</legend>
          <div class="topic-chips">
            <?php foreach ($topics as $key => $label): ?>
              <label class="topic-chip"><input type="radio" name="topic" value="<?= e($key) ?>" <?= set_radio('topic', $key, $key === 'general') ?>><span><?= e($label) ?></span></label>
            <?php endforeach; ?>
          </div><?= $err('topic') ?>
        </fieldset>
        <label class="field"><span class="field__label">Message</span>
          <textarea class="field__input" name="message" rows="5" maxlength="2000" required data-counter="msg-count"
                    placeholder="Tell us how we can help. If it is about a payment, include the date and amount."
                    aria-invalid="<?= form_error('message') ? 'true' : 'false' ?>"><?= $v('message') ?></textarea>
          <span class="field__meta"><?= $err('message') ?><span id="msg-count" class="amcq-muted">0 / 2000</span></span></label>
        <!-- Honeypot: hidden from people, filled only by bots. -->
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <button class="amcq-btn amcq-btn--primary amcq-btn--lg amcq-btn--block" type="submit"><?= icon('send') ?> Send Message</button>
        <p class="amcq-muted small contact-form__note">We only use your number or email to reply to this message.</p>
      <?= form_close() ?>
    <?php endif; ?>
  </div>
</section>

<section class="amcq-container">
  <h2 class="quick-help__title">Quick help</h2>
  <ul class="quick-help">
    <li><a href="<?= site_url('faq') ?>"><span class="round round--blue"><?= icon('question') ?></span><span><b>FAQ</b><small>Answers to common questions</small></span><?= icon('arrow-right') ?></a></li>
    <li><a href="<?= site_url('how-it-works') ?>"><span class="round round--green"><?= icon('play') ?></span><span><b>How It Works</b><small>Practice, points and ranking</small></span><?= icon('arrow-right') ?></a></li>
    <li><a href="<?= site_url('pricing') ?>"><span class="round round--amber"><?= icon('wallet') ?></span><span><b>Pricing</b><small>Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz, no subscription</small></span><?= icon('arrow-right') ?></a></li>
    <li><a href="<?= site_url('competition#rules') ?>"><span class="round round--pink"><?= icon('trophy') ?></span><span><b>Competition Rules</b><small>Eligibility, rounds and prizes</small></span><?= icon('arrow-right') ?></a></li>
  </ul>
</section>
