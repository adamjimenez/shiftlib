<?php
$content = $cms->get('home');
?>

<h1 sl-id="home.heading" sl-type="text">
	<?=$content['heading'];?>
</h1>

<div sl-id="home.copy" sl-type="editor">
	<?=$content['copy'];?>
</div>

<?php 
load_js('shiftlib');
$cms->load_page_editor();
?>