<h1>
	<?=ucfirst($this->section);?>
</h1>

<?php
if (is_array($vars['subsections'][$this->section])) {
    ?>
<ul class="mx-3">
<?php
    foreach ($vars['subsections'][$this->section] as $count => $subsection) {
        ?>
	<li>
		<a href="?option=<?=$subsection; ?>"><?=ucfirst($subsection); ?></a>
	</li>
<?php
    } ?>
</ul>
<?php
}
?>