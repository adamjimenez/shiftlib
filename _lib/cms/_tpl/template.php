<?php
$versions = [
    'datatables' => [
        'core' => '1.12.1',
        'responsive' => '2.3.0',
        'rowReorder' => '1.2.8',
        'select' => '1.4.0',
        'buttons' => '2.2.3',
        'checkboxes' => '1.2.13',
    ]
];
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>Admin Area | <?=$title ?: 'Main'; ?></title>

    <?php load_js(['jqueryui', 'bootstrap', 'cms', 'fontawesome', 'lightbox', 'vue3', 'google']); ?>
    <script type="text/javascript" src="/_lib/cms/assets/js/list.js?v=6"></script>
    <script type="text/javascript" src="/_lib/cms/assets/js/ui.list.js"></script>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="/_lib/cms/assets/images/icon/favicon.ico">
    <link rel="stylesheet" href="/_lib/cms/assets/css/metisMenu.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/slicknav.min.css">

    <!-- Start datatable css -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/<?=$versions['datatables']['core']; ?>/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/<?=$versions['datatables']['core']; ?>/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/<?=$versions['datatables']['responsive']; ?>/css/responsive.bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/<?=$versions['datatables']['responsive']; ?>/css/responsive.jqueryui.min.css">

    <link type="text/css" href="https://cdn.datatables.net/rowreorder/<?=$versions['datatables']['rowReorder']; ?>/css/rowReorder.dataTables.min.css" rel="stylesheet">
    <link type="text/css" href="https://cdn.datatables.net/select/<?=$versions['datatables']['select']; ?>/css/select.dataTables.min.css" rel="stylesheet">
    <link type="text/css" href="/_lib/cms/assets/css/dataTables.checkboxes.css" rel="stylesheet">
    <link type="text/css" href="https://cdn.datatables.net/buttons/<?=$versions['datatables']['buttons']; ?>/css/buttons.bootstrap4.min.css" rel="stylesheet">

    <!-- others css -->
    <link rel="stylesheet" href="/_lib/cms/assets/css/typography.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/default-css.css">
    <link rel="stylesheet" href="/_lib/cms/assets/css/styles.css?v=1">
    <link rel="stylesheet" href="/_lib/cms/assets/css/responsive.css">
    <!-- modernizr css -->
    <script src="/_lib/cms/assets/js/modernizr-2.8.3.min.js"></script>

    <link rel="stylesheet" href="/_lib/cms/assets/css/cms.css">
    
    <script src="/_lib/modules/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    
    <?php
    if ($this->custom_css) {
    ?>
    <link rel="stylesheet" href="<?=$this->custom_css;?>">
    <?php
    }
    ?>

    <script>
        // used for imports
        var table_config = [];
    </script>
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
    <div class="page-container notransition">
        <!-- sidebar menu area start -->

        <?php
        if ($auth->user['admin']) {
            ?>
            <div class="sidebar-menu notransition">
                <div class="sidebar-header">
                    <div class="logo">
                        <?php
                        $website = explode('.', ucfirst(str_replace('www.', '', $_SERVER['HTTP_HOST']))); ?>
                        <a href="/admin">
                            
                            <?php
                            if ($this->logo) {
                            ?>
					        <img src="/assets/img/gg-logo-white.svg" alt="Admin home" align="middle" class="py-3">
					        <?php
                            } else {
                                print $website[0];
                            }
                            ?>
                        </a>
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
                                                    $unread = 0;
                                                    
                                                    try {
                                                        $fields = $this->get_fields($section);

                                                        if ($fields['read']) {
                                                            $unread = $this->get($section, ['read' => 0], true);

                                                            if ($unread) {
                                                                ?>
                                                                (<?=$unread; ?>)
                                                                <?php
                                                            }
                                                        }
                                                    } catch (Exception $e) {
                                                    }
                                                    ?>
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
                                                        - <?=ucfirst($v['name']); ?> <?php if ($result) {
                                                            ?>(<?=$result; ?>)<?php
                                                        } ?>
                                                    </span>
                                                </a>
                                            </li>
                                            <?php
                                        } ?>
                                        <?php
                                    }
                                } ?>
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
                    <div class="col-3 clearfix">
                        <div class="nav-btn pull-left">
                            <i class="fas fa-bars"></i>
                        </div>
                    </div>
                    <!-- profile info & task notification -->
                    <div class="col-9 clearfix">
                        <ul class="notification-area pull-right">
                            <li><a href="/" title="Website"><i class="fas fa-home"></i></a></li>


                            <?php
                            if (1 == $auth->user['admin'] || $auth->user['privileges']['uploads']) {
                                ?>
                                <li>
                                    <a href="#" class="upload">
                                        <i class="fas fa-file-upload"></i>
                                    </a>
                                </li>
                                <?php
                            } ?>

                            <?php if (1 == $auth->user['admin']) {
                                ?>
                                <li>
                                    <a href="/admin?option=configure">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                </li>
                                <?php
                            } ?>

                            <?php if ($auth->user['admin']) {
                                ?>
                                <li><a href="/logout" title="Sign out"><i class="fas fa-sign-out-alt"></i></a></li>
                                <?php
                            } else {
                                ?>
                                <li><a href="/admin?option=login" title="Sign in"><i class="fas fa-sign-in-alt"></i></a></li>
                                <?php
                            } ?>

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
                <?=$include_content; ?>

            </div>
        </div>
        <!-- main content area end -->
        <!-- footer area start-->
        <footer>
            <div class="footer-area">
                <p>
                    Version <?=$this::VERSION; ?>
                </p>
            </div>
        </footer>
        <!-- footer area end-->
    </div>
    <!-- page container area end -->

    <!-- import modal start -->
    <form method="post" id="importForm" enctype="multipart/form-data" onSubmit="checkForm(); return false;">
        <div class="modal fade" id="importModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">

                        <input type="hidden" id="importSection" name="section" value="" />
                        Upload a <strong>comma delimited csv</strong> file.<br>
                        <br>

                        <div>
                            File: <span id="file_field"><input type="file" class="import_file" name="file"></span>
                        </div>

                        <div id="csv_loaded" style="display:none; width:auto;">
                            <div id="csv_preview" style="margin:1em; border: 1px solid #000; overflow: scroll;"></div>

                            <p>
                                Match up the columns with the spreadsheet columns below.
                            </p>

                            <table width="310" class="box">
                                <thead>
                                    <tr>
                                        <th>List column</th>
                                        <th>&nbsp;</th>
                                        <th>File column</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                            <br>
                            <p>
                                <label><input type="checkbox" name="update" value="1"> update existing?</label>
                            </p>
                            <p>
                                <label><input type="checkbox" name="validate" value="1"> validate?</label>
                            </p>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- import modal end -->

    <!-- stay logged in modal start -->
    <div class="modal fade stayLoggedInModal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Notice</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    You are about to be logged out.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary stayLoggedInBtn">Stay logged in</button>
                </div>
            </div>
        </div>
    </div>
    <!-- stay logged in modal end -->

    <!-- bootstrap 4 js -->
    <script src="/_lib/cms/assets/js/metisMenu.min.js"></script>
    <script src="/_lib/cms/assets/js/jquery.slimscroll.min.js"></script>
    <script src="/_lib/cms/assets/js/jquery.slicknav.min.js"></script>

    <!-- Start datatable js -->
    <script src="https://cdn.datatables.net/<?=$versions['datatables']['core']; ?>/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/<?=$versions['datatables']['core']; ?>/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/<?=$versions['datatables']['responsive']; ?>/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/<?=$versions['datatables']['responsive']; ?>/js/responsive.bootstrap.min.js"></script>

    <script type="text/javascript" src="https://cdn.datatables.net/rowreorder/<?=$versions['datatables']['rowReorder']; ?>/js/dataTables.rowReorder.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/select/<?=$versions['datatables']['select']; ?>/js/dataTables.select.min.js"></script>
    <script type="text/javascript" src="/_lib/cms/assets/js/dataTables.checkboxes.min.js"></script>

    <script type="text/javascript" src="https://cdn.datatables.net/buttons/<?=$versions['datatables']['buttons']; ?>/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/<?=$versions['datatables']['buttons']; ?>/js/buttons.bootstrap4.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/<?=$versions['datatables']['buttons']; ?>/js/buttons.colVis.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/<?=$versions['datatables']['buttons']; ?>/js/buttons.html5.min.js"></script>

    <script src="/_lib/cms/assets/js/plugins.js"></script>
    <script src="/_lib/cms/assets/js/scripts.js?v=1"></script>

    <script>
        function button_handler (value, show_prompt, dt, custom_id) {
            if (show_prompt) {
                var result = confirm('Are you sure?');

                if (!result) {
                    $('.action').val('');
                    return false;
                }
            }

            $('.action').val(value);
            var form = $(dt.table().container()).closest('form');
            var table = form.find('table').DataTable();
            var rows_selected = table.column(1).checkboxes.selected();

            // Iterate over all selected checkboxes
            $.each(rows_selected, function(index, rowId) {
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

            // columns
            $(form).append(
                $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'columns')
                .val(JSON.stringify(dt.columns().visible().toArray()))
            );

            // columns
            $('.custom_button').val(parseInt(custom_id));

            form.submit();
        }

        // Handle form submission event
        $('body').on('click', '.buttons button', function(e) {
            button_handler($(this).data('value'), $(this).data('confirm'), this);
        });

        // file import
        $('.import_file').on('change', changeFile);

        // fix close button
        /*
        $(function() {
            var bootstrapButton = $.fn.button.noConflict() // return $.fn.button to previously assigned value
            $.fn.bootstrapBtn = bootstrapButton            // give $().bootstrapBtn the Bootstrap functionality
        })
        */

        // hide empty list dropdown menu
        $(function() {
            $('.dropdown-menu').each(function() {
                if (!$(this).children().length) {
                    $(this).prev().hide();
                }
            });
        });

        // stay logged in start
        /*
        var session_duration = '<?=ini_get("session.gc_maxlifetime"); ?>';
        var logInTimer;
        function setLogInTimer() {
            logInTimer = setTimeout(function() {
                $('.stayLoggedInModal').modal('show');
            }, (session_duration - 60) * 1000);
        }
        $(function() {
            setLogInTimer();
            $('.stayLoggedInBtn').click(function() {
                $.ajax( '/_lib/api/?cmd=ping', {
                    dataType: 'json',
                    success: function(data) {
                        $('.stayLoggedInModal').modal('hide');
                        setLogInTimer();
                        if (data.error) {
                            alert(data.error);
                        }
                    }
                });
            })
        })
        */
        // stay logged in end
    </script>

    <?php    
    // debug page speed
    global $auth, $time_start;
    if ($auth->user['admin'] && $_GET['debug']) {
        $time_end = microtime(true);
        echo '<span style="color:yellow; background: red; position:absolute; top:0; left:0; z-index: 100;">Loaded in ' . number_format($time_end - $time_start, 3) . 's</span>';
    }
    ?>
</body>

</html>