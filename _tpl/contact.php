<?php
//set cms section for fields and validation
$cms->set_section('enquiries');

//handle form submission
if (isset($_POST['save'])) {
    $cms->submit(true); //true to send an email notification to admin
}
?>

<h1>Contact form</h1>
<?php if ($cms->saved) {
    ?>
    <p>
        Thanks, we will be in touch..
    </p>
    <?php
} else {
    ?>
    <form method="post" class="validate">
        <input type="hidden" name="save" value="1">
        <p>
            <?=$cms->get_field('name', 'placeholder="your name"'); ?>
        </p>
        <p>
            <?=$cms->get_field('email', 'placeholder="your email"'); ?>
        </p>
        <p>
            <?=$cms->get_field('enquiry', 'placeholder="your enquiry"'); ?>
        </p>
        <button type="submit">Submit</button>
    </form>
    <?php
} ?>