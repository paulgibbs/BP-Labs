(function( $ ) {

/**
 * Like button javascript
 *
 * @since 1.3
 */
bplLike = {

	/**
	 * Attach event handler functions to the relevant elements.
	 *
	 * @since 1.3
	 */
	init : function() {
		$(document).on( 'click', '#wp-admin-bar-bpl-likebutton a', bplLike.like );
	},

	/**
	 * Manages the spinner animation when we're waiting for AJAX results
	 *
	 * @since 1.3
	 */
	start_spinner : function () {
		var link = $( '#wp-admin-bar-bpl-likebutton a' );

		if ( ! link.data( 'stop_pulse' ) ) {
			link.children( ".ab-icon" ).animate( { opacity: 0.2 }, 400, 'linear' ).animate( { opacity: 1 }, 400, 'linear', bplLike.start_spinner );
		}
	},

	/**
	 * Send a Like
	 *
	 * @since 1.3
	 */
	like : function( e ) {
		// Get the data to send from the link we clicked on.
		var data = {};

		// Get 'nonce' and 'like' parts from this link's URL
		var url     = $( this ).attr( 'href' );
		var wpnonce = RegExp('[?&]_wpnonce=([^&]*)').exec( url );
		var like    = RegExp('[?&]like=([^&]*)').exec( url );

		data._wpnonce = decodeURIComponent( wpnonce[1].replace( /\+/g, ' ' ) );
		data.action   = 'bpl-like';
		data.like     = decodeURIComponent( like[1].replace( /\+/g, ' ' ) );

		// Send the Like request
		$.ajax( {
			data : data,
			type : 'POST',
			url  : ajaxurl,

			beforeSend : function ( e ) {
				// Hide the 'Like this Post' text
				$( '#wp-admin-bar-bpl-likebutton a' ).children( '.ab-label' ).fadeOut( 200 )
				.end()
				.data( 'stop_pulse', false ).blur();

				// Start the spinner
				bplLike.start_spinner();
			},

			complete : function ( e ) {
				// Stop the spinner, set the Like count to the menu item label, and fade it back in.
				$( '#wp-admin-bar-bpl-likebutton' ).addClass( 'liked' )
				.children( 'a' ).data( 'stop_pulse', true )
				.children( '.ab-label' ).html( e.responseText )
				.fadeIn( 200 );
			}
		} );

		return false;
	}

};

$(document).ready( function () {
	// Create the Like button object after domready event
	bplLike.init();
});

})(jQuery);