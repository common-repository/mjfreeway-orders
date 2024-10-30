<?php
nocache_headers();
$MJFplugin      = MJFreeway_Shopping_Cart::get_instance();
$cartItems      = $MJFplugin->get_cart();
$cartQuantity   = count($cartItems) > 0 ? count( $cartItems ) : '';
$categories     = $MJFplugin->get_categories_from_all_products();
$total          = 0;

get_header();
?>

  <div class="mjfreeway checkout">
    <div class="container">
      <?php
      if ( $_SERVER['QUERY_STRING'] === 'error=1' ) { ?>
        <div class="alert danger">There was a problem submitting your order. Please call the store or wait a few minutes
          and try again.
        </div>
      <?php } ?>
      <div class="row">
        <div class="col col-sm-11">
          <h1>Checkout</h1>
        </div>
        <div class="col col-sm-1 absolute-center">
            <div class="cart-icon-container">
              <img class="cart-icon" src="<?php echo plugins_url( '../images/cart.svg', __FILE__ ) ?>"/>
              <?php
              if ( count($cartItems) > 0 ) {
              ?>
              <span class="cart-quantity"><?php echo $cartQuantity ?></span>
              <?php } ?>
            </div>
        </div>
      </div>
      <div class="row">
        <div class="col col-sm-12">
          <?php if ( count($cartItems) == 0 ) { ?>
            <h2>There are no products in your cart.</h2>
          <?php } else { ?>
            <table class="table table-striped table-responsive">
              <thead class="thead-default">
              <tr>
                <th>Product</th>
                <th class="text-center">Quantity</th>
                <th class="text-center">Price</th>
                <th class="text-right">Total</th>
                <th class="actions"></th>
              </tr>
              </thead>
              <tbody id="mjfreewayCheckout">
              <?php
              foreach ( $cartItems as $key => $cartItem ) {
                $total    = $total + $cartItem->get_subtotal();
                ?>
                <tr>
                  <td><?php echo $cartItem->name ?></td>
                  <td class="text-center"><?php echo $cartItem->quantity ?></td>
                  <td class="text-center">$<?php echo $cartItem->price ?></td>
                  <td class="text-right">$<?php echo money_format( '%.2n', $cartItem->get_subtotal() ) ?></td>
                  <td class="actions">
                    <span data-productremove="<?php echo $key ?>">
                      <img src="<?php echo plugins_url( '../images/remove.png', __FILE__ ) ?>" class="remove-icon"/>
                    </span>
                  </td>
                </tr>

              <?php } ?>
              <tr>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right"><strong>$<?php echo money_format( '%.2n', $total ) ?></strong></td>
                <td class="plus-tax">+ tax</td>
              </tr>
              </tbody>
            </table>
          <?php } ?>
        </div>
      </div>
      <?php if ( $cartQuantity !== '' && !is_user_logged_in() ) { ?>

        <div class="row">
          <div class="col col-sm-12">
            <div class="guest-reservation-header text-right">
              Please <a href="<?php echo wp_login_url($_SERVER['REQUEST_URI']) ?>">login</a> to complete your reservation.
            </div>
          </div>
        </div>
      <?php } else if ($cartQuantity !== '' && is_user_logged_in()) { ?>

        <form class="row" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
              id="mjfreewayReservationForm"
              method="POST">
          <input type="hidden" name="action" value="mjfreeway_reservation_form">
          <input type="hidden" name="accountNumber" value="<?php echo get_the_author_meta('mjfreeway_customer_id', get_current_user_id()) ?>" />
          <input type="hidden" name="cart" value="<?php echo $_COOKIE['mjfreewaycart'] ?>"/>
          <div class="col col-sm-12 text-right">
            <button type="submit" id="mjfreewayReserveOrder" class="reserve-order button">Reserve your Order
            </button>
          </div>
        </form>

      <?php } ?>
      <div class="row">
        <div class="col col-sm-12 text-right">
          <a class="view-products" href="/mjfreeway-products">Continue shopping</a>
        </div>
      </div>
    </div>
  </div>
<?php get_footer(); ?>