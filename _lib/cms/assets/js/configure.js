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

			app.cmd({
				'cmd': 'move_field',
				'table': table,
				'field': field,
				'after': after,
			});
		}
	});
}

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
	$('select[multiple]:enabled[name] option:selected', formEl).each(function() {
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

// setup vue
const app = Vue.createApp({
	data() {
		return {
			tables: [],
			sections: [],
			subsections: [],
			options: [],
		}
	},
	updated() {
		initSortables();
	},
	methods: {
		cmd: async function(data, cb) {
			let formData;
			
			if (data.get) {
				formData = data;
			} else {
				formData = new FormData();
				
				for (const [key, value] of Object.entries(data)) {
					formData.append(key, value);	
				};
			}
		
			const response = await fetch(
				location.href, {
					method: "post",
					body: formData,
				}
			);
			const text = await response.text();
				
			try {
				const result = JSON.parse(text);
				
				if (false === result.success) {
					alert(result.error);
				}
				
				if (cb) {
					cb(result);
				}
			} catch (error) {
				console.log("error", text);
				alert(text);
			}
		},
		fetchData: async function() {
			let self = this;
			
			this.cmd({
				'cmd': 'get',
			}, function (result) {
				self.tables = result.tables;
			});
		},
		addTable: function () {
			$('.renameTable').find('form').trigger('reset');
			$('.renameTable').modal('show');
			$('.renameTable [name="cmd"]').val('add_table');
			$('.renameTable [name="table"]').val('');
			$('.renameTable [name="name"]').focus();
		},
		renameTable: function (table) {
			$('.renameTable').modal('show');
			$('.renameTable [name="cmd"]').val('rename_table');
			$('.renameTable [name="table"]').val(table);
			$('.renameTable [name="name"]').val(table).focus();
		},
		saveTable: function () {
			let self = this;
			let el = $('.renameTable');
			
			this.cmd(new FormData(el.find('form').get(0)), function(data) {
				self.fetchData();
				el.closest('.modal').modal('hide');
			});
		},
		deleteTable: function(table) {
			var result = confirm('Drop ' + table);
			if (!result) {
				return false;
			}
			
			let self = this;

			this.cmd({
				'cmd': 'delete_table',
				'table': table,
			}, function(data) {
				self.fetchData();
			});
		},
		addField: function (table) {
			$('.editField').find('form').trigger('reset');
			$('.editField').modal('show');
			$('.editField [name="cmd"]').val('add_field');
			$('.editField [name="table"]').val(table);
			$('.editField [name="type"]').val('text');
			$('.editField [name="name"]').focus();
		},
		editField: function (field, table) {
			$('.editField').find('form').trigger('reset');
			$('.editField').modal('show');
			$('.editField [name="cmd"]').val('edit_field');
			$('.editField [name="table"]').val(table);
			$('.editField [name="field"]').val(field.name);
			$('.editField [name="name"]').val(field.name).focus();
			$('.editField [name="type"]').val(field.type);
			$('.editField [name="label"]').val(field.label);
			$('.editField [name="required"]').prop('checked', field.required > 0);
		},
		saveField: function () {
			let self = this;
			let el = $('.editField');

			this.cmd(new FormData(el.find('form').get(0)), function(data) {
				self.fetchData();
				el.modal('hide');
			});
		},
		delField: function(column, table) {
			var result = confirm('Drop ' + column + ' from ' + table);
			if (!result) {
				return false;
			}
			
			let self = this;

			this.cmd({
				'cmd': 'delete_field',
				'table': table,
				'column': column,
			}, function(data) {
				self.fetchData();
			});
		},
		addSection: function () {
			$('.addSectionModal').modal('show');
		},
		saveSection: function () {
			let self = this;
			let el = $('.addSectionModal');
			
			var val = el.find('form').find('[name="section"]').val();
			$('.addSectionModal').modal('hide');
			
			this.sections.push(val);
		},
		deleteSection: function (section) {
			var result = confirm('Delete ' + section);
			if (!result) {
				return false;
			}
			
			const index = this.sections.indexOf(section);
			if (index > -1) { // only splice array when item is found
				this.sections.splice(index, 1);
			}
		},
		addSubsection: function (section) {
			$('.addSubsectionModal').find('[name="section"]').val(section);
			$('.addSubsectionModal').modal('show');
		},
		saveSubsection: function () {
			var el = $('.addSubsectionModal');
			var val = el.find('form').find('[name="subsection"]').val();
			var section = el.find('[name="section"]').val();
			
			if (!this.subsections[section]) {
				this.subsections[section] = [];
			}
			
			this.subsections[section].push(val);
	
			el.modal('hide');
		},
		deleteSubsection: function (subsection, section) {
			var result = confirm('Delete ' + subsection);
			if (!result) {
				return false;
			}
			
			const index = this.subsections[section].indexOf(subsection);
			if (index > -1) { // only splice array when item is found
				this.subsections[section].splice(index, 1);
			}
			
		},
		addOption: function () {
			this.options.push({
				name: '',
				value: '',
				list: false,
			});
		},
		toggleOption: function (option) {
			option.list = !option.list;
		},
		deleteOption: function (option) {
			var result = confirm('Delete ' + option.name);
			if (!result) {
				return false;
			}
			
			const index = this.options.indexOf(option);
			if (index > -1) { // only splice array when item is found
				this.options.splice(index, 1);
			}
		},
	},
	async mounted() {
		this.sections = vars.sections;
		this.subsections = vars.subsections ? vars.subsections : {};
		
		// populate options
		for (let [key, value] of Object.entries(vars.options)) {
			let list = false;
			let val = '';

			if (Array.isArray(value)) {
				list = true;
				
				value.forEach(function(item) {
					val += item + "\n";
				})
			} else if (typeof value === 'object') {
				list = true;
				
				for (let [k, v] of Object.entries(value)) {
					val += k.replace('#', '') + '=' + v + "\n";
				}
			} else {
				val = value.replaceAll(' ', '_');
			}
				
			val = val.trim();
			
			this.options.push({
				name: key,
				value: val,
				list: list,
			});
		}
		
		console.log(this.options);
		
		// initiate bootstrap tabs
		$("#tabs").tabs();
	
		// toggle fields
		$('body').on('click', '.toggle_section',
			function() {
				$(this).find('i').toggleClass('fa-rotate-90');
				$(this).closest('.item').find('.settings').slideToggle();
			});
	
		// replace dashes with spaces
		$('body').on('blur', '.field',
			function() {
				var field = $(this).closest('.item').find('.name');
	
				if (field.val() === '') {
					field.val($(this).val().replace('-', ' '))
				}
			})
	
		// don't allow dashes or underscores in section names
		$('body').on('blur', '.name, .subsection',
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
		
		await this.fetchData();
	}
}).mount("#app");