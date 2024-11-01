var cookieName = '';

(function ($, Drupal) {
  Drupal.behaviors.GOVUKBehavior = {
    attach: function (context) {
      $(once('GOVUKBehavior', '#main-content', context)).each(function () {
        cookieName = drupalSettings.govuk.cookie_name;
        // For anonymous users, once the accept/reject cookie policy cookie
        // is set then the cookie banner will still show on pages that are cached
        // in the browser.
        if (!getCookie(cookieName) && $('.govuk-cookie-banner').length) {
          $('.govuk-cookie-banner').show();
        }
      });
    }
  };
})(jQuery, Drupal);

function toggleMobileMenu() {
  if (!jQuery(".govuk-service-navigation").is(":visible")) {
    jQuery(".govuk-service-navigation").slideDown();
  }
  else {
    jQuery(".govuk-service-navigation").slideUp(function() {
      jQuery(".govuk-service-navigation").removeAttr("style");
    });
  }
}

function acceptCookiePolicy () {
  jQuery('.govuk-cookie-banner').slideUp();
  setCookie(cookieName, 'accept', 90);
}

function rejectCookiePolicy () {
  jQuery('.govuk-cookie-banner').slideUp();
  setCookie(cookieName, 'reject', 90);
}

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;SameSite=Strict";
}

function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) === 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}