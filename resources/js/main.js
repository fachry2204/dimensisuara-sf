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
  function applyTheme(theme) {
    var isLight = theme === 'light';
    $("html").attr("data-bs-theme", isLight ? "light" : "dark");
    $("#mode-switcher")
      .attr("aria-pressed", isLight ? "true" : "false")
      .attr("title", isLight ? "Gunakan tema gelap" : "Gunakan tema terang")
      .attr("aria-label", isLight ? "Gunakan tema gelap" : "Gunakan tema terang")
      .find("i").text(isLight ? "light_mode" : "dark_mode");
  }

  applyTheme(localStorage.getItem('theme') || 'dark');
  $("#mode-switcher").on("click", function () {
    var nextTheme = $("html").attr("data-bs-theme") === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', nextTheme);
    applyTheme(nextTheme);
  });

  /* menu active */
  $(function () {
    for (var e = window.location, o = $(".metismenu li a").filter(function () {
      return this.href == e
    }).addClass("").parent().addClass("mm-active"); o.is("li");) o = o.parent("").addClass("mm-show").parent("").addClass("mm-active")
  });
});