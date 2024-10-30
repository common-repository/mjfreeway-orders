<?php

class MJFreeway_Shopping_Cart {
  private static $instance;
  private $allProducts = array();

  public static function get_instance() {
    if ( ! isset( self::$instance ) ) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  private function __construct() {

    if ( ! defined( 'MJFREEWAY_API_ROOT' ) ) {
      define( 'MJFREEWAY_API_ROOT', get_option( 'mjfreeway_options' )['mjfreeway_api_url_string'] );
    }
    //filters
    add_filter( 'page_template', array( $this, 'catch_path' ) );
    add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );

    //actions
    add_action( 'init', array( $this, 'product_detail_rewrite_init' ) );
    add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    add_action( 'admin_post_mjfreeway_reservation_form', array( $this, 'prefix_process_reservation' ) );
    add_action( 'admin_init', array( $this, 'admin_init' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'add_plugin_stylesheet' ) );
    add_action( 'wp_head', array( $this, 'custom_styles' ), 100 );
    add_action( 'wp_logout', array( $this, 'clear_cookies_on_logout' ) );
    add_action( 'user_new_form', array( $this, 'custom_user_profile_fields' ) );
    add_action( 'show_user_profile', array( $this, 'custom_user_profile_fields' ) );
    add_action( 'edit_user_profile', array( $this, 'custom_user_profile_fields' ) );
    add_action( 'user_register', array( $this, 'save_custom_user_profile_fields') );
    add_action( 'personal_options_update', array( $this, 'save_customer_user_profile_fields') );
    add_action( 'edit_user_profile_update', array( $this, 'save_custom_user_profile_fields') );
  }

  public function custom_user_profile_fields($user){
    if(!current_user_can('administrator', $user->ID))
      return false;
    ?>
    <h2>MJ Freeway</h2>
    <table class="form-table">
      <tr>
        <th><label for="mjfreeway_customer_id">Customer ID</label></th>
        <td>
          <input type="text" class="regular-text" name="mjfreeway_customer_id" value="<?php echo esc_attr( get_the_author_meta( 'mjfreeway_customer_id', $user->ID ) ); ?>" id="mjfreeway_customer_id" /><br />
          <span class="description">Provided by MJ Freeway</span>
        </td>
      </tr>
    </table>
    <?php
  }

  function save_custom_user_profile_fields($user_id){
    # again do this only if you can
    if(!current_user_can('administrator', $user_id))
      return false;

    # save my custom field
    update_usermeta($user_id, 'mjfreeway_customer_id', $_POST['mjfreeway_customer_id']);
  }


  public function catch_path( $page_template ) {
    if ( is_page( 'mjfreeway-products' ) ) {
      $page_template = __DIR__ . '/pages/mjfreeway-products.php';
    } else if ( is_page( 'mjfreeway-checkout' ) ) {
      $page_template = __DIR__ . '/pages/mjfreeway-checkout.php';
    } else if ( is_page( 'mjfreeway-confirmation' ) ) {
      $page_template = __DIR__ . '/pages/mjfreeway-confirmation.php';
    } else if ( is_page( 'mjfreeway-product-detail' ) ) {
      $page_template = __DIR__ . '/pages/mjfreeway-product-detail.php';
    }

    return $page_template;
  }

  public function get_api_headers() {
    $mjfreeway_options   = get_option( 'mjfreeway_options' );
    $mjfreeway_headers   = array();
    $mjfreeway_headers[] = "X-Mjf-Api-Key: " . $mjfreeway_options['mjfreeway_api_key_string'];
    $mjfreeway_headers[] = "X-Mjf-Organization-Id: " . $mjfreeway_options['mjfreeway_organization_id_string'];
    $mjfreeway_headers[] = "X-Mjf-Facility-Id: " . $mjfreeway_options['mjfreeway_facility_id_string'];
    $mjfreeway_headers[] = "X-Mjf-User-Id: " . $mjfreeway_options['mjfreeway_user_id_string'];

    return $mjfreeway_headers;
  }

