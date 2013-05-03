<?
if( $auth->user['admin'] ){
	redirect('/admin');
}
?>

<div align="center" style="margin:100px auto;">
    <form method="post" id="login_form">
    <input type="hidden" name="login" value="1">
    <fieldset>
    <legend>Sign in</legend>
    <table>
    <tr>
    	<td valign="top"><label for="email">Username:</label></td>
    	<td valign="top"><input type="text" name="email" id="email" />
    		<? if( in_array('email',$auth->errors) ){ ?>
    		<p style="color:red;">Username is required</p>
    		<br />
    		<? } ?>
    	</td>
    </tr>
    <tr>
    	<td valign="top"><label for="password">Password:</label></td>
    	<td><input type="password" name="password" id="password" />
    		<? if( in_array('password',$auth->errors) ){ ?>
    		<p style="color:red;">Password is required</p>
    		<br />
    		<? } ?>

    		<? if( in_array('login incorrect',$auth->errors) ){ ?>
    		<p style="color:red;">Login incorrect</p>
    		<? } ?>
    	</td>
    </tr>
    </table>
    <label><input type="checkbox" name="remember" value="1" checked="checked"> Remember me on this computer.</label><br>
    <br />
    <p><button type="submit">Sign in</button></p>
    </fieldset>
    </form>
</div>

<script type="text/javascript">
$(function() {
	$('#email').focus();
});
</script>
