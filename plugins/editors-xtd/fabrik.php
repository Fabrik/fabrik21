<?php
/**
 * @package		Joomla
 * @subpackage Fabrik
 * @copyright	Copyright (C) 2005 - 2009 fabrik
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! & Fabrik are free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Editor Image buton
 *
 * @package Editors-xtd
 * @since 1.5
 */
class plgButtonFabrik extends JPlugin
{
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param 	object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgButtonImage(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Display the button
	 *
	 * @return array A two element array of ( imageName, textToInsert )
	 */

	function onDisplay($name)
	{
		$app =& JFactory::getApplication();
		$db = JFactory::getDBO();

		$doc 		=& JFactory::getDocument();
		$template 	= $app->getTemplate();
		$id = JRequest::getVar('cid');
		if (is_array($id)){
			$id = $id[0];
		}
		$section = JRequest::getInt('section', JRequest::getInt('sectionid'));
		$db->setQuery("select catid from #__content where id = $id");
		$catid = $db->loadResult();

		$link = 'index.php?option=com_fabrik&c=form&task=cck&tmpl=component&e_name='.$name.'&catid='.$catid.'&section='.$section.'&cid='.$id;

		JHTML::_('behavior.modal');

		$doc->addStyleDeclaration(
		".button2-left .fabrik{
			background:transparent url(../administrator/components/com_fabrik/images/j_button2_fabrik.png) no-repeat right top;
		}"
		);
		if (method_exists($this->_subject, 'getSelectedContent')){
			//my proposed addition to J would use this
			//see http://groups.google.com/group/joomla-dev-cms/t/a5c0c2a6c655a091?hl=en-GB
			$getsel = "var fn = " . $this->_subject->getSelectedContent($name);
		} else {
			$editorClass = get_class($this->_subject->_editor);
			$getsel = '';
			switch( $editorClass ){
				case 'plgEditorTinymce':
				case 'plgEditorJCE':
					$getsel = "var fn = function(){return tinyMCE.activeEditor.selection.getContent();};
					";
					break;
				case 'plgEditorNone':
					$getsel = "var fn = function(){
if(window.ie)
{
var content=document.getElementById('$name');
	content.focus();
	var selection=document.selection.createRange();
	var str=selection.text;
}else{
	var content=document.getElementById('$name');
	var str=content.getValue().substring(content.selectionStart, content.selectionEnd);
}
return str;
};
";
					break;
				default:
					JError::raiseNotice(500, 'The ' . $editorClass . ' is not supported by the Fabrik extended editor button');
					break;
			}
		}
		$doc->addScriptDeclaration("
		function getESelection(){
		$getsel
		var c = fn();
		c = \$A(c.split(' '));
		document.getElement('a[title=Fabrik]').href = '$link';
		c.each(function(pair){
			var bits = pair.split('=');
			switch(bits[0]){
				case 'rowid':
					document.getElement('a[title=Fabrik]').href += '&' + bits[0] + '=' +bits[1].replace('}', '');
					break;
				case 'layout':
				case 'view':
					document.getElement('a[title=Fabrik]').href += '&cck_' + bits[0] + '=' +bits[1].replace('}', '');
					break;
			}
		});
		}
		");

		$button = new JObject();
		$button->set( 'modal', true );
		$button->set('onclick', 'getESelection();');
		$button->set( 'link', $link );
		$button->set( 'text', JText::_( 'Fabrik' ) );
		$button->set( 'name', 'fabrik' );
		$button->set( 'options', "{handler: 'iframe', size: {x: 570, y: 400}}" );

		return $button;
	}
}