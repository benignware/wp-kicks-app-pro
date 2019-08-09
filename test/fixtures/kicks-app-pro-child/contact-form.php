<div class="contact">
  <?php if ($title): ?>
    <h3 class="contact-title"><?= $title ?></h3>
  <?php endif; ?>
  <?php if ($success): ?>
    <p class="contact-description lead">
      <?= __('Thanks for your request!', 'basic-contact-form') ?><br/>
      <?= __('We\'ll get back to you as soon as we can.', 'basic-contact-form') ?>
    </p>
  <?php else: ?>
    <?php if ($description): ?>
      <p class="contact-description lead"><?= $description ?></p>
    <?php endif; ?>
    <form class="contact-form" method="POST">
      <?php if (in_array('name', $fields)) : ?>
        <p class="contact-field form-group">
          <label class="contact-label">
            <?= __('Name', 'basic-contact-form') ?><?= in_array('name', $required) ? '*' : ''; ?>
          </label>
          <input class="form-control contact-input<?= $errors['name'] ? ' is-invalid' : '' ?>" placeholder="<?= __('Please enter your name', 'basic-contact-form') ?>" type="text" name="name" size="50" maxlength="80" value="<?= $data['name'] ?>" />
          <?php if (array_key_exists('name', $errors)): ?>
            <span class="invalid-feedback contact-message"><?= $errors['name'] ?></span>
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <?php if (in_array('email', $fields)) : ?>
        <p class="contact-field form-group">
          <label class="contact-label">
            <?= __('E-mail', 'basic-contact-form') ?><?= in_array('email', $required) ? '*' : ''; ?>
          </label>
          <input class="form-control contact-input<?= $errors['email'] ? ' is-invalid' : '' ?>" placeholder="<?= __('Please enter your e-mail address', 'basic-contact-form') ?>" type="text" name="email" size="50" maxlength="80" value="<?= $data['email'] ?>" />
          <?php if (array_key_exists('email', $errors)): ?>
            <span class="invalid-feedback contact-message"><?= $errors['email'] ?></span>
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <?php if (in_array('subject', $fields)) : ?>
        <p class="contact-field form-group">
          <label><?= __('Subject', 'basic-contact-form') ?><?= in_array('subject', $required) ? '*' : ''; ?></label>
          <input class="form-control contact-input<?= $errors['subject'] ? ' is-invalid' : '' ?>" placeholder="<?= __('Please enter a subject', 'basic-contact-form') ?>" type="text" name="subject" size="50" maxlength="256" value="<?= $data['subject'] ?>" />
          <?php if (array_key_exists('subject', $errors)): ?>
            <span class="invalid-feedback contact-message"><?= $errors['subject'] ?></span>
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <?php if (in_array('message', $fields)) : ?>
        <p class="contact-field form-group">
          <label class="contact-label">
            <?= __('Message', 'basic-contact-form') ?><?= in_array('message', $required) ? '*' : ''; ?>
          </label>
          <textarea class="form-control contact-input<?= $errors['message'] ? ' is-invalid' : '' ?>" placeholder="<?= __('Please describe what your inquiry is about', 'basic-contact-form') ?>" name="message" size="50" rows="8"><?= $data['message'] ?></textarea>
          <?php if (array_key_exists('message', $errors)): ?>
            <span class="invalid-feedback contact-message"><?= $errors['message'] ?></span>
          <?php endif; ?>
        </p>
      <?php endif; ?>
      <?php if (basic_contact_form_has_captcha()): ?>
        <div class="contact-field form-group">
          <?php basic_contact_form_captcha(); ?>
          <?php if (array_key_exists('captcha', $errors)): ?>
            <span class="invalid-feedback contact-message"><?= $errors['captcha'] ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <p class="contact-footer">
        <button class="btn btn-primary contact-submit" type="submit"><?= __('Send', 'basic-contact-form'); ?></button>
      </p>
    </form>
  <?php endif; ?>
</div>
