<!--plugins-->
<script src="{{ URL::asset('build/js/jquery.min.js') }}"></script>
<!--bootstrap js-->
<script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>
<!--required scripts-->
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/functions.js') }}"></script>

@unless (Route::is('login') || Route::is('register'))
<!-- Script Webchat -->
<script>
  window.REPLY_WIDGET_ID = "8b08ed73-dae4-4f21-9798-780c1a2bef91";
</script>
<script src="https://app.cloudchat.id/widget.js" async></script>
<!-- End Webchat -->
@endunless

@stack('script')
