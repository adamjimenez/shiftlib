<?php
require('../../base.php');

ini_set('auto_detect_line_endings', '1');

$i = 0;

$handle = fopen('uploads/' . $_SERVER['HTTP_HOST'] . '.csv', 'r');
if (false === $handle) {
    die('error opening ' . $_SERVER['HTTP_HOST'] . '.csv');
}
while (false !== ($data = fgetcsv($handle, 1000, ',')) and $i < 3) {
    for ($j = 0; $j < count($data); $j++) {
        if (0 == $i) {
            if ($data[$j]) {
                $rows[$i][] = $data[$j];
            } else {
                $rows[$i][] = 'Col ' . num2alpha($j);
            }
        } else {
            $rows[$i][] = $data[$j];
        }
    }
    $i++;
}

?>
<h4>Preview</h4>
<table class="box">
<?php
$i = 0;
foreach ($rows as $row) {
    if (0 == $i) {
        ?>
	<tr>
		<?php foreach ($row as $v) { ?>
		<th><?=truncate($v, 15);?></th>
		<?php } ?>
	</tr>
<?php
    } else {
        ?>
	<tr>
		<?php foreach ($row as $v) { ?>
		<td><span><?=truncate($v, 15);?></span></td>
		<?php } ?>
	</tr>
<?php
    }
    $i++;
}
?>
</table>