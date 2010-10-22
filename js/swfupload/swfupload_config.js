var swfu;

window.onload = function() {
	var settings = {
		flash_url : "/sn/js/swfupload/swfupload.swf",
		upload_url: "http://192.168.1.100/sn/upload",
		post_params: {"PHPSESSID" : "<?php echo session_id(); ?>"},
		file_size_limit : "1000 MB",
		file_types : "*.*",
		file_types_description : "All Files",
		file_upload_limit : 1000,
		file_queue_limit : 0,
		custom_settings : {
			progressTarget : "fsUploadProgress",
			cancelButtonId : "btnCancel"
		},
		debug: false,

		// Button settings
		button_image_url: "/sn/js/swfupload/images/blue_btn.png",
		button_width: "61",
		button_height: "20",
		button_placeholder_id: "spanButtonPlaceHolder",
		button_text: '<span class="swf-btn">Browser</span>',
		button_text_style : '.swf-btn { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color:#FFFFFF; }',
		button_text_left_padding: 2,
		button_text_top_padding: 2,
		
		// The event handler functions are defined in handlers.js
		file_queued_handler : fileQueued,
		file_queue_error_handler : fileQueueError,
		file_dialog_complete_handler : fileDialogComplete,
		upload_start_handler : uploadStart,
		upload_progress_handler : uploadProgress,
		upload_error_handler : uploadError,
		upload_success_handler : uploadSuccess,
		upload_complete_handler : uploadComplete,
		queue_complete_handler : queueComplete	// Queue plugin event
	};

	swfu = new SWFUpload(settings);
  };