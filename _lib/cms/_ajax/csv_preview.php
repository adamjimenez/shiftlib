<?php
require('../../base.php');

ini_set('auto_detect_line_endings', '1');

$i=0;

$handle = fopen('uploads/'.$_SERVER['HTTP_HOST'].'.csv', "r");
if( $handle=== false){
    die('error opening '.$_SERVER['HTTP_HOST'].'.csv' );
}
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE and $i<3 ) {
	for ($j=0; $j < count($data); $j++) {
		if($i==0){
			if( $data[$j] ){
				$rows[$i][]=$data[$j];
			}else{
				$rows[$i][]='Col '.num2alpha($j);
			}
		}else{
			$rows[$i][]=$data[$j];
		}
	}
	$i++;
}

?>
<h4>Preview</h4>
<table class="box">
<?
$i=0;
foreach( $rows as $row ){
	if( $i==0 ){
?>
	<tr>
		<? foreach( $row as $v ){ ?>
		<th><?=truncate($v,15);?></th>
		<? } ?>
	</tr>
<?
	}else{
?>
	<tr>
		<? foreach( $row as $v ){ ?>
		<td><span><?=truncate($v,15);?></span></td>
		<? } ?>
	</tr>
<?
	}
	$i++;
}
?>
</table>