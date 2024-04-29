<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
    $(window).on('load', () => {
        var auth = localStorage.getItem("authUser");
        if (auth == null) {
            window.location.href = "login";
        }
    });

    function logout() {
        localStorage.removeItem("authUser");
        window.location.href = "login";
    }
</script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- Toastr  Alert-->
<script src="plugins/toastr/toastr.min.js"></script>
