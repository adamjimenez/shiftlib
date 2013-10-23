<?php
require('../../base.php');

$result = array();

if( $_POST ){
    if( $auth->user ){
        if( $_POST["section"] and $_POST["field"] and $_POST["item"] and $_POST["value"] ){
            if( !table_exists('cms_ratings') ){
                mysql_query("CREATE TABLE IF NOT EXISTS `cms_ratings` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  `user` int(11) NOT NULL,
                  `section` varchar(255) NOT NULL,
                  `field` varchar(255) NOT NULL,
                  `item` int(11) NOT NULL,
                  `value` tinyint(4) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`),
                  UNIQUE KEY `user_section_field_item` (`user`,`section`,`field`,`item`)
                )") or trigger_error("SQL", E_USER_ERROR);
            }

            mysql_query("INSERT INTO cms_ratings SET
                user = '".$auth->user['id']."',
                section = '".escape($_POST["section"])."',
                field = '".escape($_POST["field"])."',
                item = '".escape($_POST["item"])."',
                value = '".escape($_POST["value"])."'
                ON DUPLICATE KEY UPDATE value = '".escape($_POST["value"])."'
            ") or trigger_error("SQL", E_USER_ERROR);

            //get average
            $row = sql_query("SELECT AVG(value) AS `value` FROM cms_ratings
                WHERE
                    section = '".escape($_POST["section"])."' AND
                    field = '".escape($_POST["field"])."' AND
                    item = '".escape($_POST["item"])."'
            ", 1);
            $value = round($row['value']);

            //update average
            mysql_query("UPDATE ".underscored(escape($_POST["section"]))." SET
                `".underscored(escape($_POST["field"]))."` = '".$value."'
                WHERE
                    id = '".escape($_POST["item"])."'
                LIMIT 1
            ") or trigger_error("SQL", E_USER_ERROR);
        }

        $result['success'] = true;
    }else{
        $result['success'] = false;
    }
}

print json_encode($result);
?>