/* Load jQuery.
--------------------------*/
jQuery(document).ready(function ($) {
  // Mobile menu.
  $('.mobile-menu').click(function () {
    $(this).toggleClass('menu-icon-active');
    $(this).next('.primary-menu-wrapper').toggleClass('active-menu');
  });
  $('.close-mobile-menu').click(function () {
    $(this).closest('.primary-menu-wrapper').toggleClass('active-menu');
    $('.mobile-menu').removeClass('menu-icon-active');
  });
  // Header search form
  $('.search-icon').on('click', function() {
    $('.search-box').toggleClass('open');
    $('.search-box-content .form-search').focus();
    return false;
  });
  $('.search-box-content input[type="search"]').attr("placeholder", "Type your search");

  $('.header-search-close').on('click', function() {
    $('.search-box').removeClass('open');
    return false;
  });
  $('.search-box').on( 'keydown', function(e) {
    if ( e.keyCode === 27 ) {
      $('.search-box.open').removeClass('open');
    }
  });
  $(document).on('click', function(event) {
    if(!$(event.target).closest(".search-box-content").length) {
      $('.search-box').removeClass('open');
    }
  })
  // Scroll To Top.
  $(window).scroll(function () {
    if ($(this).scrollTop() > 80) {
      $('.scrolltop').css('display', 'flex');
    } else {
      $('.scrolltop').fadeOut('slow');
    }
  });
  $('.scrolltop').click(function () {
    $('html, body').scrollTop(0);
  });
/* End document
--------------------------*/
});
