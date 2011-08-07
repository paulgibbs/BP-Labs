jQuery(document).ready( function() {
	// @mentions autosuggest
	if (jQuery.fn.mentions) {
		jQuery('#comment').mentions();                                                                            // comments
		jQuery('#whats-new').mentions({ resultsbox : '#whats-new-options', resultsbox_position : 'prepend' });    // What's new / status update
		jQuery('#message_content').mentions({ resultsbox : '#message_content', resultsbox_position : 'after' });  // messaging (body)
		jQuery('#topic_text').mentions({ resultsbox : '#topic_text', resultsbox_position : 'after' });            // forums (bbPress standalone)
		jQuery('#reply_text').mentions({ resultsbox : '#reply_text', resultsbox_position : 'after' });            // forums (bbPress standalone)
	}
});