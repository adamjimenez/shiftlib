<?php
if (true === isset($db_config['user']) || true === isset($db_connection)) {
    die('Error: database already configured');
}

// Check config perms
if (false === is_writable('_inc/config.php')) {
    die('Error: make sure _inc/config.php has 777 permissions and refresh');
}

if (true === isset($_POST['save'])) {
    $errors = validate($_POST, ['host', 'user', 'pass', 'name']);

    if (0 === count($errors)) {
        $connection = mysqli_connect($_POST['host'], $_POST['user'], $_POST['pass'], $_POST['name']);
        if (false === $connection) {
            die(mysqli_connect_error());
            $errors[] = 'unknown';
        }
    }

    if (0 !== count($errors)) {
        print json_encode($errors);
        exit;
    }

    if (true === isset($_POST['validate']) && true === (bool) $_POST['validate']) {
        print 1;
        exit;
    }

    $content = '<?php
#GENERAL SETTINGS
$db_config["host"] = "' . addslashes($_POST['host']) . '";
$db_config["user"] = "' . addslashes($_POST['user']) . '";
$db_config["pass"] = "' . addslashes($_POST['pass']) . '";
$db_config["name"] = "' . addslashes($_POST['name']) . '";

//fields in each section
$vars["fields"]["users"]=array(
    "name"=>"text",
    "surname"=>"text",
    "email"=>"email",
    "password"=>"password",
    "address"=>"textarea",
    "city"=>"text",
    "postcode"=>"text",
    "tel"=>"text",
    "admin"=>"select",
    "id"=>"id",
);

// sections in menu
$vars["sections"]=array(
    "users",
);

#OPTIONS
$opts["admin"]=array(
    "1"=>"admin",
    "2"=>"staff",
    "3"=>"guest",
);
?>';

    file_put_contents('_inc/config.php', $content);
    redirect('/admin');
}
?>

<?= load_js('cms'); ?>

<style>
    body {
        font-family: Arial;
    }
</style>

<h1>ShiftLib Installation</h1>
<p>Configure database</p>

<form method="post" class="validate">
    <input type="hidden" name="save" value="1">

    <label>Host:<br>
        <input type="text" name="host" placeholder="host" value="<?= $_POST['host'] ?: 'localhost'; ?>" autofocus="autofocus">
    </label>
    <br>
    <br>

    <label>Username:<br>
        <input type="text" name="user" placeholder="username" value="<?= $_POST['user'] ?: ''; ?>">
    </label>
    <br>
    <br>

    <label>Password:<br>
        <input type="text" name="pass" placeholder="password" value="<?= $_POST['pass'] ?: ''; ?>">
    </label>
    <br>
    <br>

    <label>Name:<br>
        <input type="text" name="name" placeholder="name" value="<?= $_POST['name'] ?: ''; ?>">
    </label>
    <br>
    <br>

    <button type="submit">Save</button>
</form>