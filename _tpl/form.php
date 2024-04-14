<?php
// handle form submission
if (count($_POST)) {
    // set cms section for fields and validation
    $cms->set_section('enquiries');
    
    // handle ajax form validation
    $cms->submit_handler();
}
?>

<h1>Contact form</h1>
<p>
    This form is validated with ShiftLib using an ajax request.<br>
    The result is shown using a <a href="https://handlebarsjs.com/" target="_blank">handlebars template</a>.
</p>

<form method="post" sl-validate sl-hide sl-target="#thanks">
    <p>
        <input type="text" name="name" placeholder="name">
    </p>
    <p>
        <input type="email" name="email" placeholder="email">
    </p>
    <p>
        <textarea name="enquiry" placeholder="enquiry"></textarea>
    </p>
    <button type="submit">Submit</button>
</form>

<div id="thanks" style="display: none;">
    <p>
        Thanks {{ name }}, we will be in touch..
    </p>
</div>
