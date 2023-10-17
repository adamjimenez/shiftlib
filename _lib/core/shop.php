<?php

class shop
{
    public $basket;
    public $cust;
    public $delivery;
    public $disable_confirmation_email;

    /**
     * @var int
     */
    public $discount;
    public $from_email;

    /**
     * @var array
     */
    public $guest;

    public $headers;
    public $include_vat;
    public $item_count;

    /**
     * @var int|null
     */
    public $oid;

    public $paypal_email;
    public $promo;
    public $redirect_on_add;
    public $stock_control;
    public $subtotal;
    public $test;
    public $total;

    /**
     * @var float
     */
    public $vat_rate;
    public $vat;
    public $cc;
    
    public $variations_table = 'variations';


    public function __construct()
    {
        global $shop_config, $auth, $from_email, $cms;

        $this->vat_rate = 0.2;

        $this->from_email = $from_email ?: 'auto@' . $_SERVER['HTTP_HOST'];
        $this->headers = 'From: ' . $this->from_email . "\n";

        $this->discount = 0;

        foreach ($shop_config as $k => $v) {
            $this->$k = $v;
        }

        $basket_fields = [
            'session' => 'text',
            'user' => 'int',
            'product' => 'int',
            'variation' => 'int',
            'extras' => 'textarea',
            'quantity' => 'int',
        ];
        //$cms->check_table('basket', $basket_fields);

        $orders_fields = [
            'date' => 'datetime',
            'customer' => 'int',
            'name' => 'text',
            'address' => 'textarea',
            'postcode' => 'text',
            'email' => 'text',
            'tel' => 'text',
            'mobile' => 'text',
            'subtotal' => 'decimal',
            'offer' => 'text',
            'discount' => 'decimal',
            'vat' => 'decimal',
            'delivery' => 'decimal',
            'total' => 'decimal',
            'status' => 'text',
            'txn_id' => 'text',
            'method' => 'text',
            'dispatched_date' => 'datetime',
            'refund_date' => 'datetime',
        ];
        //$cms->check_table('orders', $orders_fields);

        $order_items_fields = [
            'order' => 'int',
            'product' => 'int',
            'variation' => 'int',
            'name' => 'text',
            'extras' => 'textarea',
            'cost' => 'decimal',
            'quantity' => 'int',
        ];
        //$cms->check_table('order_items', $order_items_fields);

        if ($_SESSION['guest']) {
            $this->guest = $_SESSION['guest'];
        }

        if ($_POST['update_guest']) {
            $this->guest['email'] = $_POST['email'];
            $this->guest['name'] = $_POST['name'];
            $this->guest['tel'] = $_POST['tel'];
            $this->guest['mobile'] = $_POST['mobile'];
            $this->guest['surname'] = $_POST['surname'];
            $this->guest['address'] = $_POST['address'];
            $this->guest['address2'] = $_POST['address2'];
            $this->guest['city'] = $_POST['city'];
            $this->guest['county'] = $_POST['county'];
            $this->guest['postcode'] = format_postcode($_POST['postcode']);
            $this->guest['comments'] = $_POST['comments'];

            $_SESSION['guest'] = $this->guest;
        }

        if ($this->guest) {
            $this->cust = $this->guest;
        } elseif ($auth->user) {
            $this->cust = $auth->user;
        } else {
            $this->cust = [];
        }

        if ($_GET['add_to_basket']) {
            $_POST['add_to_basket'] = $_GET['add_to_basket'];
        }

        if ($_GET['buy_now']) {
            $_POST['buy_now'] = $_GET['buy_now'];
        }

        if (is_numeric($_POST['add_to_basket'])) {
            $quantity = is_numeric($_POST['quantity']) ? $_POST['quantity'] : 1;

            $this->add_to_basket((int)$_POST['add_to_basket'], (int)$quantity, $_POST['buy_now'], $_POST['variation'], $_POST['extras']);
        }

        if (is_array($_POST['add_to_basket'])) {
            if ($_POST['buy_now']) {
                sql_query("DELETE FROM basket WHERE session='" . session_id() . "' OR user='" . $auth->user['id'] . "'");
            }

            foreach ($_POST['add_to_basket'] as $k => $v) {
                $this->add_to_basket((int)$k, (int)$v, null, $_POST['variation'][$k], $_POST['extras'][$k]);
            }

            if ($_POST['buy_now']) {
                redirect('/checkout');
            }
        }

        global $request;
        if ($request !== 'admin' && $_POST['code'] && table_exists('promo_codes')) {
            //lookup discount
            $select_code = sql_query("SELECT * FROM promo_codes
				WHERE
					code='" . escape($_POST['code']) . "' AND
					(
						expiry>CURDATE() OR
						expiry='0000-00-00'
					)
			");

            $errors = [];
            if (!$select_code) {
                $errors[] = 'code Invalid promo code';
            }
			
            //handle validation
            if (count($errors)) {
                //validateion failed
                die(json_encode($errors));
            } elseif ($_POST['validate']) {
                //validation passed
                die('1');
            }
            
            $_SESSION['code'] = $_POST['code'];
        }

        if ($_POST['update_basket']) {
            $this->update_basket();
        }

        $this->delivery = 0;
        $this->get_basket();

        if ($_SESSION['code'] && table_exists('promo_codes')) {
            //lookup discount
            $promo = sql_query("SELECT * FROM promo_codes WHERE code='" . escape($_SESSION['code']) . "'", 1);

            if ($promo && $promo['discount']) {
                $this->promo = $promo;

                if (!$promo['min_spend'] || $promo['min_spend'] < $this->subtotal) {
                    $this->set_discount($promo['discount']);
                }
            }
        }
        $this->update_total();
    }

    public function remove(int $product)
    {
        global $auth;

        sql_query("DELETE FROM basket
			WHERE
				(session='" . session_id() . "' OR user='" . $auth->user['id'] . "') AND
				id='" . escape($product) . "'
		");

        $this->get_basket();
    }

    public function get_basket()
    {
        global $auth;

        $where_str = '';
        if ($auth->user) {
            $where_str = " OR B.user='" . $auth->user['id'] . "'";
        }
        $items = sql_query("SELECT *, B.* FROM basket B
			LEFT JOIN products P ON B.product=P.id
			WHERE
				(
					B.session='" . session_id() . "'
					$where_str
				)
			ORDER BY B.id
		");

        $this->basket = [];
        $this->item_count = 0;
        $this->subtotal = 0;
        
        $i = 0;
        foreach ($items as $k => $v) {
            if ($v['deleted'] === '1') {
                continue;
            }
            
            $this->basket[$i] = $v;
            
            $variation = [];

            if ($v['variation']) {
                $variation = sql_query("SELECT * FROM " . $this->variations_table . " WHERE id='" . escape($v['variation']) . "'", 1);
            }

            if ($variation['cost'] > 0) {
                $this->basket[$i]['cost'] = $variation['cost'];
            }

            if (table_exists('extras')) {
                $lines = explode("\n", $v['extras']);

                foreach ($lines as $line) {
                    $arr = explode(': ', $line);

                    $row = sql_query("SELECT * FROM extras WHERE
						name='" . escape($arr[0]) . "' AND
						value='" . escape($arr[1]) . "' AND
						product='" . $v['product'] . "'
					", 1);

                    if ($row) {
                        $this->basket[$i]['cost'] += $row['cost'];
                    }
                }
            }

            if ($v['options']) {
                $values = json_decode($v['options']);
                foreach ($values as $value) {
                    if (!is_string($value)) {
                        continue;
                    }
                    
                    $product_value = sql_query("SELECT * FROM product_values WHERE id = '" . escape($value) . "'", 1);
                    $product_option = sql_query("SELECT * FROM product_options WHERE id = '" . escape($product_value['product_option']) . "'", 1);
                    $this->basket[$i]['cost'] += $product_value['cost'];

                    if ($this->basket[$i]['extras']) {
                        $this->basket[$i]['extras'] .= "\n";
                    }

                    $this->basket[$i]['extras'] .= $product_option['name'] . ': ' . $product_value['value'];
                }
            }

            $this->subtotal += $v['quantity'] * $this->basket[$i]['cost'];

            $this->item_count += $v['quantity'];

            if ($v['delivery']) {
                $this->delivery += $v['delivery'];
            }
            
            $i++;
        }

        $this->vat = $this->subtotal * $this->vat_rate;
    }

    public function add_to_basket(int $product, int $quantity = 1, $buy_now = false, $variation_id = null, $extras_arr = '')
    {
        global $auth;

        if (0 == $quantity) {
            return false;
        }

        if (true === $buy_now) {
            sql_query("DELETE FROM basket WHERE session='" . session_id() . "' OR user='" . $auth->user['id'] . "'");
        }

        $extras = '';
        if (is_array($extras_arr)) {
            foreach ($extras_arr as $k => $v) {
                $extras .= spaced($k) . ': ' . $v . "\n";
            }
        } else {
            $extras = $extras_arr;
        }

        if ($variation_id) {
            $variation = sql_query("SELECT * FROM " . $this->variations_table . " WHERE
				id = '" . escape($variation_id) . "'
			", 1);

            if (!$extras) {
                if ($variation) {
                    foreach ($variation as $k => $v) {
                        if ('id' == $k or 'product' == $k or 'quantity' == $k or 'image' == $k or 'cost' == $k or 'position' == $k or '' == $v) {
                            continue;
                        }
                        $extras .= ucfirst(spaced($k)) . ': ' . $v . "\n";
                    }
                }
            }

            if ($this->stock_control) {
                if (0 == $variation['quantity']) {
                    $_SESSION['error'] = 'item is out of stock';
                    return false;
                } elseif ($quantity > $variation['quantity']) {
                    $_SESSION['error'] = 'item has limited stock';
                    $quantity = $variation['quantity'];
                }
            }
        }

        $options = '';
        if (count((array)$_POST['options'])) {
            $options = json_encode($_POST['options']);
        }

        $where_str = '';
        if ($auth->user) {
            $where_str = " OR user='" . $auth->user['id'] . "'";
        }
        $row = sql_query("SELECT * FROM basket WHERE
			(
				session='" . session_id() . "'
				$where_str
			) AND
			product='" . escape($product) . "' AND
			extras='" . escape(trim($extras)) . "' AND
			variation='" . escape($variation_id) . "' AND
			options='" . escape($options) . "'
		", 1);

        if ($row) {
            $quantity += $row['quantity'];

            if ($this->stock_control) {
                if ($quantity > $variation['quantity']) {
                    $_SESSION['error'] = 'item has limited stock';
                    $quantity = $variation['quantity'];
                }
            }

            sql_query("UPDATE basket SET
				user='" . $auth->user['id'] . "',
				quantity='" . $quantity . "'
			WHERE
				session='" . session_id() . "' AND
				product='" . escape($product) . "' AND
				extras='" . escape(trim($extras)) . "' AND
				variation='" . escape($variation_id) . "' AND
				options='" . escape($options) . "'
			");
        } else {
            sql_query("INSERT INTO basket SET
				user='" . $auth->user['id'] . "',
				quantity='" . escape($quantity) . "',
				session='" . session_id() . "',
				product='" . escape($product) . "',
				extras='" . escape(trim($extras)) . "',
				variation='" . escape($variation_id) . "',
				options='" . escape($options) . "'
			");
        }

        if ($buy_now) {
            redirect('/checkout');
        } elseif ($this->redirect_on_add) {
            redirect('/basket');
        }
    }

    public function update_basket()
    {
        if ($_POST['quantity']) {
            foreach ($_POST['quantity'] as $k => $v) {
                if (0 == $v) {
                    sql_query("DELETE FROM basket
						WHERE id='" . escape($k) . "'
					");
                } elseif ($v > 0) {
                    sql_query("UPDATE basket SET
						quantity='" . escape($v) . "'
						WHERE id='" . escape($k) . "'
					");
                }
            }
        }

        if ($_POST['remove']) {
            foreach ($_POST['remove'] as $k => $v) {
                if ($v) {
                    sql_query("DELETE FROM basket
						WHERE id='" . escape($k) . "'
					");
                }
            }
        }
    }

    public function empty_basket()
    {
        global $auth;

        sql_query("DELETE FROM basket
		    WHERE
		        session='" . session_id() . "' 
		");

        if ($auth->user['id']) {
            sql_query("DELETE FROM basket
				WHERE
					user='" . $auth->user['id'] . "'
			");
        }
    }

    public function set_delivery($price)
    {
        $this->delivery = $price;

        $this->update_total();
    }

    public function set_discount($discount)
    {
        $discount = preg_replace("/[^0-9%]\./", "", $discount);
        if ('%' == substr($discount, -1)) {
            $this->discount = ($discount / 100) * ($this->subtotal);
        } else {
            $this->discount = $discount;
        }

        $this->update_total();
    }

    public function update_total()
    {
        $this->subtotal = 0;
        $this->item_count = 0;

        foreach ($this->basket as $k => $v) {
            $this->subtotal += $v['quantity'] * $this->basket[$k]['cost'];
            $this->item_count += $v['quantity'];
        }

        $this->vat = $this->subtotal * $this->vat_rate;

        $this->total = $this->subtotal - (float)$this->discount;

        if ($this->total < 0) {
            $this->total = 0;
        }

        if ($this->include_vat) {
            $this->total += ($this->total * $this->vat_rate);
        }

        $this->total += $this->delivery;
    }

    public function prepare()
    {
        $this->update_total();

        if (!$this->total) {
            return false;
        }

        sql_query("INSERT INTO orders SET
			date=NOW(),
			customer='" . escape($this->cust['id']) . "',
			name='" . escape(($this->cust['name'] . ' ' . $this->cust['surname'])) . "',
			address='" . escape($this->cust['address']) . "\n" . escape($this->cust['address2']) . "\n" . escape($this->cust['city']) . "\n" . escape($this->cust['county']) . "',
			postcode='" . escape($this->cust['postcode']) . "',
			email='" . escape($this->cust['email']) . "',
			tel='" . escape($this->cust['tel']) . "',
			mobile='" . escape($this->cust['mobile']) . "',
			subtotal='" . escape($this->subtotal) . "',
			offer='" . escape($this->promo['name']) . "',
			discount='" . escape($this->discount) . "',
			delivery='" . escape($this->delivery) . "',
			total='" . escape($this->total) . "',
			status='pending'
		");

        $this->oid = sql_insert_id();

        if ($this->cust['comments']) {
            sql_query("UPDATE orders SET
		        comments = '" . escape($this->cust['comments']) . "'
		        WHERE
		            id = '" . $this->oid . "'
		    ");
        }

        if ($this->include_vat) {
            sql_query("UPDATE orders SET
				vat='" . $this->vat . "'
				WHERE
					id='" . $this->oid . "'
				LIMIT 1
			");
        }

        //order items
        foreach ($this->basket as $item) {
            sql_query("INSERT IGNORE INTO order_items SET
				`order`='" . escape($this->oid) . "',
				product='" . escape($item['product']) . "',
				variation='" . escape($item['variation']) . "',
				name='" . escape($item['name'] ?: $item['title']) . "',
				extras='" . escape($item['extras']) . "',
				cost='" . escape($item['cost']) . "',
				quantity='" . escape($item['quantity']) . "'
			");
        }

        return $this->oid;
    }

    public function paypal_button(string $value = 'Checkout with PayPal')
    {
        if ($this->test) {
            print '
				<form method="post" action="/paypal">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="' . $this->paypal_email . '">
				<input type="hidden" name="item_name" value="Basket contents">
				<input type="hidden" name="item_number" value="' . $this->oid . '">
				<input type="hidden" name="amount" value="' . number_format($this->total, 2) . '">
				<input type="hidden" name="no_shipping" value="0">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="currency_code" value="GBP">
				<input type="hidden" name="charset" value="UTF-8">

				<input type="hidden" name="notify_url" value="http://' . $_SERVER['HTTP_HOST'] . '/paypal">
				<input type="hidden" name="return" value="http://' . $_SERVER['HTTP_HOST'] . '">
				<input type="hidden" name="rm" value="1">

				<input type="hidden" name="email" value="' . $this->cust['email'] . '">
				<input type="hidden" name="first_name" value="' . $this->cust['name'] . '">
				<input type="hidden" name="last_name" value="' . $this->cust['surname'] . '">
				<input type="hidden" name="address1" value="' . $this->cust['address'] . '">
				<input type="hidden" name="address2" value="">
				<input type="hidden" name="city" value="' . $this->cust['city'] . '">
				<input type="hidden" name="state" value="">
				<input type="hidden" name="country" value="">
				<input type="hidden" name="zip" value="' . $this->cust['postcode'] . '">

				<button type="submit">Pay using paypal TEST MODE</button>
				</form>';
        } else {
            print '
				<form method="get" action="https://www.paypal.com/cgi-bin/webscr">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="' . $this->paypal_email . '">
				<input type="hidden" name="item_name" value="Basket contents">
				<input type="hidden" name="item_number" value="' . $this->oid . '">
				<input type="hidden" name="amount" value="' . number_format($this->total, 2) . '">
				<input type="hidden" name="no_shipping" value="0">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="currency_code" value="GBP">
				<input type="hidden" name="charset" value="UTF-8">


				<input type="hidden" name="notify_url" value="http://' . $_SERVER['HTTP_HOST'] . '/paypal">
				<input type="hidden" name="return" value="http://' . $_SERVER['HTTP_HOST'] . '">
				<input type="hidden" name="rm" value="1">

				<input type="hidden" name="email" value="' . $this->cust['email'] . '">
				<input type="hidden" name="first_name" value="' . $this->cust['name'] . '">
				<input type="hidden" name="last_name" value="' . $this->cust['surname'] . '">
				<input type="hidden" name="address1" value="' . $this->cust['address'] . '">
				<input type="hidden" name="address2" value="">
				<input type="hidden" name="city" value="' . $this->cust['city'] . '">
				<input type="hidden" name="state" value="">
				<input type="hidden" name="country" value="">
				<input type="hidden" name="zip" value="' . $this->cust['postcode'] . '">

				<button type="submit">' . $value . '</button>
				</form>';
        }
    }

    public function process_paypal()
    {
        global $admin_email;

        //check posts to make sure all is well

        if (!$this->test) {
            if ($_POST and 'true' != $_POST['for_auction']) {
                $msg = '';
                foreach ($_POST as $k => $v) {
                    $msg .= "$k = $v \n";
                }
                mail($admin_email, 'Order Placed', $msg, $this->headers);
            } else {
                die('no post');
            }

            if (!$_POST['item_number']) {
                mail($admin_email, 'Order Placed', 'No order number');
                print 'no order number';
                exit;
            }
        }

        if (!$this->test) {
            // read the post from PayPal system and add 'cmd'
            $req = 'cmd=_notify-validate';
            foreach ($_POST as $key => $value) {
                $value = urlencode(stripslashes($value));
                $req .= "&$key=$value";
            }

            $ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);

            if (!($res = curl_exec($ch))) {
                mail($admin_email, 'Curl Error', curl_error($ch), $this->headers);
                curl_close($ch);
                exit;
            }
            curl_close($ch);

            $verified = false;
            if (0 == strcmp($res, 'VERIFIED')) {
                $verified = true;
            }

            if (!$verified) {
                mail($admin_email, 'Order Not verified', $res, $this->headers);
                exit;
            }
        }

        $oid = $_POST['item_number'];
        $status = $_POST['payment_status'];
        $ref = $_POST['txn_id'];

        return $this->complete_order($oid, $ref, 'paypal');
    }

    public function send_confirmation(int $oid)
    {
        global $debug, $admin_email;

        $order = sql_query("SELECT * FROM orders WHERE id='" . escape($oid) . "'", 1);

        $items = sql_query("SELECT * FROM order_items WHERE `order`='" . $order['id'] . "'");

        $details = '';
        foreach ($items as $k => $item) {
            $details .= $item['name'] . ' x ' . $item['quantity'] . ' £' . $item['cost'];

            if ($item['extras']) {
                $json = json_decode($item['extras']);
                
                if ($json) {
                    $extras = '';
                    foreach($json as $k => $v) {
                        $extras .= $k . ': ' . $v . "\n";
                    }
                    
                    $details .= ' (' . $extras . ')';
                } else {
                    $details .= ' (' . $item['extras'] . ')';
                }
            }

            $details .= "\n";
        }

        try {
            $reps = array_merge($order, [
                'oid' => $order['id'],
                'order_id' => $order['id'],
                'details' => $details,
                'delivery' => '£' . number_format((float)$order['delivery'], 2),
                'vat' => '£' . number_format((float)$order['vat'], 2),
                'total' => '£' . number_format((float)$order['total'], 2),
                'event_date' => dateformat('d-m-Y', $order['event_date']),
                'note' => $order['note'],
            ]);

            if (!$this->disable_confirmation_email) {
                email_template($order['email'], 'Order Confirmation', $reps);

                if ($order['email'] != $this->paypal_email) {
                    email_template($this->paypal_email, 'Order Confirmation', $reps);
                }
            }

            email_template($admin_email, 'Order Confirmation', $reps);
        } catch (Exception $e) {
            $msg = '';

            if ($debug or $this->test) {
                $msg .= 'DEBUG MODE - THIS IS NOT A REAL TRANSACTION' . "\n\n";
            }

            // send out confirmation emails
            $msg .= 'Thank you for your order. Please find details of your order below:' . "\n\n";

            $msg .= 'Payment method: ';
            $msg .= '' . $method . '' . "\n\n";

            $msg .= 'Payment status: ';
            $msg .= $status . "\n\n";

            $msg .= 'Order ref: ';
            $msg .= $order['id'] . "\n\n";

            $msg .= 'Name' . "\n";
            $msg .= '=====' . "\n";
            $msg .= $order['name'] . "\n\n";

            $msg .= 'Address' . "\n";
            $msg .= '========' . "\n";
            $msg .= $order['address'] . "\n";
            $msg .= $order['postcode'] . "\n\n";

            if ($order['comments']) {
                $msg .= 'Comments' . "\n";
                $msg .= '========' . "\n";
                $msg .= $order['comments'] . "\n\n";
            }

            $msg .= 'Items' . "\n";
            $msg .= '======' . "\n";
            $msg .= $details . "\n";

            $msg .= 'Delivery:' . "\n";
            $msg .= '£' . $order['delivery'] . "\n\n";

            if ($order['offer']) {
                $msg .= 'Offer:' . "\n";
                $msg .= $order['offer'] . "\n\n";
            }
            if ($order['discount']) {
                $msg .= 'Discount:' . "\n";
                $msg .= '£' . number_format($order['discount'], 2) . "\n\n";
            }

            if ($order['vat']) {
                $msg .= 'Vat:' . "\n";
                $msg .= '£' . number_format($order['vat'], 2) . "\n\n";
            }

            $msg .= 'Total: ';
            $msg .= '£' . number_format($order['total'], 2) . "\n\n";

            //confirmation email
            mail($admin_email, 'Order Placed', $msg, $this->headers);

            if (!$this->disable_confirmation_email) {
                if ($this->cc) {
                    $this->headers .= 'Cc: ' . $this->cc . "\n";
                }

                mail($order['email'], 'Order Confirmation', $msg, $this->headers);

                if ($order['email'] != $this->paypal_email) {
                    mail($this->paypal_email, 'Order Placed', $msg, $this->headers);
                }
            }
        }
    }

    public function complete_order($oid, $ref, $method)
    {
        // check if invoice has been paid
        $order = sql_query("SELECT * FROM orders WHERE id='" . escape($oid) . "'", 1);

        if ('paid' != $order['status']) {
            //order status
            sql_query("UPDATE orders SET status='paid' WHERE id='" . $order['id'] . "' LIMIT 1");
            sql_query("UPDATE orders SET txn_id='" . escape($ref) . "' WHERE id='" . $order['id'] . "' LIMIT 1");
            sql_query("UPDATE orders SET method='" . escape($method) . "' WHERE id='" . $order['id'] . "' LIMIT 1");

            if ($this->test) {
                sql_query("UPDATE orders SET test='1' WHERE id='" . $order['id'] . "' LIMIT 1");
            }

            $this->send_confirmation($oid);

            //empty basket
            sql_query("DELETE FROM basket WHERE user='" . $order['customer'] . "'");

            return $order['id'];
        }
        return false;
    }

    public function failed_order(string $error, int $oid)
    {
        global $admin_email;

        $order = sql_query("SELECT * FROM orders WHERE id='" . escape($oid) . "'", 1);

        $_POST['error'] = $error;

        $msg = '';
        foreach ($_POST as $k => $v) {
            $msg .= "$k = $v \n";
        }

        //order status
        sql_query("UPDATE orders SET status='failed'
			WHERE
				id='" . escape($oid) . "'
			LIMIT 1
		");

        mail($admin_email, 'Order Failed', $msg, $this->headers);

        try {
            email_template($order['email'], 'Order Failed', $_POST);
        } catch (Exception $e) {
        }
    }
}
