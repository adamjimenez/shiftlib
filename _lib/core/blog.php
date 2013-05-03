<?
class blog{
    function blog($options){
		global $cms,$sections,$vars,$from_email;

		$this->blog_index = isset($options['blog_index']) ? $options['blog_index'] : array_search('blog',$sections);

		//categories
		if( count($vars["fields"]["categories"]) ){
			$this->categories=sql_query("SELECT * FROM categories
				ORDER BY category
			");
		}

		//recent posts

		//recent comments
		/*
		$recent_comments=sql_query("SELECT *,C.id FROM comments C
			INNER JOIN blog B
			ON C.blog=B.id
			ORDER BY C.date DESC LIMIT 5
		");
		*/

		//archive
		$this->months=sql_query("SELECT date,date_format(date, '%m %Y') AS `month` FROM blog
			WHERE
				display=1 AND
				date <= NOW()
			GROUP BY month
			ORDER BY date DESC
		");

		//tags
		/*
		$tags=sql_query("SELECT * FROM tags ORDER BY count DESC");

		foreach( $tags as $v ){
			$tag_total+=$v['count'];
		}

		foreach( $tags as $k=>$v ){
			$tags[$k]['size']=floor( ($v['count']/$tag_total)*30 ) +10;
		}

		$tags=subval_sort($tags,'tag');
		*/

		$limit=NULL;

		//archive
		if( is_numeric($sections[($this->blog_index+1)]) and is_numeric($sections[($this->blog_index+2)]) ){
			$date = $sections[($this->blog_index+1)].'/'.$sections[($this->blog_index+2)].'/01';

			$conditions['func']['date'] = 'month';
			$conditions['date'] = dateformat('mY',$date);
		}elseif( is_numeric($sections[($this->blog_index+1)]) ){
			$conditions['id'] = $sections[($this->blog_index+1)];

			$this->article=true;
		}elseif( $sections[($this->blog_index+1)]=='category' ){
            $conditions['func']['date'] = '<';
    		$conditions['date'] = date('d/m/Y', strtotime('tomorrow'));

			$category=sql_query("SELECT * FROM categories WHERE page_name='".escape($sections[($this->blog_index+2)])."'",1);

			if($category['id']){
				$conditions['category'][]=$category['id'];
			}
		}elseif( $sections[($this->blog_index+1)]=='tags' ){
			$conditions['tags']='*'.$sections[($this->blog_index+2)].'*';
		}elseif( $sections[($this->blog_index+1)] and $sections[($this->blog_index+1)]!=='index' ){
			$conditions['page_name']=$sections[($this->blog_index+1)];

			$this->article=true;
		}else{
        	$conditions['func']['date'] = '<';
    		$conditions['date'] = date('d/m/Y', strtotime('tomorrow'));

			$limit=6;
		}

		$conditions['display'] = 1;

		//print_r($conditions);
		$this->content = $cms->get('blog', $conditions, $limit, 'date', false);

		if( $article ){
			$title = $this->content['heading'];
		}

		if( !count($this->content) ){
			//die('no item');
		}

		if( $_POST['continue'] and $_POST['nospam']==1 ){
			if( !$_POST['name'] ){
				$errors[]='name';
			}
			if( !$_POST['email'] ){
				$errors[]='email';
			}
			if( !$_POST['comment'] or strip_tags($_POST['comment'])!==$_POST['comment'] ){
				$errors[]='comment';
			}
			if( !$_POST['blog'] ){
				$errors[]='blog';
			}

			if( count( $errors ) ){
				print json_encode($errors);
				exit;
			}elseif( $_POST['validate'] ){
				print 1;
				exit;
			}else{
				setcookie('name',$_POST['name'],time()+86400*30,'/');
				setcookie('email',$_POST['email'],time()+86400*30,'/');
				setcookie('website',$_POST['website'],time()+86400*30,'/');

				mysql_query("INSERT INTO comments SET
					date=NOW(),
					name='".escape(strip_tags($_POST['name']))."',
					email='".escape(strip_tags($_POST['email']))."',
					website='".escape(strip_tags($_POST['website']))."',
					comment='".escape(strip_tags($_POST['comment']))."',
					ip='".escape($_SERVER['REMOTE_ADDR'])."',
					blog='".escape($_POST['blog'])."'
				") or trigger_error("SQL", E_USER_ERROR);

				$id=mysql_insert_id();

				$headers="From: ".'auto@'.$_SERVER['HTTP_HOST']."\n";
				$msg='New Comment
				==========
				Name: '.$_POST['name'].'
				Email: '.$_POST['email'].'
				Website: '.$_POST['website'].'
				Comment: '.$_POST['comment'].'
				Link: http://'.$_SERVER['HTTP_HOST'].'/blog/'.$sections[($this->blog_index+1)].'/'.$sections[($this->blog_index+2)].'/#comment-'.$id;

				$msg=str_replace("\t",'',$msg);

				mail($from_email,'New comment',$msg,$headers);

				if( $options['thanks_page'] ){
					redirect($options['thanks_page']);
				}
			}
		}

		if( $_POST['approve_all'] and $auth->user ){
			switch($_POST['approve_all']){
				case 'approve':
					mysql_query("UPDATE comments
						SET
							approved=1
						WHERE
							blog='".escape($content[0]['id'])."' AND
							approved!=1
					") or trigger_error("SQL", E_USER_ERROR);
				break;

				case 'delete':
					mysql_query("DELETE FROM comments
						WHERE
							blog='".escape($content[0]['id'])."' AND
							approved!=1
					") or trigger_error("SQL", E_USER_ERROR);
				break;

				case 'delete and block':
					$comments=sql_query("SELECT * FROM comments
						WHERE
							blog='".escape($content[0]['id'])."' AND
							approved!=1
					");

					foreach( $comments as $comment ){
						mysql_query("DELETE FROM comments
							WHERE
								id='".escape($comment['id'])."' OR
								ip='".escape($comment['ip'])."'
						") or trigger_error("SQL", E_USER_ERROR);
					}
				break;
			}
		}

		if( $_POST['approve'] and $auth->user ){
			foreach( $_POST['approve'] as $k=>$v ){
				$comment=sql_query("SELECT * FROM comments
					WHERE
						id='".escape($k)."'
				");

				switch($v){
					case 'approve':
						mysql_query("UPDATE comments
							SET
								approved=1
							WHERE
								id='".escape($k)."'
						") or trigger_error("SQL", E_USER_ERROR);
					break;

					case 'delete':
						mysql_query("DELETE FROM comments
							WHERE
								id='".escape($k)."'
						") or trigger_error("SQL", E_USER_ERROR);
					break;

					case 'delete and block':
						mysql_query("DELETE FROM comments
							WHERE
								id='".escape($k)."' OR
								ip='".escape($comment[0]['ip'])."'
						") or trigger_error("SQL", E_USER_ERROR);
					break;
				}
			}
		}

		$opts['approve']=array('approve','delete','delete and block');
	}

	function subval_sort($a,$subkey)
	{
		foreach($a as $k=>$v) {
			$b[$k] = strtolower($v[$subkey]);
		}
		asort($b);
		foreach($b as $key=>$val) {
			$c[] = $a[$key];
		}
		return $c;
	}

	function recent_posts(){
		return sql_query("SELECT * FROM blog
			WHERE
				display=1 AND
				date <= NOW()
			ORDER BY date DESC
			LIMIT 5
		");
	}
}
?>