<?php
// redirect to home if logged in
$result = $auth->login();

// handle login
if (1 === $result['code']) {
    if ($_SESSION['request']) {
        redirect($_SESSION['request']);
    } else {
        redirect('/admin');
    }
}
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Login</title>

    <?php load_js(['cms']); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="/_lib/cms/assets/images/icon/favicon.ico">
    <link rel="stylesheet" href="/_lib/cms/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/metisMenu.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/slicknav.min.css">
    <!-- others css -->
    <link rel="stylesheet" href="/_lib/cms/assets/css/typography.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/default-css.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/styles.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/responsive.css">
    <!-- modernizr css -->
    <script src="/_lib/cms/assets/js/vendor/modernizr-2.8.3.min.js"></script>
</head>

<body>
    <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    <!-- preloader area start -->
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <!-- preloader area end -->
    <!-- login area start -->
    <div class="login-area">
        <div class="container">
            <div class="login-box ptb--100">
                <form action="/admin?option=login" method="post" class="validate">
                <input type="hidden" name="login" value="1">
                    <div class="login-form-head">
                        <h4>Sign In</h4>
                        <p>Hello there, Sign in and start managing your Admin</p>
                    </div>
                    <div class="login-form-body">
                        <div class="form-gp">
                            <label for="email">Email address</label>
                            <input type="text" id="email" name="email">
                            <i class="ti-email"></i>
                        </div>
                        <div class="form-gp">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password">
                            <i class="ti-lock"></i>
                        </div>
                        <div class="row mb-4 rmber-area">
                            <div class="col-6">
                                <div class="custom-control custom-checkbox mr-sm-2">
                                    <input type="checkbox" class="custom-control-input" id="remember" name="remember" value="1">
                                    <label class="custom-control-label" for="remember">Remember Me</label>
                                </div>
                            </div>
                            <?php
                            /*
                            <div class="col-6 text-right">
                                <a href="#">Forgot Password?</a>
                            </div>
                            */
                            ?>
                        </div>
                        <div class="submit-btn-area">
                            <button id="form_submit" type="submit">Submit <i class="ti-arrow-right"></i></button>
                            <?php
                            /*
                            <div class="login-other row mt-4">
                                <div class="col-6">
                                    <a class="fb-login" href="#">Log in with <i class="fa fa-facebook"></i></a>
                                </div>
                                <div class="col-6">
                                    <a class="google-login" href="#">Log in with <i class="fa fa-google"></i></a>
                                </div>
                            </div>
                            */
                            ?>
                        </div>
                        <?php
                        /*
                        <div class="form-footer text-center mt-5">
                            <p class="text-muted">Don't have an account? <a href="register.html">Sign up</a></p>
                        </div>
                        */
                        ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- login area end -->

    <!-- bootstrap 4 js -->
    <script src="/_lib/cms/assets/js/popper.min.js"></script>
    <script src="/_lib/cms/assets/js/bootstrap.min.js"></script>
    <script src="/_lib/cms/assets/js/owl.carousel.min.js"></script>
    <script src="/_lib/cms/assets/js/metisMenu.min.js"></script>
    <script src="/_lib/cms/assets/js/jquery.slimscroll.min.js"></script>
    <script src="/_lib/cms/assets/js/jquery.slicknav.min.js"></script>

    <!-- others plugins -->
    <script src="/_lib/cms/assets/js/plugins.js"></script>
    <script src="/_lib/cms/assets/js/scripts.js"></script>
</body>

</html>

<?php
exit;
?>