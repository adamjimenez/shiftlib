<?
class shop{
	function shop()
	{
		global $shop_config,$auth;

		$this->vat_rate=0.2;

		$this->from_email='auto@'.$_SERVER['HTTP_HOST'];
		$this->headers="From: ".$this->from_email."\n";

		$this->discount=0;

		foreach( $shop_config as $k=>$v ){
			$this->$k=$v;
		}

		$basket_fields=array(
			'session'=>'text',
			'user'=>'int',
			'product'=>'int',
			'variation'=>'int',
			'extras'=>'textarea',
			'quantity'=>'int',
		);
		check_table('basket', $basket_fields);

		$orders_fields=array(
			'date'=>'datetime',
			'customer'=>'int',
			'name'=>'text',
			'address'=>'textarea',
			'postcode'=>'text',
			'email'=>'text',
			'tel'=>'text',
			'mobile'=>'text',
			'subtotal'=>'decimal',
			'offer'=>'text',
			'discount'=>'decimal',
			'vat'=>'decimal',
			'delivery'=>'decimal',
			'total'=>'decimal',
			'status'=>'text',
			'txn_id'=>'text',
			'dispatched_date'=>'datetime',
			'refund_date'=>'datetime',
		);
		check_table('orders', $orders_fields);

		$order_items_fields=array(
			'order'=>'int',
			'product'=>'int',
			'variation'=>'int',
			'name'=>'text',
			'extras'=>'textarea',
			'cost'=>'float',
			'quantity'=>'int',
		);
		check_table('order_items', $order_items_fields);

		if( $_SESSION['guest'] ){
			$this->guest = $_SESSION['guest'];
		}

		if( $_POST['update_guest'] ){
			$this->guest['email']=$_POST['email'];
			$this->guest['name']=$_POST['name'];
			$this->guest['tel']=$_POST['tel'];
			$this->guest['mobile']=$_POST['mobile'];
			$this->guest['surname']=$_POST['surname'];
			$this->guest['address']=$_POST['address'];
			$this->guest['city']=$_POST['city'];
			$this->guest['postcode']=$_POST['postcode'];

			$_SESSION['guest']=$this->guest;
		}

		if( $this->guest ){
			$this->cust = $this->guest;
		}elseif( $auth->user ){
			$this->cust = $auth->user;
		}else{
		    $this->cust = array();
		}

        if( $_GET['add_to_basket'] ){
            $_POST['add_to_basket'] = $_GET['add_to_basket'];
        }

        if( $_GET['buy_now'] ){
            $_POST['buy_now'] = $_GET['buy_now'];
        }

		if( is_numeric($_POST['add_to_basket']) ){
			$quantity=is_numeric($_POST['quantity']) ? $_POST['quantity'] : 1;

			$this->add_to_basket($_POST['add_to_basket'],$quantity,$_POST['buy_now'],$_POST['variation'],$_POST['extras']);
		}

		if( is_array($_POST['add_to_basket']) ){
			if( $_POST['buy_now'] ){
				mysql_query("DELETE FROM basket WHERE session='".session_id()."' OR user='".$auth->user['id']."'") or trigger_error("SQL", E_USER_ERROR);
			}

			foreach( $_POST['add_to_basket'] as $k=>$v ){
				$this->add_to_basket($k,$v,NULL,$_POST['variation'][$k],$_POST['extras'][$k]);
			}

			if( $_POST['buy_now'] ){
				redirect('/checkout');
			}
		}

		if( $_POST['code'] && table_exists('promo_codes') ){
			//lookup discount
			$select_code=mysql_query("SELECT * FROM promo_codes
				WHERE
					code='".addslashes($_POST['code'])."' AND
					(
						expiry>CURDATE() OR
						expiry='0000-00-00'
					)
			") or trigger_error("SQL", E_USER_ERROR);

			if( mysql_num_rows($select_code) ){
				$_SESSION['code']=$_POST['code'];
			}else{
				$_SESSION['error']='Invalid promo code.';
			}
		}

		if( $_POST['update_basket'] ){
			$this->update_basket();
		}


		$this->delivery=0;
		$this->get_basket();

		if( $_SESSION['code'] ){
			//lookup discount
			$select_code=mysql_query("SELECT * FROM promo_codes WHERE code='".addslashes($_SESSION['code'])."'") or trigger_error("SQL", E_USER_ERROR);

			if( mysql_num_rows($select_code) ){
				$promo=mysql_fetch_array($select_code);

				if( $promo['discount'] ){
					$this->promo=$promo;

					$this->set_discount($promo['discount']);
				}
			}
		}
		$this->update_total();
	}

	function remove($product)
	{
		mysql_query("DELETE FROM basket
			WHERE
				(session='".session_id()."' OR user='".$auth->user['id']."') AND
				id='".escape($product)."'
		");

		$this->get_basket();
	}

	function get_basket()
	{
		global $auth;

		if( $auth->user ){
			$where_str=" OR B.user='".$auth->user['id']."'";
		}
		$this->basket=sql_query("SELECT *,B.id FROM basket B
			LEFT JOIN products P ON B.product=P.id
			WHERE
				(
					B.session='".session_id()."'
					$where_str
				)
		");

		$this->item_count=0;
		$this->subtotal=0;
		foreach( $this->basket as $k=>$v ){
			$variation=array();

			if( $v['variation'] ){
				$variation=sql_query("SELECT * FROM variations WHERE id='".escape($v['variation'])."'");
			}

			if( $variation[0]['cost']>0 ){
				$this->basket[$k]['cost']=$variation[0]['cost'];
			}

			if( table_exists('extras') ){
				$lines=explode("\n",$v['extras']);

				$extras=array();
				foreach( $lines as $line ){
					$arr=explode(': ',$line);

					$select=mysql_query("SELECT * FROM extras WHERE
						name='".escape($arr[0])."' AND
						value='".escape($arr[1])."' AND
						product='".$v['product']."'
					") or die (mysql_error());

					if( mysql_num_rows($select) ){
						$row=mysql_fetch_array($select);
						$this->basket[$k]['cost']+=$row['cost'];
					}
				}
			}

			$this->subtotal+=$v['quantity']*$this->basket[$k]['cost'];

			$this->item_count+=$v['quantity'];

			if( $v['delivery'] ){
			    $this->delivery += $v['delivery'];
			}
		}

		$this->vat=$this->subtotal*$this->vat_rate;
	}

	function add_to_basket($product,$quantity=1,$buy_now=false,$variation_id,$extras_arr)
	{
		global $auth;

		if( $quantity==0 ){
			return false;
		}

		if( $buy_now ){
			mysql_query("DELETE FROM basket WHERE session='".session_id()."' OR user='".$auth->user['id']."'");
		}

		$extras='';
		if( $extras_arr ){
			foreach( $extras_arr as $k=>$v ){
				$extras.=$k.': '.$v."\n";
			}
		}

		if( $variation_id ){
			$select_variation=mysql_query("SELECT * FROM variations WHERE
				id='".escape($variation_id)."'
			") or trigger_error("SQL", E_USER_ERROR);

			if( mysql_num_rows($select_variation) ){
				$variation=mysql_fetch_assoc($select_variation);

				foreach( $variation as $k=>$v ){
					if( $k=='id' or $k=='product' or $k=='quantity' or $k=='image' or $k=='cost' or $k=='position' or $v=='' ){
						continue;
					}
					$extras.=ucfirst($k).': '.$v."\n";
				}
			}

			if( $this->stock_control ){
				if( $variation['quantity']==0 ){
					$_SESSION['error']='item is out of stock';
					return false;
				}elseif( $quantity>$variation['quantity'] ){
					$_SESSION['error']='item has limited stock';
					$quantity=$variation['quantity'];
				}
			}
		}

		if( $auth->user ){
			$where_str=" OR user='".$auth->user['id']."'";
		}
		$select=mysql_query("SELECT * FROM basket WHERE
			(
				session='".session_id()."'
				$where_str
			) AND
			product='".escape($product)."' AND
			extras='".escape(trim($extras))."' AND
			variation='".escape($variation_id)."'
		") or trigger_error("SQL", E_USER_ERROR);

		if( mysql_num_rows($select) ){
			$row=mysql_fetch_array($select);

			$quantity+=$row['quantity'];

			if( $this->stock_control ){
				if( $quantity>$variation['quantity'] ){
					$_SESSION['error']='item has limited stock';
					$quantity=$variation['quantity'];
				}
			}

			mysql_query("UPDATE basket SET
				user='".$auth->user['id']."',
				quantity='".$quantity."'
			WHERE
				session='".session_id()."' AND
				product='".escape($product)."' AND
				extras='".escape(trim($extras))."' AND
				variation='".escape($variation_id)."'
			") or trigger_error("SQL", E_USER_ERROR);
		}else{
			mysql_query("INSERT INTO basket SET
				user='".$auth->user['id']."',
				quantity='".escape($quantity)."',
				session='".session_id()."',
				product='".escape($product)."',
				extras='".escape(trim($extras))."',
				variation='".escape($variation_id)."'
			") or trigger_error("SQL", E_USER_ERROR);
		}

		if( $buy_now ){
			redirect('/checkout');
		}elseif( $this->redirect_on_add ){
			redirect('/basket');
		}
	}

	function update_basket()
	{
		if( $_POST['quantity'] ){
			foreach( $_POST['quantity'] as $k=>$v ){
				if( $v==0 ){
					mysql_query("DELETE FROM basket
						WHERE id='".escape($k)."'
					") or trigger_error("SQL", E_USER_ERROR);
				}elseif( $v>0 ){
					mysql_query("UPDATE basket SET
						quantity='".addslashes($v)."'
						WHERE id='".escape($k)."'
					") or trigger_error("SQL", E_USER_ERROR);
				}
			}
		}

		if( $_POST['remove'] ){
			foreach( $_POST['remove'] as $k=>$v ){
				mysql_query("DELETE FROM basket
					WHERE id='".escape($k)."'
				") or trigger_error("SQL", E_USER_ERROR);
			}
		}
	}

	function empty_basket()
	{
		mysql_query("DELETE FROM basket
		    WHERE
		        session='".session_id()."' OR user='".$auth->user['id']."'
		") or trigger_error("SQL", E_USER_ERROR);
	}

	function set_delivery($price)
	{
		$this->delivery=$price;

		$this->update_total();
	}

	function set_discount($discount)
	{
		if( substr($discount,-1)=='%' ){
			$this->discount=($discount/100)*($this->subtotal);
		}else{
			$this->discount=$discount;
		}

		$this->update_total();
	}

	function update_total()
	{
		$this->total = $this->subtotal-$this->discount;

        if( $this->total<0 ){
            $this->total = 0;
        }

		if( $this->include_vat ){
			$this->total+=($this->total*$this->vat_rate);
		}

		$this->total+=$this->delivery;
	}

	function prepare()
	{
		global $auth;

		$this->update_total();

		mysql_query("INSERT INTO orders SET
			date=NOW(),
			customer='".addslashes($this->cust['id'])."',
			name='".addslashes( ($this->cust['name'].' '.$this->cust['surname']) )."',
			address='".addslashes($this->cust['address'])."\n".addslashes($this->cust['address2'])."\n".addslashes($this->cust['city'])."',
			postcode='".addslashes($this->cust['postcode'])."',
			email='".addslashes($this->cust['email'])."',
			tel='".addslashes($this->cust['tel'])."',
			mobile='".addslashes($this->cust['mobile'])."',
			subtotal='".addslashes($this->subtotal)."',
			offer='".addslashes($this->promo['name'])."',
			discount='".addslashes($this->discount)."',
			delivery='".addslashes($this->delivery)."',
			total='".addslashes($this->total)."',
			status='pending'
		") or trigger_error("SQL", E_USER_ERROR);

		$this->oid=mysql_insert_id();

		if( $this->cust['comments'] ){
		    mysql_query("UPDATE orders SET
		        comments = '".escape($_POST["comments"])."'
		        WHERE
		            id = '".$this->oid."'
		    ");
		}

		if( $this->include_vat ){
			mysql_query("UPDATE orders SET
				vat='".$this->vat."'
				WHERE
					id='".$this->oid."'
				LIMIT 1
			");
		}

		//order items
		foreach( $this->basket as $item ){
			mysql_query("INSERT INTO order_items SET
				`order`='".addslashes($this->oid)."',
				product='".addslashes($item['product'])."',
				variation='".addslashes($item['variation'])."',
				name='".addslashes($item['name'])."',
				extras='".addslashes($item['extras'])."',
				cost='".addslashes($item['cost'])."',
				quantity='".addslashes($item['quantity'])."'
			") or trigger_error("SQL", E_USER_ERROR);
		}
	}

    function gc_button($value='Pay using Google Checkout'){
        global $shop_config;

        require_once(dirname(__FILE__).'/../modules/googlecheckout/googlecart.php');
        require_once(dirname(__FILE__).'/../modules/googlecheckout/googleitem.php');
        require_once(dirname(__FILE__).'/../modules/googlecheckout/googleshipping.php');
        require_once(dirname(__FILE__).'/../modules/googlecheckout/googletax.php');

        $merchant_id = $this->gc_merchant_id;  // Your Merchant ID
    	$merchant_key = $this->gc_merchant_key;  // Your Merchant Key
    	$server_type = "production";  //sandbox
    	$currency = "GBP";
    	$cart = new GoogleCart($merchant_id, $merchant_key, $server_type, $currency);

    	foreach( $this->basket as $item ){
    		$GoogleItem = new GoogleItem($item['name'],      // Item name
			   $item['description'], // Item      description
			   $item['quantity'], // Quantity
			   $item['cost']); // Unit price
    		$cart->AddItem($GoogleItem);
    	}

    	// Add shipping options
    	$ship_1 = new GoogleFlatRateShipping("Standard Delivery", 0);

    	//$Gfilter = new GoogleShippingFilters();
    	//$Gfilter->SetAllowedCountryArea('CONTINENTAL_48');

    	//$ship_1->AddShippingRestrictions($Gfilter);

    	//$cart->AddShipping($ship_1);

    	// Add tax rules
    	//$tax_rule = new GoogleDefaultTaxRule(0.05);
    	//$tax_rule->SetStateAreas(array("MA"));
    	//$cart->AddDefaultTaxRules($tax_rule);

    	// Specify <edit-cart-url>
    	$cart->SetEditCartUrl('http://'.$_SERVER["HTTP_HOST"]."/basket");

    	// Specify "Return to xyz" link
    	$cart->SetContinueShoppingUrl('http://'.$_SERVER["HTTP_HOST"]."/thanks");

    	// Request buyer's phone number
    	$cart->SetRequestBuyerPhone(true);

    	// Display Google Checkout button
    	print $cart->CheckoutButtonCode("SMALL");
    }

	function paypal_button($value='Pay using paypal click here')
	{
		if( $this->test ){
			print '
				<form method="post" action="/paypal">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="'.$this->paypal_email.'">
				<input type="hidden" name="item_name" value="Basket contents">
				<input type="hidden" name="item_number" value="'.$this->oid.'">
				<input type="hidden" name="amount" value="'.number_format($this->total,2).'">
				<input type="hidden" name="no_shipping" value="0">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="currency_code" value="GBP">
				<input type="hidden" name="charset" value="UTF-8">

				<input type="hidden" name="notify_url" value="http://'.$_SERVER['HTTP_HOST'].'/paypal">
				<input type="hidden" name="return" value="http://'.$_SERVER['HTTP_HOST'].'">
				<input type="hidden" name="rm" value="1">

				<input type="hidden" name="email" value="'.$this->cust['email'].'">
				<input type="hidden" name="first_name" value="'.$this->cust['name'].'">
				<input type="hidden" name="last_name" value="'.$this->cust['surname'].'">
				<input type="hidden" name="address1" value="'.$this->cust['address'].'">
				<input type="hidden" name="address2" value="">
				<input type="hidden" name="city" value="'.$this->cust['city'].'">
				<input type="hidden" name="state" value="">
				<input type="hidden" name="country" value="">
				<input type="hidden" name="zip" value="'.$this->cust['postcode'].'">

				<button type="submit">Pay using paypal TEST MODE</button>
				</form>';
		}else{

			print '
				<form method="get" action="https://www.paypal.com/cgi-bin/webscr">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="'.$this->paypal_email.'">
				<input type="hidden" name="item_name" value="Basket contents">
				<input type="hidden" name="item_number" value="'.$this->oid.'">
				<input type="hidden" name="amount" value="'.number_format($this->total,2).'">
				<input type="hidden" name="no_shipping" value="0">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="currency_code" value="GBP">
				<input type="hidden" name="charset" value="UTF-8">


				<input type="hidden" name="notify_url" value="http://'.$_SERVER['HTTP_HOST'].'/paypal">
				<input type="hidden" name="return" value="http://'.$_SERVER['HTTP_HOST'].'">
				<input type="hidden" name="rm" value="1">

				<input type="hidden" name="email" value="'.$this->cust['email'].'">
				<input type="hidden" name="first_name" value="'.$this->cust['name'].'">
				<input type="hidden" name="last_name" value="'.$this->cust['surname'].'">
				<input type="hidden" name="address1" value="'.$this->cust['address'].'">
				<input type="hidden" name="address2" value="">
				<input type="hidden" name="city" value="'.$this->cust['city'].'">
				<input type="hidden" name="state" value="">
				<input type="hidden" name="country" value="">
				<input type="hidden" name="zip" value="'.$this->cust['postcode'].'">

				<button type="submit">'.$value.'</button>
				</form>';
			}
	}

	function worldpay_button($value='Pay using worldpay click here')
	{
		global $auth;

		$testmode= ($this->worldpay_testmode) ? 100 : 0;

		print '
			<form method="post" action="https://select.worldpay.com/wcc/purchase">
			<input type="hidden" name="instId"  value="'.$this->worldpay_installation_id.'">
			<input type="hidden" name="cartId" value="'.$this->oid.'">
			<input type="hidden" name="currency" value="GBP">
			<input type="hidden" name="amount"  value="'.number_format($this->total,2).'">
			<input type="hidden" name="desc" value="Basket contents">
			<input type="hidden" name="testMode" value="'.$testmode.'">

			<input type="hidden" name="name" value="'.$this->cust['name'].' '.$this->cust['surname'].'">
			<input type="hidden" name="address" value="'.$this->cust['address'].'">
			<input type="hidden" name="postcode" value="'.$this->cust['postcode'].'">
			<input type="hidden" name="country" value="GB">
			<input type="hidden" name="tel" value="'.$this->cust['tel'].'">

			<input type="hidden" name="email" value="'.$this->cust['email'].'">

			<button type="submit">'.$value.'</button>
			</form>
		';
	}

	function process_paypal()
	{
		global $debug, $admin_email;

		//check posts to make sure all is well

		if( !$this->test ){
			if( $_POST and $_POST['for_auction']!='true' ){
				foreach($_POST as $k=>$v){
					$msg.="$k = $v \n";
				}
				mail($admin_email,'Order Placed',$msg,$this->headers);
			}else{
				die('no post');
			}

			if( !$_POST['item_number'] ){
				mail($admin_email,'Order Placed','No order number');
				print 'no order number';
				exit;
			}
		}

		if( !$this->test  ){
    		// read the post from PayPal system and add 'cmd'
			$req = 'cmd=_notify-validate';
			foreach ($_POST as $key => $value) {
				$value = urlencode(stripslashes($value));
				$req .= "&$key=$value";
			}

            $ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

            if( !($res = curl_exec($ch)) ) {
                mail($admin_email,'Curl Error',curl_error($ch),$this->headers);
                curl_close($ch);
                exit;
            }
            curl_close($ch);

        	$verified=false;
            if (strcmp ($res, "VERIFIED") == 0) {
            	$verified=true;
            }

			if( !$verified ){
				mail($admin_email,'Order Not verified',$msg,$this->headers);
				exit;
			}
		}

		$oid=$_POST['item_number'];
		$status=$_POST['payment_status'];
		$ref=$_POST['txn_id'];

		return $this->complete_order($oid,$ref,'paypal');
	}

    function process_gc()
	{
        global $admin_email;

		$error='test';

		if( $error ){
			$_POST['error']=$error;
			foreach($_POST as $k=>$v){
				$msg.="$k = $v \n";
			}

			mail($admin_email,'Order not verified',$msg,$this->headers);
			exit;
		}

        exit;

		foreach($vars as $k=>$v){
			$msg.="$k = $v \n";
		}

		mail($admin_email,'Order Placed',$msg,$this->headers);

		$oid=$vars['shopping-cart_items_item-1_merchant-item-id'];
		$ref=$vars['google-order-number'];
		$status=$vars['fulfillment-order-state'];

		$this->complete_order($oid,$ref,'google checkout');
	}

	function process_netbanx()
	{
        global $admin_email;

		$error='';

		if( !$_GET['_params'] or !$_GET['checksum'] ){
			exit;
		}

		if( md5($_GET['_params'].$this->netbanx_key)!=$_GET['checksum'] ){
			$error='checksum failed';
		}

		$params=explode('::',$_GET['_params']);

		$vars=array();
		foreach( $params as $param ){
			$array=explode('=',$param);

			$vars[$array[0]]=$array[1];
		}

		if( !$vars['netbanx_reference'] ){
			$error='reference missing';
		}

		if( $error ){
			$vars['error']=$error;
			foreach($vars as $k=>$v){
				$msg.="$k = $v \n";
			}

			mail($admin_email,'Order not verified',$msg,$this->headers);
			exit;
		}

		foreach($vars as $k=>$v){
			$msg.="$k = $v \n";
		}

		mail($admin_email,'Order Placed',$msg,$this->headers);

		$oid=$vars['order_id'];
		$ref=$vars['netbanx_reference'];
		$status=$vars['auth'];

		$this->complete_order($oid,$ref,'netbanx');
	}

	function process_netbanx_upp()
	{
        global $admin_email;

		$error='';

		if( !$_POST['nbx_status'] or !$_POST['nbx_checksum'] ){
			$error='missing vars';
		}

		if( $_POST['nbx_checksum']!=sha1($_POST['nbx_payment_amount'].'GBP'.$_POST['nbx_merchant_reference'].$_POST['nbx_netbanx_reference'].$this->netbanx_key) ){
			$error='checksum failed';
		}

		if( !$_POST['nbx_netbanx_reference'] ){
			$error='reference missing';
		}

		if( $_POST['nbx_status']!='passed' ){
			$error='status not authorised';
		}

		if( $error ){
			$_POST['error']=$error;

			$_POST['netbanx_key']=$this->netbanx_key;

			$_POST['str']=$_POST['nbx_payment_amount'].'GBP'.$_POST['nbx_merchant_reference'].$_POST['nbx_netbanx_reference'].$this->netbanx_key;

			foreach($_POST as $k=>$v){
				$msg.="$k = $v \n";
			}

			mail($admin_email,'Order not verified',$msg,$this->headers);

			die('order not verified');
		}

		foreach($_POST as $k=>$v){
			$msg.="$k = $v \n";
		}

		mail($admin_email,'Order Placed',$msg,$this->headers);

		$oid=$_POST['nbx_merchant_reference'];
		$ref=$_POST['nbx_netbanx_reference'];
		$status=$_POST['nbx_status'];

		return $this->complete_order($oid,$ref,'netbanx');
	}

    function process_cardsave()
	{
        global $admin_email, $PreSharedKey, $Password;

		$oid = $_POST['OrderID'];

		$error='';

        // Check the passed HashDigest against our own to check the values passed are legitimate.
        $hashcode="PreSharedKey=" . $PreSharedKey;
        $hashcode=$hashcode . '&MerchantID=' . $_POST["MerchantID"];
        $hashcode=$hashcode . '&Password=' . $Password;
        $hashcode=$hashcode . '&StatusCode=' . $_POST["StatusCode"];
        $hashcode=$hashcode . '&Message=' . $_POST["Message"];
        $hashcode=$hashcode . '&PreviousStatusCode=' . $_POST["PreviousStatusCode"];
        $hashcode=$hashcode . '&PreviousMessage=' . $_POST["PreviousMessage"];
        $hashcode=$hashcode . '&CrossReference=' . $_POST["CrossReference"];
        $hashcode=$hashcode . '&AddressNumericCheckResult=' . $_POST["AddressNumericCheckResult"];
        $hashcode=$hashcode . '&PostCodeCheckResult=' . $_POST["PostCodeCheckResult"];
        $hashcode=$hashcode . '&CV2CheckResult=' . $_POST["CV2CheckResult"];
        $hashcode=$hashcode . '&ThreeDSecureAuthenticationCheckResult=' . $_POST["ThreeDSecureAuthenticationCheckResult"];
        $hashcode=$hashcode . '&CardType=' . $_POST["CardType"];
        $hashcode=$hashcode . '&CardClass=' . $_POST["CardClass"];
        $hashcode=$hashcode . '&CardIssuer=' . $_POST["CardIssuer"];
        $hashcode=$hashcode . '&CardIssuerCountryCode=' . $_POST["CardIssuerCountryCode"];
        $hashcode=$hashcode . '&Amount=' . $_POST["Amount"];
        $hashcode=$hashcode . '&CurrencyCode=' . $_POST["CurrencyCode"];
        $hashcode=$hashcode . '&OrderID=' . $_POST["OrderID"];
        $hashcode=$hashcode . '&TransactionType=' . $_POST["TransactionType"];
        $hashcode=$hashcode . '&TransactionDateTime=' . $_POST["TransactionDateTime"];
        $hashcode=$hashcode . '&OrderDescription=' . $_POST["OrderDescription"];
        $hashcode=$hashcode . '&CustomerName=' . $_POST["CustomerName"];
        $hashcode=$hashcode . '&Address1=' . $_POST["Address1"];
        $hashcode=$hashcode . '&Address2=' . $_POST["Address2"];
        $hashcode=$hashcode . '&Address3=' . $_POST["Address3"];
        $hashcode=$hashcode . '&Address4=' . $_POST["Address4"];
        $hashcode=$hashcode . '&City=' . $_POST["City"];
        $hashcode=$hashcode . '&State=' . $_POST["State"];
        $hashcode=$hashcode . '&PostCode=' . $_POST["PostCode"];
        $hashcode=$hashcode . '&CountryCode=' . $_POST["CountryCode"];
        $hashcode=$hashcode . '&EmailAddress=' . $_POST["EmailAddress"];
        $hashcode=$hashcode . '&PhoneNumber=' . $_POST["PhoneNumber"];

		if( $_POST['HashDigest'] != sha1($hashcode)){
			$error='checksum failed';
		}

		if( $_POST['StatusCode'] != 0 ){
			$error='Transaction error:'. $_POST["Message"];
		}

		if( $error ){
			$_POST['error']=$error;

			foreach($_POST as $k=>$v){
				$msg.="$k = $v \n";
			}

			//order status
			mysql_query("UPDATE orders SET status='failed' WHERE id='".escape($oid)."' LIMIT 1");

			mail($admin_email,'Order not verified',$msg,$this->headers);

			//die('order not verified');
			return false;
		}

		foreach($_POST as $k=>$v){
			$msg.="$k = $v \n";
		}

		mail($admin_email,'Order Placed',$msg,$this->headers);
		$ref = $_POST['CrossReference'];
		$status = $_POST['Message'];

		return $this->complete_order($oid, $ref, 'cardsave');
	}

	function process_worldpay()
	{
        global $admin_email;

		$oid=$_POST['cartId'];
		$ref=$_POST['transId'];
		$status='paid';

		$select=mysql_query("SELECT * FROM orders WHERE id='".addslashes($oid)."'");
		$order=mysql_fetch_array($select);

		$error='';

		foreach($_POST as $k=>$v){
			$msg.="$k = $v \n";
		}

		mail($admin_email,'Order Placed',$msg,$this->headers);

		if( $this->worldpay_callback_password!=$_POST['callbackPW'] ){
			$error.='wrong callback password'."\n";
		}

		if( $_POST['transStatus']!='Y' ){
			$error.='transaction not authorised'."\n";
		}

		if( $_POST['testMode']=='100' and !$this->worldpay_testmode ){
			$error.='testmode enabled'."\n";
		}

		if( $_POST['authCost']!=$order['total'] ){
			$error.='transaction amount incorrect'."\n";
		}

		if( $error ){
			$vars['error']=$error;
			foreach($vars as $k=>$v){
				$msg.="$k = $v \n";
			}

			mail($admin_email,'Order not verified',$msg,$this->headers);
			exit;
		}

		return $this->complete_order($oid,$ref,'worldpay');
	}

	function complete_order($oid,$ref,$method)
	{
		global $debug, $admin_email;

		// check if invoice has been paid
		$select=mysql_query("SELECT * FROM orders WHERE id='".addslashes($oid)."'");
		$order=mysql_fetch_array($select);

		if( $order['status']=='pending' ){
			//order status
			mysql_query("UPDATE orders SET status='paid' WHERE id='".$order['id']."' LIMIT 1");
			mysql_query("UPDATE orders SET txn_id='".addslashes($ref)."' WHERE id='".$order['id']."' LIMIT 1");
			mysql_query("UPDATE orders SET method='".addslashes($method)."' WHERE id='".$order['id']."' LIMIT 1");

			if( $this->test ){
				mysql_query("UPDATE orders SET test='1' WHERE id='".$order['id']."' LIMIT 1");
			}

			$items=sql_query("SELECT * FROM order_items WHERE `order`='".$order['id']."'");

			$details='';
			foreach( $items as $k=>$item ){
				$details.=$item['name'].' x '.$item['quantity'].' @ £'.$item['cost']."\n"
				.$item['extras']."\n\n";
			}

			try {
				$reps=array(
					'method'=>$method,
					'status'=>$status,
					'oid'=>$order['id'],
					'order_id'=>$order['id'],
					'name'=>$order['name'],
					'address'=>$order['address'],
					'postcode'=>$order['postcode'],
					'tel'=>$order['tel'],
					'details'=>$details,
					'delivery'=>'£'.number_format($order['delivery'],2),
					'vat'=>'£'.number_format($order['vat'],2),
					'total'=>'£'.number_format($order['total'],2),
					'event_date'=>dateformat('d-m-Y',$order['event_date']),
				);

				email_template($order['email'],'Order Confirmation',$reps);

				email_template($admin_email,'Order Confirmation',$reps);

				email_template($this->paypal_email,'Order Confirmation',$reps);
			} catch (Exception $e) {
				$msg='';

				if( $debug or $this->test ){
					$msg.='DEBUG MODE - THIS IS NOT A REAL TRANSACTION'."\n\n";
				}

				// send out confirmation emails
				$msg.='Thank you for your order. Please find details of your order below:'."\n\n";

				$msg.='Payment method: ';
				$msg.=''.$method.''."\n\n";

				$msg.='Payment status: ';
				$msg.=$status."\n\n";

				$msg.='Order ref: ';
				$msg.=$order['id']."\n\n";

				$msg.='Name'."\n";
				$msg.='====='."\n";
				$msg.=$order['name']."\n\n";

				$msg.='Address'."\n";
				$msg.='========'."\n";
				$msg.=$order['address']."\n";
				$msg.=$order['postcode']."\n\n";

				if( $order['comments'] ){
					$msg.='Comments'."\n";
			    	$msg.='========'."\n";
					$msg.=$order['comments']."\n\n";
				}

				$msg.='Items'."\n";
				$msg.='======'."\n";
				$msg.=$details."\n";

				$msg.='Delivery:'."\n";
				$msg.='£'.$order['delivery']."\n\n";

				if( $order['offer'] ){
					$msg.='Offer:'."\n";
					$msg.=$order['offer']."\n\n";
				}
				if( $order['discount'] ){
					$msg.='Discount:'."\n";
					$msg.='£'.number_format($order['discount'],2)."\n\n";
				}

				if( $order['vat'] ){
					$msg.='Vat:'."\n";
					$msg.='£'.number_format($order['vat'],2)."\n\n";
				}

				$msg.='Total: ';
				$msg.='£'.number_format($order['total'],2)."\n\n";

				if( $order['event_date'] ){
					$msg.='Event date:'."\n";
					$msg.=dateformat('d-m-Y',$order['event_date'])."\n\n";
				}

				//confirmation to customer
				mail($order['email'],'Order Confirmation',$msg,$this->headers);

				mail($admin_email,'Order Placed',$msg,$this->headers);

				if( $this->cc ){
					$this->headers.="Cc: ".$this->cc."\n";
				}

				mail($this->paypal_email,'Order Placed',$msg,$this->headers);
			}

			//empty basket
			mysql_query("DELETE FROM basket WHERE user='".$order['customer']."'") or trigger_error("SQL", E_USER_ERROR);

			return $order['id'];
		}else{
			return false;
		}
	}
}
?>