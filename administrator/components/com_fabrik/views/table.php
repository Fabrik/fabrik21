<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewTable {

  /**
   * set up the menu when viewing the list of  tables
   */

  function setTablesToolbar()
  {
    JToolBarHelper::customX('import', 'upload.png', 'upload_f2.png', 'Import', false);
    JToolBarHelper::publishList();
    JToolBarHelper::unpublishList();
    JToolBarHelper::title(JText::_('TABLE'), 'fabrik-table.png');
    JToolBarHelper::customX('copy', 'copy.png', 'copy_f2.png', 'Copy');
    JToolBarHelper::deleteList();
    JToolBarHelper::editListX();
    JToolBarHelper::addNewX();
    JToolBarHelper::preferences('com_fabrik', '400');
  }

  /**
   * set up the menu when editing the tables
   */

  function setTableToolbar()
  {
    $task = JRequest::getVar('task', '', 'method', 'string');
    JToolBarHelper::title($task == 'add' ? JText::_('TABLE') . ': <small><small>[ '. JText::_('NEW') .' ]</small></small>' : JText::_('TABLE') . ': <small><small>[ '. JText::_('EDIT') .' ]</small></small>', 'fabrik-table.png');
    JToolBarHelper::save();
    JToolBarHelper::apply();
    JToolBarHelper::cancel();
  }

  /**
   * import toolbar
   *
   */
  function setImportToolbar()
  {
    JToolBarHelper::title(JText::_('Import'), 'install');
    JToolBarHelper::customX('importChooseElements', 'upload.png', 'upload_f2.png', 'Import', false);
    JToolBarHelper::cancel();
  }

  /**
   * toolbar for when the user select which fields to import
   * from the csv file into
   * their new table
   */

  function setImportChooseElementsToolbar()
  {
    JToolBarHelper::title(JText::_('Select fields'), 'install');
    JToolBarHelper::customX('doImport', 'save.png', 'save_f2.png', 'Save', false);
    JToolBarHelper::cancel();
  }

  /**
   * Display the form to add or edit a table
   * @param object table
   * @param array the drop down lists used on the form
   * @param array connection tables
   * @param object menus
   * @param string compoent action
   * @param int form id that the table links to?
   * @param object parameters
   * @param object plugin mangager
   * @param object table model
   */

  function edit($row, $lists, $connectionTables, $menus, $fabrikid, $params, $pluginManager, $model, $form )
  {
    JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
    FabrikViewTable::setTableToolbar();
    JRequest::setVar('hidemainmenu', 1);
    $document =& JFactory::getDocument();
    FabrikHelperHTML::script('namespace.js', 'administrator/components/com_fabrik/views/', true);
    FabrikHelperHTML::script('pluginmanager.js', 'administrator/components/com_fabrik/views/', true);
    FabrikHelperHTML::script('admintable.js', 'administrator/components/com_fabrik/views/', true);
    JFilterOutput::objectHTMLSafe($row);
    jimport('joomla.html.editor');
    //just until joomla uses mootools 1.2
    FabrikHelperHTML::mootools();
    require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php');
    jimport('joomla.html.pane');
    FabrikHelperHTML::tips();
    $editor =& FabrikHelperHTML::getEditor();
    $pane	=& JPane::getInstance();

    $fbConfig =& JComponentHelper::getParams('com_fabrik');
    $opts = new stdClass();
    $opts->mooversion	= (FabrikWorker::getMooVersion() == 1) ? 1.2 : 1.1;
    $opts 						= FastJSON::encode($opts);

    $lang = new stdClass();
    $lang->action					= JText::_('ACTION');
    $lang->do							= JText::_('DO');
    $lang->del						= JText::_('DELETE');
    $lang->in							= JText::_('IN');
    $lang->on							= JText::_('ON');
    $lang->options				= JText::_('OPTIONS');
    $lang->please_select	= JText::_('COM_FABRIK_PLEASE_SELECT');
    $lang 						= FastJSON::encode( $lang);

    $js = "window.addEvent('domready', function() {
  		var aPlugins = [];\n";
    $js .= $pluginManager->getAdminPluginJs('table', $row, $lists);
    $js .= "controller = new TablePluginManager(aPlugins, $lang, $opts);\n";
    $usedPlugins		= $params->get('plugin', '', '_default', 'array');

    $js .= $pluginManager->getAdminSelectedPluginJS('table', $row, $lists, $params);

    $js .= "});\n";
    $js .= "var connectiontables = new Array;\n";
    $i = 0;
    if (is_array($connectionTables)) {
      foreach ($connectionTables as $k => $items) {
        foreach ($items as $v) {
          $js .= "connectiontables[".$i ++."] = new Array('$k','".addslashes($v->value)."','".addslashes($v->text)."');\n\t\t";
        }
      }
    }
    $js .= "
			function submitbutton(pressbutton) {
				if (pressbutton == 'cancel') {
					submitform( pressbutton);
					return;
				}
				var err = '';";
    $js .= $editor->save('introduction');
    $js .= "if(\$('label').value == '') {
					err = err +'".JText::_('PLEASE ENTER A TABLE LABEL', true ). '\n'."';
				}

				if($('database_name')) {
					if($('database_name').value == '') {
						if($('connection_id')) {
							if($('connection_id').value == '-1') {
								err = err +'".JText::_('PLEASE SELECT A CONNECTION', true ). '\n' ."';
							}
						}

						if($('tablename')) {
							if($('tablename').value == '' || $('tablename').value == '-1') {
								err = err + '".JText::_('PLEASE SELECT A DATABASE TABLE', true ). '\n' ."';
							}
						}
					}
				}
				if (err == '') {
					submitform( pressbutton);
				}else{
					alert (err);
					return false;
				}
			}
			var joinCounter = 0;";
    $document->addScriptDeclaration($js);
    FabrikHelperHTML::cleanMootools();

   ;?>
<form action="index.php" method="post" name="adminForm">
<table style="width: 100%">
	<tr>
		<td style="width: 50%" valign="top">
		<fieldset class="adminform"><legend><?php echo JText::_('DETAILS');?></legend>
		<table class="admintable">
			<tr>
				<td class="key"><label for="label"><?php echo JText::_('LABEL'); ?></label></td>
				<td><input class="inputbox" type="text" id="label" name="label"
					size="50" value="<?php echo $row->label?>" /></td>
			</tr>
			<tr>
				<td class="key"><?php echo JText::_('INTRODUCTION'); ?></td>
				<td><?php echo $editor->display( 'introduction', $row->introduction, '100%', '200', '45', '25', false); ?>
				</td>
			</tr>
		</table>
		</fieldset>

		<fieldset><legend><?php echo JText::_('FILTERS');?></legend>
			<table class="admintable">
				<tr>
					<td class="key"><?php echo JText::_('FILTER TRIGGER');?></td>
					<td><?php echo $lists['filter_action']; ?></td>
				</tr>
			</table>
			<?php echo $form->render('params', 'filters');?>
		</fieldset>

		<fieldset><legend><?php echo JText::_('NAVIGATION')?></legend>
			<table class="admintable">
			<tr>
				<td class="key"><label for="rows_per_page"><?php echo JText::_('ROWS PER PAGE'); ?></label>
				</td>
				<td><input type="text" name="rows_per_page" id="rows_per_page"
					class="inputbox"
					value="<?php echo ($row->rows_per_page != '') ? $row->rows_per_page : 10; ?>"
					size="3" /></td>
			</tr>
			</table>
			<?php echo $form->render('params', 'navigation');?>
		</fieldset>

		<fieldset><legend><?php echo JText::_('LAYOUT');?></legend>
			<table class="admintable">
			<tr>
				<td class="key"><?php echo JText::_('TEMPLATE'); ?></td>
				<td><?php print_r($lists['tableTemplates']); ?></td>
			</tr>
			</table>
			<?php echo $form->render('params', 'layout');?>
		</fieldset>

		<fieldset><legend><?php echo JText::_('COM_FABRIK_DETAIL_LINKS'); ?></legend>
			<?php echo $form->render('params', 'detaillinks'); ?>
		</fieldset>

		<fieldset><legend><?php echo JText::_('COM_FABRIK_EDIT_LINKS'); ?></legend>
			<?php echo $form->render('params', 'editlinks'); ?>
		</fieldset>

		<fieldset><legend><?php echo JText::_('COM_FABRIK_ADD_LINK'); ?></legend>
			<?php echo $form->render('params', 'addlink'); ?>
		</fieldset>

		<fieldset><legend><?php echo JText::_('Notes'); ?></legend> <?php echo $form->render('params', 'notes'); ?>
		</fieldset>

		<fieldset><legend><?php echo JText::_('Advanced'); ?></legend> <?php echo $form->render('params', 'advanced'); ?>
		</fieldset>
    <?php if ($fbConfig->get('use_wip')) {?>
		<fieldset><legend><?php echo JText::_('WORK IN PROGRESS'); ?></legend> <?php echo $form->render('params', 'wip'); ?>
		<?php }?>


		</td>
		<td valign="top"><?php
		echo $pane->startPane( "content-pane");
		echo $pane->startPanel(JText::_('PUBLISHING'), "publish-page");
		echo $form->render('details');
		?>
		<fieldset><legend><?php echo JText::_('RSS OPTIONS'); ?></legend> <?php
		echo $form->render('params', 'rss');
		?></fieldset>
		<fieldset><legend><?php echo JText::_('CSV OPTIONS');?></legend> <?php echo $form->render('params', 'csv');?>
		</fieldset>
		<fieldset><legend><?php echo JText::_('SEARCH') ?></legend> <?php echo $form->render('params', 'search'); ?>
		</fieldset>
		<?php
		echo $pane->endPanel();
		echo $pane->startPanel(JText::_('ACCESS'), "access-page"); ?>
		<fieldset><legend><?php echo JText::_('ACCESS'); ?></legend> <?php echo $form->render('params', 'access');?>
		</fieldset>
		<?php
		echo $pane->endPanel();
		echo $pane->startPanel(JText::_('DATA'), "tabledata-page"); ?>
		<fieldset><legend><?php echo JText::_('DATA'); ?></legend>
		<table class="admintable">
			<tr>
				<td class="key"><?php echo JText::_('CONNECTION'); ?></td>
				<td><?php echo $lists['connections']; ?></td>
			</tr>
			<?php if($row->id == 0) { ?>
			<tr>
				<td class="key"><?php echo JText::_('CREATE NEW TABLE');?></td>
				<td><input id="database_name" name="_database_name" size="40" /></td>
			</tr>
			<tr>
				<td colspan="2"><?php echo JText::_('OR');?></td>
			</tr>
			<?php } ?>

			<tr>
				<td class="key"><?php echo JText::_('LINK TO TABLE'); ?></td>
				<td><?php echo $lists['tablename']; ?></td>
			</tr>
			<?php if ($row->id <> '') { ?>
			<tr>
				<td class="key"><label for="db_primary_key"> <?php
				echo JHTML::_('tooltip', JText::_("PRIMARY KEY DESC" ), JText::_('PRIMARY KEY'), 'tooltip.png', JText::_('PRIMARY KEY'));?>
				</label></td>
				<td><?php echo $lists['db_primary_key'];?></td>
			</tr>
			<tr>
				<td class="key"><?php echo JText::_('AUTO INCREMENT'); ?></td>
				<td>
				<label>
					<input type="radio" name="auto_inc" value="0" <?php echo $row->auto_inc ? '' : 'checked="checked"'; ?> />
					<?php echo JText::_('No')?>
				</label>
				<label>
					<input type="radio" name="auto_inc" value="1" <?php echo $row->auto_inc ? 'checked="checked"' : ''; ?> />
					<?php echo JText::_('YES')?>
				</label>
				</td>
			</tr>
			<?php } ?>
			<tr>
				<td class="key"><label for="order_by"><?php echo JText::_('ORDER BY'); ?></label></td>
				<td id="orderByTd"><?php
				for ($o = 0; $o < count($lists['order_by']); $o++) { ?>
				<div class="orderby_container" style="margin-bottom:3px">
				<?php echo $lists['order_by'][$o]; ?>
				<?php if ($row->id !== 0) {
					echo JArrayHelper::getValue($lists['order_dir'], $o, $lists['order_dir'][0]); ?>
					<a class="addOrder" href="#"><img src="components/com_fabrik/images/add.png" label="<?php echo JText::_('ADD')?>" alt="<?php echo JText::_('ADD')?>" /></a>
					<a class="deleteOrder" href="#"><img src="components/com_fabrik/images/remove.png" label="<?php echo JText::_('REMOVE')?>" alt="<?php echo JText::_('REMOVE')?>" /></a>
					<?php }?>
				</div>
				<?php }?>
				</td>
			</tr>
		</table>
		</fieldset>

		<fieldset><legend><?php echo JText::_('GROUP BY'); ?></legend>
		<table class="admintable">
			<tr>
				<td class="key"><label for="group_by"><?php echo JText::_('GROUP BY'); ?></label>
				</td>
				<td id="groupByTd"><?php echo $lists['group_by'];?></td>
			</tr>
		</table>
		<?php echo $form->render('params', 'grouping');?></fieldset>

		<fieldset><legend><?php echo JHTML::_('tooltip', JText::_('PREFILTER DESC'), JText::_('PREFILTER'), 'tooltip.png', JText::_('PREFILTER')); ?></legend>
		<a class="addButton" href="#"
			onclick="oAdminFilters.addFilterOption(); return false;"><?php echo JText::_('ADD'); ?></a>
			<?php echo $form->render('params', 'prefilter');?>
		<table class="adminform" width="100%">
			<tbody id="filterContainer">
			</tbody>
		</table>
		</fieldset>

		<fieldset><legend> <?php echo JHTML::_('tooltip', JText::_('JOIN DESC'), JText::_('JOINS'), 'tooltip.png', JText::_('JOINS'));?>
		</legend> <?php if($row->id != 0) { ?> <a href="#" id="addAJoin"
			class="addButton"><?php echo JText::_('ADD'); ?></a>
		<div id="joindtd"></div>
		<?php echo $form->render('params', 'joins');?>
		<?php
		}else{
				echo JText::_('Available once saved');
		}
		?></fieldset>
		<fieldset><legend> <?php echo JHTML::_('tooltip', JText::_('RELATED DATA DESC'), JText::_('RELATED DATA'), 'tooltip.png', JText::_('RELATED DATA'));?>
		</legend> <?php if( empty($lists['linkedtables'])) {
				echo "<i>" . JText::_('No other tables link here') . "</i>";
		}else{
				?>
				<table class="adminlist linkedTables">
					<thead>
					<tr>
					<th></th>
						<th><?php echo JText::_('TABLE');?></th>
						<th><?php echo JText::_('LINK TO TABLE');?></th>
						<th><?php echo JText::_('HEADING');?></th>
						<th><?php echo JText::_('BUTTON_TEXT');?></th>
						<th><?php echo JText::_('POPUP');?></th>
					</tr>
				</thead>
				<tbody>
				<?php $i = 0;
			foreach ($lists['linkedtables'] as $linkedTable) {?>
			<tr class="row<?php echo $i % 2;?>">
				<td class="handle"></td>
				<td><?php echo JHTML::_('tooltip', $linkedTable[1], $linkedTable[0], 'tooltip.png', $linkedTable[0]);?>
				<td><?php echo $linkedTable[2]; ?></td>
				<td><?php echo $linkedTable[3]; ?></td>
				<td><?php echo $linkedTable[5]?></td>
				<td><?php echo $linkedTable[4]; ?></td>
			</tr>
			<?php $i++;
			}?>
				</tbody>
			</table>
		<table class="adminlist linkedForms" style="margin-toip:20px">
			<thead>
				<tr>
					<th></th>
					<th><?php echo JText::_('TABLE');?></th>
					<th><?php echo JText::_('LINK TO FORM');?></th>
					<th><?php echo JText::_('HEADING');?></th>
					<th><?php echo JText::_('BUTTON_TEXT');?></th>
					<th><?php echo JText::_('POPUP');?></th>
				</tr>
			</thead>
			<tbody>
			<?php $i = 0;
			foreach ($lists['linkedforms'] as $linkedForm) {?>
			<tr class="row<?php echo $i % 2;?>">
				<td class="handle"></td>
				<td><?php echo JHTML::_('tooltip', $linkedForm['formhover'], $linkedForm[0], 'tooltip.png', $linkedForm[0]);?>
				<td><?php echo $linkedForm[1]; ?></td>
				<td><?php echo $linkedForm[2]; ?></td>
				<td><?php echo $linkedForm[4]?></td>
				<td><?php echo $linkedForm[3]; ?></td>
			</tr>
			<?php $i++;
			}?>
			</tbody>
		</table>
		<?php }?></fieldset>

		<?php
		echo $pane->startPanel(JText::_('PLUGINS'), "plugins-page");?>
		<div id="plugins"></div>
		<a href="#" id="addPlugin" class="addButton"><?php echo JText::_('ADD'); ?></a>

		<?php echo $pane->endPanel();
		echo $pane->endPane(); ?></td>
	</tr>
</table>
	<input type="hidden" name="params[isview]" value="<?php echo $params->get('isview', -1); ?>" />
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="task" value="saveTable" />
	<input type="hidden" name="c" value="table" />
	<input type="hidden" name="id" value="<?php echo $row->id;?>" />
	<input type="hidden" name="fabrikid" value="<?php echo $fabrikid; ?>" />
	<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive');
	?>
	</form>

<?php
	//$joinTypeOpts = "[['inner', '" . JText::_('INNER JOIN') ."'], ['left', '" . JText::_('LEFT JOIN') ."'], ['right', '" . JText::_('RIGHT JOIN') ."']]";

	$joinTypeOpts = array();
	$joinTypeOpts[] = array('inner', JText::_('INNER JOIN'));
	$joinTypeOpts[] = array('left', JText::_('LEFT JOIN'));
	$joinTypeOpts[] = array('right', JText::_('RIGHT JOIN'));

	$activetableOpts[] = "";
	$activetableOpts[] = $row->db_table_name;
	if (array_key_exists('joins', $lists)) {
		for ($i = 0; $i < count($lists['joins']); $i ++) {
			$j = $lists['joins'][$i];
			$activetableOpts[] = $j->table_join;
			$activetableOpts[] = $j->join_from_table;
		}
	}
	$activetableOpts = array_unique($activetableOpts);
	$activetableOpts = array_values($activetableOpts);

	$joinLang = new stdClass();
	$joinLang->joinType = JText::_('JOIN TYPE');
	$joinLang->joinFromTable = JText::_('FROM');
	$joinLang->joinToTable = JText::_('TO');
	$joinLang->thisTablesIdCol = JText::_('FROM COLUMN');
	$joinLang->joinTablesIdCol = JText::_('TO COLUMN');
	$joinLang->del = JText::_('DELETE');
	$joinLang = FastJSON::encode($joinLang);

	$opts = new stdClass();
	$opts->joinOpts = $joinTypeOpts;
	$opts->tableOpts = $lists['defaultJoinTables'];
	$opts->activetableOpts = $activetableOpts;
	$opts = FastJSON::encode($opts);

	$filterOpts = new stdClass();
	$filterOpts->filterJoinDd = $model->getFilterJoinDd(false, 'params[filter-join][]');
	$filterOpts->filterCondDd = $model->getFilterConditionDd(false, 'params[filter-conditions][]', 2);
	//$filterOpts->filterAccess = addslashes(str_replace(array("\n", "\r"), '', $lists['filter-access']));
	$filterOpts->filterAccess = str_replace(array("\n", "\r"), '', $lists['filter-access']);
	$filterOpts = FastJSON::encode($filterOpts);
	$applyFilterText = (defined('_JACL')) ? 'APPLY FILTER TO' : 'APPLY FILTER BENEATH';

	$filterLang = new stdClass();
	$filterLang->join = JText::_('JOIN');
	$filterLang->field =  JText::_('FIELD');
	$filterLang->condition =  JText::_('CONDITION');
	$filterLang->value =  JText::_('VALUE');
	$filterLang->eval =  JText::_('EVAL');
	$filterLang->applyFilterTo = JText::_($applyFilterText);
	$filterLang->del = JText::_('DELETE');
	$filterLang->yes =  JText::_('YES');
	$filterLang->no =  JText::_('NO');
	$filterLang->query =  JText::_('QUERY');
	$filterLang->noquotes = JTEXT::_('NOQUOTES');
	$filterLang->text =  JText::_('TEXT');
	$filterLang->type =  JText::_('TYPE');
	$filterLang->please_select =  JText::_('COM_FABRIK_PLEASE_SELECT');
	$filterLang->grouped = JText::_('GROUPED');
	$filterLang = FastJSON::encode($filterLang);

	$script = "window.addEvent('domready', function() {
	oAdminTable = new tableForm($opts, $joinLang);
	oAdminTable.watchJoins();\n";

		if( array_key_exists('joins', $lists)) {
			for ($i = 0; $i < count($lists['joins']); $i ++) {
				$j = $lists['joins'][$i];
				$joinFormFields = FastJSON::encode($j->joinFormFields);
				$joinToFields =  FastJSON::encode($j->joinToFields);
				$script .= "	oAdminTable.addJoin('{$j->group_id}','{$j->id}','{$j->join_type}','{$j->table_join}',";
				$script .= "'{$j->table_key}','{$j->table_join_key}','{$j->join_from_table}', $joinFormFields, $joinToFields);\n";
			}
		}
		$filterfields = addslashes(str_replace(array("\n", "\r"), '', $lists['filter-fields']));
		$script .= "	oAdminFilters = new adminFilters('filterContainer', '$filterfields', $filterOpts, $filterLang);\n";

		$afilterJoins 		= $params->get('filter-join','', '_default', 'array');
		$afilterFields 		= $params->get('filter-fields','', '_default', 'array');
		$afilterConditions 	= $params->get('filter-conditions','', '_default', 'array');
		$afilterEval 		= $params->get('filter-eval','', '_default', 'array');
		$afilterValues 		= $params->get('filter-value','', '_default', 'array');
		$afilterAccess 		= $params->get('filter-access','', '_default', 'array');
		$aGrouped			= $params->get('filter-grouped','', '_default', 'array');
		for ($i=0;$i<count($afilterFields);$i++) {
			$selJoin = JArrayHelper::getValue($afilterJoins, $i, 'and');
			$selFilter 	  = $afilterFields[$i];
			$grouped 	  = $aGrouped[$i];
			$selCondition = $afilterConditions[$i];
			$filerEval	  =  JArrayHelper::getValue($afilterEval, $i, '1');
			if($selCondition == '&gt;') { $selCondition = '>';}
			if($selCondition == '&lt;') { $selCondition = '<';}
			$selValue    = 	JArrayHelper::getValue($afilterValues, $i, '');
			$selAccess 	  = $afilterAccess[$i];

			//alow for multiline js variables ?
			$selValue = htmlspecialchars_decode($selValue, ENT_QUOTES);
			$selValue = FastJSON::encode($selValue);

			if ($selFilter != '') {
				$script .= "	oAdminFilters.addFilterOption('$selJoin', '$selFilter', '$selCondition', $selValue, '$selAccess', '$filerEval', '$grouped');\n";
			}
		}
		$script .= "\n});";
	  $document->addScriptDeclaration($script);


	 $session =& JFactory::getSession();
	 $session->clear('com_fabrik.admin.table.edit.model');
  }

