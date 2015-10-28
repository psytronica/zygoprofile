<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.zygo_profile
 *
 * @copyright   Copyright (C) 2015 irina@psytronica.ru
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

class JFormFieldZygofields extends JFormField
{

	protected $type = 'zygofields';

	public function __construct()
	{
        parent::__construct();

        $lang = JFactory::getLanguage();
        $lang->load('plg_user_zygo_profile', JPATH_ADMINISTRATOR);
    }

	protected function getInput()
	{

		// if you create new jformfield using code of JFormFieldZygofields, 
		// you should add this check

		/*if(!file_exists(JPATH_ROOT.'/plugins/user/zygo_profile/zygo_profile.php')){
			return "Plugin zygo_profile is not installed";
		}*/

		$plugin = JPluginHelper::getPlugin('user', 'zygo_profile');

		/*if(empty($plugin)) return "Plugin zygo_profile is not installed";*/

		$uParams = new JRegistry();
		$uParams->loadString($plugin->params);

		$uinfo = $uParams->get("userinfo");
		if(empty($uinfo)){
			return JText::_('PLG_USER_ZYGO_PROFILE_ZYGOFELDS_NOAVATAR');
		}

		if(is_array($uinfo)){
			$names = $uinfo['code'];
			$ids = $uinfo['fieldName'];
		}else if(is_object($uinfo)){
			$names = $uinfo->code;
			$ids = $uinfo->fieldName;
		}else{
			return JText::_('PLG_USER_ZYGO_PROFILE_ZYGOFELDS_NOAVATAR');			
		}

		$names = array_values((array)$names);
		$ids = array_values((array)$ids);

		$state = array();

		foreach($ids as $k=>$id){
			if($id == 'uniqueID0') continue;
			$nm = $names[$k];
			$state[] = JHTML::_('select.option',$id, $nm );
		}

		return JHTML::_('select.genericlist', $state, 
			$name = 'jform[params]['.$this->fieldname.']', 
			$attribs = '', $key = 'value', $text = 'text', $this->value );


	}
}