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

		// Make the AJAX call
		$.ajax( {
			data    : data,
			type    : 'POST',
			url     : ajaxurl,


complete : function (e) {
console.log(e.responseText);
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