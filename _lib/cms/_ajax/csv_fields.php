<?php
require('../../base.php');

ini_set('auto_detect_line_endings', '1');

$handle = fopen(dirname(__FILE__).'/../tmp/'.$_SERVER['HTTP_HOST'].'.csv', "r");
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE AND $i<3 ) {
	for ($j=0; $j < count($data); $j++) {
		if($i==0){
			if( $data[$j] ){
				$options[$j]=$data[$j];
			}else{
				$options[$j]='Col '.num2alpha($j+1);
			}
		}
		if(strstr($data[$j],'@')){
			$vars['email']=$j;
		}
		$rows[$i][]=$data[$j];
	}
	
	if( $i==0 ){
		break;
	}
	
	$i++;	
}

print json_encode($options);
?>