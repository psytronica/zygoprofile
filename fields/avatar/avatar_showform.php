<?php

/**
* @id           $Id$
* @author       Sherza (zygopterix@gmail.com)
* @package      ZYGO Profile
* @copyright    Copyright (C) 2011 - 2012 Psytronica.ru. http://psytronica.ru  All rights reserved.
* @license      GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
*/

 defined('JPATH_BASE') or die;
 
 
$app = JFactory::getApplication();
$user = JFactory::getUser();
$userid = $user->id;
$plugin = JPluginHelper::getPlugin('user', 'zygo_profile');
$pluginParams = new JRegistry();
$pluginParams->loadString($plugin->params);
$avatar = $app->input->get('avatar', "", "raw");
$avatar_pathinfo = pathinfo($avatar,PATHINFO_DIRNAME);
$av_folder_current = $pluginParams->get('avatarfolder', 'zyprofile');
$av_folder_current_user = $av_folder_current.'/'.$userid;
$av_folder_current_dirs = array(current(explode('/', $pluginParams->get('avatarfolder'))));
$av_folder_blocked_dirs = array('administrator','bin','cache','cli','components','includes','language','layouts','libraries','logs','modules','plugins','tmp');
$av_folder_blocked_check = array_intersect($av_folder_current_dirs, $av_folder_blocked_dirs);
if (!empty($av_folder_blocked_check))
{
   echo '<meta charset="utf-8"/>'.JText::_('PLG_USER_ZYGO_PROFILE_AVATAR_DIR_ERROR_BLOCKED');
   $app->close();   
}
if ($app->isAdmin()){
$userid = $app->input->getInt('id');
$av_folder_current_user = $av_folder_current.'/'.$userid;
}
if($avatar == ''){}
elseif($avatar_pathinfo != $av_folder_current_user)
{	
	echo '<meta charset="utf-8"/>'.JText::_('PLG_USER_ZYGO_PROFILE_AVATAR_DIR_INFO_CHANGED');
	include_once (JPATH_ROOT."/plugins/user/zygo_profile/zygo_helper.php");
	$fid = false;
	foreach(ZygoHelper::$profile as $f=>$prof){
		if($prof['fieldType'] == 'avatar'){
			$fid = $f;
			break;}
	}
	$db = JFactory::getDBO();
	$db->setQuery('UPDATE `#__user_profiles` SET `profile_value` = "'.$av_folder_current_user."/".basename($avatar).
	 '\n" WHERE `#__user_profiles`.user_id = '.$userid.' AND profile_key = '.$db->quote("zygo_profile.".$fid));
	$db->execute();
	$files = scandir(JPATH_ROOT.'/'.$avatar_pathinfo);
	$source = JPATH_ROOT.'/'.$avatar_pathinfo.'/';
	$destination = JPATH_ROOT.'/'.$av_folder_current_user.'/';
	if(!is_dir($destination)) mkdir($destination,0755,true);
	foreach ( $files as $file ) {
        if (in_array($file, array(".",".."))) continue;
        if (copy($source.$file, $destination.$file)) {
            $delete[] = $source.$file;}
	}
	foreach ( $delete as $file ) {
        unlink( $file );}
	rmdir($source);
	echo '<script>parent.window.location.reload();</script>';
	$app->close();	
}

$webcam_enable = $pluginParams->get('webcam_enable', 1);
$webcam_enable_flash = $pluginParams->get('webcam_enable_flash', 1);
$webcam_force_flash = $pluginParams->get('webcam_force_flash', 0);
$webcam_jpeg_quality = $pluginParams->get('webcam_jpeg_quality', 90);
if ($webcam_enable_flash == 1) {
    $webcam_enable_flash = true;
} else {
    $webcam_enable_flash = false;
}
if ($webcam_force_flash == 1) {
    $webcam_force_flash = true;
} else {
    $webcam_force_flash = false;
}

$thumb_width = $pluginParams->get('thumb_width', 100);
$thumb_height = $pluginParams->get('thumb_height', 100);
$helptext = $pluginParams->get('texthelp');

$u =JURI::getInstance();
$ustr= $u->toString();
$ustrFull = (strpos($ustr, '?')!=-1)? $ustr.'&avatarfunc=process' : $ustr.'?avatarfunc=process';

