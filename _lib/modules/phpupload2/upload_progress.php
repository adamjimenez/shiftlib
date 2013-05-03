<?
function file_size($size)
{
	for($si = 0; $size >= 1024; $size /= 1024, $si++);
	return round($size, 1)." ".substr(' KMGT', $si, 1)."B";
}

function sec2hms ($sec, $padHours = false) 
{
	// holds formatted string
	$hms = "";
	
	$hours = intval(intval($sec) / 3600); 
	
	// add to $hms, with a leading 0 if asked for
	$hms .= ($padHours) 
		 ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
		 : $hours. ':';
	
	$minutes = intval(($sec / 60) % 60); 
	
	// then add to $hms (with a leading 0 if needed)
	$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
	
	$seconds = intval($sec % 60); 
	
	// add to $hms, again with a leading 0 if needed
	$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
	
	return $hms;
}

$v = uploadprogress_get_info($_GET['uniq']);

if( is_array($v) ){
	$v['perc']=@round($v['bytes_uploaded']/$v['bytes_total']*100);
	
	$v['status']=file_size($v['bytes_uploaded']).' of '.file_size($v['bytes_total']);
	$v['status'].=' at '.file_size($v['speed_last']).' /sec; '.sec2hms($v['est_sec'],true).' remain';

	print json_encode($v);
}
?>
