$.widget( "custom.list", {
	// default options
	options: {
		url: null,
		template: null,
		addButton: null,
		removeButton: null
	},
 
	// The constructor
	_create: function() {
		var self = this;
		this.newValueId = 0;
		
		if (this.options.addButton) {
			$('body').on('click', this.options.addButton, function(e) {
				e.preventDefault();
				self.addItem($(this).data('target'), $(this).data('template'));
			});
		}
	},
	addItem: function(data) {
		this.newValueId--;
		this.xhr = null;
		
		var row = $($(this.options.template).html()).appendTo(this.element);
		if (data) {
			for (var key in data) {
				var field = row.find('.' + key);
				if (field.attr('type') === 'checkbox') {
					field.prop('checked', data[key] == '1');
				} else {
					field.val(data[key]);
				}
			}
		}
		row.find('[data-remove]').click(function(e) {
			e.preventDefault();
			$(e.target).closest('tr').remove();
		});
	},
	addItems: function(data) {
		var self = this;
		data.forEach(function(item) {
			self.addItem(item);
		});
	},
	removeAll: function() {
		this.element.children().remove();
	},
	get: function(data) {
		var self = this;
		
		if (this.xhr) {
			this.xhr.abort();
		}
		
		return this.xhr = $.ajax({
			dataType: "json",
			url: this.options.url,
			data: data,
			success: function(data) {
				self.removeAll();
				self.addItems(data.items);
			}
		});
	}
});