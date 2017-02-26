<?php
/**
* @id           $Id$
* @author       Sherza (zygopterix@gmail.com)
* @package      ZYGO Profile
* @copyright    Copyright (C) 2015 Psytronica.ru. http://psytronica.ru  All rights reserved.
* @license      GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

ZygoHelper::init();

abstract class ZygoHelper {

	public static $profile = array();
	public static $uids_info = array();
	public static $zygo_plugin = array();
	public static $zygo_params = null;
    	public static $noavatar = "plugins/user/zygo_profile/fields/images/noPhoto.jpg";

	public static function init(){
		self::getProfileParams();
	}

    /**
     * Method provide an object of avatar links (large and thumb) for user.
     * In case if field id wasn't set, we select id of first avatar field
     * that we find in profile fields
     *
     * @param    uid    User id (int)
     * @param 	 fid 	Field id (int)
     * @return object
     */
	public static function getAvatar($uid, $fid = 0){
		if(!$fid){
			if (empty(self::$profile)) return "";

			foreach(self::$profile as $f=>$prof){
				if($prof['fieldType'] == 'avatar'){
					$fid = $f;
					break;
				}
			}

		}
		$link = ($fid)? self::getFieldData($fid, $uid) : "";
		return self::getAvatarByLink($link);
	}

    /**
     * Method provide an object of avatar links (large and thumb) 
     * Method check if file from link is exists. If not exists, then returns 
     * default avatar.
     *
     * @param 	 link 	link to avatar, without start slash from site root (string)     
     * @return   object
     */
	public static function getAvatarByLink($link){

		/* @TODO: check user permissions for showing avatar 

		$profile_fields_vip = $params->get('profile_fields_vip');
		$authAV=true;
		if(!empty($profile_fields_vip) && in_array($avatarFname, $profile_fields_vip)){
			$user = JFactory::getUser($uid);
			if(!$user->authorise('core.extrafieldshow', 'plg_user_')) $authAV= false;
		}*/

		$link = trim($link);

		$authAV = true;

		$avatar = new stdClass();

		if($authAV && $link && file_exists(JPATH_ROOT.'/'.$link)){
			$avatar->link = $link;
			$avatar->linkLarge = str_replace('/thumb', '/large', $link);
		}else{

			$avatar->link = (self::$zygo_params && self::$zygo_params->get("noavatar"))?
				self::$zygo_params->get("noavatar") : self::$noavatar;
			$avatar->linkLarge = '';		 	
		}

	   return $avatar;

	}

    /**
     * Method provide avatar html code from avatar string
     *
     * @param 	 link 	link to avatar, without start slash from site root (string)     
     * @return   string (html codes)
     */
	public static function getAvatarTmpl($link){
		$avatar = self::getAvatarByLink($link);
		$tl = ($avatar->linkLarge && self::$zygo_params->get('show_avatar_tooltip', 1))? 
		"title='<img src=\"".$avatar->linkLarge."\" >' class='hasTooltip'" : "";
		$html = "<span class='zygo_avatar'>";
		$html .= "<img src='".JURI::root().$avatar->link."' ".$tl." />";
		$html .= "</span>";
		return $html;
	}

    /**
     * Get field name that user set in plg_user_zygo_profile admin panel
     *
     * @param 	 @param 	 fid 	Field id (int)  
     * @return   string
     */
	public static function getLabel($fid){
		return (isset(self::$profile[$fid]['code']))? self::$profile[$fid]['code'] : "";
	}

    /**
     * Retrieve data of current field for current user in the same format
     * as is saved in database
     *
     * @param 	 @param 	 fid 	Field id (int)  
     * @return   string
     */
	public static function getFieldData($fid, $uid){

		$uinfo = ZygoHelper::getUserInfo($uid);
		if(strpos($fid."", "uniqueID")!==0){
			$fid = "uniqueID".$fid;
		}

		return isset($uinfo[$fid])? $uinfo[$fid] : "";

	}

    /**
     * Method provide html data of current field for current user in the same format
     *
     * @param    uid    User id (int)
     * @param 	 fid 	Field id (int)    
     * @return   string (html codes)
     */
	public static function getField($fid, $uid){

		if(strpos($fid."", "uniqueID")!==0){
			$fid = "uniqueID".$fid;
		}
		if(!isset(self::$profile[$fid])) return "";

		$finfo = self::getFieldData($fid, $uid);
		if(!$finfo && self::$profile[$fid]['fieldType'] != 'avatar') return "";

		$html = "";

		$html .= "<span class='zygo_field zygo_field_field_id_".str_replace("uniqueID", "", $fid)." zygo_field_type_".self::$profile[$fid]['fieldType']."'>";

		if(self::$profile[$fid]['fieldType'] == 'avatar'){
			$link = $finfo;
			$html .= self::getAvatarTmpl($link);
		}else{
			if(strpos($finfo, "\n")!=-1){
				if(self::$profile[$fid]['fieldType'] == 'textarea'){
					$html .= str_replace("\n", "<br />", $finfo);
				}else{
					$html .= str_replace("\n", ", ", $finfo);
				}
			}else{
				$html .= $finfo;
			}
			
		}

		$html .= "</span>";

		return $html;
	}

    /**
     * Get info of all fields of zygo profile for current users
     *
     * @param    uid    User id (int)
     * @return   array
     */
	public static function getUserInfo($uid){
		if (isset(static::$uids_info[$uid])) return static::$uids_info[$uid];
		$uinfo = self::getUsersInfo(array($uid));
		return (isset($uinfo[$uid]))? $uinfo[$uid] : array();
	}

    /**
     * Get info of all fields of zygo profile for various users
     *
     * @param    uids    User ids (array of int)
     * @return   array
     */
	public static function getUsersInfo($uids){

		if(empty($uids)){
			return static::$uids_info;
		}

		// @TODO - use cache for this plugin
		//$cache = JFactory::getCache('plg_zygo_helper_users', '');
		//$cache->setCaching( 1 );

		$key =md5(serialize($uids));
		//if (!($list = $cache->get($key))){

			$uids_pre = !empty(static::$uids_info) ? array_keys(static::$uids_info) : array();
			$diff_uids = !empty($uids_pre) ? array_diff($uids, $uids_pre) : $uids;

	    	if(!empty($diff_uids)){

		        $db     = JFactory::getDbo();

		        $diff_uids_vals = array_values($diff_uids);
		        $eq_in = (sizeof($diff_uids)==1)? "='".$diff_uids_vals[0]."'" : 'IN('.implode(', ', $diff_uids).')';
		        $query = '
		            SELECT *
		            FROM #__user_profiles
		            WHERE user_id '. $eq_in.'

		        ';

		        $db->setQuery($query);
		        $infoRaw = $db->loadObjectList();
		        $info = array();

		        foreach($infoRaw as $ir){
		          if(!isset(static::$uids_info[$ir->user_id])) static::$uids_info[$ir->user_id] = array();
		          static::$uids_info[$ir->user_id][str_replace('zygo_profile.', '', $ir->profile_key)]=$ir->profile_value;
		        }
			}
			$list = static::$uids_info;
			//$cache->store($list, $key);
		/*}else{
			foreach($list as $kkk=>$obj){
				static::$uids_info[$kkk]=$obj;
			}
		}*/

		return $list;

	}

    /**
     * Retrieve zygo profile params from plg_user_zygo_profile and save them as
     * array of arrays ($profile)
     * $profile[field id][field key] = field_value
     *
     * @return   void
     */
	public static function getProfileParams(){
		if(self::$profile) return;

       	self::$zygo_plugin = JPluginHelper::getPlugin('user', 'zygo_profile');
		self::$zygo_params = new JRegistry();
		self::$zygo_params->loadString(self::$zygo_plugin->params);
		if(empty(self::$zygo_params)) return;

		$userinfo = self::$zygo_params->get('userinfo');
		if(empty($userinfo) || !is_object($userinfo) && !is_array($userinfo)) return;
		
		self::$profile = array();

		$ids = is_object($userinfo) ? $userinfo->fieldName : $userinfo['fieldName'];
		$idsNums = array();
		foreach($ids as $num=>$id){
			$idsNums[$num] = $id;
		}

		foreach($userinfo as $nkey=>$obj){
			if ($nkey == 'fieldName' || empty($obj) || is_string($obj)) continue;
			foreach($obj as $fkey=>$val){
				$id = $idsNums[$fkey];
				if ($id == "uniqueID0") continue;
				if(!isset(self::$profile[$id])) self::$profile[$id] = array();
				self::$profile[$id][$nkey] = $val;
			}
		}
		

	}

    /**
	 * Method return word termination according with object quantity
	 * It is important only for languages as russian. 
	 * In english, spanish and other languages $plural1 = $plural2
	 * for exemple:
	 * 1 гостЬ - 23 гостЯ - 36 гостЕЙ
	 * plugin returns "Ь", "Я" or "ЕЙ" depends of number (1, 23 or 36)
     *
     * @param    quantity    Quantity of object (int)
     * @param    singular    Word termination in singular (string)
     * @param    plural1    Word termination in plural form 1 (string)
     * @param    plural2    Word termination in plural form 2 (string)
     * @return   string
     */
	public static function getPluralTerm($quantity, $singular, $plural1, $plural2=""){

		$quantity = (int) $quantity;

		if($plural2 == $plural1){
			// as in english, spanish and some other languages
			return ($quantity == 1)? $singular : $plural1;
		}else{
			// in some languages (russian) there are 2 different terminations in plural
			// depending on quantity

			// if ends with value, 10 < value < 15
			if($quantity%100 > 10 && $quantity%100 < 15){
				return $plural2;

			// if ends with 1
			}else if($quantity%10 == 1){
				return $singular;

			// if ends with value, 1 < value < 5
			}else if($quantity%10 > 1 && $quantity%10 < 5){
				return $plural1;

			}else{
				return $plural2;
			}
		}
	}
}
