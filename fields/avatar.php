<?php

/**
* @id           $Id$
* @author       Sherza (zygopterix@gmail.com)
* @package      ZYGO Profile
* @copyright    Copyright (C) 2011 - 2012 Psytronica.ru. http://psytronica.ru  All rights reserved.
* @license      GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
*/

defined('JPATH_BASE') or die;

class JFormFieldAvatar extends JFormField
{

	protected $type = 'avatar';

	public function __construct()
	{
		parent::__construct();
		JHTML::_('behavior.modal');

	}
	protected function getInput()
	{
		$doc = JFactory::getDocument();
		$app = JFactory::getApplication();
        $user = JFactory::getUser();

        $u =JURI::getInstance();
        $ustr= $u->toString();

        $ustrFull = (strpos($ustr, '?')!==false)? $ustr.'&avatarfunc=showform' : $ustr.'?avatarfunc=showForm';

        $uid = $app->getUserState('com_users.edit.profile.id', $app->input->get('id'));

        if(!$app->isAdmin() && !$uid && ($app->input->get('option')=='com_users')){

        	$uid = $user->id;
        	$ustrFull.='&id='.$uid;
        
        }

        $plugin = JPluginHelper::getPlugin('user', 'zygo_profile');
		$pluginParams = new JRegistry();
		$pluginParams->loadString($plugin->params);

		$max_width = $pluginParams->get('max_width', 500);	
		$thumb_width = $pluginParams->get('thumb_width', 100);
		$thumb_height = $pluginParams->get('thumb_height', 100);


		$frameWidth = $max_width + $thumb_width + 50;
		$html=''; $value='';

		// Для совместимости с предыдущими версиями, где $this->value был array 
		if(is_array($this->value)){
			$vv= array_values($this->value);
			$value=isset($vv[0])? $vv[0] : '';
		}else{
			$value = $this->value;
		}

		$no_av_link = $pluginParams->get('noavatar', 'plugins/user/zygo_profile/fields/images/noPhoto.jpg');

		$noAvImg = '<img src="'.JURI::root().$no_av_link.'" id="zenoavatar" style="width:'.$thumb_width.'px; height:'.$thumb_height.'px" />';

		if($value){
			$avImg=($pluginParams->get('show_avatar_tooltip', 1))? 
			'<img src="'.JURI::root().$value.'?date='.ceil(microtime(true)).'" class="hasTooltip required" title="<img src=\''.JURI::root().str_replace('thumb', 'large', $value).'\' />" />' :  
			'<img src="'.JURI::root().$value.'?date='.ceil(microtime(true)).'" class="required"  />';
		}else{
			$avImg = $noAvImg;
		}

		if($value) $ustrFull .= '&avatar='.$value;
		$html.='<div id="ze_avatar_wrapper" class="img-polaroid img-rounded" style="display:inline-block; margin-bottom:10px;">'.$avImg.'</div><br />';
		$html.='<div style="display:none">';
		$html.='<input id="ze_avatar_input" name="jform[zygo_profile]['.$this->fieldname.'][value]" value="'.$value.'" />';
		$html.='</div>';
		

		if($uid || !$app->isAdmin()){
			$html.='<a class="modal btn" href="'.$ustrFull.'" rel="{handler: \'iframe\', size: {x: '.$frameWidth.', y: (parseInt(jQuery(window).height())-50)}}">
				<span class="icon-apply"></span>
			  '.JText::_('PLG_USER_ZYGO_PROFILE_CHANGE_AVATAR').'
			</a>';
			$html.=' <a class="btn btn-danger" href="javascript:void(null)" onclick="zeDelAvatar()" >
				<span class="icon-trash"></span>
			  '.JText::_('PLG_USER_ZYGO_PROFILE_DELETE_AVATAR').'
			</a>';
		}else{
			$html.= JText::_("PLG_USER_ZYGO_PROFILE_UPLOAD_AVATAR_AFTER_USER_SAVE");
		}
		$html.='<div id="zenoavatarDiv" style="display:none">'.$noAvImg.'</div>';

		$doc->addScriptDeclaration("
			function zeDelAvatar(){
				if(confirm('".JText::_("PLG_USER_ZYGO_PROFILE_DELETE_AVATAR_SURE")."')){
					jQuery('#ze_avatar_wrapper').html(jQuery('#zenoavatarDiv').html());
					jQuery('#ze_avatar_input').val('noavatar');
				}
			}
		");

		return $html;
	}


}
