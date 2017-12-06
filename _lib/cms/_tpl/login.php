<?
if( $auth->user['admin'] ){
	redirect('/admin');
}
?>
<style>
    .header-row {
      display: none;
    }
    .leftcol {
      display: none;
    }
    .content-wrapper {
        min-height: 900px;
        z-index: 500;
        margin-left: 0px;
        margin-top: 0px;
    }
    .form-container {
        position: absolute;
        top: 150px;
        left: 50%;
        width: 300px;
        margin-left: -150px;
        height: 378px;
        padding: 10px;
        border-radius: 10px;
        background: #333;
    }
    .form-container-inner {
        
        border: 3px solid #fff;
        width: 294px;
        float: left;
        background: #fff;
        height: 372px;
        border-radius: 10px;
    }
    #content .inner {
        background: none;
    }
    .input-field {
        float: left;
        width: 284px;
        padding: 5px;
        background: none;
        border-radius: 10px;
        border: 1px solid #333;
    }
    
    
    @import url(https://fonts.googleapis.com/css?family=Roboto:300);
    
    .login-page {
      padding: 8% 0 0;
      margin: auto;
    }
    .form {
      position: relative;
      z-index: 1;
      background: #FFFFFF;
      max-width: 360px;
      margin: 0 auto 100px;
      padding: 45px 45px 25px 45px;
      text-align: center;
      box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
    }
    .form input {
      font-family: "Roboto", sans-serif;
      outline: 0;
      background: #f2f2f2;
      width: 100%;
      border: 0;
      margin: 0 0 15px;
      padding: 15px;
      box-sizing: border-box;
      font-size: 14px;
    }
    .form button {
      font-family: "Roboto", sans-serif;
      text-transform: uppercase;
      outline: 0;
      background: #4CAF50;
      width: 100%;
      border: 0;
      padding: 15px;
      color: #FFFFFF;
      font-size: 14px;
      -webkit-transition: all 0.3 ease;
      transition: all 0.3 ease;
      cursor: pointer;
    }
    .form button:hover,.form button:active,.form button:focus {
      background: #43A047;
    }
    .form .message {
      margin: 15px 0 0;
      color: #b3b3b3;
      font-size: 12px;
    }
    .form .message a {
      color: #4CAF50;
      text-decoration: none;
    }
    .form .register-form {
      display: none;
    }
    .container {
      position: relative;
      z-index: 1;
      max-width: 300px;
      margin: 0 auto;
    }
    .container:before, .container:after {
      content: "";
      display: block;
      clear: both;
    }
    .container .info {
      margin: 50px auto;
      text-align: center;
    }
    .container .info h1 {
      margin: 0 0 15px;
      padding: 0;
      font-size: 36px;
      font-weight: 300;
      color: #1a1a1a;
    }
    .container .info span {
      color: #4d4d4d;
      font-size: 12px;
    }
    .container .info span a {
      color: #000000;
      text-decoration: none;
    }
    .container .info span .fa {
      color: #EF3B3A;
    }
    .form input[type=checkbox] {
        float: none;
        width: auto;
        padding: 10px;
        
    }
    .login-page legend {
        
        float: left;
        width: 100%;
        text-align: center;
        font-size: 22px;
        color: #333;
        margin-bottom: 30px;
        background: none;
        margin-top: -25px;
    }
    
    
</style>

<div class="login-page">
  <div class="form">
      
      <legend>Sign In</legend>
    
    <form class="login-form validate" method="post" id="login_form" action="/admin?option=login">
        <input type="hidden" name="login" value="1">
      <input type="text" name="email" id="email" placeholder="username"/>
          <? if( in_array('email',$auth->errors) ){ ?>
              <p style="color:red;">Username is required</p>
              <br>
          <? } ?>
          
      <input type="password" name="password" id="password" placeholder="password"/>
      <? if( in_array('password',$auth->errors) ){ ?>
      <p style="color:red;">Password is required</p>
      <br>
      <? } ?>
      
      
      <button>login</button>
      <p class="message"><input type="checkbox" name="remember" value="1"> Remember me?</p>
    </form>
  </div>
</div>


<script type="text/javascript">
$(function() {
	$('#email').focus();
});
</script>