  public function multiRequest( $data, $order ) {
    // array of curl handles
    $curly = array();
    // data to be returned
    $result = array();
    // multi handle
    $mh = curl_multi_init();

    // loop through $data and create curl handles
    // then add them to the multi-handle
    foreach ( $data as $id => $item ) {
      $curly[ $id ] = curl_init();
      $cartItem     = explode( '|', $item );
      $cartItemID   = $cartItem[0];
      $quantity     = $cartItem[1];
      $pricingID    = $cartItem[2];

      $fields = array(
        "item_master_id" => $cartItemID,
        "quantity"       => $quantity
      );
      if ( $pricingID ) {
        $fields["pricing_weight_id"] = $pricingID;
      }

      curl_setopt( $curly[ $id ], CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt( $curly[ $id ], CURLOPT_HTTPHEADER, $this->get_api_headers() );
      curl_setopt( $curly[ $id ], CURLOPT_URL, MJFREEWAY_API_ROOT . "orders/" . $order["id"] . "/products" );
      curl_setopt( $curly[ $id ], CURLOPT_CUSTOMREQUEST, "POST" );
      curl_setopt( $curly[ $id ], CURLOPT_POSTFIELDS, http_build_query( $fields ) );
      curl_setopt( $curly[ $id ], CURLOPT_POST, 1 );
      curl_setopt( $curly[ $id ], CURLOPT_HEADER, 0 );
      curl_setopt( $curly[ $id ], CURLOPT_RETURNTRANSFER, 1 );

      curl_multi_add_handle( $mh, $curly[ $id ] );
    }

    // execute the handles
    $running = null;
    do {
      curl_multi_exec( $mh, $running );
    } while ( $running > 0 );

    // get content and remove handles
    foreach ( $curly as $id => $c ) {
      $result[ $id ] = curl_multi_getcontent( $c );
      curl_multi_remove_handle( $mh, $c );
    }

    // all done
    curl_multi_close( $mh );

    return $result;
  }

  public function prefix_process_reservation() {
    $wholeCart = explode( ',', $_POST['cart'] );

    if ( count( $wholeCart ) > 0 ) {
      $ch = curl_init();
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->get_api_headers() );
      curl_setopt( $ch, CURLOPT_URL, MJFREEWAY_API_ROOT . "orders" );
      curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
      curl_setopt( $ch, CURLOPT_POST, 1 );

      $fields = array(
        "order_source" => "online",
        "consumer_id"  => $_POST['accountNumber'],
        "order_type"   => "sale",
      );

      curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $fields ) );
      $result = curl_exec( $ch );
      $order  = json_decode( $result, true );

      $mhResult = $this->multiRequest( $wholeCart, $order );