/**
 * Display all available tables
 * @param array array of table_rule objects
 * @param object page navigation
 * @param array lists
 */

function show($tables, $pageNav, $lists) {
  JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
  FabrikViewTable::setTablesToolbar();
  $user	  = &JFactory::getUser();
  JHTML::_('behavior.tooltip');
  ?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
<table summary="table filter">
	<tr>
		<td><?php echo $lists['packages'];?></td>
		<td><?php
		echo JText::_('TABLE') . ": ";
		echo $lists['filter_table'];
		?></td>
	</tr>
</table>
<table class="adminlist">
	<thead>
		<tr>
			<th width="2%"><?php echo JHTML::_('grid.sort',  '#', 't.id', @$lists['order_Dir'], @$lists['order']); ?></th>
			<th width="1%"><input type="checkbox" name="toggle" value=""
				onclick="checkAll(<?php echo count($tables);?>);" /></th>
			<th width="14%"><?php echo JHTML::_('grid.sort', 'Table name', 'label', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
			<th width="15%"><?php echo JHTML::_('grid.sort', 'DB Table', 'db_table_name', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
			<th width="14%"><?php echo JText::_('ELEMENT');?></th>
			<th width="14%"><?php echo JText::_('FORM'); ?></th>
			<th width="20%"><?php echo JText::_('VIEW DATA');?></th>
			<th width="5%"><?php echo JHTML::_('grid.sort', 'Published', 'state', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
			<th width="20%"><?php echo JText::_('VIEW DETAILS'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="9"><?php echo $pageNav->getListFooter(); ?></td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$k = 0;
	for ( $i = 0, $n = count($tables); $i < $n; $i ++) {
	  $row = & $tables[$i];
	  $checked		= JHTML::_('grid.checkedout', $row, $i);
	  $link 	= JRoute::_('index.php?option=com_fabrik&c=table&task=edit&cid='. $row->id);
	  $formLink = JRoute::_('index.php?option=com_fabrik&c=form&task=edit&cid='.$row->form_id);
	  if (array_key_exists($row->id, $lists['table_groups'])) {
	  	$elementLink = JRoute::_('index.php?option=com_fabrik&c=element&task=edit&cid=0&filter_groupId='.$lists['table_groups'][$row->id]->group_id);
	  	$elementLink = '<a href="'.$elementLink.'">'.JText::_('ADD').'</a>';
	  } else {
	  	$elementLink = JText::_('No group found');
	  }

	  $row->published = $row->state;
	  $published		= JHTML::_('grid.published', $row, $i);
	  $params = new JParameter($row->attribs, 'administrator/components/com_fabrik/models/table.xml');
 		$img = $params->get('isview') ? 'base_view.png' : 'base.png';
		$title = $params->get('isview') ? JText::_('VIEW') : JText::_('TABLE');
		$img = JHTML::image('media/com_fabrik/images/'.$img, $title, 'title="'.$title.'" class="dbimg"')
	  ?>
		<tr class="<?php echo "row$k"; ?>">
			<td width="3%"><?php echo $img. $row->id; ?></td>
			<td width="1%"><?php echo $checked;?></td>
			<td width="27%"><?php
			if ($row->checked_out && ( $row->checked_out != $user->get('id'))) {
			  ?> <span class="editlinktip hasTip"
				title="<?php echo $row->label . "::" . $params->get('note'); ?>"> <?php echo $row->label; ?>
			</span> <?php } else {
			  ?> <a href="<?php echo $link;?>"> <span class="editlinktip hasTip"
				title="<?php echo $row->label . "::" . $params->get('note'); ?>"> <?php echo $row->label; ?>
			</span> </a> <?php } ?></td>
			<td>

				<?php echo $row->db_table_name;?>
			</td>
			<td width="14%">
				<?php echo $elementLink; ?>
			</td>
			<td width="28%"><a href="<?php echo $formLink; ?>"><?php echo JText::_('EDIT'); ?></a>
			</td>
			<td width="20%"><a href="#view"
				onclick="return listItemTask('cb<?php echo $i;?>','viewTable');"><?php echo JText::_('VIEW DATA');?></a>
			</td>
			<td width="5%"><?php echo $published;?></td>
			<td width="20%"><a href="#showlinkedelements"
				onclick="return listItemTask('cb<?php echo $i;?>','showlinkedelements');"><?php echo JText::_('VIEW DETAILS');?></a>
			</td>
		</tr>
		<?php $k = 1 - $k;
	} ?>
	</tbody>
</table>
<input type="hidden" name="option" value="com_fabrik" /> <input
	type="hidden" name="boxchecked" value="0" /> <input type="hidden"
	name="c" value="table" /> <input type="hidden" name="task" value="" />
<input type="hidden" name="filter_order"
	value="<?php echo $lists['order']; ?>" /> <input type="hidden"
	name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" /> <?php echo JHTML::_('form.token'); ?>
</form>
	<?php }

	/**
	 * show a summary of the table's forms, groups and elements
	 * @param object form
	 * @param array element group objects
	 */

	function showTableDetail( &$form, &$formGroupEls )
	{
	  echo "<h1 class=\"sectionname\">".JText::_('TABLE PARTS') . "</h1>";
	  echo "<h3>".JText::_('FORM') ."</h3>";
	  echo "<a href=\"index.php?option=com_fabrik&amp;c=form&amp;task=edit&amp;cid=$form->id\">".$form->label."</a><br />";
	  echo "<h3>".JText::_('ELEMENTS') ."</h3>";
	  echo "<table style=\"margin-bottom:50px;\" class=\"adminlist\" width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" >\n";
	  echo "<tr><th class='title'>". JText::_('ELEMENT') ."</th><th class='title'>". JText::_('LABEL') ."</th><th class='title'>".JText::_('GROUP')."</th></tr>";
	  $k = 1;
	  foreach ( $formGroupEls as $el) {
	    $cid = $el->id;
	    echo "<tr class='sectiontableentry$k row$k'>"
	    ."<td><a href=\"index.php?option=com_fabrik&amp;c=element&amp;task=edit&amp;cid=$cid\">$el->name</a></td>"
	    ."<td>"."$el->label"."</td><td><a href='index.php?option=com_fabrik&amp;c=group&amp;task=edit&amp;cid=$el->group_id'>"."$el->group_name"."</a></td></tr>";
	    $k = 1 - $k;
	  }
	  echo "</table>";
	}

	function copyRename($tables)
	{
?>
<form action="index.php" method="post" name="adminForm">
<?php foreach ($tables as $table) {?>
	<h2><?php echo JText::_('COM_FABRIK_RENAME_TABLE')?></h2>
	<label>
		<?php echo $table->label?>:
		<input type="text" name="names[<?php echo $table->id?>][tableLabel]" value="<?php echo $table->label?>" />
	</label>
	<h2><?php echo JText::_('COM_FABRIK_RENAME_FORM')?></h2>
	<label>
		<?php echo $table->formlabel?>:
		<input type="text" name="names[<?php echo $table->id?>][formLabel]" value="<?php echo $table->formlabel?>" />
	</label>
	<h2><?php echo JText::_('COM_FABRIK_RENAME_GROUPS')?></h2>
	<ul>
	<?php foreach ($table->groups as $group) {?>
		<li>
		<label><?php echo $group->name?>:
		<input type="text" name="names[<?php echo $table->id?>][groupNames][<?php echo $group->id?>]" value="<?php echo $group->name?>" />
		</label>
		</li>
	<?php }?>
	</ul>
	<input type="hidden" name="cid[]" value="<?php echo $table->id?>" />
	<?php }?>
	<input type="submit" name="submit" value="<?php echo JText::_('COM_FABRIK_RENAME'); ?>" />
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="c" value="table" />
	<input type="hidden" name="task" value="doCopy" />
	<?php echo JHTML::_('form.token'); ?>
	</form>
<?php
	}

	function askDeleteTableMethod($names)
	{
	  $cid= JRequest::getVar('cid', array(), 'post', 'array');
	  ?>
<h1><?php echo Jtext::_('DELETE TABLE');?></h1>
<ul>
<?php foreach ($names as $name) {
  echo "<li>$name</li>";
}?>
</ul>
<form action="index.php" method="post" name="adminForm">
<table class="adminform" style="width: 500px">
	<!-- Modified by cyberTiger 06 sep 2008 - start -->
	<!-- New suggested way of handling table deletions... -->
	<tr>
		<td><?php echo JText::_('DELETETABLES_INTRO') ?></td>
	</tr>
	<tr>
		<td><label><input type="radio" name="recordsDeleteDepth" value="0"
			checked="checked" /><?php echo JText::_('DELETE TABLES ONLY') ?></label>
		</td>
	</tr>
	<tr>
		<td><label><input type="radio" name="recordsDeleteDepth" value="1" /><?php echo JText::_('DELETE TABLES AND FORMS') ?></label>
		</td>
	</tr>
	<tr>
		<td><label><input type="radio" name="recordsDeleteDepth" value="2" /><?php echo JText::_('DELETE TABLES FORMS AND GROUPS') ?></label>
		</td>
	</tr>
	<tr>
		<td><label><input type="radio" name="recordsDeleteDepth" value="3" /><?php echo JText::_('DELETE TABLES FORMS GROUPS AND ELEMENTS') ?></label>
		</td>
	</tr>
	<!-- lets keep the ids of the tables to be removed -->
	<tr>
		<td><?php foreach ($cid as $id) { ?> <input type="hidden" name="cid[]"
			value="<?php echo $id;?>" /> <?php } ?></td>
	</tr>
	<tr>
		<td><?php echo JText::_('REMOVE DATABASE TABLE AS WELL'); ?>:</td>
	</tr>
	<tr>
		<td><label><input type="radio" value="0" name="dropTablesFromDB"
			checked="checked" /> <?php echo JText::_('NO'); ?></label></td>
	</tr>
	<tr>
		<td><label><input type="radio" value="1" name="dropTablesFromDB" /> <?php echo JText::_('YES'); ?></label>
		</td>
	</tr>
	<!-- Modified by cyberTiger 06 sep 2008 - end -->
	<tr>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
		<div style="padding-left: 400px"><input type="submit" name="submit"
			value="<?php echo JText::_('DELETE'); ?>" /> <input type="hidden"
			name="option" value="com_fabrik" /> <input type="hidden" name="task"
			value="doCyberTigerRemove" /> <input type="hidden" name="c"
			value="table" /></div>
		</td>
	</tr>
</table>
<?php echo JHTML::_('form.token'); ?></form>
<?php
	}

	/**
	 * show an import CSV file form
	 *
	 */
	function import( $connection )
	{
	  FabrikViewTable::setImportToolbar();
	  ?>
<form id="adminForm" name="adminForm" method="post" action="index.php"
	enctype="multipart/form-data">
<table class="admintable">
	<tbody>
		<tr>
			<td><label for="connection_id"><?php echo JText::_('CONNECTION') ?></label></td>
			<td><?php echo $connection ?></td>
		</tr>
		<tr>
			<td><label for="userfile"><?php echo JText::_('CSV FILE') ?></label></td>
			<td><input class="text_area" name="userfile" id="userfile"
				type="file" size="40" /></td>
		</tr>
		<tr>
			<td><label for="db_table_name"><?php echo JText::_('CREATE NEW TABLE') ?></label></td>
			<td><input name="db_table_name" id="db_table_name" size="40" /></td>
		</tr>
		<tr>
			<td><label for="label"><?php echo JText::_('LABEL') ?></label></td>
			<td><input name="label" id="label" size="40" /></td>
		</tr>
		<tr>
			<td><label for="label"><?php echo JText::_('INSERTNEWPRIMARYKEY') ?></label></td>
			<td>
				<label><input type="radio" checked="checked" name="addkey[]" value="0" /><?php echo JText::_('NO')?></label>
				<label><input type="radio" name="addkey[]" value="1" /><?php echo JText::_('YES')?></label>
			</td>
		</tr>
		<tr>
		<td align="left"><label for="field_delimiter"><?php echo JText::_('FIELD DELIMITER');?></label>
		</td>
		<td>
		<input size="2" class="input" id="field_delimiter" name="field_delimiter" value="," />
		<label><input type="checkbox" name="tabdelimited" value="1" /><?php echo JText::_('COM_FABRIK_OR_TAB_DELIMITED')?></label>
		</td>
	</tr>
	<tr>
		<td align="left"><label for="text_delimiter"><?php echo JText::_('TEXT DELIMITER');?></label>
		</td>
		<td>
		<input size="2" class="input" name="text_delimiter" id="text_delimiter" value='&quot;' />
		</td>
	</tr>
	</tbody>
</table>
<input type="hidden" name="option" value="com_fabrik" />
<input type="hidden" name="c" value="table" />
<input type="hidden" name="task" value="" />
<?php echo JHTML::_('form.token'); ?>
</form>

	  <?php
	}

	function importChooseElements($fields, $lists)
	{
		$addkey =  JRequest::getVar('addkey', array(), 'default', 'array');
		$addkey = $addkey[0];
	  FabrikViewTable::setImportChooseElementsToolbar();
	  ?>
<form id="adminForm" name="adminForm" method="post" action="index.php">
<table class="adminlist">
	<thead>
		<tr>
			<th><?php echo JText::_('FIELD') ?></th>
			<th><?php echo JText::_('ELEMENT TYPE') ?></th>
			<th><?php echo JText::_('LABEL') ?></th>
			<?php if ($addkey == 0) {?>
				<th><?php echo JText::_('PRIMARY KEY') ?></th>
				<th><?php echo JText::_('AUTO INCREMENT')?></th>
			<?php }?>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($fields as $field) {
		$field = strtolower($field);
		$cleaned = FabrikString::clean($field, "UTF-8","ASCII//TRANSLIT//IGNORE"); ?>
		<tr>
			<td><label for="userfile"><?php echo $cleaned ?></label></td>
			<td><?php echo $lists['elementtype']; ?></td>
			<td>
			<input name="label[]" value="<?php echo str_replace("_", " ", $cleaned); ?>" />
			<input type="hidden" name="field[]" value="<?php echo $cleaned; ?>" />
			</td>
			<?php if ($addkey == 0) {?>
				<td>
					<label>
						<input type="radio" checked="checked" name="primarykey[<?php echo $cleaned; ?>][]" value="0" /><?php echo JText::_('NO') ?>
					</label>
					<label>
						<input type="radio" name="primarykey[<?php echo $cleaned; ?>][]" value="1" /><?php echo JText::_('YES') ?>
					</label>
				</td>
				<td>
					<label>
						<input type="radio" checked="checked" name="autoinc[<?php echo $cleaned; ?>][]" value="0" /><?php echo JText::_('NO') ?>
					</label>
					<label>
						<input type="radio" name="autoinc[<?php echo $cleaned; ?>][]" value="1" /><?php echo JText::_('YES') ?>
					</label>
				</td>
				<?php } ?>
		</tr>
		<?php } ?>
	</tbody>
</table>
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="c" value="table" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="db_table_name" value="<?php echo JRequest::getVar('db_table_name') ?>" />
	<input type="hidden" name="db_table_label" value="<?php echo JRequest::getVar('label') ?>" />
	<input type="hidden" name="connection_id" value="<?php echo JRequest::getVar('connection_id') ?>" />
	<input type="hidden" name="addkey" value="<?php echo $addkey?>" />
		<?php echo JHTML::_('form.token'); ?>
</form>

		<?php
	}
}
?>