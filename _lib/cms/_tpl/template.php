<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

	<title>Admin Area | <?=$title ?: 'Main'; ?></title>
	
	<script type="text/javascript">
		var section='<?=$vars['section'];?>';
		var fields=(<?=json_encode($fields);?>);
	</script>
	<?php load_js(['jqueryui', 'cms', 'google', 'lightbox', 'fontawesome']); ?>
	<script type="text/javascript" src="/_lib/cms/js/list.js?v=5"></script>
	<script type="text/javascript" src="/_lib/cms/js/ui.list.js"></script>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="/_lib/cms/assets/images/icon/favicon.ico">
    <link rel="stylesheet" href="/_lib/cms/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/metisMenu.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/slicknav.min.css">
    
    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.jqueryui.min.css">

	<link type="text/css" href="https://cdn.datatables.net/rowreorder/1.2.5/css/rowReorder.dataTables.min.css" rel="stylesheet">
	<link type="text/css" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" rel="stylesheet">
	<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet">
	
	<link type="text/css" href="https://cdn.datatables.net/buttons/1.6.1/css/buttons.bootstrap4.min.css" rel="stylesheet">

    <!-- others css -->
    <link rel="stylesheet" href="/_lib/cms/assets/css/typography.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/default-css.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/styles.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/responsive.css">
    <!-- modernizr css -->
    <script src="/_lib/cms/assets/js/vendor/modernizr-2.8.3.min.js"></script>

    <link rel="stylesheet" href="/_lib/cms/css/cms.css">
</head>

