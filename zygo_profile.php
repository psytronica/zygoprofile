<?php

/**
 * @id           $Id$
 * @author       Sherza (zygopterix@gmail.com)
 * @package      ZYGO Profile
 * @copyright    Copyright (C) 2015 Psytronica.ru. http://psytronica.ru  All rights reserved.
 * @license      GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

defined('JPATH_BASE') or die;

/**
 * An example custom profile plugin.
 *
 * @package        Joomla.Plugins
 * @subpackage    user.profile
 * @version        1.6
 */
class plgUserZygo_profile extends JPlugin {
	/**
	 * @param    string    The context for the data
	 * @param    int        The user id
	 * @param    object
	 * @return   boolean
	 * @since    1.6
	 */

	public $app = null;
	// image that uses if in plugin settings in backend was not selected
	// default avatar image
	public static $noavatar = "plugins/user/zygo_profile/fields/images/noPhoto.jpg";
	public static $show_avatar_tooltip = 1;
	public $layout          = "";

	public function __construct(&$subject, $config) {

		parent::__construct($subject, $config);
		$this->loadLanguage();

		$this->app = JFactory::getApplication();
		if ($this->app->input->get('avatarfunc')) {
			include_once (JPATH_ROOT.'/plugins/user/zygo_profile/fields/avatar/avatar_'.strtolower($this->app->input->getString('avatarfunc')).'.php');

			$this->app->close();
		}

		if ($this->params->get('noavatar')) {
			self::$noavatar = $this->params->get('noavatar');
		}

		self::$show_avatar_tooltip = $this->params->get('show_avatar_tooltip', 1);

		$this->getLayout();
	}

	/**
	 * Get current layout ("edit" when shows registration/change userdata form
	 * and "" when shows user info page)
	 *
	 * @return void
	 */
	protected function getLayout() {

		$this->layout = $this->app->input->getString('layout');
		$active       = $this->app->getMenu()->getActive();
		if (isset($active->query['layout'])) {
			$this->layout = $active->query['layout'];
		}
	}

	/**
	 * Runs on content preparation
	 *
	 * @param   string  $context  The context for the data
	 * @param   object  $data     An object containing the data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentPrepareData($context, $data) {
		// Check we are manipulating a valid form.
		if (!in_array($context, array('com_users.profile', 'com_users.registration', 'com_users.user', 'com_admin.profile'))) {
			return true;
		}

		$userinfo = $this->checkUserInfo();
		if (!$userinfo) {return true;
		}

		$userId = isset($data->id)?$data->id:0;

		// Load the profile data from the database.
		$db = JFactory::getDbo();
		$db->setQuery(
			'SELECT profile_key, profile_value FROM #__user_profiles'.
			' WHERE user_id = '.(int) $userId.
			' AND profile_key LIKE \'zygo_profile.%\''.
			' ORDER BY ordering'
		);
		$results = $db->loadRowList();

		// Check for a database error.
		if ($db->getErrorNum()) {
			$this->_subject->setError($db->getErrorMsg());
			return false;
		}

		// Merge the profile data.
		if (is_array($data)) {
			$data['zygo_profile'] = array();
		} else {
			$data->zygo_profile = array();
		}

		$fNamesToTypes  = array();
		$fields_avatars = array();

        
		foreach ($userinfo->fieldName as $fieldNum => $fname) {
			if (is_array($userinfo->fieldType)) {
				$fNamesToTypes[$fname] = $userinfo->fieldType[$fieldNum];
			} else {
				$fNamesToTypes[$fname] = $userinfo->fieldType->$fieldNum;
			}

			if ($fNamesToTypes[$fname] == "avatar") {$fields_avatars[] = $fname;
			}
            
            if($fNamesToTypes[$fname] == "html"){
                
                if (is_array($userinfo->fieldParams)) {
                    $fieldParams = $userinfo->fieldParams[$fieldNum];
                } else {
                    $fieldParams = $userinfo->fieldParams->$fieldNum;
                }                
                
                if (is_array($data)) {
                    $data['zygo_profile'][$fname] = $fieldParams;
                } else {
                    $data->zygo_profile[$fname] = $fieldParams;
                }   
                JHtml::register('users.'.$fname, array('plgUserZygo_profile', 'textInProfile'));
            }
		}

		foreach ($results as $v) {
			$k = str_replace('zygo_profile.', '', $v[0]);
			if (isset($fNamesToTypes[$k]) && $fNamesToTypes[$k] != 'textarea') {
				$v1arr = ($this->layout == 'edit')?explode("\n", $v[1]):str_replace("\n", ", ", $v[1]);
			} else {
				$v1arr = $v[1];
			}

			if (!$this->layout) {

				if (!empty($fields_avatars) && in_array($k, $fields_avatars)) {

					$avatarArr = explode(",", $v1arr);
					$v1arr     = $avatarArr[0];
					JHtml::register('users.'.$k, array('plgUserZygo_profile', 'avInProfile'));
				} else {
					JHtml::register('users.'.$k, array('plgUserZygo_profile', 'textInProfile'));
				}
                
                if (isset($fNamesToTypes[$k]) && $fNamesToTypes[$k] == 'checkbox'){
                    $v[1] = ((int)$v[1])? JText::_("JYES") : JText::_("JNO");                  
                }
			}
            

			$dt = (!empty($v1arr) && isset($v1arr[1]))?$v1arr:$v[1];

			if(is_array($dt) && in_array($fNamesToTypes[$k], 
					array("text", "date"))){
				$dt = "";
			}

			if (is_array($data)) {
				$data['zygo_profile'][$k] = $dt;
			} else {
				$data->zygo_profile[$k] = $dt;
			}
            

		}
        
        
		return true;
	}
	/**
	 * Shows avatar in user extended profile (not in registration/change user data form)
	 * process fields from plugin settings that have type "avatar"
	 *
	 * @return string (of html tags)
	 */
	public static function avInProfile($link) {

		if ($link) {

			$file = JPATH_SITE.'/'.$link;

			jimport('joomla.filesystem.path');
			JPath::check($file);

			if (!file_exists($file)) {
				$link = "";
			} else {
				$linkLarge = trim(str_replace('/thumb', '/large', $link));
			}

		}

		if ($link && self::$show_avatar_tooltip) {

			$v1arr = '<img class="hasTooltip avatar avInProfile" src="'.
			JURI::root().$link.'?date='.ceil(microtime(true)).'" title="<img src=\''.JURI::root().$linkLarge.'?date='.ceil(microtime(true)).'\'>">';

		} else {

			if(!$link) $link  = self::$noavatar;
			$v1arr = '<img class="avatar avInProfile" src="'.JURI::root().$link.'?date='.ceil(microtime(true)).'">';
		}
		return $v1arr;
	}
    
