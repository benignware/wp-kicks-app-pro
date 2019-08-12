<?php

class Customize_Test_Control extends WP_Customize_Control {
  public $type = 'test';
  /**
  * Render the control's content.
  */
  public function render_content() {
  ?>
    <button class="button button-primary" id="create-new-menu-submit" tabindex="0"><?php _e( 'Create Menu' ); ?></button>
  <?php
  }
}