      if ($_POST['fulfillment_method'] && $_POST['fulfillment_method'] === 'delivery') {
        curl_setopt( $ch, CURLOPT_URL, MJFREEWAY_API_ROOT . "orders/" . $order["id"] );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
        $orderUpdate = array(
          "fulfillment_method" => $_POST['fulfillment_method'],
          "delivery_address_id" => (int)$_POST["delivery_address_id"],
        );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $orderUpdate ) );
        $result         = curl_exec( $ch );
      }

      curl_setopt( $ch, CURLOPT_URL, MJFREEWAY_API_ROOT . "orders/" . $order["id"] . "/submit" );
      curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
      curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $fields ) );

      $result         = curl_exec( $ch );
      $submittedOrder = json_decode( $result, true );

      if ( $submittedOrder["is_submitted"] === 1 ) {
        wp_safe_redirect( get_site_url() . '/mjfreeway-confirmation?o=' . $submittedOrder['name'] );
      } else {
        wp_safe_redirect( get_site_url() . '/mjfreeway-checkout?error=1' );
      }
    }
  }

  public function get_consumer_info() {

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->get_api_headers() );
    curl_setopt( $ch, CURLOPT_URL, MJFREEWAY_API_ROOT . "consumers/" . get_the_author_meta('mjfreeway_customer_id', get_current_user_id()) );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );

    $result = curl_exec( $ch );
    $consumer  = json_decode( $result, true );
    return $consumer;
  }

  public function add_admin_menu() {
    add_menu_page( 'MJ Freeway General Settings', 'MJ Freeway', 'manage_options', 'mjfreeway', array($this, 'options_page' ), plugin_dir_url( __FILE__ ).'images/mjf-admin-logo.png');
  }

  public function product_detail_rewrite_init() {
    add_rewrite_rule(
      '^mjfreeway-products/([0-9]+)',
      'index.php?pagename=mjfreeway-product-detail',
      'top' );
    flush_rewrite_rules();
  }

  public function get_cookie_cart() {
    $cart = isset( $_COOKIE['mjfreewaycart'] ) ? explode( ',', $_COOKIE['mjfreewaycart'] ) : null;

    return $cart;
  }

  public function get_product_detail( $productId ) {
    $allProducts = $this->get_all_products();

    foreach ( $allProducts as $item ) {
      if ( $item->id === (int) $productId ) {
        return $item;
      }
    }

    return null;
  }

  public function get_products_by_category( $cat ) {
    $productsToShow = array();
    $allProducts    = $this->get_all_products();

    if ( ! isset( $cat ) ) {
      return $allProducts;
    }
    //get products by category
    foreach ( $allProducts as $item ) {
      if ( $item->category->id === (int) htmlspecialchars( $cat ) ) {
        array_push( $productsToShow, $item );
      }
    }

    return $productsToShow;
  }

  public function get_categories_from_all_products() {
    $categories  = array();
    $allProducts = $this->get_all_products();
    foreach ( $allProducts as $item ) {
      if ( ! in_array( $item->category, $categories ) ) {
        array_push( $categories, $item->category );
      }
    }

    return $categories;
  }

  public function get_fulfillment() {
    return isset( $_COOKIE['mjfreewayfulfillment'] ) ? $_COOKIE['mjfreewayfulfillment'] : 'in_store';
  }

  public function get_cart() {
    $cookieCart = $this->get_cookie_cart();
    if ( $cookieCart[0] === '' || $cookieCart === null ) {
      return;
    }

    $allProducts = $this->get_all_products();
    $cartItems   = array();

    foreach ( $cookieCart as $key => $item ) {
      $cartItem = explode( '|', $item );

      $cartItemProductId = $cartItem[0];
      $quantity          = (int) $cartItem[1];
      $pricingID         = (int) $cartItem[2];
      $pricing           = 0;
      $pricingName       = '';

      foreach ( $allProducts as $i ) {
        if ( $i->id === (int) $cartItemProductId ) {
          $name = $i->name;
          if ( $pricingID ) {
            foreach ( $i->pricing as $weightPrice ) {
              if ( $weightPrice["pricing_weight_id"] === $pricingID ) {
                $pricing = $weightPrice["default_price"];
                $pricingName = $weightPrice["name"];
              }
            }
          } else {
            $pricing = $i->pricing;
          }
          $c = new MJFreeway_CartItem( $name, $quantity, $pricing, $pricingName );
          array_push( $cartItems, $c );
        }
      }
    }

    return $cartItems;
  }


  public function get_all_products() {

    if ( count( $this->allProducts ) > 0 ) {
      return $this->allProducts;
    }

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, MJFREEWAY_API_ROOT . "catalog?available_online=1&in_stock=1" );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->get_api_headers() );
    $result = curl_exec( $ch );
    if ( curl_errno( $ch ) ) {
      echo 'Error:' . curl_error( $ch );
    }
    curl_close( $ch );

    $this->allProducts = array();

    $items = json_decode( $result, true );

    $items = $items ? $items["data"] : [];

    usort($items, function($a, $b) {
      return strcmp($a['name'], $b['name']);
    });

    foreach ( $items as $item ) {
      if ($item["in_stock"]) {
        $price       = null;
        $pricingType = $item["pricing_type"];
        if ( $pricingType === 'weight' ) {
          $price = $item["pricing"]["weight_prices"];
        } else {
          $price = $item["pricing"]["default_price"];
        }

        $p = new MJFreeway_Product(
          $item['id'],
          $item['description'] ? $item['description'] : 'No product description.',
          $price,
          $item["name"],
          $item['primary_image_urls'][0]['url'],
          new MJFreeway_ProductCategory( $item['category_name'], $item['category_id'] ) );
        array_push( $this->allProducts, $p );
      }
    }

    return $this->allProducts;
  }

  public function options_page() {
    ?>
    <div>
      <h2>MJFreeway Settings</h2>
      <form action="options.php" method="post">
        <?php settings_fields( 'mjfreeway_options' ); ?>
        <?php do_settings_sections( 'mjfreeway_plugin' ); ?>
        <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>"/>
      </form>
    </div>
    <?php
  }

  public function admin_init() {
    register_setting( 'mjfreeway_options', 'mjfreeway_options', array( $this, 'mjfreeway_validate_options' ) );
    add_settings_section( 'mjfreeway_main', 'Main Settings', array(
      $this,
      'mjfreeway_plugin_section_text'
    ), 'mjfreeway_plugin' );
    add_settings_field( 'mjfreeway_api_key_string', 'API KEY', array(
      $this,
      'mjfreeway_api_key_string'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_api_url_string', 'API URL (with trailing slash)', array(
      $this,
      'mjfreeway_api_url_string'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_organization_id_string', 'Organization ID', array(
      $this,
      'mjfreeway_organization_id_string'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_facility_id_string', 'Facility ID', array(
      $this,
      'mjfreeway_facility_id_string'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_user_id_string', 'User ID', array(
      $this,
      'mjfreeway_user_id_string'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_css_string', 'Custom CSS', array(
      $this,
      'mjfreeway_setting_textarea'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_purgatory_string', 'Unverified User Message', array(
      $this,
      'mjfreeway_purgatory_textarea'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_confirmation_string', 'Confirmation Message', array(
      $this,
      'mjfreeway_confirmation_textarea'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_directions_string', 'Directions Link', array(
      $this,
      'mjfreeway_directions_string'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_display_registration_link', 'Display Registration Link on Product Detail page?', array(
      $this,
      'mjfreeway_display_registration_link'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_toggle_delivery', 'Enable delivery features (beta)', array(
      $this,
      'mjfreeway_toggle_delivery'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
    add_settings_field( 'mjfreeway_toggle_express', 'Enable express checkout features', array(
      $this,
      'mjfreeway_toggle_express'
    ), 'mjfreeway_plugin', 'mjfreeway_main' );
  }

  public function mjfreeway_plugin_section_text() {
    // Put something here if you want a subheading in the admin settings
    echo 'API key, API URL, Organization ID, Facility ID and User ID are required fields.';
  }

  public function mjfreeway_api_key_string() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input id='mjfreeway_api_key_string' name='mjfreeway_options[mjfreeway_api_key_string]' size='40' type='text' value='{$options['mjfreeway_api_key_string']}' />";
  }

  public function mjfreeway_api_url_string() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input id='mjfreeway_api_url_string' name='mjfreeway_options[mjfreeway_api_url_string]' size='40' type='text' value='{$options['mjfreeway_api_url_string']}' />";
  }

  public function mjfreeway_organization_id_string() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input id='mjfreeway_organization_id_string' name='mjfreeway_options[mjfreeway_organization_id_string]' size='40' type='text' value='{$options['mjfreeway_organization_id_string']}' />";
  }

  public function mjfreeway_facility_id_string() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input id='mjfreeway_facility_id_string' name='mjfreeway_options[mjfreeway_facility_id_string]' size='40' type='text' value='{$options['mjfreeway_facility_id_string']}' />";
  }

  public function mjfreeway_user_id_string() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input id='mjfreeway_user_id_string' name='mjfreeway_options[mjfreeway_user_id_string]' size='40' type='text' value='{$options['mjfreeway_user_id_string']}' />";
  }

  public function mjfreeway_directions_string() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input id='mjfreeway_directions_string' name='mjfreeway_options[mjfreeway_directions_string]' size='40' type='text' value='{$options['mjfreeway_directions_string']}' /><p>Appears as a link on the confirmation page.</p>";
  }

  public function mjfreeway_setting_textarea() {
    $options = get_option( 'mjfreeway_options' );
    echo "<textarea id='mjfreeway_css_string' name='mjfreeway_options[css_string]' rows='8' cols='40'>{$options['css_string']}</textarea><p>Applied site-wide. Use .mjfreeway as the parent selector to target plugin elements.</p>";
  }

  public function mjfreeway_purgatory_textarea() {
    $options = get_option( 'mjfreeway_options' );
    echo "<textarea id='mjfreeway_purgatory_string' name='mjfreeway_options[mjfreeway_purgatory_string]' rows='8' cols='40'>{$options['mjfreeway_purgatory_string']}</textarea><p>Appears on the product detail page when a user is logged in but does not have an MJ Freeway Customer ID in their profile.</p>";
  }

  public function mjfreeway_confirmation_textarea() {
    $options = get_option( 'mjfreeway_options' );
    echo "<textarea id='mjfreeway_confirmation_string' name='mjfreeway_options[mjfreeway_confirmation_string]' rows='8' cols='40'>{$options['mjfreeway_confirmation_string']}</textarea><p>Appears on the order confirmation page.</p>";
  }

  public function mjfreeway_display_registration_link() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input type='checkbox' id='mjfreeway_display_registration_link' name='mjfreeway_options[mjfreeway_display_registration_link]' value='1'";
    checked( $options['mjfreeway_display_registration_link'] );
    echo " />";
  }
  public function mjfreeway_toggle_delivery() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input type='checkbox' id='mjfreeway_toggle_delivery' name='mjfreeway_options[mjfreeway_toggle_delivery]' value='1'";
    checked( $options['mjfreeway_toggle_delivery'] );
    echo " />";
  }
  public function mjfreeway_toggle_express() {
    $options = get_option( 'mjfreeway_options' );
    echo "<input type='checkbox' id='mjfreeway_toggle_express' name='mjfreeway_options[mjfreeway_toggle_express]' value='1'";
    checked( $options['mjfreeway_toggle_express'] );
    echo " />";
  }

  public function mjfreeway_validate_options( $input ) {
    return $input;
  }

  public function add_plugin_stylesheet() {
    wp_enqueue_style( 'bootstrap', plugins_url( '/css/bootstrap-grid.min.css', __FILE__ ) );
    wp_enqueue_style( 'fontawesome', plugins_url( '/css/fontawesome.min.css', __FILE__ ) );
    wp_enqueue_style( 'mjfreeway', plugins_url( '/css/mjfreeway-plugin.css', __FILE__ ) );
    wp_enqueue_script( 'mjfreeway', plugins_url( '/js/mjfreeway-plugin.min.js', __FILE__ ), '', '1.0', true );
    update_option( 'mjfreeway_display_registration_link', 1);
  }

  //activate plugin and add templated pages
  public function activate_plugin() {
    $this->create_custom_page( 'mjfreeway-products' );
    $this->create_custom_page( 'mjfreeway-checkout' );
    $this->create_custom_page( 'mjfreeway-confirmation' );
    $this->create_custom_page( 'mjfreeway-product-detail' );
  }

  //deactivate plugin and remove templated pages
  public function deactivate_plugin() {

    $pages = array(
      'mjfreeway-products',
      'mjfreeway-checkout',
      'mjfreeway-confirmation',
      'mjfreeway-product-detail'
    );
    foreach ( $pages as $page ) {
      $page_id = get_page_by_path( $page );
      wp_delete_post( $page_id->ID );
    }

    $page_id = get_option( 'mjfreeway-products' );
    wp_delete_post( $page_id );

  }

  public function create_custom_page( $page_name ) {
    $pageExists = false;
    $pages      = get_pages();
    foreach ( $pages as $page ) {
      if ( $page->post_name == $page_name ) {
        $pageExists = true;
        break;
      }
    }
    if ( ! $pageExists ) {
      wp_insert_post( [
        'post_type'   => 'page',
        'post_name'   => $page_name,
        'post_status' => 'publish',
        'post_title'  => $page_name,
      ] );
    }
  }

  public function login_redirect( $redirect_to, $request, $user ) {
    //is there a user to check?
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
      //check for admins
      if ( $redirect_to ) {
        // redirect them to the default place
        return $redirect_to;
      } else {
        return home_url();
      }
    } else {
      return $redirect_to;
    }
  }

  public function clear_cookies_on_logout() {
    setCookie( 'mjfreewaycart', '', time() - 3600, '/', '', 0 );
    unset( $_COOKIE["mjfreewaycart"] );
  }

  public function custom_styles() {
    $options = get_option( 'mjfreeway_options' );
    echo "<style>" . $options['css_string'] . "</style>";
  }

}
