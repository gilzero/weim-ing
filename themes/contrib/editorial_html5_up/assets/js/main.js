/*
	Editorial by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
*/

/* Custom */


/* Add class mobile links if  there is an admin panel*/

var element =  document.getElementById('toolbar-bar');
if (typeof(element) != 'undefined' && element != null)
{
	document.querySelector('.togle').classList.add('togle__admin');
	document.querySelector('.mobile-logo').classList.add('mobile-logo__admin');
	document.querySelector('.togle-wrap').classList.add('togle-wrap__admin');
	document.querySelector('.sidebarfirst').classList.add('sidebarfirst__admin');
	document.querySelector('.mobile-logo-admin').classList.add('mobile-logo-admin__admin');

}


(function ($, Drupal, drupalSettings) {

	$('.togle').on('click', function(e) {
		e.preventDefault();
		$(this).toggleClass('togle_active');
		$('.sidebarfirst').toggleClass('sidebarfirst_active');
		$('.mobile-logo').toggleClass('mobile-logo__active');
		$('.page-content').toggleClass('page-content_active');
		$('.togle-wrap').toggleClass('togle-wrap__active');
	  });
	  
})(jQuery, Drupal, drupalSettings);

/* When the user scrolls down, hide the navbar. When the user scrolls up, show the navbar */
(function ($, Drupal, drupalSettings) {

$(window).resize(function() {
	  var prevScrollpos = window.pageYOffset;
	  window.onscroll = function() {
		var currentScrollPos = window.pageYOffset;
		if (prevScrollpos > currentScrollPos) {
		  document.getElementById("togle").style.top = "10px";
		  document.getElementById("mobile-logo").style.top = "12px";
		  document.getElementById("togle-wrap").style.top = "0";
		} else {
		  document.getElementById("togle").style.top = "-50px";
		  document.getElementById("mobile-logo").style.top = "-50px";
		  document.getElementById("togle-wrap").style.top = "-64px";
		}
		prevScrollpos = currentScrollPos;
	  }

  });
})(jQuery, Drupal, drupalSettings);

/* When the user scrolls down, hide the navbar. When the user scrolls up, show the navbar */
/* If there is an admin panel */

(function ($, Drupal, drupalSettings) {


var element =  document.getElementById('toolbar-bar');
if (typeof(element) != 'undefined' && element != null)
{

	$(window).resize(function() {
		var prevScrollposAdmin = window.pageYOffset;
		window.onscroll = function() {
		var currentScrollPosAdmin = window.pageYOffset;
		if (prevScrollposAdmin > currentScrollPosAdmin) {
			document.querySelector(".togle__admin").style.top = "10px";
			document.querySelector(".mobile-logo__admin").style.top = "50px";
			document.querySelector(".togle-wrap__admin").style.top = "40px";
			
		} else {
			document.querySelector(".togle__admin").style.top = "-80px";
			document.querySelector(".mobile-logo__admin").style.top = "-90px";
			document.querySelector(".togle-wrap__admin").style.top = "-104px";
		}
		prevScrollposAdmin = currentScrollPosAdmin;
		}
	
	});
	var scrolled;
		window.onscroll = function() {
	    scrolled = window.pageYOffset || document.documentElement.scrollTop;
	    if(scrolled > 42){
	        $(".sidebarfirst_active").css({"top": "0"})
	    }
	    if(42 > scrolled){
	        $(".sidebarfirst_active").css({"top": "42px"})         
	    }

	}
}
})(jQuery, Drupal, drupalSettings);





