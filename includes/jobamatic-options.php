<div class="wrap">
  <?php screen_icon(); ?>
  <h2>Jobamatic Settings</h2>
  <form action="options.php" method="post">
    <?php
     settings_fields('jobamatic_options');
     do_settings_sections('jobamatic');
    ?>
    <div style="margin-top: 18px;">
      <?php submit_button(); ?>
    </div>
    
  </form>
</div>
