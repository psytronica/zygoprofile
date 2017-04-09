/*
* Modified by SherZa (irina@psytronica.ru)
*/

jQuery(function($){

	//create a preview of the selection
	function preview(img, selection) { 
		//get width and height of the uploaded image.
		var current_width = $('#uploaded_image').find('#thumbnail').width();
		var current_height = $('#uploaded_image').find('#thumbnail').height();

		var scaleX = ZE_THUMB_WIDTH / selection.width; 
		var scaleY = ZE_THUMB_HEIGHT / selection.height; 
		
		$('#uploaded_image').find('#thumbnail_preview').css({ 
			width: Math.round(scaleX * current_width) + 'px', 
			height: Math.round(scaleY * current_height) + 'px',
			marginLeft: '-' + Math.round(scaleX * selection.x1) + 'px', 
			marginTop: '-' + Math.round(scaleY * selection.y1) + 'px' 
		});
		$('#x1').val(selection.x1);
		$('#y1').val(selection.y1);
		$('#x2').val(selection.x2);
		$('#y2').val(selection.y2);
		$('#w').val(selection.width);
		$('#h').val(selection.height);
	} 

	//show and hide the loading message
	function loadingmessage(msg, show_hide){
		if(show_hide=="show"){
			$('#loader').show();
			$('#progress').show().text(msg);
			$('#uploaded_image').html('');
		}else if(show_hide=="hide"){
			$('#loader').hide();
			$('#progress').text('').hide();
		}else{
			$('#loader').hide();
			$('#progress').text('').hide();
			$('#uploaded_image').html('');
		}
	}

	//delete the image when the delete link is clicked.
	function deleteimage(large_image, thumbnail_image){
		loadingmessage('Please wait, deleting images...', 'show');
		$.ajax({
			type: 'POST',
			url: ZE_IMAGE_HANDLING_PATH,
			data: 'a=delete&large_image='+large_image+'&thumbnail_image='+thumbnail_image,
			cache: false,
			success: function(response){
				loadingmessage('', 'hide');
				response = unescape(response);
				var response = response.split("|");
				var responseType = response[0];
				var responseMsg = response[1];
				if(responseType=="success"){
					$('#upload_status').show().html('<strong>Success</strong><span>'+responseMsg+'</span>');
					$('#uploaded_image').html('');
				}else{
					$('#upload_status').show().html('<strong>Unexpected Error</strong><span>Please try again</span>'+response);
				}
			}
		});
	}

	function zeGetThumbCoords(thumbImg){
		var selWidth = parseInt(thumbImg.width());
		var selHeight = parseInt(thumbImg.height());
		var sel={};

		if(selHeight/selWidth > ZE_THUMB_HEIGHT/ZE_THUMB_WIDTH){
			 sel.X1=0;
			 sel.X2=selWidth;
			 heightProp=(ZE_THUMB_HEIGHT/ZE_THUMB_WIDTH)*selWidth;
			 sel.Y1=parseInt((selHeight-heightProp)/2);
			 sel.Y2=parseInt((selHeight+heightProp)/2);
		}else{
			 sel.Y1=0;
			 sel.Y2=selHeight;
			 WidthProp=(ZE_THUMB_WIDTH/ZE_THUMB_HEIGHT)*selHeight;
			 sel.X1=parseInt((selWidth-WidthProp)/2);
			 sel.X2=parseInt((selWidth+WidthProp)/2);
		}
		return sel;

	}

	$(document).ready(function () {
			$('#loader').hide();
			$('#progress').hide();


			var thumbImg = $('#uploaded_image').find('#thumbnail');
			if(thumbImg[0]){

				var sel=zeGetThumbCoords(thumbImg);

				thumbImg.imgAreaSelect({ aspectRatio: '1:'+(ZE_THUMB_HEIGHT/ZE_THUMB_WIDTH), onSelectChange: preview, x1:sel.X1, x2:sel.X2, y1:sel.Y1, y2:sel.Y2 }); 
			}
			
			var myUpload = $('#upload_link').upload({
			   name: 'image',
			   action: ZE_IMAGE_HANDLING_PATH,
			   enctype: 'multipart/form-data',
			   params: {upload:'Upload'},
			   autoSubmit: true,
			   onSubmit: function() {
			   		$('#upload_status').html('').hide();
					if($('#uploaded_image').find('#thumbnail').height()){
						$('#uploaded_image').find('#thumbnail').imgAreaSelect({ disable: true, hide: true }); 
					}
					loadingmessage(UPLOADING_MSG, 'show');
			   },
			   onComplete: function(response) {
			   		loadingmessage('', 'hide');
					response = unescape(response);
					var response = response.split("|");
					var responseType = response[0];
					var responseMsg = response[1];
					if(responseType=="success"){
						var current_width = response[2];
						var current_height = response[3];
						//display message that the file has been uploaded
						$('#upload_status').show().html('<div class="alert alert-success"><strong class="success">'+UPLOADING_SUCCESS_MSG+'</strong> <span>'+UPLOADING_SUCCESS_DESC_MSG+'</span></div>');
						//put the image in the appropriate div
						$('#uploaded_image').html('<img src="'+responseMsg+'" id="thumbnail" /><div style="width:'+ZE_THUMB_WIDTH+'px; height:'+ZE_THUMB_HEIGHT+'px;"><img src="'+responseMsg+'" style="position: relative;" id="thumbnail_preview" /></div>');
							var intId = setInterval( function() {
								var tHeight = $('#uploaded_image').find('#thumbnail').height();
								if(tHeight){ 

									clearInterval(intId); 

									var thumbImg = $('#uploaded_image').find('#thumbnail');
									var sel=zeGetThumbCoords(thumbImg);

									thumbImg.imgAreaSelect({ aspectRatio: '1:'+(ZE_THUMB_HEIGHT/ZE_THUMB_WIDTH), onSelectChange: preview, x1:sel.X1, x2:sel.X2, y1:sel.Y1, y2:sel.Y2 }); 

								}
							} , 100);
						//find the image inserted above, and allow it to be cropped


						//display the hidden form
						$('#thumbnail_form').show();
						
					}else if(responseType=="error"){
						$('#upload_status').show().html('<div class="alert alert-error"><h1>Error</h1><p>'+responseMsg+'</p></div>');
						$('#uploaded_image').html('');
						$('#thumbnail_form').hide();
					}else{
						$('#upload_status').show().html('<div class="alert alert-error"><h1>Unexpected Error</h1></div>'+response);
						$('#uploaded_image').html('');
						$('#thumbnail_form').hide();
					}
			   }
			});
		
		//create the thumbnail
		$('#save_thumb').click(function() {
			var x1 = $('#x1').val();
			var y1 = $('#y1').val();
			var x2 = $('#x2').val();
			var y2 = $('#y2').val();
			var w = $('#w').val();
			var h = $('#h').val();
			if(x1=="" || y1=="" || x2=="" || y2=="" || w=="" || h==""){
				alert("You must make a selection first");
				return false;
			}else{
				//hide the selection and disable the imgareaselect plugin
				$('#uploaded_image').find('#thumbnail').imgAreaSelect({ disable: true, hide: true });
				var srcAvArr = $('#uploaded_image').find('#thumbnail').attr('src').split('/');

				loadingmessage(SAVING_THUMB_MSG, 'show');

				$.ajax({
					type: 'POST',
					url: ZE_IMAGE_HANDLING_PATH,
					data: 'save_thumb=SaveThumbnail&x1='+x1+'&y1='+y1+'&x2='+x2+'&y2='+y2+'&w='+w+'&h='+h+'&zelarge='+srcAvArr[srcAvArr.length - 1],
					cache: false,
					success: function(response){
						loadingmessage('', 'hide');
						response = unescape(response);
						var response = response.split("|");
						var responseType = response[0];
						var responseLargeImage = response[1];
						var responseThumbImage = response[2];
						if(responseType=="success"){

							window.parent.document.getElementById('ze_avatar_wrapper').innerHTML = '<img src="'+ZE_PATH+responseThumbImage+'?date='+(new Date().getTime())+'" />';
							window.parent.document.getElementById('ze_avatar_input').value = responseThumbImage;

							window.parent.SqueezeBox.close();
							/*$('#upload_status').show().html('<b>Success</b><span>The thumbnail has been saved!</span>');
							//load the new images
							$('#uploaded_image').html('<img src="'+responseLargeImage+'" alt="Large Image"/>&nbsp;<img src="'+responseThumbImage+'" alt="Thumbnail Image"/><br /><a href="javascript:deleteimage(\''+responseLargeImage+'\', \''+responseThumbImage+'\');">Delete Images</a>');
							//hide the thumbnail form
							$('#thumbnail_form').hide();*/
						}else{
							$('#upload_status').show().html('<h1>Unexpected Error</h1><p>Please try again</p>'+response);
							//reactivate the imgareaselect plugin to allow another attempt.

							var thumbImg = $('#uploaded_image').find('#thumbnail');
							var sel=zeGetThumbCoords(thumbImg);

							thumbImg.imgAreaSelect({ aspectRatio: '1:'+(ZE_THUMB_HEIGHT/ZE_THUMB_WIDTH), onSelectChange: preview, x1:sel.X1, x2:sel.X2, y1:sel.Y1, y2:sel.Y2 }); 
 
							$('#thumbnail_form').show();
						}
					}
				});
				
				return false;
			}
		});
		// webcam
		Webcam.on( 'error', function(err) {
			alert( 'No supported webcam interface found.' );
			$('#webcam_attach').show();
			$('#webcam_reset').hide();
			$('#webcam_preview').hide();
			$('#webcam_upload').hide();
			$('#webcam_freeze').hide();
			$('#webcam_unfreeze').hide();
			Webcam.off();
			Webcam.reset();
		} );
		$('#webcam_attach').click(function() {
			Webcam.set({
				width: 400,
				height: 300,
				jpeg_quality: WEBCAM_JPEG_QUALITY,
				enable_flash: WEBCAM_ENABLE_FLASH,
				force_flash: WEBCAM_FORCE_FLASH,
				upload_name: 'image'
			});
			$('#webcam_attach').hide();
			$('#webcam_reset').show();
			$('#webcam_preview').show();
			$('#webcam_freeze').show();
			Webcam.attach( '#webcam_preview' );
		});
		$('#webcam_snapshot').click(function() {
			alert( '#webcam_snapshot' );		
		});
		$('#webcam_freeze').click(function() {
			$('#webcam_unfreeze').show();
			$('#webcam_upload').show();
			$('#webcam_freeze').hide();
			Webcam.freeze();		
		});
		$('#webcam_unfreeze').click(function() {
			$('#webcam_unfreeze').hide();
			$('#webcam_upload').hide();
			$('#webcam_freeze').show();
			Webcam.unfreeze();		
		});	
		$('#webcam_upload').click(function() {
			Webcam.snap( function(data_uri) {	
				Webcam.on( 'uploadProgress', function(progress) {
					$('#upload_status').html('').hide();
					if($('#uploaded_image').find('#thumbnail').height()){
						$('#uploaded_image').find('#thumbnail').imgAreaSelect({ disable: true, hide: true }); 
					}
					loadingmessage(UPLOADING_MSG, 'show');
				});			
				Webcam.on( 'uploadComplete', function(code, text) {
					loadingmessage('', 'hide');
					response = unescape(text);
					var response = response.split("|");
					var responseType = response[0];
					var responseMsg = response[1];
					if(responseType=="success"){
						var current_width = response[2];
						var current_height = response[3];
						//display message that the file has been uploaded
						$('#upload_status').show().html('<div class="alert alert-success"><strong class="success">'+UPLOADING_SUCCESS_MSG+'</strong> <span>'+UPLOADING_SUCCESS_DESC_MSG+'</span></div>');
						//put the image in the appropriate div
						$('#uploaded_image').html('<img src="'+responseMsg+'" id="thumbnail" /><div style="width:'+ZE_THUMB_WIDTH+'px; height:'+ZE_THUMB_HEIGHT+'px;"><img src="'+responseMsg+'" style="position: relative;" id="thumbnail_preview" /></div>');
						var intId = setInterval( function() {
						var tHeight = $('#uploaded_image').find('#thumbnail').height();
						if(tHeight){ 
							clearInterval(intId); 
							var thumbImg = $('#uploaded_image').find('#thumbnail');
							var sel=zeGetThumbCoords(thumbImg);
							thumbImg.imgAreaSelect({ aspectRatio: '1:'+(ZE_THUMB_HEIGHT/ZE_THUMB_WIDTH), onSelectChange: preview, x1:sel.X1, x2:sel.X2, y1:sel.Y1, y2:sel.Y2 }); 
								}
							} , 100);
						$('#thumbnail_form').show();
						}else if(responseType=="error"){
							$('#upload_status').show().html('<div class="alert alert-error"><h1>Error</h1><p>'+responseMsg+'</p></div>');
							$('#uploaded_image').html('');
							$('#thumbnail_form').hide();
						}else{
							$('#upload_status').show().html('<div class="alert alert-error"><h1>Unexpected Error</h1></div>'+response);
							$('#uploaded_image').html('');
							$('#thumbnail_form').hide();
						}
				});
				Webcam.upload( data_uri, ZE_IMAGE_HANDLING_PATH );
				$('#webcam_unfreeze').hide();
				$('#webcam_upload').hide();
				$('#webcam_freeze').show();
			});
		});
		$('#webcam_reset').click(function() {
			$('#webcam_attach').show();
			$('#webcam_reset').hide();
			$('#webcam_preview').hide();
			$('#webcam_upload').hide();
			$('#webcam_freeze').hide();
			$('#webcam_unfreeze').hide();
			Webcam.reset();
		});
		// webcam end
	}); 
});
