<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Myday | Log in</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>LOGIN</b></a>
        </div>
        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to start your session</p>
                <div class="d-flex justify-content-center">
                    <p class="mt-1" style="color: red; display:none" id="lin_error">Login Error</p>
                </div>
                <form method="post" name="login" id="login">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" name="email" id="email"
                            placeholder="User Email">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" id="password"
                            placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary btn-block" id="login_btn">Sign In</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

                <p class="mb-1">

                </p>
                <p class="mb-0">

                </p>
            </div>
            <!-- /.login-card-body -->
        </div>
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.min.js"></script>

    <script src="plugins/toastr/toastr.min.js"></script>
</body>
<script>
    $(window).on('load', () => {
        var auth = localStorage.getItem("authUser");
        if (auth != null) {
            window.location.href = "home";
        }
    });
    $("#login_btn").on("click", login);

    function login() {
        $.ajax({

            method: "post",
            //  url: "http://localhost:8000/api/login",
            url: "{{ config('app.api_url') }}" + "login",
            datatype: "html",
            data: $('#login').serialize(),
            success: function(data) {
                console.log('====================================');
                console.log(data.user.user_type);
                console.log('====================================');
                if (data.user.user_type=="Admin") {
                    localStorage.setItem("authUser", JSON.stringify(data));
                    toastr.success('Login Successful');
                    setTimeout(function() {
                        location.href = "home"
                    }, 3000);
                }else{
                    $("#lin_error").show();
                    $("#lin_error").html("You Are Not Admin");
                }
                //  if(data.trim() == "1"){
                //   $("#modal-item").hide();
                //   alertify.success('Ok');http://localhost:8000/ api/
                //   setTimeout(function(){location.reload()},3000);
                //  },
                console.log(data);
            },
            error: function(data) {
                console.log('====================================');
                console.log(data);
                console.log('====================================');
                $("#lin_error").show();
                $("#lin_error").html(data.responseJSON.message);
                toastr.error('Login Error');
            },
        });
    }
</script>

</html>
