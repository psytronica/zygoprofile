<?php

/**
 * @id           $Id$
 * @author       Sherza (zygopterix@gmail.com)
 * @package      ZYGO Profile
 * @copyright    Copyright (C) 2015 Psytronica.ru. http://psytronica.ru  All rights reserved.
 * @license      GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

defined('JPATH_BASE') or die;

class JFormFieldUserinfo extends JFormField {

	protected $type = 'userinfo';

	public function __construct() {
		parent::__construct();

		if (defined('ZYGO_USERINFO_JUST_ADDED')) {
			return;
		}

		define('ZYGO_USERINFO_JUST_ADDED', true);

		$doc = JFactory::getDocument();
		$doc->addStyleSheet(JURI::root().'plugins/user/zygo_profile/fields/zygo.css');
		$doc->addScript(JURI::root().'plugins/user/zygo_profile/fields/zygo.js');

		$js  ='var ZYGO_ADD_NEW_FIELD = "'.JText::_('PLG_USER_ZYGO_PROFILE_ADD_NEW_FIELD').'"; ';
		$js .='var ZYGO_FIELDPARAMS = "'.JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDPARAMS').'"; ';
		
		$js .='var ZYGO_FIELDPARAMS_HTML = "'.JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDPARAMS_HTML').'"; ';

		$doc->addScriptDeclaration($js);

	}
	protected function getLabel() {
		return '';
	}

	protected function correctValue() {

		$codeKeys = array();

		if (!empty($this->value['fieldName'])) {

			$count    = 0;
			foreach ($this->value['fieldName'] as $key => $codeVal) {
				$codeKeys[$key] = $count;
				$count++;
			}

			$newValue = array();
			foreach ($this->value as $vtypeName => $vtype) {
				if (!empty($vtype)) {
					if (is_string($vtype)) {
						$newValue[$vtypeName] = $vtype;
					} else {
						$newValue[$vtypeName] = array();
						foreach ($vtype as $valName => $val) {
							if (!is_array($val) && !is_object($val)) {
								$newValue[$vtypeName][$valName] = $val;
							} else {
								$newValue[$vtypeName][$valName] = array();
								foreach ($val as $vc => $v) {
									$newValue[$vtypeName][$valName][] = $v;
								}
							}
						}
					}
				}
			}

			$this->value = $newValue;

		}
		if (!isset($this->value)) {
			$this->value = array();
		}
		if (!isset($this->value['code'][0])) {
			$this->value['code'] = array();
			$this->value['code'][0] = true;
 		}

		return $codeKeys;
	}


	protected function setTemplates()
	{

		$label = $this->element['label']?(string) $this->element['label']:(string) $this->element['name'];
		$fn = $this->fieldname;

		$head_tmpl = '<div class="zygo_head" rel="'.$fn.'" id="zygo_head_'.$fn.'">'.
						JText::_($label).':<a href="javascript:void(null)" rel="'.$fn.'">'.
						JText::_('PLG_USER_ZYGO_PROFILE_ADD_NEW_FIELD').'</a></div>';


		$this->main_tpl = array(
			
			'<div style="clear:both"></div>'.

			"<div class='zygo_wrapper' id='zygo_".$fn."'>".

			$head_tmpl.

			"<table id='zygo_table_".$fn."' class='zygo_table'>".
			"<thead><tr><th width='20px'></th>".
			"<th><div class='zygo50'>".JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDLABEL')."</div>".
			"<div class='zygo50'>".JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDTYPE')."</th>".
			"<th>".JText::_('ID')."</div></th>".
			"<th width='10px'></th><th width='10px'></th>".
			"</tr></thead><tbody>",

			"</tbody></table></div>"
		);

		$this->line_tpl = array(
			'<tr class="zygo_line zygo_line%num%">'.
				'<td class="first"><span class="sortable-handler">'.
					'<i class="icon-menu"></i>'.
				'</span></td>'
			,


			'<td class="zygo_td_buttons"><div class="zygo_tdhead"><span class="zygo_toggle_btn"></span></div></td><td class="zygo_td_buttons last"><div class="zygo_tdhead">'.
			'<span class="zygo_remove_btn"></span></div></td></tr>'		
		);


	}




	protected function getInput() {


		$codeKeys = $this->correctValue();
		$this->setTemplates();

		JHtml::_('sortablelist.sortable', 'zygo_table_'.$this->fieldname, '', '', '');


		$doc = JFactory::getDocument();

		$lastValue = (!empty($codeKeys))?(max(array_keys($codeKeys))+1):1;

		$doc->addScriptDeclaration('
			ZYGO_NUM_ALL["'.$this->fieldname.'"] = '.$lastValue.';
		');

		$html = $this->main_tpl[0];


		foreach ($this->value['code'] as $num => $codeUniqueID) {


			$uniqueIDVal = ($num > 0 && isset($this->value['code'][$num]) 
									&& $this->value['code'][$num])?
				$this->value['code'][$num]	:	
				JText::_('PLG_USER_ZYGO_PROFILE_ADD_NEW_FIELD').$num;


			$html .= str_replace("%num%", $num, $this->line_tpl[0]);

			$html .= "<td>";

			$html .= "<div class='zygo_tdhead'><div class='zygo50'>";

			$html .= '<input type="text" name="jform[params]['.$this->fieldname.'][code]['.$num.']" class="zygo_code_input" value="'.$uniqueIDVal.'"/>';

			$html .= "</div><div class='zygo50'>";

			$html .= $this->getTypeList($num);

			$html .= "</div><div style='clear:both'></div></div>";

			$type_value = ($num > 0 && isset($this->value['fieldType'][$num]))?
					$this->value['fieldType'][$num]:'';

			$html .= $this->getMoreInputs($num, $type_value);

			$html .= "</td>";

			$html .= '<td class="zygoid"><div class="zygo_tdhead"><span class="zygoid_wrapper zygo_styled">'.$num.'</span></div></td>';
		

			$html .= $this->line_tpl[1];


		}
		$html .= $this->main_tpl[1];

		$orderingVal = (isset($this->value['ordering']) && $this->value['ordering'])?$this->value['ordering']:'';
		$html .= '<input type="hidden" name="jform[params]['.$this->fieldname.'][ordering]" id="jform_params_'.$this->fieldname.'ordering" class="zygo_ordering" rel="ordering" value="'.$orderingVal.'"/>';

		return $html;
	}

	function getTypeList($num){

		$opts      = array(
			'text'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_TEXT'),
			'textarea'    => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_TEXTAREA'),
			'select'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_SELECT'),
			'multiselect' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_MULTISELECT'),
			'radio'       => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_RADIO'),
			'date'        => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_DATE'),
			'avatar'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_AVATAR'),
            'checkbox'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_CHECKBOX'),
            'checkboxes'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_CHECKBOXES'),
            'html'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_HTML')
        );

		$name = 'fieldType';
		$value = ($num > 0 && isset($this->value[$name][$num]))?
					$this->value[$name][$num]:'';

		$options = array();
		foreach ($opts as $optionVal => $optionLabel) {
			$options[] = JHtml::_('select.option', $optionVal, $optionLabel);
		}
		return JHTML::_('select.genericlist', $options, 'jform[params]['.$this->fieldname.']['.$name.']['.$num.']', 'class = "zygo_type"', 'value', 'text', $value);		

	}

	function moreFieldParams(){

		$params   = array();
		$params[] = array(
			'name'  => 'fieldName',
			'label' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDNAME'),
			'type'  => 'input',
		);
		$params[] = array(
			'name'  => 'fieldDescription',
			'label' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDDESCRIPTION'),
			'type'  => 'input',
		);
		$params[] = array(
			'name'  => 'fieldMessage',
			'label' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDMESSAGE'),
			'type'  => 'input',
		);
		$params[] = array(
			'name'  => 'fieldOptions',
			'label' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDLOPTIONS'),
			'type'  => 'multitext',
		);
		$params[] = array(
			'name'    => 'fieldFilter',
			'label'   => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDFILTER'),
			'type'    => 'list',
			'options' => array('' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_NOFILTER'), 'safehtml' => 'SafeHtml', 'string' => 'String', 'raw' => 'Raw')
		);
		$params[] = array(
			'name'  => 'fieldParams',
			'label' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDPARAMS'),
			'type'  => 'textarea',
		);
		$params[] = array(
			'name'  => 'fieldDefaultValue',
			'label' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDDEFAULTVALUE'),
			'type'  => 'input',
		);
		$params[] = array(
			'name'    => 'fieldRequiredProfile',
			'label'   => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDREQUIREDPROFILE'),
			'type'    => 'list',
			'options' => array(
				'2'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_REQUIRED'),
				'1'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_OPTIONAL'),
				'0'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_DISABLED'))
		);
		$params[] = array(
			'name'    => 'fieldRequiredRegistration',
			'label'   => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDREQUIREDREGISTRATION'),
			'type'    => 'list',
			'options' => array(
				'2'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_REQUIRED'),
				'1'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_OPTIONAL'),
				'0'      => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_OPTION_DISABLED'))
		);
		$params[] = array(
			'name'  => 'fieldUsergroups',
			'label' => JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDUSERGROUPS'),
			'type'  => 'usergroup',
		);
		return $params;
	}

	function getMoreInputs($num, $fieldTypeValue){

		$params = $this->moreFieldParams();

		$html = '<div class="zygo_more">';

		$showOptionsVariants = array('select', 'multiselect', 'radio', 'checkboxes');
		foreach ($params as $param) {

			$value = ($num > 0 && isset($this->value[$param['name']][$num]))?$this->value[$param['name']][$num]:'';

			$thisHidden = ($param['type'] == 'hidden' || ($param['type'] == 'multitext' && !in_array($fieldTypeValue, $showOptionsVariants)))?
                        'style="display:none"':'';

            if( $fieldTypeValue=="html" && $param['name'] == "fieldDefaultValue" ){
                $thisHidden = 'style="display:none"';
            }
            
            if( $fieldTypeValue=="html" && $param['name'] == "fieldParams" ){
                $param['label'] = JText::_("PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELDPARAMS_HTML");
            }              
            
			$html .= '<div class="zygo_more_param zygo_more_param_'.$param['name'].
						'"  '.$thisHidden.'>';

            $html .='<span class="zygo_more_label" id="zygo_more_label_'.$this->fieldname.$param['name'].$num.'">'.$param['label']."</span>";

			switch ($param['type']) {

				case 'list':
					$options = array();
					foreach ($param['options'] as $optionVal => $optionLabel) {
						$options[] = JHtml::_('select.option', $optionVal, $optionLabel);
					}
					$html .= JHTML::_('select.genericlist', $options, 'jform[params]['.$this->fieldname.']['.$param['name'].']['.$num.']', 'class = "zeinputbox"', 'value', 'text', $value);

					break;
				case 'textarea':

					$html .= '<textarea type="text" name="jform[params]['.$this->fieldname.']['.$param['name'].']['.$num.']" id="jform_params_'.$this->fieldname.$param['name'].$num.'" rel="'.$param['name'].
                        '" value="'.  htmlspecialchars( $value).'" class = "zeinputbox" />'.$value.'</textarea>';
					break;

				case 'multitext':

					$value_value = ($num > 0 && isset($this->value[$param['name'].'_value'][$num]))?$this->value[$param['name'].'_value'][$num]:'';
					$value_text  = ($num > 0 && isset($this->value[$param['name'].'_text'][$num]))?$this->value[$param['name'].'_text'][$num]:'';

					$multitext = '<div class="zygo_multitext_wrapper">

						      <input type="button" pname="'.$param['name'].'" fname="'.$this->fieldname.'" class="zygo-add-option btn btn-success"
							value="+ '.JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_ADDFIELD').'">

							<table class="table zygo-options-list"><thead><tr>
							<th>'	.JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_FIELDVALUE').'</th>
							<th>'	.JText::_('PLG_USER_ZYGO_PROFILE_FIELDGROUP_FIELD_FIELDTEXT').'</th>
							</tr></thead><tbody>';
					if (!empty($value_value)) {
						foreach ($value_value as $valNum => $val) {
							$multitext .= '<tr class="zygo-option-div"><td>'.
								'<input type="text" name="jform[params]['.$this->fieldname.']['.$param['name'].'_value]['.$num.'][]" value="'.$val.'" />'.
									'</td><td>'.
								'<input type="text" name="jform[params]['.$this->fieldname.']['.$param['name'].'_text]['.$num.'][]" value="'.$value_text[$valNum].'" /><input type="button" class="zygo-remove-option btn btn-danger" value="-"></td></tr>';
						}
					}
					$multitext .= '</tbody></table></div>';
					$html .= $multitext;
					break;
				case 'usergroup':
					$name = 'jform[params]['.$this->fieldname.']['.$param['name'].']['.$num.'][]';
					$id   = '"jform_params_'.$this->fieldname.$param['name'].$num.'"';

					$html .= JHtml::_('access.usergroup', $name, $value, 'multiple="true"', false, $id);
					break;
				case 'input':
				case 'hidden':
				default:

					if ($param['name'] == 'fieldName' && !$value) {$value = 'uniqueID'.$num;
					}

					$html .= '<input type="text" name="jform[params]['.$this->fieldname.']['.$param['name'].']['.$num.']" id="jform_params_'.$this->fieldname.$param['name'].$num.'" rel="'.$param['name'].'" value="'.$value.'" class = "jform_params_'.$this->fieldname.$param['name'].' zeinputbox" />';
					break;
			}

			$html .= '<div style="clear:both"></div>
			</div>';

		}

		$html .= '</div>';

		return $html;
	}

}
