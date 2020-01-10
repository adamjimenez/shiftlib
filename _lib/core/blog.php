<?php
class blog
{
    public $blog_index;

    public $table_blog;

    public $table_categories;

    /**
     * @var mixed|int|string|bool
     */
    public $category_field;

    /**
     * @var mixed
     */
    public $conditions;

    /**
     * @var mixed
     */
    public $categories;

    /**
     * @var mixed
     */
    public $months;

    public $tags;

    /**
     * @var bool|mixed
     */
    public $article;

    public $content;

    public $p;

    public $commented;

    public function blog($options = null)
    {
        global $cms, $sections, $vars, $from_email, $opts, $request;

        $this->blog_index = isset($options['blog_index']) ? $options['blog_index'] : array_search('blog', $sections);

        $this->table_blog = $options['table_blog'] ?: 'blog';
        $this->table_categories = $options['table_categories'] ?: 'blog_categories';

        $this->category_field = array_search($this->table_categories, $opts);
        
        $this->conditions = [];

        //categories
        if (count($vars['fields'][$this->table_categories])) {
            $this->categories = sql_query('SELECT * FROM ' . underscored($this->table_categories) . '
				ORDER BY category
			');
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
        $this->months = sql_query("SELECT date, date_format(date, '%m %Y') AS `month` FROM " . $this->table_blog . '
			WHERE
				display=1 AND
				date <= NOW()
			GROUP BY month
			ORDER BY date DESC
		');

        //tags
        if (table_exists('blog_tags')) {
            $tags = sql_query('SELECT * FROM blog_tags ORDER BY count DESC');

            foreach ($tags as $v) {
                $tag_total += $v['count'];
            }

            foreach ($tags as $k => $v) {
                $tags[$k]['size'] = floor(($v['count'] / $tag_total) * 30) + 10;
            }

            $this->tags = $this->subval_sort($tags, 'size', false);
        }

        $limit = null;

        if (in_array($this->table_blog, $sections) and $sections[$this->blog_index] == $this->table_blog) {
            //archive
            if (is_numeric($sections[($this->blog_index + 1)]) and is_numeric($sections[($this->blog_index + 2)])) {
                $date = $sections[($this->blog_index + 1)] . '/' . $sections[($this->blog_index + 2)] . '/01';

                $this->conditions['func']['date'] = 'month';
                $this->conditions['date'] = $date;
            } elseif (is_numeric($sections[($this->blog_index + 1)])) {
                $this->conditions['id'] = $sections[($this->blog_index + 1)];
                $this->article = true;
            } elseif ('category' == $sections[($this->blog_index + 1)]) {
                $this->conditions['func']['date'] = '<';
                $this->conditions['date'] = date('Y-m-d', strtotime('tomorrow'));

                $category = sql_query('SELECT * FROM ' . underscored($this->table_categories) . " WHERE
				    page_name='" . escape($sections[($this->blog_index + 2)]) . "'
				", 1);

                if ($category['id']) {
                    $this->conditions[underscored($this->category_field)][] = $category['id'];
                }
            } elseif ('tags' == $sections[($this->blog_index + 1)]) {
                $this->conditions['tags'] = '*' . $sections[($this->blog_index + 2)] . '*';
            } elseif ($sections[($this->blog_index + 1)] and 'index' !== $sections[($this->blog_index + 1)]) {
                $this->conditions['page_name'] = $sections[($this->blog_index + 1)];

                $this->article = true;
            } else {
                $this->conditions['func']['date'] = '<';
                $this->conditions['date'] = date('d/m/Y', strtotime('tomorrow'));
                
                if ($_GET['s']) {
                    $this->conditions['s'] = $_GET['s'];
                }
                if ($_GET['w']) {
                    $this->conditions['w'] = $_GET['w'];
                }

                $limit = 6;
            }

            $this->conditions['display'] = 1;

            //print_r($this->conditions);exit;
            $this->content = $cms->get($this->table_blog, $this->conditions, $limit, 'date', false);
            $this->p = $cms->p;

            if ($this->article) {
                $title = $this->content['heading'];

                if (!$this->content) {
                    trigger_404();
                }
            }
        }

        if ($_POST['save_blog_comment'] and 1 == $_POST['nospam']) {
            if (!$_POST['name']) {
                $errors[] = 'name';
            }
            if (!$_POST['email']) {
                $errors[] = 'email';
            }
            if (
                !$_POST['comment'] or
                strip_tags($_POST['comment']) !== $_POST['comment'] or
                strstr($_POST['comment'], '[/url]')
            ) {
                $errors[] = 'comment';
            }
            if (!$_POST['blog']) {
                $errors[] = 'blog';
            }
            
            global $recaptcha_secret;
            
            if ($recaptcha_secret) {
                if (!$_POST['g-recaptcha-response']) {
                    $errors[] = 'captcha';
                } else {
                    $data = [
                        'secret' => $recaptcha_secret,
                        'response' => $_POST['g-recaptcha-response'],
                    ];
                    
                    $verify = curl_init();
                    curl_setopt($verify, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
                    curl_setopt($verify, CURLOPT_POST, true);
                    curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($verify);
                    
                    if (!$response['success']) {
                        $errors[] = 'captcha';
                    }
                }
            }

            if (count($errors)) {
                print json_encode($errors);
                exit;
            } elseif ($_POST['validate']) {
                print 1;
                exit;
            }
            setcookie('name', $_POST['name'], time() + 86400 * 30, '/');
            setcookie('email', $_POST['email'], time() + 86400 * 30, '/');
            setcookie('website', $_POST['website'], time() + 86400 * 30, '/');

            sql_query("INSERT INTO comments SET
					date=NOW(),
					name='" . escape(strip_tags($_POST['name'])) . "',
					email='" . escape(strip_tags($_POST['email'])) . "',
					website='" . escape(strip_tags($_POST['website'])) . "',
					comment='" . escape(strip_tags($_POST['comment'])) . "',
					ip='" . escape($_SERVER['REMOTE_ADDR']) . "',
					blog='" . escape($_POST['blog']) . "'
				");

            $id = sql_insert_id();

            $headers = 'From: ' . 'auto@' . $_SERVER['HTTP_HOST'] . "\n";
            $msg = 'New Comment
				==========
				Name: ' . $_POST['name'] . '
				Email: ' . $_POST['email'] . '
				Website: ' . $_POST['website'] . '
				Comment: ' . $_POST['comment'] . '
				Link: http://' . $_SERVER['HTTP_HOST'] . '/' . $request . '/#comment-' . $id;

            $msg = str_replace("\t", '', $msg);

            mail($from_email, 'New comment', $msg, $headers);

            if ($options['thanks_page']) {
                redirect($options['thanks_page']);
            } else {
                $this->commented = true;
            }
        }

        if ($_POST['approve_all'] and $auth->user) {
            switch ($_POST['approve_all']) {
                case 'approve':
                    sql_query("UPDATE comments
						SET
							approved=1
						WHERE
							blog = '" . escape($content[0]['id']) . "' AND
							approved!=1
					");
                break;

                case 'delete':
                    sql_query("DELETE FROM comments
						WHERE
							blog = '" . escape($content[0]['id']) . "' AND
							approved!=1
					");
                break;

                case 'delete and block':
                    $comments = sql_query("SELECT * FROM comments
						WHERE
							blog = '" . escape($content[0]['id']) . "' AND
							approved!=1
					");

                    foreach ($comments as $comment) {
                        sql_query("DELETE FROM comments
							WHERE
								id = '" . escape($comment['id']) . "' OR
								ip = '" . escape($comment['ip']) . "'
						");
                    }
                break;
            }
        }

        if ($_POST['approve'] and $auth->user) {
            foreach ($_POST['approve'] as $k => $v) {
                $comment = sql_query("SELECT * FROM comments
					WHERE
						id='" . escape($k) . "'
				");

                switch ($v) {
                    case 'approve':
                        sql_query("UPDATE comments
							SET
								approved=1
							WHERE
								id='" . escape($k) . "'
						");
                    break;

                    case 'delete':
                        sql_query("DELETE FROM comments
							WHERE
								id='" . escape($k) . "'
						");
                    break;

                    case 'delete and block':
                        sql_query("DELETE FROM comments
							WHERE
								id='" . escape($k) . "' OR
								ip='" . escape($comment[0]['ip']) . "'
						");
                    break;
                }
            }
        }

        $opts['approve'] = ['approve','delete','delete and block'];
    }

    /**
     * @return mixed[]
     */
    public function subval_sort($a, $subkey, $asc = true)
    {
        foreach ($a as $k => $v) {
            $b[$k] = strtolower($v[$subkey]);
        }
        if ($asc) {
            asort($b);
        } else {
            arsort($b);
        }
        foreach ($b as $key => $val) {
            $c[] = $a[$key];
        }
        return $c;
    }

    public function recent_posts($limit = 5)
    {
        return sql_query('SELECT * FROM ' . $this->table_blog . '
			WHERE
				display=1 AND
				date <= NOW()
			ORDER BY date DESC
			LIMIT ' . escape($limit) . '
		');
    }

    public function rss_feed()
    {
        $news = sql_query('SELECT
        		id,
        		page_name,
        		heading,
        		copy,
        		date
        	FROM ' . $this->table_blog . '
        	WHERE
        		display=1
        	ORDER BY date DESC
        ');

        header('Content-Type: text/xml');

        $xml = new SimpleXMLElement('<feed xmlns="http://www.w3.org/2005/Atom" />');

        $xml->addChild('title', $_SERVER['HTTP_HOST']);

        $link = $xml->addChild('link');
        $link->addAttribute('href', 'http://' . $_SERVER['HTTP_HOST'] . '/');

        foreach ($news as $k => $v) {
            $entry = $xml->addChild('entry');

            $entry->addChild('title', htmlspecialchars($v['heading']));
            $summary = $entry->addChild('summary', htmlspecialchars($v['copy']));
            $summary->addAttribute('type', 'html');

            $link = $entry->addChild('link');
            $url = 'https://' . $_SERVER['HTTP_HOST'] . '/';
            if ($this->blog_index > 0) {
                $url .= 'blog/';
            }
            $url .= $v['page_name'];
            $link->addAttribute('href', $url);
        }

        echo $xml->asXML();
        exit;
    }
}

function blog_save_handler()
{
    global $vars;

    if (!$vars['fields']['blog']['tags']) {
        return;
    }

    $table_tags = 'blog_tags';

    $fields = [
        'tag' => 'text',
        'count' => 'int',
        'id' => 'id',
    ];
    check_table($table_tags, $fields);

    //update tag cloud
    $blogs = sql_query('SELECT tags FROM ' . $this->table_blog . ' WHERE display=1');

    foreach ($blogs as $blog) {
        $content .= $blog['tags'] . ',';
    }
    $content = strtolower($content);

    $words = explode(',', $content);

    $stop_words = [
        'a',
        'and',
        'are',
        'as',
        'at',
        'be',
        'for',
        'has',
        'i',
        'in',
        'is',
        'it',
        'not',
        'of',
        'off',
        'on',
        'only',
        'so',
        'the',
        'that',
        'their',
        'there',
        'this',
        'to',
        'with',
        'you',
    ];

    foreach ($words as $word) {
        $word = preg_replace("/[^A-Za-z0-9'\-\s]/", '', $word);
        $word = trim($word);
        if (!$word or 1 == strlen($word) or in_array($word, $stop_words) or is_numeric($word)) {
            continue;
        }

        $tags[$word]++;
    }

    sql_query("DELETE FROM $table_tags");

    foreach ($tags as $tag => $count) {
        sql_query("INSERT INTO $table_tags SET
			tag='" . escape($tag) . "',
			count='" . $count . "'
		");
    }

    // email subscribers
    $blog = sql_query('SELECT * FROM ' . $this->table_blog . " WHERE id='" . escape($_GET['id']) . "'");

    if ($_POST['display'] and !$blog[0]['display'] and $vars['fields']['newsletter']) {
        $users = sql_query('SELECT * FROM newsletter');

        $valid_users = [];
        foreach ($users as $user) {
            if (is_email($user['email'])) {
                $valid_users[] = $user;
            }
        }

        foreach ($valid_users as $user) {
            $reps = [];
            $reps['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/blog/' . str_to_pagename($_POST['page_name']);
            $reps['unsubscribe_link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/newsletter-unsubscribe?email=' . $user['email'];

            email_template($user['email'], 'New Blog Entry', $reps);
        }
    }
}

global $cms_handlers;
$cms_handlers[] = [
    'section' => 'blog',
    'event' => 'beforeSave',
    'handler' => 'blog_save_handler',
];
