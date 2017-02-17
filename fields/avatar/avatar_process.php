<?php
 defined('JPATH_BASE') or die;
/*
* Copyright (c) 2008 http://www.webmotionuk.com / http://www.webmotionuk.co.uk
* "Jquery image upload & crop for php"
* Date: 2008-11-21
* Ver 1.0
* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
* ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
* IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
* PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
* STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF 
* THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* http://www.opensource.org/licenses/bsd-license.php
*
* Modified by SherZa (irina@psytronica.ru)
*/

$session = JFactory::getSession();
$app = JFactory::getApplication();

$userid= $app->input->getInt('id');
if(!$userid){
	$userid  = $session->get( 'zeavuserid');
	if(!$userid){
		mt_srand(make_seed());
		$userid = '0_'.mt_rand();
		$session->set( 'zeavuserid',$userid);
	}
}else{
	$user = JFactory::getUser();
	$canEdit = $user->authorise('core.edit', 'com_users');
	if($user->id!=$userid && !$canEdit) echo "error|ACCESS DENIED";
}

function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}

if (JRequest::getVar('upload')=="Upload") { 
	mt_srand(make_seed());
	$randval = mt_rand();
	$session->set( 'zeRandAvatar', $randval);
}else{
	$randval = $session->get( 'zeRandAvatar');
}


$plugin = JPluginHelper::getPlugin('user', 'zygo_profile');
$pluginParams = new JRegistry();
$pluginParams->loadString($plugin->params);


#########################################################################################################
# CONSTANTS																								#
# You can alter the options below																		#
#########################################################################################################

$av_folder = JPATH_ROOT."/".$pluginParams->get('avatarfolder', "zyprofile");
if(!is_dir($av_folder)) mkdir($av_folder,0755,true);
$upload_dir = $av_folder.'/'.$userid;   
if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);			
											// The directory for the images to be saved in
$upload_path = $upload_dir."/";				// The path to where the image will be saved
$large_image_name = "tmp_large".$randval;     // New name of the large image (append the timestamp to the filename)
$thumb_image_name = "tmp_thumb".$randval;     // New name of the thumbnail image (append the timestamp to the filename)
// docenttmp
$max_file = $pluginParams->get('max_file', 2); 							// Maximum file size in MB
$max_width = $pluginParams->get('max_width', 500);							// Max width allowed for the large image
$thumb_width = $pluginParams->get('thumb_width', 100);
$thumb_height = $pluginParams->get('thumb_height', 100);
// Only one of these image types should be allowed for upload


//docenttmp
$allowed_image_types = array_map('trim', explode(',', $pluginParams->get('allowed_image_types', 'bmp','gif','jpg','png')));
$dis_allowed_image_types = array('php','js','exe','phtml','java','perl','py','dll','bat','cmd','com','cpl','hta','sys');
$image_ext = "";
foreach ($allowed_image_types as $mime_type => $ext) {
    $image_ext.= strtoupper($ext)." ";
}


//Image Locations
$large_image_location = $upload_path.$large_image_name;
$thumb_image_location = $upload_path.$thumb_image_name;


########################################################
#	UPLOAD THE IMAGE								   #
########################################################
if (JRequest::getVar('upload')=="Upload") { 
	//Get the file information
	if (!isset($_FILES['image'])){
		$_FILES['image'] = $_FILES['webcam'];
		}
	$userfile_name = $_FILES['image']['name'];
	$userfile_tmp = $_FILES['image']['tmp_name'];
	$userfile_size = $_FILES['image']['size'];
	$userfile_type = $_FILES['image']['type'];
	$filename = basename($_FILES['image']['name']);
	$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));

	$error = "";
	
	//Only process if the file is a JPG and below the allowed limit
	if((!empty($_FILES["image"])) && ($_FILES['image']['error'] == 0)) {
		
        // docenttmp
		$dis_allowed_check = array_intersect($allowed_image_types, $dis_allowed_image_types);
        if (!empty($dis_allowed_check))
           {
              $error = JText::_('PLG_USER_ZYGO_PROFILE_DIS_ALLOWED_IMAGE_EXTENSIONS_ERROR').$image_ext;            
           }
        if (!in_array($file_ext, $allowed_image_types))
           {
              $error = JText::_('PLG_USER_ZYGO_PROFILE_ALLOWED_IMAGE_EXTENSIONS_ERROR').$image_ext;            
           }
        
        
		//check if the file size is above the allowed limit
		if ($userfile_size > ($max_file*1048576)) {
			$error.= JText::_('PLG_USER_ZYGO_PROFILE_MAX_FILE_ERROR').$max_file." MB";
		}
		
	}else{
		$error= "Please select an image for upload";
	}
	//Everything is ok, so we can upload the image.
	if (strlen($error)==0){
		
		if (isset($_FILES['image']['name'])){

    		$files = scandir($upload_dir);
		    foreach ($files as $file) {
		        if(strpos($file, 'tmp_')!==false){
		 			unlink($upload_path.$file);
		        }
		    }


			//this file could now has an unknown file extension (we hope it's one of the ones set above!)
			$large_image_location = $large_image_location.".".$file_ext;
			$thumb_image_location = $thumb_image_location.".".$file_ext;
			
			//put the file ext in the session so we know what file to look for once its uploaded
			$session->set( 'user_file_ext', ".".$file_ext);
			
			if(!is_dir($upload_dir)) mkdir($upload_dir);
			move_uploaded_file($userfile_tmp, $large_image_location);
			chmod($large_image_location, 0777);
			
			$width = getWidth($large_image_location);
			$height = getHeight($large_image_location);
			//Scale the image if it is greater than the width set above
			if ($width > $max_width){
				$scale = $max_width/$width;
				$uploaded = resizeImage($large_image_location,$width,$height,$scale);
			}else{
				$scale = 1;
				$uploaded = resizeImage($large_image_location,$width,$height,$scale);
			}
			//Delete the thumbnail file so the user can create a new one
			/*if (file_exists($thumb_image_location)) {
				unlink($thumb_image_location);
			}*/

			echo "success|".str_replace(JPATH_ROOT.'/', JURI::root(), $large_image_location)."|".getWidth($large_image_location)."|".getHeight($large_image_location);
		}
	}else{
		echo "error|".$error;
	}
}

