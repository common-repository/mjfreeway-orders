(function ($) {

  var cartCookieName = 'mjfreewaycart';

  // delete product from cart
  if ($('#mjfreewayCheckout').length > 0) {
    $('[data-productremove]').on('click', function (e) {
      e.preventDefault();
      var cookie = $.cookie(cartCookieName);
      var cart = cookie ? cookie.toString().split(',') : [];
      var product = $(this).data('productremove');
      var newCart = cart.splice(product, 1);
      $.cookie(cartCookieName, cart.toString(), { path: '/' });
      window.location.reload();
    });
  }

  // add product to cart
  $('#mjfreewayAddToCart').on('click', function (e) {
    e.preventDefault();
    var cookie = $.cookie(cartCookieName);
    var cart = cookie ? cookie.toString().split(',') : [];
    var pricingWeightInputVal = $('[name=mjfreeway-pricing-weight-id]:checked').val();
    var pricingWeightId = pricingWeightInputVal ? pricingWeightInputVal : '';
    cart.push($(this).data('product') + '|' + $('#mjfreewaySelectQuantity').val() + '|' + pricingWeightId);
    $.cookie(cartCookieName, cart.toString(), { path: '/' });
    window.location.href = '/mjfreeway-checkout'
  });

})(jQuery);