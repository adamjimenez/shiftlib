<?php
namespace cms;

class postcode extends component
{
	function is_valid($value) {
		return format_postcode($value) !== false;
	}
	
	function format_value($value) {
		return format_postcode($value);
	}
	
	function search_field($name, $value) {
		global $vars;
		
		$field_name = underscored($name);
		
		//distance options
		$opts['distance'] = [
		    3 => '3 miles',
		    10 => '10 miles',
		    15 => '15 miles',
		    20 => '20 miles',
		    30 => '30 miles',
		    40 => '40 miles',
		    50 => '50 miles',
		    75 => '75 miles',
		    100 => '100 miles',
		    150 => '150 miles',
		    200 => '200 miles',
		];
	?>
		Distance from <?=$name;?><br>
		
		Within
		<select name="func[<?=$field_name;?>]">
		<option value=""></option>
			<?=html_options($opts['distance'], $_GET['func'][$field_name]);?>
		</select>
		of
		<input type="text" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="7">
	<?php
	}
}