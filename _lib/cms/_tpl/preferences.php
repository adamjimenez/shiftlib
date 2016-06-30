<div class="col-sm-6">
	<form method="post">
		<input type="hidden" name="save" value="true" >
		<h3>Change Your Password:</h3>
		<input type="password" name="password" class="form-control showpassword"><br />
		<button type="submit" class="btn btn-default save-btn">Save</button>
	</form>
</div>

<script>

	$(function(){
	$(".showpassword").each(function(index,input) {
		var $input = $(input);
		$('<label class="showpasswordlabel btn btn-default"/>').append(
			$("<input type='checkbox' class='showpasswordcheckbox' />").click(function() {
				var change = $(this).is(":checked") ? "text" : "password";
				var rep = $("<input type='" + change + "' />")
					.attr("id", $input.attr("id"))
					.attr("name", $input.attr("name"))
					.attr('class', $input.attr('class'))
					.val($input.val())
					.insertBefore($input);
				$input.remove();
				$input = rep;
			 })
		).append($("<span/>").text(" Show Password")).insertAfter($input);
	});
});
</script>
