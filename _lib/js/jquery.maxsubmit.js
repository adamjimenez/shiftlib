/**
 * Copyright 2013-2014 Academe Computing Ltd
 * Released under the MIT license
 * Author: Jason Judge <jason@academe.co.uk>
 * Version: 1.2.1
 */
/**
 * jquery.maxsubmit.js
 *
 * Checks how many parameters a form is going to submit, and
 * gives the user a chance to cancel if it exceeds a set number.
 * PHP5.3+ has limits set by default on the number of POST parameters
 * that will be accepted. Parameters beyond that number, usually 1000,
 * will be silently discarded. This can have nasty side-effects in some
 * applications, such as editiong shop products with many variations
 * against a product, which can result in well over 1000 submitted
 * parameters (looking at you WooCommerce). This aims to provide some
 * level of protection.
 *
 */

(function($) {
	/**
	 * Set a trigger on a form to pop up a warning if the fields to be submitted
	 * exceed a specified maximum.
	 * Usage: $('form#selector').maxSubmit({options});
	 */
	$.fn.maxSubmit = function(options) {
		// this.each() is the wrapper for each form.
		return this.each(function() {

			var settings = $.extend({
				// The maximum number of parameters the form will be allowed to submit
				// before the user is issued a confirm (OK/Cancel) dialogue.

				max_count: 1000,

				// The message given to the user to confirm they want to submit anyway.
				// Can use {max_count} as a placeholder for the permitted maximum
				// and {form_count} for the counted form items.

				max_exceeded_message:
					'This form has too many fields for the server to accept.\n'
					+ ' Data may be lost if you submit. Are you sure you want to go ahead?',

				// The function that will display the confirm message.
				// Replace this with something fancy such as jquery.ui if you wish.

				confirm_display: function(form_count) {
					if (typeof(form_count) === 'undefined') form_count = '';
					return confirm(
						settings
							.max_exceeded_message
							.replace("{max_count}", settings.max_count)
							.replace("{form_count}", form_count)
					);
				}
			}, options);

			// Form elements will be passed in, so we need to trigger on
			// an attempt to submit that form.

			// First check we do have a form.
			if ($(this).is("form")) {
				$(this).on('submit', function(e) {
					// We have a form, so count up the form items that will be
					// submitted to the server.

					// For now, add one for the submit button.
					var form_count = $(this).maxSubmitCount() + 1;

					if (form_count > settings.max_count) {
						// If the user cancels, then abort the form submit.
						if (!settings.confirm_display(form_count)) return false;
					}

					// Allow the submit to go ahead.
					return true;
				});
			}

			// Support chaining.
			return this;
		});
	};

	/**
	 * Count the number of fields that will be posted in a form.
	 * If return_elements is true, then an array of elements will be returned
	 * instead of the count. This is handy for testing.
	 * TODO: elements without names will not be submitted.
	 * Another approach may be to get all input fields at once using $("form :input")
	 * then knock out the ones that we don't want. That would keep the same order as the
	 * items would be submitted.
	 */
	$.fn.maxSubmitCount = function(return_elements) {
		// Text fields and submit buttons will all post one parameter.

		// Find the textareas.
		// These will count as one post parameter each.
		var fields = $('textarea:enabled[name]', this).toArray();

		// Find the basic textual input fields (text, email, number, date and similar).
		// These will count as one post parameter each.
		// We deal with checkboxes, radio buttons sparately.
		// Checkboxes will post only if checked, so exclude any that are not checked.
		// There may be multiple form submit buttons, but only one will be posted with the
		// form, assuming the form has been submitted by the user with a button.
		// An image submit will post two fields - an x and y coordinate.
		fields = fields.concat(
			$('input:enabled[name]', this)
				// Data items that are handled later.
				.not("[type='checkbox']:not(:checked)")
				.not("[type='radio']")
				.not("[type='file']")
				.not("[type='reset']")
				// Submit form items.
				.not("[type='submit']")
				.not("[type='button']")
				.not("[type='image']")
				.toArray()
		);

		// Single-select lists will always post one value.
		fields = fields.concat(
			$('select:enabled[name]', this)
				.not('[multiple]')
				.toArray()
		);

		// Multi-select lists will post one parameter for each selected option.
		// The parent select is $(this).parent() with its name being $(this).parent().attr('name')
		$('select[multiple]:enabled[name] option:selected', this).each(function() {
			// We collect all the options that have been selected.
			fields = fields.concat(this);
		});

		// Each radio button group will post one parameter.
		// We assume all checked radio buttons will be posted.
		fields = fields.concat(
			$('input:enabled:radio:checked', this)
				.toArray()
		);

		// TODO: provide an option to return an array of objects containing the form field names,
		// types and values, in a form that can be compared to what is actually posted.
		if (typeof(return_elements) === 'undefined') return_elements = false;

		if (return_elements === true) {
			// Return the full list of elements for analysis.
			return fields;
		} else {
			// Just return the number of elements matched.
			return fields.length;
		}
	};
}(jQuery));

