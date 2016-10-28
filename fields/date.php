<?php
/**
 * @id           $Id$
 * @author       Sherza (zygopterix@gmail.com)
 * @package      ZYGO Profile
 * @copyright    Copyright (C) 2011 - 2012 Psytronica.ru. http://psytronica.ru  All rights reserved.
 * @license      GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('text');

class JFormFieldDate extends JFormFieldText {

	protected $type = 'Date';

	protected function getInput() {
		// Translate placeholder text
		$hint = $this->translateHint?JText::_($this->hint):$this->hint;

		// Initialize some field attributes.
		$size         = !empty($this->size)?' size="'.$this->size.'"':'';
		$maxLength    = !empty($this->maxLength)?' maxlength="'.$this->maxLength.'"':'';
		$class        = !empty($this->class)?' class="'.$this->class.'"':'';
		$readonly     = $this->readonly?' readonly':'';
		$disabled     = $this->disabled?' disabled':'';
		$required     = $this->required?' required aria-required="true"':'';
		$hint         = $hint?' placeholder="'.$hint.'"':'';
		$autocomplete = !$this->autocomplete?' autocomplete="off"':' autocomplete="'.$this->autocomplete.'"';
		$autocomplete = $autocomplete == ' autocomplete="on"'?'':$autocomplete;
		$autofocus    = $this->autofocus?' autofocus':'';
		$spellcheck   = $this->spellcheck?'':' spellcheck="false"';

		// Initialize JavaScript field attributes.
		$onchange = $this->onchange?' onchange="'.$this->onchange.'"':'';

		$value = ($this->value)?$this->value:$this->default;

		// Including fallback code for HTML5 non supported browsers.
		JHtml::_('jquery.framework');
		JHtml::_('script', 'system/html5fallback.js', false, true);

		return '<input type="date" name="'.$this->name.'"'.$class.' id="'.$this->id.'" value="'
		.$value.'"'.$spellcheck.$size.$disabled.$readonly
		.$onchange.$autocomplete.$maxLength.$hint.$required.$autofocus.' />';
	}
}
