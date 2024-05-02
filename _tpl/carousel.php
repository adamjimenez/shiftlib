<?php
require('_inc/blocks/carousel.php');

$page_data = $cms->get_page();
$meta = $page_data['meta'];
$content = $cms->get('home');
?>

<h1 sl-id="home.heading" sl-type="text">
	<?=$content['heading'];?>
</h1>

<div sl-name="main" sl-type="block">
	<?=$cms->render_blocks($meta['main']);?>
</div>

<?php 
load_js('shiftlib');
$cms->load_page_editor($page_data);