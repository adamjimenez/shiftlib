<?
if( !$shop_enabled ){
    die('shop is not enabled');
}

if( $_POST['status'] ){
    $order = sql_query("SELECT * FROM orders WHERE
    	id='".escape($_GET['id'])."'
    ", 1);

	sql_query("UPDATE orders SET
		status='".escape($_POST['status'])."'
		WHERE
		    id='".escape($_GET['id'])."' LIMIT 1
	");

	if( $_POST['status'] == 'dispatched' ){
		sql_query("UPDATE orders SET
			dispatched_date=NOW()
			WHERE
			    id='".escape($_GET['id'])."' LIMIT 1
		");

		$this->trigger_event('dispatched', array($order));
	}elseif( $_POST['status'] == 'refunded' ){
		sql_query("UPDATE orders SET
			refund_date=NOW()
			WHERE
			    id='".escape($_GET['id'])."' LIMIT 1
		");
	}
}

$order = sql_query("SELECT * FROM orders WHERE
	id='".escape($_GET['id'])."'
", 1);

if( !$order ){
	print 'order not found';
}else{
	$items = sql_query("SELECT * FROM order_items WHERE `order`='".escape($_GET['id'])."'");

	$details = '';
	foreach( $items as $k=>$item ){
		$details.=$item['name'].' x '.$item['quantity'].' @ £'.$item['cost']."\n"
		.$item['extras']."\n\n";
	}
?>
<div id="container">
<h1>Viewing order <span style="color:red"><?=$_GET['id'];?></span></h1>
<div align="center">

    <table width="420" border="1" cellspacing="0" cellpadding="4" align="center" style="margin: 5px">
    <tr>
    	<td style="padding: 10px">
    		<h2>Invoice Details</h2>
    		<p>
    		<p>Date <strong><?=dateformat('d/m/Y',$order['date']);?></strong></p>
    		<br>
    		<strong>Order ref:</strong>
    		<?=$order['id'];?><br><br>
    		<strong>Order status:</strong>
    		<?=$order['status'];?><br><br>
    		<strong>Name:</strong>
    		<?=$order['name'];?><br><br>
    		<strong>Address:</strong><br>
    		<?=nl2br($order['address']);?><br>
    		<?=$order['postcode'];?><br><br>
    		<strong>Email:</strong>
    		<?=$order['email'];?><br><br>
    		<strong>Tel:</strong>
    		<?=$order['tel'];?><br><br>
    		<strong>Mobile:</strong>
    		<?=$order['mobile'];?><br><br>
    		<strong>Comments:</strong>
    		<?=$order['comments'];?><br><br>
    		<strong>Details:</strong><br>
    		<?=nl2br(htmlentities($details));?><br>
    		<strong>Delivery:</strong>
    		&pound;<?=$order['delivery'];?><br>

    		<? if($order['offer']){ ?>
                <strong>Offer:</strong>
                <?=$order['offer'];?><br><br>
            <? } ?>
            <? if($order['discount']){ ?>
                <strong>Discount:</strong>
                &pound;<?=number_format($order['discount'],2);?><br><br>
            <? } ?>

    		<strong>Total:</strong> &pound;<?=number_format($order['total'],2);?><br>
            <br>

            <strong>Payment Method:</strong>
            <?=$order['method'];?><br><br>

    		<? if( $order['event_date'] ){ ?>
    			<p>Event date: <strong><?=dateformat('d/m/Y',$order['event_date']);?></strong></p>
    		<? } ?>

    		<? if( $order['organiser'] ){ ?>
    			<p>Organiser: <strong><?=$order['organiser'];?></strong></p>
    		<? } ?>

    		<? if( $order['status']=='dispatched' ){ ?>
    			<p>Dispatched on <strong><?=$order['dispatched_date'];?></strong></p>
    		<? } ?>

    		<? if( $order['status']=='refunded' ){ ?>
    			<p>Refunded on <strong><?=$order['refund_date'];?></strong></p>
    		<? } ?>

    		<form method="post">
    		Change status to:
    		<select name="status">
    			<option value=""></option>
    			<option value="dispatched">dispatched</option>
    			<option value="refunded">refunded</option>
    		</select>
    		<button type="submit">Save</button>
    		</form>

    		<br>
    		</p>
    	</td>
    </tr>
    </table>

</div>
<?
}
?>

<br>
<a href="?option=orders&cust=<?=$order['customer'];?>">Back to orders</a>