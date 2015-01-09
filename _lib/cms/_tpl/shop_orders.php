<?
if( !$shop_enabled ){
    die('shop is not enabled');
}

$cust = sql_query("SELECT * FROM users WHERE id='".escape($_GET['cust'])."'", 1);

$query = "SELECT * FROM orders
    WHERE
    	status!='pending' AND
    	status!='deleted'
";

$p = new paging( $query, 20, 'date', false);

$paging = $p->get_paging();
$orders = sql_query($p->query);
?>
<div id="container">
    <h1>Previous orders <span style="color:red"><?=$cust['name'];?> <?=$cust['surname'];?></span></h1>

    <div align="center">
        <form method="get">
        <input type="hidden" name="option" value="shop_order">
        <input type="text" name="id">
        <button type="submit">Lookup order</button>
        </form>

        <table width="420" border="1" cellspacing="0" cellpadding="4" align="center" style="margin:5px" class="box">
        <tr>
        	<th>Date</th>
        	<th>Name</th>
        	<th>Ref</th>
        	<th>Amount</th>
        	<th>Status</th>
        </tr>
        <?
        if( count($orders) ){
        	foreach( $orders as $k=>$v ){
        ?>
        <tr>
        	<td><?=dateformat('d-m-Y',$v['date']);?></td>
        	<td><?=$v['name'];?></td>
        	<td><a href="?option=shop_order&id=<?=$v['id'];?>"><?=$v['id'];?></a></td>
        	<td>&pound;<?=number_format(($v['total']),2);?></td>
        	<td><?=$v['status'];?></td>
        </tr>
        <?
        	}
        }else{
        ?>
        <tr>
        	<td colspan="5">No previous orders</td>
        </tr>
        <?
        }
        ?>
        </table>

        <p>
            <?=$paging;?>
        </p>
    </div>
</div>