########################################################
#	CREATE THE THUMBNAIL							   #
########################################################
if (JRequest::getVar('save_thumb')=="Save Thumbnail") { 
	//Get the new coordinates to crop the image.
	$x1 = $_POST["x1"];
	$y1 = $_POST["y1"];
	$x2 = $_POST["x2"];
	$y2 = $_POST["y2"];
	$w = $_POST["w"];
	$h = $_POST["h"];
	//Scale the image to the thumb_width set above

	$zelarge=JRequest::getVar('zelarge');
	if($zelarge){
		$large_image_location=$upload_path.$zelarge;
		$zethumb=(strpos($zelarge, 'tmp_')!==false)? 
				str_replace('tmp_large', 'tmp_thumb', $zelarge):
				str_replace('large', 'tmp_thumb', $zelarge);
		$thumb_image_location=$upload_path.$zethumb;
		$noDelete=array($zelarge, $zethumb);

	}else{
		$file_ext = $session->get( 'user_file_ext');
		$large_image_location = $large_image_location.$file_ext;
		if(!file_exists($large_image_location)){
			$large_image_location=str_replace('tmp_', '', $large_image_location);
		}
		$thumb_image_location = $thumb_image_location.$file_ext;
		$noDelete=array('tmp_large'.$randval.$file_ext, 'tmp_thumb'.$randval.$file_ext);
	}

	$scale = $thumb_width/$w;
	$cropped = resizeThumbnailImage($thumb_image_location, $large_image_location,$w,$h,$x1,$y1,$scale);
	echo "success|".str_replace(JPATH_ROOT.'/', '',$large_image_location)."|".str_replace(JPATH_ROOT.'/', '',$thumb_image_location);

    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if(strpos($file, 'tmp_')!==false && !in_array($file, $noDelete)){
 			unlink($upload_path.$file);
        }
    }

}

#####################################################
#	DELETE BOTH IMAGES								#
#####################################################
if (JRequest::getVar('a')=="delete" && strlen($_POST['large_image'])>0 && strlen($_POST['thumbnail_image'])>0){
//get the file locations 
	$large_image_location = $_POST['large_image'];
	$thumb_image_location = $_POST['thumbnail_image'];
	if (file_exists($large_image_location)) {
		unlink($large_image_location);
	}
	if (file_exists($thumb_image_location)) {
		unlink($thumb_image_location);
	}
	echo "success|Files have been deleted";
}



##########################################################################################################
# IMAGE FUNCTIONS																						 #
# You do not need to alter these functions																 #
##########################################################################################################
function resizeImage($image,$width,$height,$scale) {
	$image_data = getimagesize($image);
	$imageType = image_type_to_mime_type($image_data[2]);
	$newImageWidth = ceil($width * $scale);
	$newImageHeight = ceil($height * $scale);
	$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
	switch($imageType) {
		case "image/gif":
			$source=imagecreatefromgif($image); 
			break;
	    case "image/pjpeg":
		case "image/jpeg":
		case "image/jpg":
			$source=imagecreatefromjpeg($image); 
			break;
	    case "image/png":
		case "image/x-png":
			$source=imagecreatefrompng($image); 
			break;
  	}
	imagecopyresampled($newImage,$source,0,0,0,0,$newImageWidth,$newImageHeight,$width,$height);
	
	switch($imageType) {
		case "image/gif":
	  		imagegif($newImage,$image); 
			break;
      	case "image/pjpeg":
		case "image/jpeg":
		case "image/jpg":
	  		imagejpeg($newImage,$image,90); 
			break;
		case "image/png":
		case "image/x-png":
			imagepng($newImage,$image);  
			break;
    }
	
	chmod($image, 0777);
	return $image;
}
//You do not need to alter these functions
function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale){
	list($imagewidth, $imageheight, $imageType) = getimagesize($image);
	$imageType = image_type_to_mime_type($imageType);
	
	$newImageWidth = ceil($width * $scale);
	$newImageHeight = ceil($height * $scale);
	$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
	switch($imageType) {
		case "image/gif":
			$source=imagecreatefromgif($image); 
			break;
	    case "image/pjpeg":
		case "image/jpeg":
		case "image/jpg":
			$source=imagecreatefromjpeg($image); 
			break;
	    case "image/png":
		case "image/x-png":
			$source=imagecreatefrompng($image); 
			break;
  	}
	imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);
	switch($imageType) {
		case "image/gif":
	  		imagegif($newImage,$thumb_image_name); 
			break;
      	case "image/pjpeg":
		case "image/jpeg":
		case "image/jpg":
	  		imagejpeg($newImage,$thumb_image_name,90); 
			break;
		case "image/png":
		case "image/x-png":
			imagepng($newImage,$thumb_image_name);  
			break;
    }
	chmod($thumb_image_name, 0777);
	return $thumb_image_name;
}
//You do not need to alter these functions
function getHeight($image) {
	$size = getimagesize($image);
	$height = $size[1];
	return $height;
}
//You do not need to alter these functions
function getWidth($image) {
	$size = getimagesize($image);
	$width = $size[0];
	return $width;
}
