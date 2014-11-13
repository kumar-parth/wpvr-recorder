jQuery(document).ready(function(){
	for ( var i = 0; i <= wpvr_var.post_id.length - 1; i++ ) {  
		var file_url = wpvr_var.file_url[i];
		var post_id = wpvr_var.post_id[i];
		jplayer(post_id, file_url);
	}	
	function jplayer( post_id, file_url ) {
		jQuery("#jquery_jplayer_" + post_id).jPlayer({
			ready: function () {
			  jQuery(this).jPlayer("setMedia", {
				wav: file_url
			  });
			},
			swfPath: wpvr_var.plugin_url+'/wpvr-recorder/js/',
			supplied: "wav",
			cssSelectorAncestor: "#jp_container_" + post_id,
			wmode: "window"
		});
	}
});