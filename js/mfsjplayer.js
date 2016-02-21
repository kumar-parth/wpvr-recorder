jQuery(document).ready(function(){  
	var file_url = wpvr_var.file_url;
	var post_id = wpvr_var.post_id;
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
});