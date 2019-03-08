<?php
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
		$details.=$item['name'].' x '.$item['quantity'].' @ £'.$item['cost'];
		
		if ($item['extras']) {
			$details .= $item['extras'];
		}
		
		$details.= "\n";
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
    		<p><strong>Date:</strong> <?=dateformat('d/m/Y',$order['date']);?></p>
    		<br>
    		<strong>Order ref:</strong>
    		<?=$order['id'];?><br><br>
    		<strong>Order status:</strong>
    		<?=$order['status'];?><br><br>
    		
    		<?php if ($order['name']) { ?>
    		<strong>Name:</strong>
    		<?=$order['name'];?><br><br>
    		<?php } ?>
    		
    		<?php if ($order['address']) { ?>
    		<strong>Address:</strong><br>
    		<?=nl2br($order['address']);?><br>
    		<?=$order['postcode'];?><br><br>
    		<?php } ?>
    		
    		<strong>Email:</strong>
    		<?=$order['email'];?><br><br>
    		
    		<?php if ($order['tel']) { ?>
    		<strong>Tel:</strong>
    		<?=$order['tel'];?><br><br>
    		<?php } ?>
    		
    		<?php if ($order['mobile']) { ?>
    		<strong>Mobile:</strong>
    		<?=$order['mobile'];?><br><br>
    		<?php } ?>
    		
    		<?php if ($order['comments']) { ?>
    		<strong>Comments:</strong>
    		<?=$order['comments'];?><br><br>
    		<?php } ?>
    		
    		<strong>Details:</strong><br>
    		<?=nl2br(htmlentities($details));?><br>
    		
    		<?php if ($order['note']) { ?>
    		<strong>Note:</strong>
    		<?=$order['note'];?><br><br>
    		<?php } ?>
    		
    		<strong>Delivery:</strong>
    		&pound;<?=number_format($order['delivery'], 2);?><br>

    		<?php if($order['offer']){ ?>
                <strong>Offer:</strong>
                <?=$order['offer'];?><br><br>
            <?php } ?>
            
            <?php if($order['discount']){ ?>
                <strong>Discount:</strong>
                &pound;<?=number_format($order['discount'], 2);?><br><br>
            <?php } ?>

    		<strong>Total:</strong> &pound;<?=number_format($order['total'], 2);?><br>
            <br>

            <strong>Payment Method:</strong>
            <?=$order['method'];?><br><br>
            
            <?php if($order['txn_id']){ ?>
            <strong>Txn id:</strong>
            <?=$order['txn_id'];?><br><br>
            <?php } ?>

    		<?php if( $order['event_date'] ){ ?>
    			<p>Event date: <strong><?=dateformat('d/m/Y', $order['event_date']);?></strong></p>
    		<?php } ?>

    		<?php if( $order['session'] ){ ?>
    			<p>Session: <strong><?=$order['session'];?></strong></p>
    		<?php } ?>

    		<?php if( $order['organiser'] ){ ?>
    			<p>Organiser: <strong><?=$order['organiser'];?></strong></p>
    		<?php } ?>

    		<?php if( $order['status']=='dispatched' ){ ?>
    			<p>Dispatched on <strong><?=$order['dispatched_date'];?></strong></p>
    		<?php } ?>

    		<?php if( $order['status']=='refunded' ){ ?>
    			<p>Refunded on <strong><?=$order['refund_date'];?></strong></p>
    		<?php } ?>

    		<form method="post">
    		Change status to:
    		<select name="status">
    			<option value=""></option>
    			<option value="paid">paid</option>
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
<?php
}
?>

<br>
<a href="?option=shop_orders&cust=<?=$order['customer'];?>">Back to orders</a>