$script ='
var ZE_PATH = "'.JURI::root().'";
var ZE_IMAGE_HANDLING_PATH = "'.$ustrFull.'";
var ZE_THUMB_WIDTH = '.$thumb_width.';
var ZE_THUMB_HEIGHT = '.$thumb_height.';
var WEBCAM_ENABLE_FLASH = \''.$webcam_enable_flash.'\';
var WEBCAM_FORCE_FLASH = \''.$webcam_force_flash.'\';
var WEBCAM_JPEG_QUALITY = \''.$webcam_jpeg_quality.'\';
var UPLOADING_MSG = \''.JText::_('PLG_USER_ZYGO_PROFILE_UPLOADING_MSG').'\';
var UPLOADING_SUCCESS_MSG = \''.JText::_('PLG_USER_ZYGO_PROFILE_UPLOADING_SUCCESS_MSG').'\';
var UPLOADING_SUCCESS_DESC_MSG = \''.JText::_('PLG_USER_ZYGO_PROFILE_UPLOADING_SUCCESS_DESC_MSG').'\';
var SAVING_THUMB_MSG = \''.JText::_('PLG_USER_ZYGO_PROFILE_SAVING_THUMB_MSG').'\';
';


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
	<link rel="stylesheet" href="<?php echo JURI::root(); ?>plugins/user/zygo_profile/fields/avatar/css/avatar.css" type="text/css" />
	<script src="<?php echo JURI::root(); ?>media/jui/js/jquery.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="<?php echo JURI::root(); ?>plugins/user/zygo_profile/fields/avatar/js/jquery.imgareaselect.min.js"></script>
	<script type="text/javascript" src="<?php echo JURI::root(); ?>plugins/user/zygo_profile/fields/avatar/js/jquery.ocupload-packed.js"></script>
	<script type="text/javascript" src="<?php echo JURI::root(); ?>plugins/user/zygo_profile/fields/avatar/js/avatar.js"></script>
	<script type="text/javascript" src="<?php echo JURI::root(); ?>plugins/user/zygo_profile/fields/avatar/js/webcam.js"></script>
	<script type="text/javascript">
		<?php echo $script; ?>
	</script>
</head>
<body>


	<div id="ze_upload_avatar_wrapper">
					
		<noscript>Javascript must be enabled!</noscript>
		<div id="upload_status"></div>
		<div style="float:left">
			<input type="button" id="upload_link" class="btn btn-primary" value="<?php echo JText::_("PLG_USER_ZYGO_PROFILE_SELECT_AVATAR"); ?>" />
		</div>
		<div style="float:left;" >
			<input type="button" id="webcam_attach" <?php if($webcam_enable == 0) echo 'style="display:none;"'; ?> class="btn btn-primary" value="<?php echo JText::_("PLG_USER_ZYGO_PROFILE_WEBCAM_ATTACH"); ?>" />
			<input type="button" id="webcam_reset" style="display:none;" class="btn btn-danger" value="<?php echo JText::_("PLG_USER_ZYGO_PROFILE_WEBCAM_RESET"); ?>" />
		</div>		
		<div id="thumbnail_form" <?php if(!$avatar) echo 'style="display:none;"'; ?>>
			<form name="form" action="" method="post">
				<input type="hidden" name="x1" value="" id="x1" />
				<input type="hidden" name="y1" value="" id="y1" />
				<input type="hidden" name="x2" value="" id="x2" />
				<input type="hidden" name="y2" value="" id="y2" />
				<input type="hidden" name="w" value="" id="w" />
				<input type="hidden" name="h" value="" id="h" />
				<input type="submit" name="save_thumb" class="btn btn-success" value="<?php echo JText::_("PLG_USER_ZYGO_PROFILE_SAVE_AVATAR"); ?>" id="save_thumb" />
				<input type="button" class="btn"  onclick="window.parent.SqueezeBox.close();" value="<?php echo JText::_("PLG_USER_ZYGO_PROFILE_CANCEL"); ?>" />
			</form>
		</div>
		<div style="clear:both"></div>

		<?php if(trim($helptext)) echo '<div id="zehelptext">'.$helptext.'</div>'; ?>

		<span id="loader" style="display:none;"><img src="<?php echo JURI::root(); ?>plugins/user/zygo_profile/fields/avatar/loader.gif" alt="Loading..."/></span> 
		<span id="progress"></span>
		<br />
		<div id="uploaded_image">
			<?php
				if($avatar){
					echo '<img src="'.JURI::root().str_replace('thumb', 'large', $avatar).'" id="thumbnail" />
					<div style="width:'.$thumb_width.'px; height:'.$thumb_height.'px;">
						<img src="'.JURI::root().str_replace('thumb', 'large', $avatar).'" style="position: relative;" id="thumbnail_preview" />
					</div>';

				}
			?>
		</div>
		<div style="clear:both"></div>
		<br/>
		<div style="float:right;">
			<a id="webcam_upload" class="btn btn-success" style="display:none;" href="#"><?php echo JText::_("PLG_USER_ZYGO_PROFILE_WEBCAM_UPLOAD"); ?></a>
			<a id="webcam_unfreeze" class="btn btn-danger" style="display:none;" href="#"><?php echo JText::_("PLG_USER_ZYGO_PROFILE_WEBCAM_UNFREEZE"); ?></a>
			<a id="webcam_freeze" class="btn btn-primary" style="display:none;"href="#"><?php echo JText::_("PLG_USER_ZYGO_PROFILE_WEBCAM_FREEZE"); ?></a>
		</div>
		<div id="webcam_preview" style="display:none;"></div>
		<div style="clear:both"></div>	
	</div>


</body>
</html>
