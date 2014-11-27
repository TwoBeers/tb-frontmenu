/**
 * TB FrontMenu - v1.0
 *
 * https://github.com/TwoBeers/tb-frontmenu
 *
 * Copyright 2014 TwoBeers.net http://www.twobeers.net/
 */


var tb_frontmenu;

(function($) {

tb_frontmenu = {

	//initialize
	init : function() {

		var $menu = $( '#tb-frontmenu' );
		var frontmenuTO = false; //the TimeOut for functions fired on window resize

		$menu.toggleClass( 'no-js js' );

		// add the handles for expanding sub-menus in low-res screens
		$( '.top-item-container', $menu ).prepend( '<div class="dashicons dashicons-arrow-down-alt2 sub-menu-toggler"></div>' );

		// initialize the sub-menus (hidden)
		$( '.sub-menu', $menu ).css( 'display', 'block' ).hide();

		// when the mouse enters/leaves the menu block
		$( '.top-item-container', $menu ).hoverIntent(

			function() { //when mouse enters

				//nothing here

			},

			function() { //when mouse leaves, hide the sub list

				var $this = $( this );

				$( '.sub-menu', $this ).stop().slideUp( 400, function() {

					$this.removeClass( 'expanded not-extended extended' );

				});

				$( '.sub-menu-toggler', $this ).removeClass( 'dashicons-arrow-up-alt2' ).addClass( 'dashicons-arrow-down-alt2' );

				$( '.attachment-tb-frontpage-thumb', $this ).stop().css( 'opacity', 1 );

			}

		);

		// when the mouse enters/leaves the top item title (only for high-res screens)
		$( '.top-item-container > a', $menu ).hoverIntent(

			function(){ //when mouse enters, slide down the sub list

				if ( $menu.hasClass( 'compact' ) ) return; //in low-res screens, do nothing

				var $parent = $( this ).parent();

				if ( ! $parent.parent().hasClass( 'menu-item-has-children' ) ) return;

				if ( ! $parent.hasClass( 'expanded' ) )
					$( '.sub-menu', $parent ).css( 'height', 'auto' );

				ul_height = $( '.sub-menu', $parent ).height();
				img_height = $( '.featured-container', $parent ).height();

				if ( ul_height > img_height ) {
					to_height = ul_height;
					ext_class = 'not-extended';
				} else {
					to_height = img_height;
					ext_class = 'extended';
				}

				if ( ! $parent.hasClass( 'expanded' ) ) {

					$parent.addClass( 'expanded ' + ext_class );

					$( '.sub-menu', $parent ).css( 'height', to_height ).slideDown( 400, 'easeInOutCubic' );

					$( '.attachment-tb-frontpage-thumb', $parent ).animate( { opacity: 0 }, 400 );

				}

			},

			function(){ //when mouse leaves

				//nothing here

			}

		);

		// when the handle is clicked, slides down/up the sub-menu
		$( '.sub-menu-toggler' ).click(

			function() {

				var $parent = $( this ).parent();

				$( '.sub-menu', $parent ).css( 'height', 'auto' ).stop().slideToggle( 'slow' );

				$( this ).toggleClass( 'dashicons-arrow-down-alt2 dashicons-arrow-up-alt2' );

			}

		);

		// call the "resize stuff" function once
		tb_frontmenu.resizeStuff();

		// what to do when the window resizes (with a delay for avoiding too much executions
		$( window ).resize( function() {

			if( frontmenuTO !== false )
				clearTimeout( frontmenuTO );

			frontmenuTO = setTimeout( tb_frontmenu.resizeStuff, 200 ); //200 is time in miliseconds

		});


	},

	//the "resize stuff" function
	resizeStuff: function() {

		var threshold = tb_frontmenu_data.threshold;
		var $menu = $( '#tb-frontmenu' );

		// add a class when in compact view
		if ( window.innerWidth < threshold )
			$menu.addClass( 'compact' );
		else
			$menu.removeClass( 'compact' );

		//if test-mode is enabled, display an inline alert, pointing out the actual window size
		if ( tb_frontmenu_data.test_mode == 1 ) {

			if ( $( '#tb-frontmenu-windowsize' ).length == 0 )
				$menu.before( '<div id="tb-frontmenu-windowsize"></div>' );

			$( '#tb-frontmenu-windowsize' ).html( 'current window width: ' + window.innerWidth + 'px' );

		}

	}

};

$(document).ready(function($){ tb_frontmenu.init(); });

})(jQuery);