$(function () {
  "use strict";

  /* scrollar */
  new PerfectScrollbar(".notify-list")

  /* toggle button */
  $(".btn-toggle").click(function () {
    $("body").hasClass("toggled") ? ($("body").removeClass("toggled"), $(".sidebar-wrapper").unbind("hover")) : ($("body").addClass("toggled"), $(".sidebar-wrapper").hover(function () {
      $("body").addClass("sidebar-hovered")
    }, function () {
      $("body").removeClass("sidebar-hovered")
    }))
  })

  /* menu */
  $(function () {
    $('#sidenav').metisMenu();
  });

  $(".sidebar-close").on("click", function () {
    $("body").removeClass("toggled")
  })

  /* sticky header */
  $(document).ready(function () {
    $(window).on("scroll", function () {
      if ($(this).scrollTop() > 60) {
        $('.top-header .navbar').addClass('sticky-header');
      } else {
        $('.top-header .navbar').removeClass('sticky-header');
      }
    });
  });

  /* mode switcher */
  $("#mode-switcher").on("click", function () {
    // Check if theme is set in local storage
    if (!localStorage.getItem('theme')) {
      localStorage.setItem('theme', 'semi-dark');
    }

    // Switch theme in local storage
    if (localStorage.getItem('theme') === 'dark') {
      localStorage.setItem('theme', 'semi-dark');
    } else {
      localStorage.setItem('theme', 'dark');
    }

    // Apply theme
    if (localStorage.getItem('theme') === 'dark') {
      $("html").attr("data-bs-theme", "dark")
    } else {
      $("html").attr("data-bs-theme", "semi-dark")
    }
  });

  /* menu active */
  $(function () {
    for (var e = window.location, o = $(".metismenu li a").filter(function () {
      return this.href == e
    }).addClass("").parent().addClass("mm-active"); o.is("li");) o = o.parent("").addClass("mm-show").parent("").addClass("mm-active")
  });
});