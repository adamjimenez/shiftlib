<?php
$query = "SELECT *,L.date FROM cms_logs L
	LEFT JOIN users U ON L.user=U.id
	WHERE
		section='" . escape($_GET['option']) . "' AND
		item='" . escape($id) . "'
	ORDER BY L.id DESC
";

$p = new paging($query, 20);
$logs = sql_query($p->query);

if (count($logs)) {
    ?>
<div style="overflow: scroll; background: #fff;">
	<?php
    foreach ($logs as $k => $v) {
        if ('users' == $_GET['option']) {
            $item_table = underscored($v['section']);

            if ($vars['fields'][$v['section']]) {
                $item = sql_query("SELECT * FROM `$item_table` WHERE id='" . escape($v['item']) . "'", 1);
                $label = key($vars['fields'][$v['section']]);
                $item_name = $item[$label];
            }
        }

        $name = $v['name'] ? $v['name'] . ' ' . $v['surname'] : $v['email']; ?>
	<p>
		<strong><a href="?option=<?=$v['section']; ?>&view=true&id=<?=$v['item']; ?>"><?=$item_name; ?></a> <?=ucfirst($v['task']); ?> by <a href="?option=users&view=true&id=<?=$v['user']; ?>"><?=$name; ?></a> on <?=$v['date']; ?></strong><br>
		<?=nl2br(htmlentities($v['details'])); ?>
	</p>
	<?php
    } ?>
	<p>
		<?=$p->get_paging(); ?>
	</p>
</div>
<?php
}
?>