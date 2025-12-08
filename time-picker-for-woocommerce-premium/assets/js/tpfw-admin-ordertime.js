(function($, data, wp) {
	$(function() {
		var $tbody = $('.wc-shipping-class-rows'),
			$save_button = $('.wc-shipping-class-save'),
			$row_template = wp.template('wc-shipping-class-row'),
			$blank_template = wp.template('wc-shipping-class-row-blank'),
			ShippingClass = Backbone.Model.extend({
				changes: {},
				logChanges: function(changedRows) {
					var changes = this.changes || {};
					_.each(changedRows, function(row, id) {
						changes[id] = _.extend(changes[id] || {
							term_id: id
						}, row);
					});
					this.changes = changes;
					this.trigger('change:classes');
				},
				discardChanges: function(id) {
					var changes = this.changes || {};
					delete changes[id];
					if (0 === _.size(this.changes)) {
						shippingClassView.clearUnloadConfirmation();
					}
				},
			}),
			ShippingClassView = Backbone.View.extend({
				rowTemplate: $row_template,
				initialize: function() {
					this.listenTo(this.model, 'change:classes', this.setUnloadConfirmation);
					this.listenTo(this.model, 'saved:classes', this.clearUnloadConfirmation);
					this.listenTo(this.model, 'saved:classes', this.render);
					$tbody.on('change', {
						view: this
					}, this.updateModelOnChange);
					$(document.body).on('click', '.wc-shipping-class-add', {
						view: this
					}, this.onAddNewRow);
					$(document.body).on('click', '.is-primary.woocommerce-save-button[type=submit]', {
						view: this
					}, this.onSubmit);
				},
				block: function() {
					$(this.el).block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				},
				unblock: function() {
					$(this.el).unblock();
				},
				render: function() {
					var classes = _.indexBy(this.model.get('classes'), 'term_id'),
						view = this;
					this.$el.empty();
					this.unblock();
					if (_.size(classes)) {
						classes = _.sortBy(classes, function(shipping_class) {
							return shipping_class.name;
						});
						$.each(classes, function(id, rowData) {
							view.renderRow(rowData);
						});
					} else {
						view.$el.append($blank_template);
					}
					return classes;
				},
				renderRow: function(rowData) {
					var view = this;
					view.$el.append(view.rowTemplate(rowData));
					view.initRow(rowData);
				},
				initRow: function(rowData) {
					var view = this;
					var $tr = view.$el.find('tr[data-id="' + rowData.term_id + '"]');
					_.each(tpfwOrdertimeLocalizeScript.categories, function(el) {
						$tr.find('.wc-shipping-class-cats').find('select.tpfw-multiple-select').append('<option value="' + el.cat_ID + '">' + el.name + '</option>');
					});
					_.each(tpfwOrdertimeLocalizeScript.tags, function(el) {
						$tr.find('.wc-shipping-class-tags').find('select.tpfw-multiple-select').append('<option value="' + el.term_id + '">' + el.name + '</option>');
					});
					$tr.find('select').each(function() {
						var $this = $(this);
						let values = ($(this).attr('value')).split(',');
						_.each(values, function(value, i, list) {
							$this.find('option[value="' + value + '"]').prop('selected', true);
						});
					});
					$tr.find('.view').show();
					$tr.find('.wc-shipping-class-edit').on('click', {
						view: this
					}, this.onEditRow);
					$tr.find('.wc-shipping-class-delete').on('click', {
						view: this
					}, this.onDeleteRow);
					$tr.find('.editing .wc-shipping-class-edit').trigger('click');
					$tr.find('.wc-shipping-class-cancel-edit').on('click', {
						view: this
					}, this.onCancelEditRow);
					if (true === rowData.editing) {
						$tr.addClass('editing');
						$tr.find('.wc-shipping-class-edit').trigger('click');
					}
				},
				onSubmit: function(event) {
					var rows = {};
					var elements = $tbody.find("tr");
					_.each(elements, function(element, i, list) {
						if ($(element).data('id') != undefined) {
							var td = [];
							var key = $(element).data('id');
							td.push(['term_id', key]);
							_.each($(element).find('td'), function(element2) {
								if ($(element2).find('input').length) {
									let temp = [];
									temp.push($(element2).find('input').data('attribute'), $(element2).find('input').val());
									td.push(temp);
								} else if ($(element2).find('select').length) {
									let temp = [];
									temp.push($(element2).find('select').data('attribute'), $(element2).find('select').val());
									td.push(temp);
								}
							});
							const entries = new Map(td);
							const obj_ = Object.fromEntries(entries);
							var obj = {};
							obj[key] = obj_;
							rows = $.extend(rows, obj);
						}
					}, this);
					document.getElementById('tpfwfixedordertimes').value = JSON.stringify(rows);
				},
				onAddNewRow: function(event) {
					event.preventDefault();
					var view = event.data.view,
						model = view.model,
						classes = _.indexBy(model.get('classes'), 'term_id'),
						changes = {},
						size = _.size(classes),
						newRow = _.extend({}, data.default_shipping_class, {
							term_id: 'new-' + size + '-' + Date.now(),
							editing: true,
							newRow: true
						});
					changes[newRow.term_id] = newRow;
					model.logChanges(changes);
					view.renderRow(newRow);
					$('.wc-shipping-classes-blank-state').remove();
				},
				onEditRow: function(event) {
					event.preventDefault();
					$(this).closest('tr').addClass('editing');
					$(this).closest('tr').find('.view').hide();
					$(this).closest('tr').find('.edit').show();
					event.data.view.model.trigger('change:classes');
				},
				onDeleteRow: function(event) {
					var view = event.data.view,
						model = view.model,
						classes = _.indexBy(model.get('classes'), 'term_id'),
						changes = {},
						term_id = $(this).closest('tr').data('id');
					event.preventDefault();
					if (classes[term_id]) {
						delete classes[term_id];
						changes[term_id] = _.extend(changes[term_id] || {}, {
							deleted: 'deleted'
						});
						model.set('classes', classes);
						model.logChanges(changes);
					}
					view.render();
				},
				onCancelEditRow: function(event) {
					var view = event.data.view,
						model = view.model,
						row = $(this).closest('tr'),
						term_id = $(this).closest('tr').data('id'),
						classes = _.indexBy(model.get('classes'), 'term_id');
					event.preventDefault();
					model.discardChanges(term_id);
					if (classes[term_id]) {
						classes[term_id].editing = false;
						row.after(view.rowTemplate(classes[term_id]));
						view.initRow(classes[term_id]);
					}
					row.remove();
				},
				setUnloadConfirmation: function() {
					this.needsUnloadConfirm = true;
					$save_button.removeAttr('disabled');
				},
				clearUnloadConfirmation: function() {
					this.needsUnloadConfirm = false;
					$save_button.attr('disabled', 'disabled');
				},
				unloadConfirmation: function(event) {
					if (event.data.view.needsUnloadConfirm) {
						event.returnValue = data.strings.unload_confirmation_msg;
						window.event.returnValue = data.strings.unload_confirmation_msg;
						return data.strings.unload_confirmation_msg;
					}
				},
				updateModelOnChange: function(event) {
					var model = event.data.view.model,
						$target = $(event.target),
						term_id = $target.closest('tr').data('id'),
						attribute = $target.data('attribute'),
						value = $target.val(),
						classes = _.indexBy(model.get('classes'), 'term_id'),
						changes = {};
					if (!classes[term_id] || classes[term_id][attribute] !== value) {
						changes[term_id] = {};
						changes[term_id][attribute] = value;
					}
					model.logChanges(changes);
				}
			}),
			shippingClass = new ShippingClass({
				classes: data.classes
			}),
			shippingClassView = new ShippingClassView({
				model: shippingClass,
				el: $tbody
			});
		if ($('#tpfwfixedordertimes').length) {
			shippingClassView.render();
		}
	});
})(jQuery, tpfwOrdertimeLocalizeScript, wp);