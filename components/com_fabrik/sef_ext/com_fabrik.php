<?php
/**
 * sh404SEF support for com_fabrik component.
 * Author : peamak
 * contact : tom@spierckel.net
 *
 * {shSourceVersionTag: Version beta - 2009-03-05}
 *
 * This is a sh404SEF native plugin file for Fabrik component (http://fabrikar.com)
 *
 */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG, $sefConfig;
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin($lang, $shLangName, $shLangIso, $option);
if ($dosef == false) return;
// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//
/// $$$rob dont load if joomfish installed?
/// $$$tom that's actually the opposite: load only if joomfish is installed;)
/// as per: http://fabrikar.com/forums/showthread.php?p=66613#post66613
if (defined('JOOMFISH_PATH')) {
	if (isset($sh_LANG)) {
		$shLangIso = shLoadPluginLanguage('com_fabrik', $shLangIso, '_COM_SEF_SH_CREATE_NEW');
	}
}
// ------------------  load language file - adjust as needed ----------------------------------------

// Don't do SEF if controller is set (return directly in fact)
if (isset($controller)) {
	return;
}

$task = isset($task) ? @$task : null;
$Itemid = isset($Itemid) ? @$Itemid : null;

$tableid = isset($tableid) ? @$tableid : null;
$view = isset($view) ? @$view : null;
$fabrik = isset($fabrik) ? @$fabrik : null;
$rowid = isset($rowid) ? @$rowid : null;

// Prepare Menu Title
$shFabrikName = null;
$shFabrikName = empty($shFabrikName) ?  getMenuTitle($option, $task, $Itemid, null, $shLangName) : $shFabrikName;
$shFabrikName = (empty($shFabrikName) || $shFabrikName == '/') ? 'Fabrik':$shFabrikName; // V 1.2.4.t

// Load Fabrik Paramaters (Backend > Components > Fabrik > Tables --> [Parameters] (top-right button)
$params = &JComponentHelper::getParams('com_fabrik');

