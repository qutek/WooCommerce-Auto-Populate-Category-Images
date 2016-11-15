(function($){

	// default
	var i,
		items_to_process = [],
		items_total = items_to_process.length,
		items_count = 0,
		items_percent = 0,
		items_successes = 0,
		items_errors = 0,
		items_failedlist = '',
		items_resulttext = '',
		items_timestart = new Date().getTime(),
		items_timeend = 0,
		items_totaltime = 0,
		items_continue = true;

	var ajaxUrl = WAPCI.ajax_url,
		ajaxNonce = WAPCI.nonce;

	WAPCI_Populate = function() {
		var self = this;
		var button = false;
		var default_text = '';

		self.init = function() {
			$(document).on('click', '#wp-admin-bar-wapci-populate a', self.prepare);
			$(document).on('click', '#show-populate-status-link', self.toggle_wrapper);
		}
		
		self.prepare = function(e) {
			e.preventDefault();

			self.button = $(this);
			self.default_text = $(this).text();

			// self.build_container();
			
			var r = confirm("Are you sure to process ?");

			if (r == true) {
			    self.process_ajax();
			}
			
		};

		self.process_ajax = function(e){
			// alert(ajaxNonce);

			self.button.text('Getting product categories..');

			$.ajax( {
				dataType: "json",
				data: {
					action: 'wapci_get_product_cats',
					nonce: ajaxNonce,
				},
				type:     'post',
				url:      ajaxUrl,
				nonce: ajaxNonce,
				success: function(data) {

					if(data.status == 'success'){

						self.button.text(data.message + ' ( Processing.. )');
						// console.log(data);

						// reset vars and set items
						self.reset_items_to_process(data.items);

						self.populate(items_to_process.shift());

					} else {
						alert(data.message);
					}
	              	
              	}
			} );
		};
		
		self.populate = function(id) {
			$.ajax({
				type: 'POST',
				dataType: "json",
				url: ajaxUrl,
				data: { 
					action: "wapci_populate", 
					id: id,
					nonce: ajaxNonce
				},
				success: function( response ) {
					// console.log(response);

					if ( response !== Object( response ) || ( typeof response.status === "undefined" && typeof response.message === "undefined" ) ) {
						response = new Object;
						response.status = 'failed';
						response.message = "Something went wrong";
					}

					if ( response.status == 'success' ) {
						self.update_status( id, true, response );
					}
					else {
						self.update_status( id, false, response );
					}

					if ( items_to_process.length && items_continue ) {
						self.populate(items_to_process.shift());
					}
					else {
						self.finish_up();
					}
				},
				error: function( response ) {
					self.update_status( id, false, response );

					if ( items_to_process.length && items_continue ) {
						self.populate(items_to_process.shift());
					}
					else {
						self.finish_up();
					}
				}
			});
		};

		self.update_status = function(id, success, response){

			items_count = items_count + 1;

			if ( success ) {
				items_successes = items_successes + 1;
				
				self.button.text(response.message + ' ( Processing.. )');

				console.log(response.log);
			}
			else {
				items_errors = items_errors + 1;
				if(items_failedlist == ''){
					items_failedlist += id;
				} else {
					items_failedlist += ',' + id;
				}

				self.button.text('Process failed.');

				console.log(response.log);
			}
		};

		self.finish_up = function(){
			self.button.text('Process completed.');

			console.log(items_count + " processed");
			console.log(items_successes + " item success");
			console.log(items_errors + " item failed");

			if(items_errors > 0){

				console.log("Item failed list : " + items_failedlist);

				var slug = 'category';
				if(items_errors > 1)
					slug = 'categories';

				var text = items_errors + ' '+slug+' failed to process / no image found.';
				alert(text);
			}

			setTimeout(function() {
			    self.button.text(self.default_text);
			}, 2000);
		};

		self.build_container = function(){

			var wrapper_html  = '<div id="show-populate-status-wrap" class="hidden" tabindex="-1" aria-label="Screen Options Tab">';
				wrapper_html += '<div class="wapci-container">';
				wrapper_html += '<div class="wapci-left">';
				wrapper_html += 'left';
				wrapper_html += '</div>';
				wrapper_html += '<div class="wapci-right">';
				wrapper_html += 'right';
				wrapper_html += '</div>';
				wrapper_html += '</div>';
				wrapper_html += '</div>';

			var button_html  = '<div id="contextual-help-link-wrap" class="hide-if-no-js screen-meta-toggle toggle-show-populate-status">';
				button_html += '<button type="button" id="show-populate-status-link" class="button show-settings" aria-controls="show-populate-status-wrap" aria-expanded="false">Show Populate Status</button>';
				button_html += '</div>';
			
			if($('#show-populate-status-link').length == 0) {
				$('#screen-meta').append(wrapper_html);
				$('#screen-meta-links').append(button_html);
			}

			$('#show-populate-status-link').trigger('click');

		};

		self.toggle_wrapper = function(){
			var id = '#'+$(this).attr('aria-controls');
			if($(id).is(":visible")){
				$('#screen-meta').hide();
				$(id).slideUp();
				$('#screen-meta-links .screen-meta-toggle').css('visibility', 'visible');
				$(this).removeClass('screen-meta-active');
			} else {
				$('#screen-meta .hidden').hide();
				$('#screen-meta').show();
				$(id).slideDown('fast');
				$('#screen-meta-links .screen-meta-toggle').css('visibility', 'hidden');
				$('#screen-meta-links .screen-meta-toggle.toggle-show-populate-status').css('visibility', 'visible');
				$(this).addClass('screen-meta-active');
			}
		};

		self.reset_items_to_process = function(items){
			i;
			items_to_process = items;
			items_total = items_to_process.length;
			items_count = 0;
			items_percent = 0;
			items_successes = 0;
			items_errors = 0;
			items_failedlist = '';
			items_resulttext = '';
			items_timestart = new Date().getTime();
			items_timeend = 0;
			items_totaltime = 0;
			items_continue = true;
		};
		
		return self;
	}

	var WAPCI_Populate_Js;

	$(document).ready(function() {
		if(WAPCI_Populate_Js == null) {
			WAPCI_Populate_Js = new WAPCI_Populate();
			WAPCI_Populate_Js.init();
		}
	});

})(jQuery);