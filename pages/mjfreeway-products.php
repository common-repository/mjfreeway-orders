<?php
nocache_headers();

$MJFplugin      = MJFreeway_Shopping_Cart::get_instance();
$cartItems      = $MJFplugin->get_cart();
$cartQuantity   = count($cartItems) > 0 ? count( $cartItems ) : '';
$productsToShow = $MJFplugin->get_products_by_category(isset($_GET['cat']) ? $_GET['cat'] : null);
$categories     = $MJFplugin->get_categories_from_all_products();
get_header();
?>
<div class="mjfreeway products-list">
    <div class="container">
      <div class="row">
        <div class="col col-sm-11">
          <h1>Products</h1>
        </div>
        <div class="col col-md-1 absolute-center">
            <div class="cart-icon-container">
              <a href="/mjfreeway-checkout">
                <img class="cart-icon" src="<?php echo plugins_url('../images/cart.svg', __FILE__) ?>"/>
                <?php
                if ( count($cartItems) > 0 ) {
                  ?>
                <span class="cart-quantity"><?php echo $cartQuantity ?></span>
                <?php }  ?>
              </a>
            </div>
        </div>
      </div>
      <div class="row">
        <div class="col col-12 col-sm-4 col-md-3 col-lg-2">
          <h2>Categories</h2>
          <div class="categories">
            <?php
              foreach ($categories as $category) {
         
                echo '<a href="/mjfreeway-products?cat=' . $category->id . '" class="category-link">' . $category->name . '</a>';
              }
            ?>
          </div>
        </div>
        <div class="col col-12 col-sm-8 col-md-9 col-lg-10">
        <div class="products-list-container" id="mjfreewayProducts">
        <?php
          if (count($productsToShow) > 0) {
            foreach ( $productsToShow as $item ) {
              ?>
              <div class="card">
                <a href="/mjfreeway-products/<?php echo $item->id ?>" class="card-image" style="background-image: url('<?php
                if ( $item->imageUrl ) {
                  echo $item->imageUrl;
                } else {
                  echo plugins_url( '../images/mj-placeholder.png', __FILE__ );
                }
                ?>');"></a>
                <h3 class="card-title"><?php echo $item->name ?></h3>
                <p class="card-text"><?php echo $item->description ?></p>
              </div>
              <?php
            }
          } else {
            echo '<h3>There are no products in that category.</h3>';
          }
        ?>
        </div>
      </div>
    </div>
  </div>

<?php get_footer(); ?>