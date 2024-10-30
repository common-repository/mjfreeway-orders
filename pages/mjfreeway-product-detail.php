<?php
nocache_headers();

// get current url with query string.
$current_url =  home_url( $wp->request );

// get the position where '/page.. ' text start.
$productId = explode('/mjfreeway-products/', $current_url)[1];

get_header();

$MJFplugin      = MJFreeway_Shopping_Cart::get_instance();
$cartItems      = $MJFplugin->get_cart();
$cartQuantity   = count($cartItems) > 0 ? count( $cartItems ) : '';
$needle         = $MJFplugin->get_product_detail($productId);
 
?>
  <div class="mjfreeway product-detail">
    <div class="container">
      <div class="row">
        <div class="col col-sm-11">
          <h1><?php echo $needle->name ?></h1>
        </div>
        <div class="col col-sm-1 absolute-center">
            <div class="cart-icon-container">
              <a href="/mjfreeway-checkout">
                <img class="cart-icon" src="<?php echo plugins_url('../images/cart.svg', __FILE__) ?>"/>
                <?php
                if (count($cartItems) > 0) {
                  ?>
                <span class='cart-quantity'><?php echo $cartQuantity ?></span>
                <?php }  ?>
              </a>
            </div>
        </div>
        <div class='col-sm-12 col-md-3'><img class='list-image' src='<?php
          if ($needle->imageUrl) {
            echo $needle->imageUrl;
          }
          else {
            echo plugins_url('../images/mj-placeholder.png', __FILE__);
          }
          ?>'/></div>
        <div class='col-sm-12 col-md-9'>
          <div class='description'><?php echo $needle->description ?></div>
          <div class='price-container'>
            <?php if ( is_array($needle->pricing)) {
              echo '<label for="mjfreeway-pricing-amount">Select a price:</label>';
              foreach($needle->pricing as $index=>$item) {
                $selected = '';
                if ($index === 0) $selected = 'checked';
                echo '<div class="price" >';
                echo '<input type="radio" class="price-amount" required ' . $selected . ' name="mjfreeway-pricing-weight-id" id="price-label-' . $index . '" value="' . $item["pricing_weight_id"] . '"/>';
                echo '<label for="price-label-' . $index . '">' . '$' . $item['default_price'] . ' ' .$item['name'] . '</label>';
                echo '</div>';
              }
            } else {
              echo '<div class="price" >';
                echo '$' . $needle->pricing;
              echo '</div>';
            } ?>
          </div>
          <?php if (is_user_logged_in()) { ?>
            <?php
            $customerID = get_the_author_meta('mjfreeway_customer_id', get_current_user_id());
            if ($customerID && is_numeric($customerID)) { ?>
            <div class='quantity'>
              <label for='mjfreewaySelectQuantity'>Quantity:</label>
              <select class="form-control selectQuantity" id='mjfreewaySelectQuantity'>
                <option value='1' selected>1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
              </select>
            </div>
            <div class="actions">
              <button type="button" id="mjfreewayAddToCart" data-product="<?php echo $needle->id ?>">Add to Cart</button>
            </div>
            <?php } else {
              $text = get_option('mjfreeway_options')['mjfreeway_purgatory_string'];
              if (!$text) $text = 'Your account is pending final verification. Please call us for more information.';
              ?>
            <h3><?php echo $text; ?></h3>
            <?php } ?>
          <?php } else { ?>
            <h3>Please <a href="<?php echo wp_login_url($_SERVER['REQUEST_URI']) ?>">login</a> to reserve a product.
              <?php if (checked( get_option( 'mjfreeway_options' )['mjfreeway_display_registration_link'], 1, false )) { ?>Need an account? <a href="/wp-login.php?action=register">Register</a>.<?php } ?></h3>
          <?php } ?>
          <a href="/mjfreeway-products">Back to products</a>
        </div>
      </div>
    </div>
  </div>

<?php get_footer(); ?>