// Override General params with those from the current Menu
$thisMenu =& shRouter::shGetMenu();
$thisMenuItem = $thisMenu->getItem( @$Itemid);
if ($thisMenuItem) {
	$thisMenuParams =& new JParameter($thisMenuItem->params);
	if (trim($thisMenuParams->get('fabrik_sef_prepend_menu_title'))) {
		$params->set('fabrik_sef_prepend_menu_title', $thisMenuParams->get('fabrik_sef_prepend_menu_title'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_tablename_on_forms'))) {
		$params->set('fabrik_sef_tablename_on_forms', $thisMenuParams->get('fabrik_sef_tablename_on_forms'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_format_records'))) {
		$params->set('fabrik_sef_format_records', $thisMenuParams->get('fabrik_sef_format_records'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_customtxt_form'))) {
		$params->set('fabrik_sef_customtxt_form', $thisMenuParams->get('fabrik_sef_customtxt_form'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_customtxt_new'))) {
		$params->set('fabrik_sef_customtxt_new', $thisMenuParams->get('fabrik_sef_customtxt_new'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_customtxt_edit'))) {
		$params->set('fabrik_sef_customtxt_edit', $thisMenuParams->get('fabrik_sef_customtxt_edit'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_customtxt_details'))) {
		$params->set('fabrik_sef_customtxt_details', $thisMenuParams->get('fabrik_sef_customtxt_details'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_customtxt_own'))) {
		$params->set('fabrik_sef_customtxt_own', $thisMenuParams->get('fabrik_sef_customtxt_own'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_format_viz'))) {
		$params->set('fabrik_sef_format_viz', $thisMenuParams->get('fabrik_sef_format_viz'));
	}
	if (trim($thisMenuParams->get('fabrik_sef_customtxt_viz'))) {
		$params->set('fabrik_sef_customtxt_viz', $thisMenuParams->get('fabrik_sef_customtxt_viz'));
	}

}

// Always prepend Menu Title
if ($params->get('fabrik_sef_prepend_menu_title', 0)) {
	$title[] = $shFabrikName;
}

$fb_tableNameOnForms = false;
if ($params->get('fabrik_sef_tablename_on_forms', 0)) {
	$fb_tableNameOnForms = true;
}

$fb_formatRecords = $params->get('fabrik_sef_format_records', 'param_id');

//Build the table's name
if (!function_exists('shBuildTableName')) {
	function shBuildTableName($tableid, $fabrik, $fb_tableNameOnForms) {
		if (empty($tableid)) return null;
		$db =& JFactory::getDBO();
		$sqltable = 'SELECT label, id, db_table_name, db_primary_key FROM #__fabrik_tables WHERE id = '.(int)$tableid;
		$db->setQuery($sqltable);
		if (empty($shLangName))
		$tableName = $db->loadResult('label', false);

		return isset($tableName) && (!isset($fabrik) || $fb_tableNameOnForms) ? $tableName : '';

	}
}

//Build the form's name
if (!function_exists('shBuildFormName')) {
	function shBuildFormName($fabrik)
	{
		if (empty($fabrik)) return null;
		$db =& JFactory::getDBO();
		$sqlform = 'SELECT label, id FROM #__fabrik_forms WHERE id = '.(int)$fabrik;
		$db->setQuery($sqlform);
		if (empty($shLangName))
		$formName = $db->loadResult('label', false);
		//else $formName = $db->loadResult('label');
		return isset($formName) ? $formName : '';
	}
}

// Build record's name
if (!function_exists('shBuildRowName')) {
	function shBuildRowName($rowid, $fabrik, $fb_formatRecords, $params)
	{

		if (empty($rowid)) return null;

		if ($rowid == '-1') {
			shRemoveFromGETVarsList('rowid');
			return trim($params->get('fabrik_sef_customtxt_own', 'my'));
		}

		if ($fb_formatRecords == 'param_id') {
			shAddToGETVarsList('id', $rowid);
			shRemoveFromGETVarsList('rowid');
			return null;
		}
		if ($fb_formatRecords == 'id_only') {
			shRemoveFromGETVarsList('rowid');
			return $rowid;
		}

		$db =& JFactory::getDBO();
		$sqltable = 'SELECT db_table_name, db_primary_key, attribs FROM #__fabrik_tables WHERE form_id = '.(int)$fabrik.' LIMIT 1';
		$db->setQuery($sqltable);
		$db_info = $db->loadObject();
		$db_table = &$db_info->db_table_name;
		$db_table_pk = &$db_info->db_primary_key;

		$mySlug = null;
		$paramsTable =& new JParameter(@$db_info->attribs);
		$mySlug = $paramsTable->get('sef-slug');

		if ($mySlug) {
			$rowLabelQ = "SELECT $mySlug FROM $db_table WHERE $db_table_pk = ".$db->Quote($rowid)." LIMIT 1";
			$db->setQuery($rowLabelQ);
			$thisRowLabel = $db->loadResult();
		}
		if (empty($shLangName))
		$rowLabel = @$thisRowLabel;

		if (!empty($rowLabel)) {
			shRemoveFromGETVarsList('rowid');
		}

		switch($fb_formatRecords)
		{
			default:
			case 'id_slug':
				$rowLabel = $rowid.'-'.$rowLabel;
				break;
			case 'slug_id':
				$rowLabel = $rowLabel.'-'.$rowid;
				break;
			case 'slug_only':
				$rowLabel = $rowLabel;
				break;
		}

		return isset($rowLabel) ? $rowLabel : '';
	}
}

// Build visualization's name
if (!function_exists('shBuildVizName')) {
	function shBuildVizName($vizId, $params) {

		if ($params->get('fabrik_sef_format_viz') == 'param_id') {
			shAddToGETVarsList('id', $vizId);
			return $params->get('fabrik_sef_customtxt_viz', 'viz');
		}
		if ($params->get('fabrik_sef_format_viz') == 'viz-id') {
			return $params->get('fabrik_sef_customtxt_viz', 'viz').'-'.$vizId;
		}
		if ($params->get('fabrik_sef_format_viz') == 'id-viz') {
			return $vizId.'-'.$params->get('fabrik_sef_customtxt_viz', 'viz');
		}

		$db =& JFactory::getDBO();
		$sqlviz = 'SELECT label FROM #__fabrik_visualizations WHERE id = '.(int)$vizId.' LIMIT 1';
		$db->setQuery($sqlviz);
		$vizLabel = $db->loadResult();

		if (empty($shLangName)) {
			switch($params->get('fabrik_sef_format_viz'))
			{
				default:
				case 'label-id':
					$rowLabel = @$vizLabel.'-'.$vizId;
					break;
				case 'id-label':
					$rowLabel = $vizId.'-'.@$vizLabel;
					break;
				case 'label_only':
					$rowLabel = @$vizLabel;
					break;
			}
		}

		return isset($rowLabel) ? $rowLabel : '';
	}
}

//Show the table's name
if (isset($tableid)) {
	$title[] = shBuildTableName($tableid, $fabrik, $fb_tableNameOnForms);
}

//Show the form's name
if (isset($fabrik)) {
	unset($controller);
	if (!$fb_tableNameOnForms) {
		shRemoveFromGETVarsList('tableid');
	}
	$title[] = shBuildFormName($fabrik);

}

// Build record's name
if (isset($rowid)) {
	$title[] = shBuildRowName($rowid, $fabrik, $fb_formatRecords, $params);
}

// Deal with Visualizations
if (isset($controller) && strstr(@$controller, 'visualization')) {
	//$title[] = 'viz';
	if (!isset($Itemid)) {
		$dosef = false;
	}
	$menuViz =& shRouter::shGetMenu();
	$menuItemViz = $menuViz->getItem( @$Itemid);
	if( $menuItemViz) {
		$menuParamsViz =& new JParameter($menuItemViz->params);
		$vizId = $menuParamsViz->get('visualizationid');
		if ($vizId) {

			$title[] = shBuildVizName($vizId, $params);

			shRemoveFromGETVarsList('controller');
		}
	}
}

if (isset($view)) {
	switch ($view) {
		case 'form':
			if (empty($rowid) && empty($tableid) && trim($params->get('fabrik_sef_customtxt_form', 'form') != '*')) {
				$title[] = trim($params->get('fabrik_sef_customtxt_form', 'form'));
			} else if (empty($rowid)) {
				$title[] = trim($params->get('fabrik_sef_customtxt_new', 'new'));
			} else {
				$title[] = trim($params->get('fabrik_sef_customtxt_edit', 'edit'));
			}
			break;
		case 'details':
			if (!shBuildRowName(@$rowid, @$tableid, @$fb_formatRecords, @$params)) {
				$title[] = trim($params->get('fabrik_sef_customtxt_details', 'details'));
			}
			break;
	}
}


shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('calculations');
shRemoveFromGETVarsList('fabrik');
shRemoveFromGETVarsList('tableid');
shRemoveFromGETVarsList('c');
shRemoveFromGETVarsList('view');
shRemoveFromGETVarsList('Itemid');
shRemoveFromGETVarsList('lang');
shRemoveFromGETVarsList('resetfilters');
shRemoveFromGETVarsList('calculations');
shRemoveFromGETVarsList('random');

// For new entries in forms and if a title is set for rowids, don't show '?rowid='
if ((empty($rowid)) || (isset($title[$rowid]))) {
	shRemoveFromGETVarsList('rowid');
}

// ------------------  standard plugin finalize function - don't change ---------------------------
if ($dosef) {
	$string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString,
	(isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null),
	(isset($shLangName) ? @$shLangName : null));
}
// ------------------  standard plugin finalize function - don't change ---------------------------

?>
