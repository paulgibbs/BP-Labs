jQuery(document).ready(function($) {
	$(this).ajaxSend(function(e, r, s) {
		if (-1 != s.data.indexOf("action=post_update")) {
			s.data = s.data + "&_bpla_as_nonce=" + encodeURIComponent($("#_bpla_as_nonce").val());
		} else if (-1 != s.data.indexOf("action=new_activity_comment")) {
			var comment_id = s.data.split("&")[3].split("=")[1];
			s.data = s.data + "&_bpla_as_nonce_" + comment_id + "=" + encodeURIComponent($("#_bpla_as_nonce_" + comment_id).val());
		}
	});
});