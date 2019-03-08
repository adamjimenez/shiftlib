<?php
ini_set('memory_limit','512M');

/* backup the db OR just a table */
function backup_tables($tables = '*')
{

	//get all of the tables
	if($tables == '*')
	{
		$tables = array();
		$result = mysqli_query($db_connection, 'SHOW TABLES');
		while($row = mysqli_fetch_row($result))
		{
			$tables[] = $row[0];
		}
	}
	else
	{
		$tables = is_array($tables) ? $tables : explode(',',$tables);
	}

	$return = '';

	//cycle through
	foreach($tables as $table)
	{
		$result = mysqli_query($db_connection, 'SELECT * FROM '.$table);
		$num_fields = mysqli_num_fields($result);

		//$return.= 'DROP TABLE '.$table.';';
		$row2 = mysqli_fetch_row(mysqli_query($db_connection, 'SHOW CREATE TABLE '.$table));
		$return.= "\n\n".$row2[1].";\n\n";

		for ($i = 0; $i < $num_fields; $i++)
		{
			while($row = mysqli_fetch_row($result))
			{
				$return.= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j<$num_fields; $j++)
				{
					$row[$j] = addslashes($row[$j]);
					$row[$j] = preg_replace("/\n/","/\\n/", $row[$j]);
					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
					if ($j<($num_fields-1)) { $return.= ','; }
				}
				$return.= ");\n";
			}
		}
		$return.="\n\n\n";
	}

	//save file
	header('Pragma: cache');
	header('Content-Type: text/comma-separated-values; charset=UTF-8');
	header('Content-Disposition: attachment; filename="'.$_SERVER['HTTP_HOST'].'.sql"');
	die($return);
}

backup_tables();
?>