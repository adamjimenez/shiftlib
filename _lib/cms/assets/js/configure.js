console.log(vars);

function initSortables() {

	$(".subsections").sortable({
		handle: '.handle',
		opacity: 0.5,
		items: ".draggable",
		axis: 'y'
	});

	$("#sections").sortable({
		handle: '.handle',
		opacity: 0.5,
		items: ".draggableSections",
		axis: 'y'
	});

	$(".fields").sortable({
		handle: '.handle',
		opacity: 0.5,
		items: ".draggable",
		axis: 'y',
		stop: function(event, ui) {
			var table = ui.item.closest('.table').find('.table_name').text();
			var field = ui.item.find('.field_name').text();
			var after = ui.item.closest('.field').prev().find('.field_name').text();

			cmd({
				'cmd': 'move_field',
				'table': table,
				'field': field,
				'after': after,
			});
		}
	});
}

function populate_sections() {
	// populate sections
	Object.entries(vars.sections).forEach(entry => {
		let key = entry[0];
		let value = entry[1];

		count.sections++;
		var html = $('#sectionTemplate').html()
		.split('{$count}').join('[]');

		var row = $(html).appendTo($('#sections>.items'));

		row.find('.name').val(value);

		// subsections
		if (vars.subsections && vars.subsections[value]) {
			vars.subsections[value].forEach(function(item) {
				count.subsections++;
				var html = $('#sectionTemplate').html()
				.split('{$count}').join('[' + value + '][]');

				var subsectionRow = $(html).appendTo(row.find('.subsections>.items'));
				subsectionRow.find('input').val(item);
			});
		}
	});

	// populate dropdowns
	Object.entries(vars.options).forEach(entry => {
		let key = entry[0];
		let value = entry[1];

		count.options++;
		var html = $('#dropdownTemplate').html().split('{$count}').join(count.options);
		var row = $(html).appendTo($('#dropdowns .items'));

		// add table options
		Object.entries(tables).forEach(entry => {
			let table = entry[0];

			$('<option value="' + table + '">' + table + '</option>').appendTo(row.find('.section'));
		});

		if (typeof value == "string") {
			row.removeClass('list').find('.section').val(value).prop('disabled', false).show();
			row.find('textarea').prop('disabled', true);
		} else {
			val = '';
			if (Array.isArray(value)) {
				val = '';
				value.forEach(function(item) {
					val += item + "\n";
				})
			} else {
				Object.entries(value).forEach(item => {
					val += item[0] + '=' + item[1] + "\n";
				});
			}

			row.find('textarea').val(val.trim());
		}

		row.find('.name').val(key);
	});

	initSortables();
}

function cmd(data, cb) {
	$.ajax({
		method: "POST",
		data: data,
		dataType: 'json'
	})
	.done(function(response) {
		console.log(response);

		if (response.error) {
			alert(response.error);
		} else {
			cb(response);
		}
	})
	.fail(function(jqXHR, textStatus) {
		alert("error: " + textStatus);
	});
}

var tables;
function load_tables(cb) {
	cmd({
		'cmd': 'get'
	},
		function(data) {
			$('.table').remove();

			tables = data.tables;

			// populate tables
			Object.entries(data.tables).forEach(entry => {
				let table = entry[0];
				let field = entry[1];

				count.tables++;
				var html = $('#tableTemplate').html()
				.split('{$count}').join(count.tables);

				var row = $(html).appendTo($('#tables>.items'));

				// display
				row.find('.table_name').text(table).attr('data-name', table);

				// fields
				Object.entries(field).forEach(item => {
					let name = item[0];
					let field = item[1];

					//console.log(field)

					count.fields++;
					var html = $('#fieldTemplate').html();

					var fieldRow = $(html).appendTo(row.find('.fields>.items'));
					fieldRow.find('.field_name').text(field.name);

					fieldRow.find('.label').text(field.label);

					fieldRow.attr('data-required', field.required);

					if (field.type) {
						switch (field.type) {
							case 'int':
								field.type = 'integer';
								break;
							case 'number':
								field.type = 'decimal';
								break;
							case 'parent':
								field.type = 'select_parent';
								break;
							case 'phpupload':
								field.type = 'upload';
								break;
							case 'phpuploads':
								field.type = 'uploads';
								break;
						}

						fieldRow.find('.type').text(field.type.replace('-', '_'));
					}
				});
			});

			initSortables();

			if (cb) {
				cb();
			}
		});
}


