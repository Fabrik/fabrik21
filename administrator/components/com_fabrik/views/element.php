<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewElement {

	/**
	 * set up the menu when viewing the list of  validation rules
	 */

	function setElementsToolbar()
	{
		JToolBarHelper::title(JText::_('ELEMENTS'), 'fabrik-element.png');
		JToolBarHelper::customX('addToTableView', 'publish.png', 'publish_f2.png', JText::_('ADD TO TABLE VIEW'));
		JToolBarHelper::customX('removeFromTableView', 'unpublish.png', 'unpublish_f2.png', JText::_('REMOVE FROM TABLE VIEW'));
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::customX('copySelectGroup', 'copy.png', 'copy_f2.png', 'Copy');
		JToolBarHelper::deleteList('', 'checkRemove');
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}

	/**
	 * menu setup for when you are confirming the changing of an element's field structure
	 */

	function setUpdateConfirmElementToolbar()
	{
	  JToolBarHelper::title(JText::_('UPDATEFIELDSTRUCTURE') , 'fabrik-element.png');
	  JToolBarHelper::save('elementUpdate');
		JToolBarHelper::cancel('elementRevert');
	}

	/**
	 * menu setup for when you are confirming the changing of an element's field structure
	 */

	function setCopyElementToolbar()
	{
	  JToolBarHelper::title(JText::_('SELECT_GROUPS_TO_COPY_ELEMENTS_TO') , 'fabrik-element.png');
	  JToolBarHelper::save('copy');
		JToolBarHelper::cancel();
	}

	/**
	 * set up the menu when editing the validation rule
	 */

	function setElementToolbar()
	{
		$task = JRequest::getVar('task', '', 'method', 'string');
		JToolBarHelper::title($task == 'add' ? JText::_('ELEMENT') . ': <small><small>[ '. JText::_('NEW') .' ]</small></small>' : JText::_('ELEMENT') . ': <small><small>[ '. JText::_('EDIT') .' ]</small></small>', 'fabrik-element.png');
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::cancel();
	}

	function setCheckRemoveToolBar()
	{
		JToolBarHelper::title(JText::_('DELETE') . ' ' . JText::_('ELEMENT') , 'fabrik-element.png');
		JToolBarHelper::apply('remove');
		JToolBarHelper::cancel();
	}

	function editJs($row, $pluginManager, $lists, $params)
	{
		FabrikHelperHTML::mootools();
		FabrikHelperHTML::tips();
		$fbConfig =& JComponentHelper::getParams('com_fabrik');
		FabrikHelperHTML::script('namespace.js', 'administrator/components/com_fabrik/views/', true);
		//FabrikHelperHTML::script('element.js', 'components/com_fabrik/views/form/', true);
		FabrikHelperHTML::script('adminelement.js', 'administrator/components/com_fabrik/views/', true);
		$document =& JFactory::getDocument();
		$js = "/* <![CDATA[ */

	function submitbutton(pressbutton) {
		var adminform = document.adminForm;
		/*  do field validation */
		if (pressbutton == 'cancel') {
			submitform( pressbutton);
			return;
   	}
   	if($('javascriptActions').getElements('select[name^=js_action]').get('value').contains('')) {
   		alert('". JText::_('ENSURE_JS_EVENT_SELECTED', true). "');
   		return;
   	}

   	var empty = $('javascriptActions').getElements('select[name^=js_e_condition]').some(function(s) {
   		var c = s.findClassUp('adminform');
   		return (c.getElement('textarea').get('value') == '' && s.get('value') == '');
   	});
   	if(empty) {
   		alert('". JText::_('ENSURE_JS_CONDITION_SELECTED', true). "');
   		return;
   	}

   	var myRegxp = /^([a-zA-Z0-9_]+)$/;

		if(myRegxp.test(adminform.name.value)==false || adminform.name.value.indexOf('___') != -1) {
			alert( '". JText::_('PLEASE_ENTER_A_VALID_ELEMENTNAME', true )."');
		} else {
			submitbutton2( pressbutton);
		}
	}

	function submitbutton2(pressbutton) {";
		if ($fbConfig->get('fbConf_wysiwyg_label', false)) {
			$editor =& FabrikHelperHTML::getEditor();
			$js .=  $editor->save('label');
		}
		$js .="//iternate through the plugin controllers to match selected plugin
		var adminform = document.adminForm;
		var er = false;

		\$A(pluginControllers).each(function(plugin) {
			if($('detailsplugin').value == plugin.element) {
				var r = plugin.controller.onSave();
				if(r == false) {
					er = true;
				}
			}
		});

		if(er === false) {
			submitform( pressbutton);
		}
		return false;
	}
	window.addEvent('domready', function() {\n";

		$opts = new stdClass();
		$opts->plugin = $row->plugin;
		$opts->parentid = $row->parent_id;
		$opts->jsevents = $lists['jsActions'];
		$opts->elements = $lists['elements'];
		$js .= "\tvar options = ".FastJSON::encode($opts) . ";\n";

		$js .= "\tvar lang = {'jsaction':'".JText::_('ACTION')."','code':'". JText::_('CODE')."'};\n".
		"var aPlugins = [];\n";

	  $c = 0;
		//	set up the template code for creating validation plugin interfaces
	  foreach ($pluginManager->_plugIns['validationrule'] as $usedPlugin => $oPlugin) {
	  	$pluginParams = new fabrikParams('', $oPlugin->_xmlPath, 'fabrikplugin');
			$oPlugin->_pluginParams =& $pluginParams;
			$pluginParams->_duplicate = true;
			$oPlugin->_adminVisible = false;
			$o = new stdClass();
			$o->$usedPlugin = $oPlugin->_pluginLabel;
			$o->value = $usedPlugin;
			$o->label = $oPlugin->_pluginLabel;
			$o->html = $oPlugin->renderAdminSettings($usedPlugin, $row, $pluginParams, 0);
			$o = FastJSON::encode($o);
			$js .= "aPlugins.push($o);\n";
			$c ++;
		}

		$js .= "\tvar controller = new fabrikAdminElement(aPlugins, options, lang);\n";

		//add in active validations
		$usedPlugins = $params->get('validation-plugin', '', '_default', 'array');
		$c = 0;

		foreach ($usedPlugins as $usedPlugin) {
			$plugin =& $pluginManager->_plugIns['validationrule'][$usedPlugin];
			$plugin->renderOrder = $c;
			unset($plugin->pluginParams);
			$pluginParams = $plugin->setParams($params);
			$pluginParams->_duplicate = true;
			$names = $pluginParams->_getParamNames();
			$plugin->_adminVisible = true;
	    $plugin->_counter = $c; //@TODO isnt this the same as renderOrder?
			$data = $oPlugin->renderAdminSettings($usedPlugin, $row, $pluginParams, $lists, 0);
			$data = addslashes(str_replace("\n", "", $data));
			$js .= "controller.addValidation('".$data."', '".$usedPlugins[$c]."');\n";
			$c ++;
		}

		foreach ($pluginManager->_plugIns['element'] as $key => $tmp) {
			$oPlugin =& $pluginManager->_plugIns['element'][$key];
			//do this to reduce the number of queries
			$oPlugin->_element =& $row;
			if (is_a($oPlugin, 'FabrikModelElement')) {
				$oPlugin->setId($row->id);
			} else {
				JError::raiseError(500, "could not load $key");
				jexit();
			}
			$js .= $oPlugin->getAdminJS();
		}

		$js .= "});
		/* ]]> */";

		$document->addScriptDeclaration($js);
	}

	function editLeft($row, $lists, $params, $form, $fbConfig)
	{
		?>
		<fieldset class="admintable">
			<legend><?php echo JText::_('DETAILS');?></legend>
			<table class="admintable">
				<tr>
					<td class="key">
						<label for="name">
						<?php echo JHTML::_('tooltip', JText::_('NAMEDESC'), JText::_('NAME'), 'tooltip.png', JText::_('NAME', true)); ?>
						</label>
					</td>
					<td>
						<input class="inputbox" type="text" id="name" name="name" size="75" value="<?php echo $row->name; ?>" />
						<input type="hidden" id="name_orig" name="name_orig" value="<?php echo $row->name; ?>" />
						<input type="hidden" id="plugin_orig" name="plugin_orig" value="<?php echo $row->plugin; ?>" />
					</td>
				</tr>
				<tr>
					<td class="key">
						<label for="label">
							<?php echo JHTML::_('tooltip', JText::_('LABELDESC'), JText::_('LABEL'), 'tooltip.png', JText::_('LABEL', true));?>
						</label>
					</td>
					<td>
						<?php if ($fbConfig->get('fbConf_wysiwyg_label', false)) {
							$editor =& FabrikHelperHTML::getEditor();
							echo $editor->display( 'label', $row->label, '100%', '200', '50', '5', false);
						} else { ?>
							<input class="inputbox" type="text" id="label" name="label" size="75" value="<?php echo $row->label; ?>" />
						<?php }
						?>
					</td>
				</tr>
			</table>
			<?php
			echo $form->render('details', 'basics');
			?>
		</fieldset>
		<?php
	}

	function editPluginOptions($pluginManager, $lists, $row)
	{
		?>
		<fieldset class="admintable">
			<legend><?php echo JText::_('OPTIONS');?></legend>
			<?php
			foreach ($pluginManager->_plugIns['element'] as $key => $tmp) {
					$oPlugin =& $pluginManager->_plugIns['element'][$key];
					$oPlugin->setId($row->id);
					//do this to reduce the number of queries
					$oPlugin->_element =& $row;
					$oPlugin->renderAdminSettings($lists);
				}
			?>
		</fieldset>
		<?php
	}

	function editPublishing($form)
	{
		?>
		<fieldset>
		<?php

		echo $form->render('details', 'publishing');
		echo $form->render('params', 'publishing2');
		?>
		</fieldset>
		<fieldset class="admintable">
			<legend><?php echo JText::_('RSS');?></legend>
			<?php
			echo $form->render('params', 'rss');
			?>
			</fieldset>
			<fieldset>
			<legend><?php echo JText::_('TIPS')?></legend>
			<?php echo $form->render('params');?>
			</fieldset>

		<?php
	}

	function editTableSettingsNoFieldset($form)
	{
		echo $form->render('details', 'tablesettings');
		echo $form->render('params', 'tablesettings2');
		echo $form->render('details', 'filtersettings');
		echo $form->render('params', 'filtersettings2');
		echo $form->render('params', 'tablecsssettings');
		echo $form->render('params', 'calculations');
	}

	function editTableSettings($form)
	{
		?>
		<fieldset>
				<?php
				echo $form->render('details', 'tablesettings');
				echo $form->render('params', 'tablesettings2');
				?>
			</fieldset>
			<fieldset>
				<legend><?php echo JText::_('FILTERS'); ?></legend>
				<?php
				echo $form->render('details', 'filtersettings');
				echo $form->render('params', 'filtersettings2');
				?>
			</fieldset>

			<fieldset>
				<legend><?php echo JText::_('TABLECSS'); ?></legend>
					<?php echo $form->render('params', 'tablecsssettings')?>
			</fieldset>

			<fieldset>
				<legend><?php echo JText::_('CALCULATIONS'); ?></legend>
				<?php echo $form->render('params', 'calculations');?>
			</fieldset>
		<?php
	}

	function editValidations($form)
	{
	?>
	<fieldset>
			<legend><?php echo JText::_('VALIDATIONS'); ?></legend>
			<div id="elementValidations"></div>
			<a href="#" class="addButton" id="addValidation"><?php echo JText::_('ADD'); ?></a>
		</fieldset>
	<?php }

	function editJavascript($form)
	{
		?>
		<fieldset>
			<legend><?php echo JText::_('JAVASCRIPT'); ?></legend>
			<div id="javascriptActions"></div>
			<a class="addButton" href="#" id="addJavascript"><?php echo JText::_('ADD'); ?></a>
		</fieldset>
		<?php
	}

	function edit($row, $pluginManager, $lists, $params, $form)
	{
		JFilterOutput::objectHTMLSafe($row, ENT_QUOTES, '');
		FabrikViewElement::setElementToolbar();
		FabrikViewElement::editJs($row, $pluginManager, $lists, $params);
		jimport('joomla.html.pane');
		$pane	=& JPane::getInstance();
		$fbConfig =& JComponentHelper::getParams('com_fabrik');
		JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
		JRequest::setVar('hidemainmenu', 1);
		$document =& JFactory::getDocument();

		 if ($fbConfig->get('fbConf_wysiwyg_label', false)) {
			$editor =& FabrikHelperHTML::getEditor();
		 }

	?>
<form action="index.php" method="post" name="adminForm" >
<?php if ($row->parent_id != 0) {
	?>
	<div id="system-message">
	<dl>
		<dd class="notice">
		<ul>
			<li>
				<?php echo JText::_('THIS ELEMENTS PROPERTIES ARE LINKED TO') ?>:
			</li>
			<li>
				<a href="#" id="swapToParent" class="element_<?php echo $lists['parent']->id ?>"><?php echo $lists['parent']->label ?></a>
			</li>
			<li>
				<label><input id="unlink" name="unlink" id="unlinkFromParent" type="checkbox"> <?php echo JText::_('UNLINK') ?></label>
			</li>
		</ul>
		</dd>
	</dl>
	</div>
<?php }?>
<?php echo JHTML::_('form.token');?>
	<input type="hidden" name="id" value="<?php echo (int)$row->id; ?>" />
<table style="width:100%" id="elementFormTable" >
<tbody>
	<tr>
		<td style="width:50%" valign="top">
			<?php FabrikViewElement::editLeft($row, $lists, $params, $form, $fbConfig);
			FabrikViewElement::editPluginOptions($pluginManager, $lists, $row )?>
		</td>
		<td style="width:50%" valign="top">
		<?php
		echo $pane->startPane( "content-pane");

		echo $pane->startPanel(JText::_('PUBLISHING'), "publish-page");
		FabrikViewElement::editPublishing($form);
		echo $pane->endPanel();

		echo $pane->startPanel(JText::_('TABLE SETTINGS'), "table-page");
		FabrikViewElement::editTableSettings($form);
		echo $pane->endPanel();

		echo $pane->startPanel(JText::_('VALIDATIONS'), "validations-page");
		FabrikViewElement::editValidations($form);
		echo $pane->endPanel();

		echo $pane->startPanel(JText::_('JAVASCRIPT'), "javascript-page");
		FabrikViewElement::editJavascript($form);
		echo $pane->endPanel();

		echo $pane->endPane();
		?></td>
	</tr>
	</tbody>
</table>
	<input type="hidden" name="task" value="save" />
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="c" value="element" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="redirectto" value="" />
	</form>
		<?php
		echo JHTML::_('behavior.keepalive');
		FabrikHelperHTML::cleanMootools();
	}

	/**
	* Display all available validation rules
	* @param array array of validation_rule objects
	* @param object page navigation
	* @param array lists
	*/

	function show($elements, $pageNav, $lists)
	{
		FabrikViewElement::setElementsToolbar();
		$user = &JFactory::getUser();
		?>
		<form action="index.php" method="post" name="adminForm">
			<table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr>
					<td><?php echo JText::_('NAME').": "; ?>
						<input type="text" name="filter_elementName" value="<?php echo $lists['search'];?>" class="text_area" onChange="document.adminForm.submit();" />
					</td>
					<td>
						<?php echo $lists['filter_formId']?>
					</td>
					<td>
						<?php echo $lists['groupId']; ?>
					</td>
					<td>
						<?php echo $lists['elementId']; ?>
					</td>
					<td>
						<?php echo $lists['filter_showInTable'];?>
					</td>
					<td>
						<?php echo $lists['filter_published']; ?>
					</td>
				</tr>
			</table>
			<table class="adminlist">
			<thead>
			<tr>
				<th width="2%"><?php echo JHTML::_('grid.sort',  '#', 'e.id', @$lists['order_Dir'], @$lists['order']); ?></th>
				<th width="2%"> <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($elements);?>);" /></th>
				<th width="20%" >
					<?php echo JHTML::_('grid.sort', 'Name', 'e.name', @$lists['order_Dir'], @$lists['order']); ?>
				</th>
				<th width="20%" >
					<?php echo JHTML::_('grid.sort', 'Label', 'e.label', @$lists['order_Dir'], @$lists['order']); ?>
				</th>
				<th width="15%" >
					<?php echo JText::_('FULL_ELEMENT_NAME'); ?>
				</th>
				<th width="15%" >
					<?php echo JHTML::_('grid.sort', 'Group', 'g.name', @$lists['order_Dir'], @$lists['order']); ?>
				</th>

				<th width="10%" >
					<?php echo JHTML::_('grid.sort', 'Element type', 'plugin', @$lists['order_Dir'], @$lists['order']); ?>
				</th>
				<th width="3%">
					<?php echo JHTML::_('grid.sort', 'Show in table', 'show_in_table_summary', @$lists['order_Dir'], @$lists['order']); ?>
				</th>
				<th width="3%">
					<?php echo JHTML::_('grid.sort', 'Published', 'e.state', @$lists['order_Dir'], @$lists['order']); ?>
				</th>
				<th width="10%">
					<?php echo JHTML::_('grid.sort',  'Order', 'e.ordering', @$lists['order_Dir'], @$lists['order']); ?>
					<?php echo JHTML::_('grid.order', $elements); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
				<tr>
				<td colspan="10">
					<?php echo $pageNav->getListFooter(); ?>
				</td>
				</tr>
			</tfoot>
			<tbody>
			<?php $k = 0;
			for ($i = 0, $n = count($elements); $i < $n; $i ++) {
				$row 				= & $elements[$i];
				$checked		= JHTML::_('grid.checkedout',   $row, $i);
				$link 			= JRoute::_('index.php?option=com_fabrik&c=element&task=edit&cid='. $row->id);
				$row->published = $row->state;
				$published		= JHTML::_('grid.published', $row, $i);

				//PN 19-Jun-11: Create the element plugin model - used for selfDiagnose():
				$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
				$elementModel = $pluginManager->getPlugIn($row->plugin, 'element');
				$elementModel->setId($row->id);
				$elementModel->getElement(true);
				$elementSelfDiagnose = $elementModel->selfDiagnose();

				?>
				<tr class="<?php echo "row$k"; ?>">
					<td width="4%">
					<?php if ($row->parent_id != 0) {
				echo "<a href='index.php?option=com_fabrik&c=element
&task=edit&cid=".$row->parent_id."'>" . JHTML::image('media/com_fabrik/images/link.png', JText::_('LINKED_ELEMENT'), 'title="'.JText::_('LINKED_ELEMENT').'"') . '</a>&nbsp';
			}else{
				echo JHTML::image('media/com_fabrik/images/link_break.png', JText::_('PARENT_ELEMENT'), 'title="'.JText::_('PARENT_ELEMENT').'"').'&nbsp;';
			}?>
			<?php echo $row->id; ?>
					</td>
					<td>
						<?php echo $checked; ?>
					</td>
					<td>

						<?php
						if ($row->checked_out && ( $row->checked_out != $user->get('id'))) {
							echo $row->name;
						} else {
							echo '<a ';
								//PN 19-Jun-11: If there's an element error, display the link in red with an error mesage:
								if($elementSelfDiagnose)
								{
									echo 'style="color:red" ';
									echo 'title="'.$elementSelfDiagnose.'" ';
								}
								echo 'href="'.$link.'">';
								echo $row->name;
							echo '</a>';
						} ?>

					</td>
					<td>

						<?php echo $row->label;?>
					</td>
					<td>
						<?php echo $row->tablename;?>
					</td>

					<td>
						<a href="index.php?option=com_fabrik&c=group&task=edit&cid=<?php echo $row->group_id?>">
							<?php echo ($row->group_name); ?>
						</a>
					</td>
					<td><?php echo htmlentities($row->pluginlabel); ?></td>
					<td>
					<?php if ($row->show_in_table_summary == "1") {
						$img = 'publish_g.png';
						$alt = JText::_('SHOW IN TABLE');
					} else {
						$img = "publish_x.png";
						$alt = JText::_('HIDE IN TABLE');
					}
					?>
						<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $row->show_in_table_summary ? "removeFromTableview" : "addToTableView";?>');">
							<img src="images/<?php echo $img;?>" border="0" alt="<?php echo $alt; ?>" />
						</a>
					</td>
					<td>
						<?php echo $published;?>
					</td>
					<td class="order">
					<?php $condition = $row->group_id == @ $elements[$i -1]->group_id;
					echo '<span>' . $pageNav->orderUpIcon($i, ($condition), 'orderUpElement') . '</span>';
					$condition = $row->group_id == @ $elements[$i +1]->group_id;
					echo '<span>' . $pageNav->orderDownIcon($i, $n, ($condition), 'orderDownElement') . '</span>';
					?>
						<input type="text" name="order[]" size="5" value="<?php echo $row->ordering; ?>" class="text_area" style="text-align: center" />
					</td>
				</tr>
			<?php
			$k = 1 - $k;
		} ?>
			</tbody>
		</table>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="c" value="element" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" />
		<?php echo JHTML::_('form.token'); ?>
	</form>
	<?php }

	/**
	 * decide if you want to drop the tables columns for each element marked to be deleted
	 *
	 * @param array $elements
	 */

	function checkRemove($elements)
	{
		FabrikViewElement::setCheckRemoveToolBar();
		?>
		<h1><?php echo JText::_('DO YOU WANT TO DROP THE DATABASE COLUMN AS WELL?') ?></h1>
		<form action="index.php" method="post" name="adminForm" id="adminForm">
		<table class="adminlist">
			<thead>
				<tr>
					<th><?php echo JText::_('Drop') ?></th>
					<th><?php echo JText::_('Element') ?></th>
				</tr>
			</thead>
				<tbody>
				<?php
				$c = 0;
				foreach ($elements as $element) { ?>
					<tr class="row<?php echo $c % 2; ?>">
						<td>
							<label><input type="radio" name="drop[<?php echo $element->id ?>][]" value="0" checked="checked" /><?php echo JText::_('No') ?></label>
							<label><input type="radio" name="drop[<?php echo $element->id ?>][]" value="1" /><?php echo JText::_('Yes') ?></label>
						</td>
						<td>
							<?php echo $element->label; ?>
							<input type="hidden" name="cid[]" value="<?php echo $element->id ?>" />
						</td>
					</tr>
				<?php $c ++;
				} ?>
				</tbody>
			</table>
			<input type="hidden" name="option" value="com_fabrik" />
			<input type="hidden" name="c" value="element" />
			<input type="hidden" name="task" value="remove" />
			<?php echo JHTML::_('form.token'); ?>
		</form>
		<?php
	}

	function confirmElementUpdate(&$model)
	{
		$session =& JFactory::getSession();

	  JRequest::setVar('hidemainmenu', 1);
	  FabrikViewElement::setUpdateConfirmElementToolbar();
	  $element = $model->getElement();
	  $db =& JFactory::getDBO();
	  $tableModel =& $model->getTableModel();
	  $config =& JFactory::getConfig();
    $prefix = $config->getValue('dbprefix');

	  $tablename = $tableModel->getTable()->db_table_name;
	  $hasprefix = (strstr($tablename, $prefix) === false) ? false : true;
	  $tablename = str_replace($prefix, '#__', $tablename);
	  $origDesc = $session->get('com_fabrik.admin.element.origdesc');
	  $core = array(
	  '#__banner',
'#__bannerclient',
'#__bannertrack',
'#__categories',
'#__components',
'#__contact_details',
'#__content',
'#__content_frontpage',
'#__content_rating',
'#__core_acl_aro',
'#__core_acl_aro_groups',
'#__core_acl_aro_map',
'#__core_acl_aro_sections',
'#__core_acl_groups_aro_map',
'#__core_log_items',
'#__core_log_searches',
'#__fabrik_calendar_events',
'#__fabrik_connections',
'#__fabrik_elements',
'#__fabrik_formgroup',
'#__fabrik_forms',
'#__fabrik_groups',
'#__fabrik_joins',
'#__fabrik_jsactions',
'#__fabrik_packages',
'#__fabrik_plugins',
'#__fabrik_tables',
'#__fabrik_validations',
'#__fabrik_visualizations',
'#__groups',
'#__menu',
'#__menu_types',
'#__messages',
'#__messages_cfg',
'#__migration_backlinks',
'#__modules',
'#__modules_menu',
'#__newsfeeds',
'#__plugins',
'#__poll_data',
'#__poll_date',
'#__poll_menu',
'#__polls',
'#__sections',
'#__session',
'#__stats_agents',
'#__templates_menu',
'#__users',
'#__weblinks'
	 );

	  if (in_array($tablename, $core)) {
	    JError::raiseNotice(E_WARNING, 'The table you are updating is a core Joomla or Fabrik table');
	  } else {
		  if ($hasprefix) {
		    JError::raiseNotice(E_WARNING, 'The table you are updating to has a prefix of "jos_", whilst it is not a core Joomla or Fabrik table, it may well be used by other components');
		  }
	  }
	  $newDesc = $session->get('com_fabrik.admin.element.newdesc');
	  $oldName = $session->get('com_fabrik.admin.element.oldname');;
	  $origPlugin = JRequest::getVar('origplugin');
	  ?>

	  <form name="adminForm" method="post" action="index.php">
	  	<ul style="list-style:none;font-weight:bold;color:#0055BB;background:#C3D2E5 url(templates/khepri/images/notice-info.png) no-repeat scroll 4px center;padding:10px;margin-bottom:10px;border-top:3px solid #84A7DB;border-bottom:3px solid #84A7DB">
	  	  <?php if($db->NameQuote($element->name) !== $oldName) { ?>
	  			<li style="padding-left:30px"><?php echo JText::sprintf('UPDATEELEMENTNAME', $oldName, $db->NameQuote($element->name)  )?></li>
	  		<?php }?>
	  		<?php if (strtolower($origDesc) !== strtolower($newDesc)) {?>
	  			<li style="padding-left:30px"><?php echo JText::sprintf('UPDATEELEMENTSTRUCTURE', $origDesc, $newDesc )?></li>
	  		<?php }?>
	  	</ul>
	  	<?php echo JText::_('UPDATEFIELDSTRUCTUREDESC')?>
	  	<input type="hidden" name="option" value="com_fabrik" />
	  	<input type="hidden" name="c" value="element" />
	  	<input type="hidden" name="task" value="elementUpdate" />
	  	<input type="hidden" name="id" value="<?php echo $element->id?>" />
	  	<input type="hidden" name="origtaks" value="<?php echo JRequest::getVar('origtaks')?>" />
	  	<input type="hidden" name="oldname" value="<?php echo $oldName?>" />
	  	<input type="hidden" name="origplugin" value="<?php echo $origPlugin?>" />
	  		<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive'); ?>
	  </form>
	  <?php
	}

	function copySelectGroup($elements, $groups)
	{
		?>
		 <form name="adminForm" method="post" action="index.php">
		 	<table class="adminlist">
		 		<tbody>
		 			<?php foreach ($elements as $element) {?>
		 				<tr>
		 					<td><input type="text" name="name[<?php echo $element->id;?>]" value="<?php echo $element->label?>" /></td>
		 					<td>
		 					<select name="group[<?php echo $element->id;?>]">
		 						<?php foreach ($groups as $group) {?>
		 							<option value="<?php echo $group->id?>"><?php echo $group->name?></option>
		 						<?php }?>
		 					</select>
		 					</td>
		 				</tr>
		 			<?php }?>
		 		</tbody>
		 		<thead>
		 			<tr>
		 				<th><?php echo JText::_('ELEMENT')?></th>
		 				<th><?php echo JText::_('COPY_TO_GROUP')?></th>
		 			</tr>
		 		</thead>
		 	</table>
		 <input type="hidden" name="option" value="com_fabrik" />
	  	<input type="hidden" name="c" value="element" />
	  	<input type="hidden" name="task" value="copy" />

	  	<?php echo JHTML::_('form.token');
			echo JHTML::_('behavior.keepalive'); ?>
		 </form>
		<?php
	}
}
?>