<?php

setcookie("mjfreewaycart", "", time() - 3600, '/');
get_header();
?>

  <div class="mjfreeway confirmation">
    <div class="container">
      <div class="row">
        <div class="col-sm-12">
          <h1>Reservation Confirmation</h1>
          <h2>Your order number: <?php echo $_GET['o'] ?></h2>
          <p>Thank you for your reservation!</p>
          <p>
            <?php
              $options = get_option('mjfreeway_options');
              $text = $options['mjfreeway_confirmation_string'];
              echo $text;
            ?>
          </p>
          <?php
            $text = $options['mjfreeway_directions_string'];
            if ($text) { ?>
          <p><a href="<?php echo $text ?>">Directions</a></p>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

<?php get_footer(); ?>