$(function () {
	$("#tabs").tabs();

	load_tables(populate_sections);

	$('body').on('click', '.table_name', function() {
		var name = $(this).text();

		$('.renameTable').modal('show');
		$('.renameTable [name="cmd"]').val('edit_table');
		$('.renameTable [name="table"]').val(name);
		$('.renameTable [name="name"]').val(name).focus();
	});

	$('body').on('click', '.renameTable .save', function() {
		var el = $(this);
		cmd($(this).closest('form').serialize(), function(data) {
			load_tables();
			el.closest('.modal').modal('hide');
		});
	});

	$('body').on('click', '.field_name', function() {
		var table = $(this).closest('.table').find('.table_name').text();
		var field = $(this).text();
		var row = $(this).closest('.field');
		
		$('.editField').find('form').trigger('reset');
		$('.editField').modal('show');
		$('.editField [name="cmd"]').val('edit_field');
		$('.editField [name="table"]').val(table);
		$('.editField [name="field"]').val(field);
		$('.editField [name="name"]').val(field).focus();
		$('.editField [name="type"]').val(row.find('.type').text());
		$('.editField [name="label"]').val(row.find('.label').text());
		$('.editField [name="required"]').prop('checked', row.attr('data-required') > 0);
	});

	$('body').on('click', '.editField .save', function() {
		var el = $(this);
		cmd($(this).closest('form').serialize(), function(data) {
			load_tables(function() {
				// reopen section
				var table = $('.editField [name="table"]').val();
				$('.table_name[data-name="' + table + '"]').closest('.item').find('.toggle_section').click();
			});
			el.closest('.modal').modal('hide');
		});
	});

	$('body').on('click', '.addTable', function() {
		var name = $(this).text();

		$('.renameTable').find('form').trigger('reset');
		$('.renameTable').modal('show');
		$('.renameTable [name="cmd"]').val('add_table');
		$('.renameTable [name="table"]').val('');
		$('.renameTable [name="name"]').focus();
	});

	$('body').on('click', '.addSection', function() {
		$('.addSectionModal').modal('show');

		// add options
		$('.addSectionModal [name="section"] option').remove();

		Object.entries(tables).forEach(entry => {
			let table = entry[0];

			$('<option value="' + table + '">' + table + '</option>').appendTo($('.addSectionModal [name="section"]'));
		});
	});

	$('body').on('click', '.addSectionModal .save', function() {
		var el = $(this);
		var val = el.closest('form').find('[name="section"]').val();

		count.sections++;
		var html = $('#sectionTemplate').html()
		.split('{$count}').join(count.sections);

		var row = $(html).appendTo($('#sections>.items'));

		initSortables();
		$('.addSectionModal').modal('hide');

		row.find('input').val(val).focus();
	});

	$('body').on('click', '.addSubsection', function() {
		var el = $(this);

		$('.addSubsectionModal').modal('show');

		// add options
		$('.addSubsectionModal [name="subsection"] option').remove();

		//$('.addSubsectionModal [name="section"]').val(el.attr('data-section_id'));
		
		$('.addSubsectionModal').data('parent', el.closest('.subsections').children('.items'));

		Object.entries(tables).forEach(entry => {
			let table = entry[0];

			$('<option value="' + table + '">' + table + '</option>').appendTo($('.addSubsectionModal [name="subsection"]'));
		});
	});

	$('body').on('click', '.addSubsectionModal .save', function() {
		var el = $(this);

		var val = el.closest('form').find('[name="subsection"]').val();
		//var parent = $('.addSubsection[data-section_id="' + section + '"]').parent().find('.items');
		var parent = $('.addSubsectionModal').data('parent');
		var section = parent.closest('.section').find('.name').val();

		count.subsections++;
		var html = $('#sectionTemplate').html()
		.split('{$count}').join('[' + section + '][]');

		var row = $(html).appendTo(parent);
		row.find('input').val(val).first().focus();

		$('.addSubsectionModal').modal('hide');
	});


	$('body').on('click', '.toggle_list_type', function () {
		var row = $(this).closest('.item');

		if (row.hasClass('list')) {
			row.find('.section').prop('disabled', false).show();
			row.find('textarea').prop('disabled', true).hide();
			row.removeClass('list');
		} else {
			row.find('.section').prop('disabled', true).hide();
			row.find('textarea').prop('disabled', false).show();
			row.addClass('list');
		}
	});

	$('body').on('click',
		'.del_row',
		function () {
			var result = confirm('Are you sure?');
			if (!result) {
				return false;
			}

			$(this).closest('.item').remove();
		});

	$('body').on('click',
		'.delTable',
		function () {
			var result = confirm('Are you sure?');
			if (!result) {
				return false;
			}

			cmd({
				'cmd': 'delete_table',
				'table': $(this).closest('.table').find('.table_name').text(),
			}, function(data) {
				load_tables();
				$(this).closest('.item').remove();
			});
		});

	$('body').on('click',
		'.delField',
		function () {
			var result = confirm('Are you sure?');
			if (!result) {
				return false;
			}

			cmd({
				'cmd': 'delete_field',
				'table': $(this).closest('.table').find('.table_name').text(),
				'column': $(this).closest('.field').find('.field_name').text(),
			}, function(data) {
				load_tables();
				$(this).closest('.item').remove();
			});
		});

	$('body').on('click',
		'.addField',
		function() {
			var table = $(this).closest('.table').find('.table_name').text();

			$('.editField').find('form').trigger('reset');
			$('.editField').modal('show');
			$('.editField [name="cmd"]').val('add_field');
			$('.editField [name="table"]').val(table);
			$('.editField [name="type"]').val('text');
			$('.editField [name="name"]').focus();
		});

	$('body').on('click',
		'.addDropdown',
		function() {
			count.options++;
			var html = $('#dropdownTemplate').html()
			.split('{$count}').join(count.options);
			var row = $(html).appendTo($('#dropdowns .items'));
			row.find('input').focus();

			// add table options
			Object.entries(tables).forEach(entry => {
				let table = entry[0];

				$('<option value="' + table + '">' + table + '</option>').appendTo(row.find('.section'));
			});
		});

	$('body').on('click',
		'.toggle_section',
		function() {
			$(this).find('i').toggleClass('fa-rotate-90');
			$(this).closest('.item').find('.settings').slideToggle();
		});

	$('body').on('blur',
		'.field',
		function() {
			var field = $(this).closest('.item').find('.name');

			if (field.val() === '') {
				field.val($(this).val().replace('-', ' '))
			}
		})

	// don't allow dashes or underscores in section names
	$('body').on('blur',
		'.name, .subsection',
		function() {
			$(this).val($(this).val().split("-").join(" ").split("_").join(" ").trim())
		})

	// check field count doesn't exceed phps max allowed input setting
	$('form[method*=post]').on('submit',
		function(e) {
			if (post_count(this) > max_input_vars) {
				e.preventDefault();
				alert('Save aborted: This form has too many fields for the server to accept.');
			}
		})

	// resize textarea on tab change
	$('a[data-toggle="pill"]').on('shown.bs.tab',
		function (e) {
			$('textarea.autosize').trigger('autosize.resize');
		});

});

/**
* Count the number of fields that will be posted in a form.
*/
function post_count(formEl) {
	// These will count as one post parameter each.
	var fields = $('textarea:enabled[name]',
		formEl).toArray();

	// Find the basic textual input fields (text, email, number, date and similar).
	fields = fields.concat(
		$('input:enabled[name]',
			formEl)
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
		$('select:enabled[name]',
			formEl)
		.not('[multiple]')
		.toArray()
	);

	// Multi-select lists will post one parameter for each selected option.
	$('select[multiple]:enabled[name] option:selected',
		formEl).each(function() {
			// We collect all the options that have been selected.
			fields = fields.concat(formEl);
		});

	// Each radio button group will post one parameter.
	fields = fields.concat(
		$('input:enabled:radio:checked',
			formEl)
		.toArray()
	);

	return fields.length;
}