<body>
    <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    <!-- preloader area start -->
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <!-- preloader area end -->
    <!-- page container area start -->
    <div class="page-container">
        <!-- sidebar menu area start -->

        <?php
        if ($auth->user['admin']) {
            ?>
        <div class="sidebar-menu">
            <div class="sidebar-header">
                <div class="logo">
					<?php if (file_exists('images/logo.gif')) { ?>
					<a href="/admin">
						<img src="/images/logo.gif" alt="Admin home">
					</a>
					<?php
                    } else {
                        $website = explode('.', ucfirst(str_replace('www.', '', $_SERVER['HTTP_HOST']))); ?>
					<a href="/admin">
						<?=$website[0]; ?>
					</a>
					<?php
                    } ?>
                </div>
            </div>
            <div class="main-menu">
                <div class="menu-inner">
                    <nav>
                        <ul class="metismenu" id="menu">

							<?php
                            foreach ($vars['sections'] as $section) {
                                preg_match('/([a-zA-Z0-9\-\s]+)/', $section, $matches);
                                $option = trim($matches[1]);

                                if ('-' == $section) {
                                    ?>
									<li><hr></li>
							<?php
                                } elseif (1 == $auth->user['admin'] or $auth->user['privileges'][$option]) {
                                    ?>
								<li <?php if ($option == $_GET['option']) { ?>class="active"<?php } ?>>
									<a href="?option=<?=$option; ?>" title="<?=ucfirst($section); ?>">
										<span>
											<?=ucfirst($section); ?>
											<?php
                                            if (in_array('read', $vars['fields'][$section])) {
                                                $unread = $this->get($section, ['read' => 0], true);

                                                if ($unread) {
                                                    ?>
												(<?=$unread; ?>)
											<?php
                                                }
                                            } ?>
										</span>
									</a>
								</li>

								<?php
                                foreach ($this->filters as $v) {
                                    if ($v['section'] != $option) {
                                        continue;
                                    }

                                    parse_str($v['filter'], $conditions);
                                    $result = $this->get($v['section'], $conditions, true); ?>
									<li <?php if ($v['filter'] == http_build_query($_GET)) { ?>id="current"<?php } ?>>
										<a href="?<?=$v['filter']; ?>" title="<?=ucfirst($v['name']); ?>">
											<span>
												- <?=ucfirst($v['name']); ?> <?php if ($result) { ?>(<?=$result;?>)<?php } ?>
											</span>
										</a>
									</li>
								<?php
                                } ?>
							<?php
                                }
                            } ?>

							<?php
                            if ($shop_enabled and (1 == $auth->user['admin'] or $auth->user['privileges']['orders'])) { ?>
							<li <?php if ('shop_orders' == $_GET['option']) { ?>id="current"<?php } ?>>
								<a href="?option=shop_orders"><span>Orders</span></a>
							</li>
							<?php } ?>
							<?php if ((1 == $auth->user['admin'] or $auth->user['privileges']['email_templates'])) { ?>
							<li <?php if ('email_templates' == $_GET['option']) { ?>id="current"<?php } ?>>
								<a href="?option=email templates"><span>Email Templates</span></a>
							</li>
							<?php } ?>
							<?php if (1 == $auth->user['admin'] and $sms_config['provider']) { ?>
							<li <?php if ('sms templates' == $_GET['option']) { ?>id="current"<?php } ?>>
								<a href="?option=sms templates"><span>SMS Templates</span></a>
							</li>
							<?php } ?>

							<?php if (1 == $auth->user['admin'] or $auth->user['privileges']['uploads']) { ?>
							<li>
								<a href="#" class="upload"><span>Uploads</span></a>
							</li>
							<?php } ?>

							<?php if (1 == $auth->user['admin']) { ?>
							<li <?php if ('configure' == $_GET['option']) { ?>id="current"<?php } ?>>
								<a href="/admin?option=configure"><span>Configure</span></a>
							</li>
							<?php } ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <?php
        }
        ?>

        <!-- sidebar menu area end -->
        <!-- main content area start -->
        <div class="main-content">
            <!-- header area start -->
            <div class="header-area">
                <div class="row align-items-center">
                    <!-- nav and search button -->
                    <div class="col-md-6 col-sm-6 clearfix">
                        <div class="nav-btn pull-left">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <!-- profile info & task notification -->
                    <div class="col-md-6 col-sm-6 clearfix">
                        <ul class="notification-area pull-right">

                         	<?php if ($auth->user['admin']) { ?>
								<li>Hello, <?=$auth->user['name'] ? $auth->user['name'] . ' ' . $auth->user['surname'] : $auth->user['email'];?></li>
								<li><a href="/">Website</a></li>
								<li><a href="/logout">Log out</a></li>
							<?php } else { ?>
								<li><a href="/admin?option=login">Log in</a></li>
							<?php } ?>

                        </ul>
                    </div>
                </div>
            </div>
            <!-- header area end -->

            <div <?php if (!strstr($include_content, 'main-content-inner')) { ?>class="main-content-inner"<?php } ?>>

				<?php
                if ($_SESSION['message']) {
                    ?>
                    <div class="alert-items m-3">
                        <div class="alert alert-primary" role="alert">
                            <?=nl2br($_SESSION['message']); ?>
                        </div>
                    </div>
				<?php
                    unset($_SESSION['message']);
                }
                ?>
				<?=$include_content;?>

            </div>
        </div>
        <!-- main content area end -->
        <!-- footer area start-->
        <footer>
            <div class="footer-area">
                <p>© Copyright <?=date('Y');?>. All right reserved. ShiftCreate Ltd.</p>
            </div>
        </footer>
        <!-- footer area end-->
    </div>
    <!-- page container area end -->

    <!-- bootstrap 4 js -->
    <script src="/_lib/cms/assets/js/popper.min.js"></script>
    <script src="/_lib/cms/assets/js/bootstrap.min.js"></script>
    <script src="/_lib/cms/assets/js/metisMenu.min.js"></script>
    <script src="/_lib/cms/assets/js/jquery.slimscroll.min.js"></script>
    <script src="/_lib/cms/assets/js/jquery.slicknav.min.js"></script>

    <!-- Start datatable js -->
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>

	<script type="text/javascript" src="https://cdn.datatables.net/rowreorder/1.2.5/js/dataTables.rowReorder.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
	<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
	
	<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.bootstrap4.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.colVis.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>

    <script src="/_lib/cms/assets/js/plugins.js"></script>
    <script src="/_lib/cms/assets/js/scripts.js"></script>

    <script>
        function button_handler (value, show_prompt, node) {
	   		if (show_prompt) {
				var result = confirm('Are you sure?');

				if (!result) {
					$('.action').val('');
					return false;
				}
	   		}

			$('.action').val(value);
	    	var form = $(node).closest('form');
	    	var table = form.find('table').DataTable();
	    	var rows_selected = table.column(1).checkboxes.selected();

			// Iterate over all selected checkboxes
			$.each(rows_selected, function(index, rowId){
    			// Create a hidden element
    			$(form).append(
    				$('<input>')
    					.attr('type', 'hidden')
    					.attr('name', 'id[]')
    					.val(rowId)
    				);
    		});

			// check if all selected
			if ($(table.table().container()).find('.selectAllPages').data('selected')) {
    			$(form).append(
    				$('<input>')
    					.attr('type', 'hidden')
    					.attr('name', 'select_all_pages')
    					.val(1)
    				);
            };

			form.submit();
        }
    
	   // Handle form submission event
	   $('body').on('click', '.buttons button', function(e) {
	       button_handler($(this).data('value'), $(this).data('confirm'), this);
	   });
    </script>
</body>

</html>