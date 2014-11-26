/* this is the admin script */

var tb_frontmenu_options;

(function($) {

tb_frontmenu_options = {

	//initialize
	init : function() {

		var frame;
		var $rows = $('#frontmenu-rows');

		$('#frontmenu-items').find('.frontmenu-item').each( function() {

			var $parent = $(this);
			var $field_id = $('.plugin_option_imageid',$parent);

			$('.choose-featured-from-library',$parent).click( function( event ) {

				var $el = $(this);

				event.preventDefault();

				// If the media frame already exists, reopen it.
				if ( frame ) {
					frame.close();
				}

				// Create the media frame.
				frame = wp.media.frames.customLogo = wp.media({
					// Set the title of the modal.
					title: $el.data('choose'),

					// Tell the modal to show only images.
					library: {
						type: 'image'
					},

					// Customize the submit button.
					button: {
						// Set the text of the button.
						text: $el.data('update'),
						// Tell the button not to close the modal, since we're
						// going to refresh the page when the image is selected.
						close: true
					}
				});

				// When an image is selected, run a callback.
				frame.on( 'select', function() {
					// Grab the selected attachment.
					var attachment = frame.state().get('selection').first().toJSON();
					if ( attachment.width >= tb_frontmenu_data.image_width && attachment.height >= tb_frontmenu_data.image_height ) {
						$field_id.val(attachment.id);
						$('.thumbnail-container',$parent).html( '<a href="' + attachment.editLink + '" target="_blank"><img class="featured" src="' + attachment.sizes.thumbnail.url + '" alt="featured"/></a>' );
						$('.frontmenu-tools',$parent).addClass( 'featured' );
					} else {
						$('.thumbnail-container',$parent).html( '<div class="ui-state-error">' + tb_frontmenu_data.small_image_alert + '</div>' );
					}
				});

				// Finally, open the modal.
				frame.open();
			});

			$('.remove-img-id',$parent).click( function( event ) {
				event.preventDefault();
				$('.thumbnail-container img',$parent).fadeOut( "slow", function() {
					$field_id.val('');
					$('.frontmenu-tools',$parent).removeClass( 'featured' );
					$('.thumbnail-container',$parent).html( '' );
				});
			});

			var cpoBackground = {
				// a callback to fire whenever the color changes to a valid color
				change: function(event, ui){
					$parent.prev('h3').css('background-color', ui.color.toString())
				},
			};

			var cpoText = {
				// a callback to fire whenever the color changes to a valid color
				change: function(event, ui){
					$parent.prev('h3').css('color', ui.color.toString())
				},
			};
 
			$('.plugin_option_colorpicker.to-background',$parent).each( function() {
				$(this).wpColorPicker(cpoBackground);
			});

			$('.plugin_option_colorpicker.to-text',$parent).each( function() {
				$(this).wpColorPicker(cpoText);
			});
		
			$('h3',$parent).click( function() {
				$('.frontmenu-tools',$parent).slideToggle( "slow" );
			});

		});

		$rows.sortable({
			update: function( event, ui ) {
				tb_frontmenu_options.update_layout();
			}
		});

		$( "#frontmenu-items" ).accordion( { collapsible: true, active: false, heightStyle: "content" } );

		$( "#frontmenu-margin-slider" ).slider();

		$( "#frontmenu-margin-slider" ).slider({
			range: "min",
			value: parseInt( tb_frontmenu_data.slider_value, 10 ),
			min: 0,
			max: 20,
			slide: function( event, ui ) {
				$( "#tb_frontmenu_options-margin" ).val( ui.value );
				$( "#frontmenu-margin-slider span" ).html( ui.value );
			}
		});


		$('#button-add-row2').click( function() {
			$rows.append( $('<div class="row row-2"><span class="item"></span><span class="item"></span><a href="javascript:void(0)" class="dashicons dashicons-no remove-row" onclick="tb_frontmenu_options.remove_row(this)"></a></div>') )
			tb_frontmenu_options.update_layout();
		});

		$('#button-add-row3').click( function() {
			$rows.append( $('<div class="row row-3"><span class="item"></span><span class="item"></span><span class="item"></span><a href="javascript:void(0)" class="dashicons dashicons-no remove-row" onclick="tb_frontmenu_options.remove_row(this)"></a></div>') )
			tb_frontmenu_options.update_layout();
		});
		//$('#frontmenu-items').find('.frontmenu-item').append( $( '<img src="" alt="featured"/>' ) );
		
		
		$('#tab-selector a').click( function() {

			tb_frontmenu_options.switchTab( $(this) );
			return false;
		
		});
		
			tb_frontmenu_options.blocks_equal_items();
		
		$('#to-defaults').click (function () {
			var answer = confirm(tb_frontmenu_data.confirm_to_defaults)
			if (!answer){
				return false;
			}
		});
		
		
		
	},

	update_layout : function() {
		csv = [];
		$('#frontmenu-rows').find('.row').each( function() {
			if ( $(this).hasClass('row-2') ) {
				csv[csv.length] = "2";
			} else if ( $(this).hasClass('row-3') ) {
				csv[csv.length] = "3";
			}
		});
		$('#menu-layout').val( csv.join() );
		$('#frontmenu-rows').find('span').each( function( index ) {
			$(this).text(index+1);
		});
			tb_frontmenu_options.blocks_equal_items();

	},

	blocks_equal_items : function() {
		blocks_count = $('#frontmenu-rows .item').length;
		items_count = $('#frontmenu-items .frontmenu-item').length;

		$( '#frontmenu-alert' ).slideUp( "slow", function() {
			if ( items_count === 0 ) {
				$( '#frontmenu-alert' ).removeClass().addClass( 'error' ).html( '<p><a href="' + tb_frontmenu_data.admin_menu_href + '">' + tb_frontmenu_data.assign_menu + '</a></p>' ).slideDown();
			} else if ( blocks_count != items_count ) {
				$( '#frontmenu-alert' ).removeClass().addClass( 'error' ).html( '<p>blocks: ' + blocks_count + ' - items: ' + items_count + '</p>' ).slideDown();
			} else {
				$( '#frontmenu-alert' ).removeClass().addClass( 'updated' ).html( '<p>blocks: ' + blocks_count + ' - items: ' + items_count + '</p>' ).slideDown();
			}
		});

	},

	remove_row : function( el ) {
		var $parent_row = $(el).parent();
		$parent_row.fadeOut( "slow", function() {
			$parent_row.remove();
			tb_frontmenu_options.update_layout();
		});
	},

	//show only a set of rows
	switchTab : function ( tab ) {
	
	
		$('#tab-selector a').removeClass('nav-tab-active');
		tab.addClass('nav-tab-active');
		$('#tabs .tab-content').hide();
		$(tab.attr('href')).show();
	
	}

};

$(document).ready(function($){ tb_frontmenu_options.init(); });

})(jQuery);