	/**
	 * Process the rest of fields from plugin settings to show them in
	 * user extended profile (not in registration/change user data form)
	 *
	 * @return string
	 */
	public static function textInProfile($text) {
		echo $text;
	}

	/**
	 * Checks if some parameters of extended profile was created in
	 * zygo_profile plugin settings
	 *
	 * @return   object/array (depends on the way joomla saves information
	 * from plugin parameter data)
	 */
	protected function checkUserInfo() {

		$userinfo = $this->params->get('userinfo');
		if (empty($userinfo) ||
			(is_object($userinfo) && isset($userinfo->code)
				 && sizeof((Array) $userinfo->code) < 2)) {
			return false;
		}

		return $userinfo;
	}

	/**
	 * @param    JForm    The form to be altered.
	 * @param    array    The associated data for the form.
	 * @return    boolean
	 * @since    1.6
	 */
	public function onContentPrepareForm($form, $data) {

		$userinfo = $this->checkUserInfo();
		if (!$userinfo) {

			if ($this->app->isAdmin()) {
				echo "<div class='alert alert-info'>";
				echo "<span class='icon-save'></span> ";
				echo JText::_("PLG_USER_ZYGO_PROFILE_NOPROFILE");
				echo "</div>";
			}

			return true;
		}

		// Load user_profile plugin language
		$lang = JFactory::getLanguage();
		$lang->load('plg_user_zygo_profile', JPATH_ADMINISTRATOR);

		if (!($form instanceof JForm)) {
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}
		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), array('com_users.profile', 'com_users.registration', 'com_users.user', 'com_admin.profile'))) {
			return true;
		}
		if ($form->getName() == 'com_users.profile' || $form->getName() == 'com_users.user'){

			$formXMLGen = $this->getUserdataParams($data);
			$form->load($formXMLGen);

			foreach ($userinfo->fieldName as $fieldNum => $fname) {

				$fieldReq = is_array($userinfo->fieldRequiredProfile)?
				$userinfo->fieldRequiredProfile[$fieldNum]:
				$userinfo->fieldRequiredProfile->$fieldNum;

				if ($fieldReq > 0) {
					$form->setFieldAttribute($fname, 'required', $fieldReq == 2, 'zygo_profile');
				} else {
					$form->removeField($fname, 'zygo_profile');
				}

				$type        = (is_array($userinfo->fieldType))?$userinfo->fieldType[$fieldNum]:$userinfo->fieldType->$fieldNum;
                
                if($type=="html"){
                    
                    $fieldParams = (is_array($userinfo->fieldParams))?
                            $userinfo->fieldParams[$fieldNum] : $userinfo->fieldParams->$fieldNum;
                    
                    $form->setFieldAttribute($fname, 'html', $fieldParams, 'zygo_profile');
                }                
                
			}
		}

		//In this example, we treat the frontend registration and the back end user create or edit as the same.
		 elseif ($form->getName() == 'com_users.registration') {

			$formXMLGen = $this->getUserdataParams($data);
			$form->load($formXMLGen);

			$userinfo = $this->params->get('userinfo');

			foreach ($userinfo->fieldName as $fieldNum => $fname) {

				$fieldReq = is_array($userinfo->fieldRequiredRegistration)?
				$userinfo->fieldRequiredRegistration[$fieldNum]:
				$userinfo->fieldRequiredRegistration->$fieldNum;

				if ($fieldReq > 0) {
					$form->setFieldAttribute($fname, 'required', $fieldReq == 2, 'zygo_profile');
				} else {
					$form->removeField($fname, 'zygo_profile');
				}
                
				$type        = (is_array($userinfo->fieldType))?$userinfo->fieldType[$fieldNum]:$userinfo->fieldType->$fieldNum;
                
                if($type=="html"){
                    
                    $fieldParams = (is_array($userinfo->fieldParams))?
                            $userinfo->fieldParams[$fieldNum] : $userinfo->fieldParams->$fieldNum;
                    
                    $form->setFieldAttribute($fname, 'html', $fieldParams, 'zygo_profile');
                }
			}
		}
	}

	/**
	 * Getting field attributes from user attribute string in plugin field parameters
	 *
	 * if user make incorrect string of attributes this function will show error string
	 * and user attributes will not appear in html attribs of field
	 *
	 * @return array (of attributes) or null
	 */
	private function parseFieldAttributes($fieldParams, $fname) {

		try
		{
			$attribs = new SimpleXMLElement("<element $fieldParams />");

			return $attribs->attributes();
		}
		 catch (Exception $e) {
			echo "<code>".JText::_("FIELD_ATTRIBUTES_PROBLEM").": '".$fname."'</code>";
		}

		return null;

	}

	/**
	 * Generate user extended profile xml
	 *
	 * Method is called before generating user registration/change account details form
	 * (frontend and backend) and before showing user profile
	 *
	 * @return   string (of xml data)
	 */
	protected function getUserdataParams($data) {

		$userinfo = $this->params->get('userinfo');
		$html     = '<form>
        <fields name="zygo_profile">
        <fieldset name="zygo_profile" addfieldpath="/plugins/user/zygo_profile/fields"
            label="PLG_USER_zygo_profile_SLIDER_LABEL"
        >';
		$showOptionsVariants = array('list', 'radio', 'checkboxes');

		$userId = isset($data->id)?$data->id:0;
		$user   = JFactory::getUser($userId);

		foreach ($userinfo->fieldName as $fieldNum => $fname) {
			if ($fname != 'uniqueID0') {
				$type        = (is_array($userinfo->fieldType))?$userinfo->fieldType[$fieldNum]:$userinfo->fieldType->$fieldNum;
				$fieldParams = (is_array($userinfo->fieldParams))?$userinfo->fieldParams[$fieldNum]:$userinfo->fieldParams->$fieldNum;
				$fieldFilter = (is_array($userinfo->fieldFilter))?$userinfo->fieldFilter[$fieldNum]:$userinfo->fieldFilter->$fieldNum;

				$fieldDescription  = (is_array($userinfo->fieldDescription))?$userinfo->fieldDescription[$fieldNum]:$userinfo->fieldDescription->$fieldNum;
				$code              = (is_array($userinfo->code))?$userinfo->code[$fieldNum]:$userinfo->code->$fieldNum;
				$fieldMessage      = (is_array($userinfo->fieldMessage))?$userinfo->fieldMessage[$fieldNum]:$userinfo->fieldMessage->$fieldNum;
				$fieldDefaultValue = (is_array($userinfo->fieldDefaultValue))?$userinfo->fieldDefaultValue[$fieldNum]:$userinfo->fieldDefaultValue->$fieldNum;

				if (isset($userinfo->fieldUsergroups)) {
					$fieldUsergroups = "";
					if (is_array($userinfo->fieldUsergroups) && isset($userinfo->fieldUsergroups[$fieldNum])) {

						$fieldUsergroups = $userinfo->fieldUsergroups[$fieldNum];

					} else if (is_object($userinfo->fieldUsergroups) && isset($userinfo->fieldUsergroups->$fieldNum)) {

						$fieldUsergroups = $userinfo->fieldUsergroups->$fieldNum;
					}

					if ($fieldUsergroups && !array_intersect($fieldUsergroups, $user->groups)) {
						continue;
					}
				}

				$fParams = array(
					"name"        => $fname,
					"type"        => $type,
					"id"          => $fname,
					"description" => $fieldDescription,
					"label"       => $code,
					"message"     => $fieldMessage//,
					//"hint" => $fieldMessage - placeholder
				);

				if ($fieldDefaultValue) {
					$fParams["default"] = $fieldDefaultValue;
				}

				if ($fieldFilter) {$fParams['filter'] = $fieldFilter;
				}

				switch ($type) {
					case 'multiselect':
						$fParams['multiple'] = "true";
					case 'select':
						$type            = 'list';
						$fParams['type'] = "list";
						break;
					case 'html':
						$fieldParams = "";
						break;                    
					case 'date':
						if (!$this->params->get('showdate', 0)) {
							$type            = 'calendar';
							$fParams['type'] = "calendar";
						}
						break;

					default:
						# @TODO after adding new field types in plugin settings
						# add corresponding information here
						break;
				}

				if ($fieldParams) {

					$fParamsArr = @$this->parseFieldAttributes($fieldParams, $code);
					if (!empty($fParamsArr)) {
						foreach ($fParamsArr as $pname => $pval) {
							if (!isset($fParams[$pname])) {
								$pmsall          = (array) $pval;
								$fParams[$pname] = $pmsall[0];
							}
						}
					}
				}

				$html .= "<field\n";
				foreach ($fParams as $pname => $pval) {
					$html .= $pname.'="'.$pval.'"'."\n";
				}
				if (in_array($type, $showOptionsVariants) && is_array($userinfo->fieldOptions_value) && isset($userinfo->fieldOptions_value[$fieldNum]) && !empty($userinfo->fieldOptions_value[$fieldNum])) {
					$html .= '>';

					$fieldOptions_text = is_array($userinfo->fieldOptions_text)?
					$userinfo->fieldOptions_text[$fieldNum]:
					$userinfo->fieldOptions_text->$fieldNum;

					foreach ($userinfo->fieldOptions_value[$fieldNum] as $optNum => $optVal) {

						$optText = is_array($fieldOptions_text)?
						$fieldOptions_text[$optNum]:
						$fieldOptions_text->$optNum;
						$html .= '<option value="'.$optVal.'">'.$optText.'</option>';

					}
					$html .= '</field>';
				} else if (in_array($type, $showOptionsVariants) && is_object($userinfo->fieldOptions_value) && isset($userinfo->fieldOptions_value->$fieldNum) && !empty($userinfo->fieldOptions_value->$fieldNum)) {
					$html .= '>';

					$fieldOptions_text = is_array($userinfo->fieldOptions_text)?
					$userinfo->fieldOptions_text[$fieldNum]:
					$userinfo->fieldOptions_text->$fieldNum;

					foreach ($userinfo->fieldOptions_value->$fieldNum as $optNum => $optVal) {

						$optText = is_array($fieldOptions_text)?
						$fieldOptions_text[$optNum]:
						$fieldOptions_text->$optNum;
						$html .= '<option value="'.$optVal.'">'.$optText.'</option>';

					}
					$html .= '</field>';

				} else {
					$html .= '/>';
				}

			}
		}

		$html .= '</fieldset>
            </fields>
        </form>';
		return $html;

	}

	/**
	 * Utility method to act on a user after it has been saved.
	 *
	 * This method creates a contact for the saved user
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isnew    True if a new user is stored.
	 * @param   boolean  $success  True if user was succesfully stored in the database.
	 * @param   string   $msg      Message.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onUserAfterSave($data, $isNew, $result, $error) {
		$userId = JArrayHelper::getValue($data, 'id', 0, 'int');
 
		$userinfo = $this->checkUserInfo();
 
		if ($userId && $result && isset($data['zygo_profile']) && (count($data['zygo_profile']))) {
 
			try
			{
				$db = JFactory::getDbo();
				$db->setQuery('DELETE FROM #__user_profiles WHERE user_id = '.$userId.' AND profile_key LIKE \'zygo_profile.%\'');
				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}
 
				$tuples = array();
				$order  = 1;
 
				foreach ($data['zygo_profile'] as $k => $v) {
 
					$fieldNum = str_replace("uniqueID", "", $k);
					if (is_array($userinfo->fieldType)) {
						$ftype = $userinfo->fieldType[$fieldNum];
					} else {
						$ftype = $userinfo->fieldType->$fieldNum;
					}
 
 
					if ($ftype == "avatar" && (strpos($v['value'], 'tmp_') !== false)) {
 
						if ($isNew) {
							$session = JFactory::getSession();
							$uid_pre = $session->get('zeavuserid');
 
							$avArr       = explode('/', $v['value']);
							$avThumbName = array_pop($avArr);
							$avFolder    = JPATH_ROOT.'/'.implode('/', $avArr);
							$v['value']  = str_replace($uid_pre, $userId, $v['value']);
							rename($avFolder, str_replace($uid_pre, $userId, $avFolder));
						}
 
						$avField     = $v['avatar'];
						$avatarThumb = $v['value'];
						$avArr       = explode('/', $avatarThumb);
						$avThumbName = array_pop($avArr);
						$avFolder    = JPATH_ROOT.'/'.implode('/', $avArr);
						$avLargeName = str_replace('thumb', 'large', $avThumbName);
						$largeName   = str_replace('tmp_', '', $avLargeName);
						if (file_exists($avFolder.'/'.$avLargeName)) {
							// if large begins with "tmp_", as thumb,
							// in other words, it means that new avatar was uploaded,
							// (it was not only new thumb generation)
							rename($avFolder.'/'.$avLargeName, $avFolder.'/'.$largeName);
						}
 
						$thumbName = str_replace('tmp_', '', $avThumbName);
						rename($avFolder.'/'.$avThumbName, $avFolder.'/'.$thumbName);
 
						$files = scandir($avFolder);
						$noDel = array('.', '..', $thumbName, $largeName);
						foreach ($files as $file) {
							if (!in_array($file, $noDel)) {
								unlink($avFolder.'/'.$file);
							}
						}
						$v['value'] = implode('/', $avArr).'/'.$thumbName;
					} else if ($ftype == "avatar" && ($v['value'] != 'noavatar')) {
 
						$avatarThumb = $v['value'];
						$avArr       = explode('/', $avatarThumb);
						$avThumbName = array_pop($avArr);
						$avFolder    = JPATH_ROOT.'/'.implode('/', $avArr);
						$files       = scandir($avFolder);
						foreach ($files as $file) {
							if (strpos($file, 'tmp_') !== false) {
								unlink($avFolder.'/'.$file);
							}
						}
					} else if ($ftype == "avatar") {
 
						$avFolder = JPATH_ROOT."/".$this->params->get('avatarfolder').'/'.$userId;
 
						$files = scandir($avFolder);
						foreach ($files as $file) {
							if ($file != '.' && $file != '..') {
								unlink($avFolder.'/'.$file);
							}
						}
						$v['value'] = '';
					}
 
					$v        = (is_array($v))?implode("\n", $v):$v;
					$tuples[] = '('.$userId.', '.$db->quote('zygo_profile.'.$k).', '.$db->quote($v).', '.$order++ .')';
				}
 
				$db->setQuery('INSERT INTO #__user_profiles VALUES '.implode(', ', $tuples));
				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}
			}
			 catch (JException $e) {
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}
 
		$cache = JFactory::getCache('com_users', '');
		$cache->clean('com_users');
 
		return true;
	}

	/**
	 * Remove all user profile information for the given user ID
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param    array        $user        Holds the user data
	 * @param    boolean        $success    True if user was succesfully stored in the database
	 * @param    string        $msg        Message
	 */
	public function onUserAfterDelete($user, $success, $msg) {
		if (!$success) {
			return false;
		}

		$userId = JArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId) {
			try
			{
				$db = JFactory::getDbo();
				$db->setQuery(
					'DELETE FROM #__user_profiles WHERE user_id = '.$userId.
					" AND profile_key LIKE 'zygo_profile.%'"
				);

				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}
			}
			 catch (JException $e) {
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}

	/**
	 * User Email spam filter
	 *
	 * Method is called when user sign up or change parameters of his account
	 */
	public function onUserBeforeSave($user, $isnew, $data) {

		if ($this->params->get('spam')) {

			// antibot register
			$spammer = "http://www.stopforumspam.com/api?email=".$user['email']."&ip=".$_SERVER['REMOTE_ADDR'];
			$res     = file_get_contents($spammer);
			if (preg_match("/\byes\b/i", $res)) {
				header('Location: '.JURI::root());
				exit;
			}

		}

	}

}
