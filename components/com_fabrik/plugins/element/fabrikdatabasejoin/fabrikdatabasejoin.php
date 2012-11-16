<?php
/**
 * Plugin element to render list of data looked up from a database table
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');


class FabrikModelFabrikDatabasejoin  extends FabrikModelElement {

	var $_pluginName = 'fabrikdatabasejoin';

	/** @var object connection */
	var $_cn = null;

	var $_joinDb = null;

	/** @var created in getJoin **/
	var $_join = null;

	/** @var string for simple join query*/
	var $_sql = null;

	/** @var array option values **/
	var $_optionVals = null;

	/** @var bol is a join element */
	var $_isJoin = true;

	/** @var array linked form data */
	var $_linkedForms = null;

	/** @var additionl where for auto-complete query */
	var $_autocomplete_where = "";

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * testing to see that if the aFields are passed by reference do they update the table object?
	 * @param array containing field sql
	 * @param array containing field aliases
	 * @param string table name (depreciated)
	 * @param array options
	 */

	function getAsField_html(&$aFields, &$aAsFields, $table = '', $opts = array())
	{
		if ($this->isJoin()) {
			return parent::getAsField_html($aFields, $aAsFields, $table, $opts);
		}
		$table = $this->actualTableName();
		$params =& $this->getParams();
		$db =& JFactory::getDBO();
		$tableModel =& $this->getTableModel();

		$element =& $this->getElement();
		$tableRow = $tableModel->getTable();
		$joins =& $tableModel->getJoins();
		foreach ($joins as $tmpjoin) {
			if ($tmpjoin->element_id == $element->id) {
				$join =& $tmpjoin;
				break;
			}
		}

		$connection =& $tableModel->getConnection();
		//make sure same connection as this table
		$fullElName = $table."___".$element->name;
		if ($params->get('join_conn_id') == $connection->_id || $element->plugin != 'fabrikdatabasejoin') {
			$join =& $this->getJoin();
			$joinTableName = $join->table_join_alias;

			$tables = $this->getForm()->getLinkedFabrikTables($params->get('join_db_name'));

			//	store unjoined values as well (used in non-join group table views)
			//this wasnt working for test case:
			//events -> (db join) event_artists -> el join (artist)

			// $$$ rob in csv import keytable not set
			// $$$ hugh - if keytable isn't set, the safeColName blows up!
			// Trying to debug issue with linked join elements, which don't get detected by
			// getJoins or getJoin 'cos element ID doesn't match element_id in fabrik_joins
			//$k = isset($join->keytable ) ? $join->keytable : $join->join_from_table;
			//$k = FabrikString::safeColName("`$join->keytable`.`$element->name`");
			$keytable = isset($join->keytable) ? $join->keytable : $join->join_from_table;
			$k = FabrikString::safeColName("`$keytable`.`$element->name`");

			$k2 = $this->getJoinLabelColumn();

			if (JArrayHelper::getValue($opts, 'inc_raw', true)) {
				$aFields[]				= "$k AS ".$db->nameQuote($fullElName."_raw");
				$aAsFields[]			= $db->nameQuote($fullElName."_raw");
			}
			$aFields[] 				= "$k2 AS ".$db->nameQuote($fullElName);
			$aAsFields[] 			= $db->nameQuote($fullElName);

		} else {
			$aFields[] 		= $db->nameQuote($table).'.'.$db->nameQuote($element->name).' AS '.$db->nameQuote($fullElName);
			$aAsFields[] 	= $db->nameQuote($fullElName);
		}
	}

	public function getRawColumn($useStep = true)
	{
		$join =& $this->getJoin();
		$element =& $this->getElement();
		$k = isset($join->keytable) ? $join->keytable : $join->join_from_table;
		$name = $element->name . "_raw";
		return $useStep ? $k."___".$name : FabrikString::safeColName("$k.$name");
	}

	/**
	 * get the field name to use as the column that contains the join's label data
	 * @param bol use step in element name
	 * @return string join label column either returns concat statement or quotes `tablename`.`elementname`
	 */

	function getJoinLabelColumn($useStep = false)
	{
		if (!isset($this->joinLabelCols)) {
			$this->joinLabelCols = array();
		}
		if (array_key_exists((int)$useStep, $this->joinLabelCols)) {
			return $this->joinLabelCols[$useStep];
		}
		$params =& $this->getParams();
		$db = $this->getDb();
		$join =& $this->getJoin();
		if (($params->get('join_val_column_concat') != '') && JRequest::getVar('overide_join_val_column_concat') != 1) {
			$val = str_replace("{thistable}", $join->table_join_alias, $params->get('join_val_column_concat'));
			$w = new FabrikWorker();
			$val = $w->parseMessageForPlaceHolder($val, array(), false);
			return "CONCAT(".$val.")";
		}
		$label = FabrikString::shortColName($join->_params->get('join-label'));
		if ($label == '') {
			JError::raiseWarning(500, 'Could not find the join label for '.$this->getElement()->name . ' try unlinking and saving it');
			$label = $this->getElement()->name;
		}
		$joinTableName = $join->table_join_alias;
		$this->joinLabelCols[(int)$useStep] = $useStep ? $joinTableName.'___'.$label :$db->nameQuote($joinTableName).'.'.$db->nameQuote($label);// "``.`$label`";
		return $this->joinLabelCols[(int)$useStep];
	}

	/**
	 * get as field for csv export
	 * can be overwritten in the plugin class - see database join element for example
	 * testing to see that if the aFields are passed by reference do they update the table object?
	 *
	 * @param array containing field sql
	 * @param array containing field aliases
	 * @param string table name (depreciated)
	 */

	function getAsField_csv(&$aFields, &$aAsFields, $table = '')
	{
		$this->getAsField_html($aFields, $aAsFields, $table);
	}

	/**
	 * @return object join table
	 */

	function getJoin()
	{
		if (isset($this->_join)) {
			return $this->_join;
		}
		$params =& $this->getParams();
		$element =& $this->getElement();
		$tableModel =& $this->getTableModel();
		$table =& $tableModel->getTable();
		$joins =& $tableModel->getJoins();
		foreach ($joins as $join) {
			if ($join->element_id == $element->id) {
				if (!isset($join->_params)) {
					$join->_params = new fabrikParams($join->attribs);
				}
				return $join;
			}
		}
		//	default fall back behaviour - shouldnt get used

		if (!is_null($this->_join)) {
			return $this->_join;
		}
		$join =& JTable::getInstance('Join', 'Table');
		$groupModel =& $this->getGroup();
		$element =& $this->getElement();
		if ($groupModel->isJoin()) {

			$joinModel =& $groupModel->getJoinModel();
			$j =& $joinModel->getJoin();

			//$table = $j->join_from_table;
			$join->join_from_table = $j->table_join;
		} else {
			$join->join_from_table = $table->db_table_name;
		}

		$params =& $this->getParams();

		$join->join_type 		= 'LEFT';
		$join->_name 					= $element->name;
		$join->table_join 			= $params->get('join_db_name');
		$join->table_join_key 	= $params->get('join_key_column');
		$join->table_key 			= $element->name;
		$join->table_join_alias = $join->table_join;
		if (!isset($join->_params)) {
			$join->_params = new fabrikParams($join->attribs);
		}
		$this->_join = $join;
		return $join;
	}

	/**
	 * load this elements joins
	 */

	function getJoins()
	{
		$db =& JFactory::getDBO();
		if (!isset($this->_aJoins)) {
			$sql = "SELECT * FROM #__fabrik_joins WHERE element_id = ".(int)$this->_id." ORDER BY id";
			$db->setQuery($sql);
			$this->_aJoins = $db->LoadObjectList();
		}
		return $this->_aJoins;
	}

	function getJoinsToThisKey(&$table)
	{
		$db =& JFactory::getDBO();
		$sql = "SELECT *, t.label AS tablelabel FROM #__fabrik_elements AS el " .
		"LEFT JOIN #__fabrik_formgroup AS fg
				ON fg.group_id = el.group_id
				LEFT JOIN #__fabrik_forms AS f
				ON f.id = fg.form_id
				LEFT JOIN #__fabrik_tables AS t
				ON t.form_id = f.id " .
		"WHERE " .
		" plugin = 'fabrikdatabasejoin' AND" .
		" join_db_name = ".$db->Quote($table->db_table_name)."' " .
		"AND join_conn_id = ".(int)$table->connection_id;

		$db->setQuery($sql);
		return $db->loadObjectList();
	}
	/**
	 * get array of option values
	 *
	 * @param array $data
	 * @param int repeat group counter
	 * @param bool do we add custom where statement into sql
	 * @return array option values
	 */

	function &_getOptionVals($data = array(), $repeatCounter = 0, $incWhere = true)
	{
		$params = $this->getParams();
		$db =& $this->getDb();

		// $$$ hugh - attempting to make sure we never do an uncontrained query for auto-complete
		$displayType = $params->get('database_join_display_type', 'dropdown');
		if ($displayType == 'auto-complete' && empty($this->_autocomplete_where)) {
			$value = $this->getValue($data, $repeatCounter);
			$this->_autocomplete_where = $this->getJoinValueColumn() . " = " . $db->Quote($value);
		}

		// @TODO - if a calc elemenmt has be loaded before us, _optionVals will have been set,
		// BUT using $incWhere as false.  So is this join has a WHERE clause, it's never getting run.
		// So I'm adding the _sql[$incWhere] test to try and resolve this ...
		if (isset($this->_optionVals)) {
			if (isset($this->_sql[$incWhere])) {
				return $this->_optionVals;
			}
		}

		$sql = $this->_buildQuery($data, $incWhere);
		if (!$sql) {
			$this->_optionVals = array();
		} else {
			$db->setQuery($sql);
			FabrikHelperHTML::debug($db->getQuery(), $this->getElement()->name .'databasejoin element: get options query');
			$this->_optionVals = $db->loadObjectList();
			if ($db->getErrorNum() != 0) {
				JError::raiseNotice(500,  $db->getErrorMsg());
			}
			FabrikHelperHTML::debug($this->_optionVals, 'databasejoin elements');
			if (!is_array($this->_optionVals)) {
				$this->_optionVals = array();
			}

			$eval = $params->get('dabase_join_label_eval');
			if (trim($eval) !== '') {
				foreach ($this->_optionVals as &$opt) {
					eval($eval);
				}
			}
		}
		return $this->_optionVals;
	}

	/**
	 * fix html validation warning on empty options labels
	 * @param array option objects $rows
	 * @param string object label
	 * @return null
	 */
	private function addSpaceToEmptyLabels(&$rows, $txt = 'text')
	{
		foreach ($rows as &$t) {
			if ($t->$txt == '') {
				$t->$txt ='&nbsp;';
			}
		}
	}

	/**
	 * get a list of the HTML options used in the database join drop down / radio buttons
	 * @param array data from current record (when editing form?)
	 * @param array int repeat group counter
	 * @param bool do we include custom where in query
	 * @return array option objects
	 */

	function _getOptions($data = array(), $repeatCounter = 0, $incWhere = true)
	{
		$element =& $this->getElement();
		$params =& $this->getParams();
		$showBoth = $params->get('show_both_with_radio_dbjoin', '0');
		$this->_joinDb =& $this->getDb();

		$col	= $element->name;
		$tmp = array();

		$aDdObjs =& $this->_getOptionVals($data, $repeatCounter, $incWhere);

		$table = $this->getTableModel()->getTable()->db_table_name;
		if (is_array($aDdObjs)) {
			$tmp = array_merge($tmp, $aDdObjs);
		}
		$this->addSpaceToEmptyLabels($tmp);
		$displayType = $params->get('database_join_display_type', 'dropdown');
		if ($displayType == 'dropdown' && $params->get('database_join_show_please_select', true)) {
			array_unshift($tmp, JHTML::_('select.option', $params->get('database_join_noselectionvalue') , $this->_getSelectLabel()));
		}
		return $tmp;
	}

	function getOneOption($value, $data, $repeatCounter = 0)
	{
		$default = $this->getValue($data, $repeatCounter);
		$params =& $this->getParams();
		// @TODO - if a calc elemenmt has be loaded before us, _optionVals will have been set,
		// BUT using $incWhere as false.  So is this join has a WHERE clause, it's never getting run.
		// So I'm adding the _sql[$incWhere] test to try and resolve this ...

		// $$$ rob - eek using 2 only as the key meant that repeatgroups with autocomplete db joins were not being filled in with selected value
		$queryKey = 2 . '.' . $default;

		if (isset($this->_optionVals)) {
			if (isset($this->_sql[$queryKey])) {
				return $this->_optionVals;
			}
		}
		$db =& $this->getDb();
		$this->_autocomplete_where = $this->getJoinValueColumn() . " = " . $db->Quote($value);
		$sql = $this->_buildQuery($data, $queryKey);
		if (!$sql) {
			$this->_optionVals = array();
		} else {
			$db->setQuery($sql);
			FabrikHelperHTML::debug($db->getQuery(), $this->getElement()->name .'databasejoin element: get one option query');
			$this->_optionVals = $db->loadObjectList();
			if ($db->getErrorNum() != 0) {
				JError::raiseNotice(500,  $db->getErrorMsg());
			}
			FabrikHelperHTML::debug($this->_optionVals, 'databasejoin elements');
			if (!is_array($this->_optionVals)) {
				$this->_optionVals = array();
			}

			$eval = $params->get('dabase_join_label_eval');
			if (trim($eval) !== '') {
				foreach ($this->_optionVals as &$opt) {
					eval($eval);
				}
			}
		}
		return $this->_optionVals;
	}

	protected function _getSelectLabel()
	{
		return $this->getParams()->get('database_join_noselectionlabel', JText::_('COM_FABRIK_PLEASE_SELECT'));
	}

	/**
	 * check to see if prefilter should be applied
	 * Kind of an inverse access lookup
	 * @param int group id to check against
	 * @param string ref for filter
	 * @return bol must apply filter - true, ignore filter (user has enough access rights) false;
	 */

	function mustApplyWhere($gid, $ref)
	{
		// $$$ hugh - adding 'where when' so can control whether to apply WHERE either on
		// new, edit or both (1, 2 or 3)
		$params =& $this->getParams();
		$wherewhen = $params->get('database_join_where_when', '3');
		$isnew = JRequest::getInt('rowid', 0) === 0;
		if ($isnew && $wherewhen == '2') {
			return false;
		}
		else if (!$isnew && $wherewhen == '1') {
			return false;
		}

		// prefilters with JACL are applied to a single group only
		// not a group and groups beneath them (think author, registered)
		// so if JACL on then prefilters work in the inverse in that they are only applied
		// to the group selected

		if (defined('_JACL')) {
			return FabrikWorker::getACL($gid, 'dbjoinwhere' . $ref);
		} else {
			return FabrikWorker::getACL($gid, 'dbjoinwhere' . $ref, '<=');
		}
	}

	/**
	 * create the sql query used to get the join data
	 * @param array
	 * @return string or false if query can't be built
	 */

	function _buildQuery($data = array(), $incWhere = true)
	{
		$db =& JFactory::getDBO();
		if (isset($this->_sql[$incWhere])) {
			return $this->_sql[$incWhere];
		}
		$params =& $this->getParams();
		$element =& $this->getElement();
		$formModel =& $this->getForm();

		$where = $this->_buildQueryWhere($data, $incWhere);
		//$$$rob not sure these should be used anyway?
		$table 	= $params->get('join_db_name');
		$key		= $this->getJoinValueColumn();
		$val		= $this->_getValColumn();
		$orderby	= 'text';
		$join =& $this->getJoin();
		if ($table == '') {
			$table = $join->table_join;
			$key = $join->table_join_key;
			$val = $db->nameQuote($join->_params->get('join-label', $val));
		}
		if ($key == '' || $val == '') {
			return false;
		}
		$sql = "SELECT DISTINCT($key) AS value, $val AS text";
		$desc = $params->get('join_desc_column');
		if ($desc !== '') {
			$sql .= ", ".$db->nameQuote($desc)." AS description";
		}
		$sql .= " FROM " . $db->nameQuote($table) . " AS " . $db->nameQuote($join->table_join_alias);
		$sql .= " $where ";
		// $$$ hugh - let them specify an order by, i.e. don't append default if the $where already has an 'order by'
		// @TODO - we should probably split 'order by' out in to another setting field, because if they are using
		// the 'apply where beneath' and/or 'apply where when' feature, any custom ordering will not be applied
		// if the 'where' is not being applied, which probably isn't what they want.
		if (!JString::stristr($where, 'order by')) {
			if (isset($this->orderBy)) {
				$sql .= $this->orderBy;
				unset($this->orderBy);
			} else {
				$sql .= "ORDER BY $orderby ASC ";
			}
		}
		$this->_sql[$incWhere] = $sql;
		return $this->_sql[$incWhere];
	}

	function _buildQueryWhere($data = array(), $incWhere = true)
	{
		$where = '';
		$tableModel =& $this->getTableModel();
		$params =& $this->getParams();
		$element =& $this->getElement();
		$whereaccess = $params->get('database_join_where_access', 26);

		if ($this->mustApplyWhere($whereaccess, $element->id) && $incWhere) {
			$where = $params->get('database_join_where_sql');
		} else {
			$where = '';
		}
		$join =& $this->getJoin();
		$where = str_replace("{thistable}", $join->table_join_alias, $where);

		// $$$rob 11/10/2011  remove order by statements which will be re-inserted at the end of _buildQuery()
		// $$$ hugh - fixing corner case of ORDER BY in a subquery in a where filter, see ...
		// http://fabrikar.com/forums/showthread.php?p=129499#post129499
		// We should really make "ORDER BY" a separate option, but for now, "fix" is to only
		// match an ORDER BY which is at the end of a line, not buried in a subquery
		if (preg_match('#(ORDER\s+BY\s+\S+\s*[ASC|DESC]*)$#i', $where, $matches)) {
			$this->orderBy = $matches[0];
			//$where = str_replace($this->orderBy, '', $where);
			$where = preg_replace('#(ORDER\s+BY\s+\S+\s*[ASC|DESC]*)$#', '', $where);
		}
		if (!empty($this->_autocomplete_where)) {
			$where .= stristr($where, 'WHERE') ? " AND " . $this->_autocomplete_where : ' WHERE ' . $this->_autocomplete_where;
		}
		if ($where == '') {
			return $where;
		}

		$w = new FabrikWorker();
		$data = is_array($data) ? $data : array();
		$where = $w->parseMessageForPlaceHolder($where, $data, false);
		return $where;
	}

	/**
	 * get the element name or concat statement used to build the dropdown labels or
	 * table data field
	 *
	 * @return string
	 */

	function _getValColumn()
	{
		$params =& $this->getParams();
		$join =& $this->getJoin();
		if ($params->get('join_val_column_concat') == '') {
			return $params->get('join_val_column');
		} else {
			$val = str_replace("{thistable}", $join->table_join_alias, $params->get('join_val_column_concat'));
			$w = new FabrikWorker();
			$val = $w->parseMessageForPlaceHolder($val, array(), false);
			return "CONCAT(" . $val . ")";
		}
	}


	/**
	 * get the database object
	 *
	 * @return object database
	 */

	function &getDb()
	{
		$cn =& $this->getConnection();
		if (!$this->_joinDb) {
			$this->_joinDb =& $cn->getDb();
		}
		return $this->_joinDb;
	}

	/**
	 * get connection
	 *
	 * @return object connection
	 */

	function &getConnection()
	{
		if (is_null($this->_cn)) {
			$this->_loadConnection();
		}
		return $this->_cn;
	}

	protected function connectionParam()
	{
		return 'join_conn_id';
	}

	/**
	 * @access protected
	 * load connection object
	 * @return object connection table
	 */

	protected function _loadConnection()
	{
		$params =& $this->getParams();
		$cid = $this->getTableModel()->getConnection()->getConnection()->id;
		// $$ rob for prefab small change so we can load an abstract verion of the db join element
		$id = $params->get($this->connectionParam(), $cid);
		if ($cid == $id) {
			$this->_cn =& $this->getTableModel()->getConnection();
		} else {
			$this->_cn =& JModel::getInstance('Connection', 'FabrikModel');
			$this->_cn->setId($id);
		}
		return $this->_cn->getConnection();
	}

	function getROValue($data, $repeatCounter = 0)
	{
		$v = $this->getValue($data, $repeatCounter);
		return $this->getLabelForValue($v);
	}

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns field element
	 */

	function render($data, $repeatCounter = 0)
	{
		if ($this->isJoin()) {
			$this->hasSubElements = true;
		}
		$params 			=& $this->getParams();
		$formModel		=& $this->getForm();
		$groupModel 	=& $this->getGroup();
		$element 			=& $this->getElement();
		$aGroupRepeats[$element->group_id] = $groupModel->canRepeat();

		$displayType = $params->get('database_join_display_type', 'dropdown');
		$db =& $this->getDb();

		if (!$db) {
			JError::raiseWarning(JText::sprintf('Could not make join to %s', $element->name));
			return '';
		}
		if (isset($formModel->_aJoinGroupIds[$groupModel->_id])) {
			$joinId = $formModel->_aJoinGroupIds[$groupModel->_id];
			$joinGroupId = $groupModel->_id;
		} else {
			$joinId = '';
			$joinGroupId = '';
		}

		/*get the default value */
		$w = new FabrikWorker();
		$default = $this->getValue($data, $repeatCounter);
		$default = $w->parseMessageForPlaceHolder($default);

		// $$$ rob dont load in all options for autocomplete as slows loading on large data sets
		// $tmp = ($displayType == 'auto-complete' && $this->_editable) ? array() : $this->_getOptions($data, $repeatCounter);
		// $$$ hugh - doesn't matter if editable, otherwise it just failsd on _getROElement().  Don't load for auto-complete, period.
		//$tmp = $this->_getOptions($data, $repeatCounter);

		// $$$ rob - huh? (^^ above line + comment ^^) how can it not matter for editable?
		// If you have repeat auto-completes in a form then we must get the correct label/value to show in the field
		 $tmp = ($displayType == 'auto-complete') ? $this->getOneOption($default, $data, $repeatCounter) : $this->_getOptions($data, $repeatCounter);

		$default = $this->getValue($data, $repeatCounter);
		$default = $w->parseMessageForPlaceHolder($default);
		$thisElName = $this->getHTMLName($repeatCounter);
		//weird situation in http://gb.inetis.ch/ where lots of inner joins and repeating groups meant deafult was a csv list?????
		if (is_string($default) && strpos($default, ',')) {
			$default = explode(',', $default);
			$default = $default[$repeatCounter];
		}

		//get the default label for the drop down (use in read only templates)
		$defaultLabel = '';
		$defaultValue = '';
		foreach ($tmp as $obj) {
			if ($obj->value == $default) {
				$defaultValue = $obj->value;
				$defaultLabel = $obj->text;
			}
		}

		$id = $this->getHTMLId($repeatCounter);
		//$$$rob should be canUse() otherwise if user set to view but not use the dd was shown
		//if ($this->canView()) {
		if ($this->canUse()) {
			$str = '';
			/*if user can access the drop down*/
			switch ($displayType) {
				case 'dropdown':
				default:
					$str .= JHTML::_('select.genericlist', $tmp, $thisElName, 'class="fabrikinput inputbox" size="1"', 'value', 'text', $default, $id);
					break;
				case 'radio':
					// $$$ rob 24/05/2011 - always set one value as selected for radio button if none already set
					if ($defaultValue == '' && !empty($tmp)) {
						$defaultValue = $tmp[0]->value;
					}
					// $$$ rob 24/05/2011 - add options per row
					$options_per_row = intval($params->get('dbjoin_options_per_row', 0));
					$str .= "<div class=\"fabrikSubElementContainer\" id=\"$id\">";
					$str .= FabrikHelperHTML::aList($displayType, $tmp, $thisElName, 'class="fabrikinput inputbox" size="1" id="'.$id.'"', $defaultValue, 'value', 'text', $options_per_row);
					break;
				case 'checkbox':
					$idname = $this->getFullName(false, true, false)."_id";
					$join =& $this->getJoin();
					$repeatnumName =$join->table_join."___repeatnum";
					$defaults = explode(GROUPSPLITTER, JArrayHelper::getValue($data, $idname));
					if ($groupModel->canRepeat() && $groupModel->isJoin()) {
						//$$$ rob 21/07/2011 if checkbox is in repeat group then we want to filter the defaults to only show those selected in this repeat group
						$fk = $groupModel->getJoinModel()->getPrimaryKey().'_raw';
						$fkeyvals = JArrayHelper::getValue($data['join'][$this->getGroup()->getGroup()->join_id], $fk);
						$repeatNums = JArrayHelper::getValue($data['join'][$this->getGroup()->getGroup()->join_id], $repeatnumName);
						$currentFkVal = $fkeyvals[$repeatCounter];
						$repeatNums = explode(GROUPSPLITTER, $repeatNums[0]);
						$allDefaults = $defaults;
						$defaults = array();
						foreach ($repeatNums as $rk => $v) {
							if ($v == $currentFkVal) {
								$defaults[] = $allDefaults[$rk];
							} else {
								$defaults[] = '';
							}
						}
					}
					// $$$ rob 24/05/2011 - add options per row
					$options_per_row = intval($params->get('dbjoin_options_per_row', 0));
					$str .= "<div class=\"fabrikSubElementContainer\" id=\"$id\">";
					if (is_string($default))
					{
					$joinids = $default == '' ? array() : explode(GROUPSPLITTER, $default);
					}
					else
					{
						$joinids = $default;
					}
					$str .= FabrikHelperHTML::aList($displayType, $tmp, $thisElName, 'class="fabrikinput inputbox" size="1" id="'.$id.'"', $defaults, 'value', 'text', $options_per_row, $this->_editable);
					//if ($this->isJoin() && $this->_editable) {
					if ($this->_editable) {
						$joinidsName = 'join['.$join->id.']['.$join->table_join.'___id]';
						if ($groupModel->canRepeat()) {
							$joinidsName .= "[$repeatCounter]";
						}
						$joinidsName .= '[]';
						$tmpids = array();
						foreach ($tmp as $obj) {
							$o = new stdClass();
							$o->text = $obj->text;
							if (in_array($obj->value, $defaults)) {
								$index = array_search($obj->value, $defaults);
								$o->value = JArrayHelper::getValue($joinids, $index);
							} else {
								$o->value = 0;
							}
							$tmpids[] = $o;
						}
						$str .= "<div class=\"fabrikHide\">";
						$str .= FabrikHelperHTML::aList($displayType, $tmpids, $joinidsName, 'class="fabrikinput inputbox" size="1" id="'.$id.'"', $joinids, 'value', 'text', $options_per_row, $this->_editable);
						$str .= "</div>";
					}
					$defaultLabel = $str;
					break;
				case 'auto-complete':
					$str .= "<input type=\"text\" size=\"" . $params->get('dbjoin_autocomplete_size', '20') . "\" name=\"{$thisElName}-auto-complete\" id=\"{$id}-auto-complete\" value=\"$defaultLabel\" class=\"fabrikinput inputbox autocomplete-trigger\"/>";
					// $$$ rob need class for duplicate group js code to work
					$str .= "<input type=\"hidden\" size=\"20\" name=\"{$thisElName}\" id=\"{$id}\" class=\"fabrikinput\" value=\"$default\"/>";
					break;
			}

			// $$$ rob 24/02/2011 removed ids from the two <a>s
			if ($params->get('fabrikdatabasejoin_frontend_select')) {
				$str .= "<a href=\"#\" class=\"toggle-selectoption\" title=\"" . JText::_('SELECT') . "\">
				<img src=\"".COM_FABRIK_LIVESITE."media/com_fabrik/images/search.png\"  alt=\"" . JText::_('SELECT')."\" />
				</a>";
			}

			if ($params->get('fabrikdatabasejoin_frontend_add')) {
				$str .= "<a href=\"#\" title=\"".JText::_('add option') ."\" class=\"toggle-addoption\">";
				$str .= "\n<img src=\"".COM_FABRIK_LIVESITE."media/com_fabrik/images/action_add.png\" alt=\"".JText::_('add option').">\"/>\n</a>";
			}

			$str .= ($displayType == "radio") ? "</div>" : '';
		} else {
			/* make a hidden field instead*/
			//$$$ rob no - the readonly data should be made in form view _loadTmplBottom
			//$str = "<input type='hidden' class='fabrikinput' name='$thisElName' id='$id' value='$default' />";
		}

		if (!$this->_editable) {
			if ($defaultLabel === $params->get('database_join_noselectionlabel', JText::_('COM_FABRIK_PLEASE_SELECT'))) {
				$defaultLabel = '';//no point showing 'please select' for read only
			}
			if ($params->get('databasejoin_readonly_link') == 1) {
				$popupformid = (int)$params->get('fabrikdatabasejoin_popupform');
				if ($popupformid !== 0) {
					$db->setQuery("SELECT id FROM #__fabrik_tables WHERE form_id = $popupformid");
					$tableid = $db->loadResult();
					$url = 'index.php?option=com_fabrik&view=details&fabrik='.$popupformid.'&tableid='.$tableid.'&rowid='.$defaultValue;
					$defaultLabel = '<a href="'.JRoute::_($url).'">' . $defaultLabel . '</a>';
				}
			}
			return $defaultLabel;
		}
		if ($params->get('join_desc_column') !== '') {
			$str .= "<div class=\"dbjoin-description\">";
			for ($i=0; $i < count($this->_optionVals); $i++) {
				$opt = $this->_optionVals[$i];
				$display = $opt->value == $default ? '' : 'none';
				$c = $i+1;
				$str .= "<div style=\"display:$display\" class=\"notice description-".$c."\">".$opt->description."</div>";
			}
			$str .= "</div>";
		}
		return $str;
	}

	/**
	 * get the default value for the database join element
	 *
	 * @param array $data
	 * @param int $repeatCounter
	 * @param array options
	 * @return string default value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (!isset($this->defaults))
		{
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults))
		{
			$groupModel =& $this->getGroup();
			$group =& $groupModel->getGroup();
			$joinid = $this->isJoin() ? $this->getJoinModel()->getJoin()->id : $group->join_id;
			$formModel =& $this->getForm();
			$element =& $this->getElement();

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			//otherwise get the default value so if we don't find the element's value in $data we fall back on this value
			$value = JArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);

			$name = $this->getFullName(false, true, false);
			//$$$ROB default to getting raw but you can override in the $opts array if you want the label instead
			if (!array_key_exists('valueFormat', $opts)) {
				$opts['valueFormat'] = 'raw';
			}
			if ($opts['valueFormat'] == 'raw') {
				$name .=  "_raw";
			}
			// $name could already be in _raw format - so get inverse name e.g. with or without raw
			$rawname = substr($name, -4) === '_raw' ? substr($name, 0 ,-4) :  $name . '_raw';
			if ($groupModel->isJoin() || $this->isJoin())
			{
				// $$$ rob 22/02/2011 this test barfed on fileuploads which weren't repeating
				//if ($groupModel->canRepeat() || !$this->isJoin()) {
				if ($groupModel->canRepeat())
				{
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
						$value = $data['join'][$joinid][$name][$repeatCounter];
					}
					else
					{
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name]))
						{
							$value = $data['join'][$joinid][$name][$repeatCounter];
						}
					}
				}
				else
				{
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid]))
					{
						$value = $data['join'][$joinid][$name];
					}
					else
					{
						if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($rawname, $data['join'][$joinid]))
						{
							$value = $data['join'][$joinid][$rawname];
						}
					}
					// $$$ rob if you have 2 tbl joins, one repeating and one not
					// the none repeating one's values will be an array of duplicate values
					// but we only want the first value
					if (is_array($value) && !$this->isJoin())
					{
						$value = array_shift($value);
					}
				}
			}
			else
			{
				if ($groupModel->canRepeat())
				{
					//repeat group NO join
					$thisname = $name;
					if (!array_key_exists($name, $data))
					{
						$thisname = $rawname;
					}
					if (array_key_exists($thisname, $data))
					{
						if (is_array($data[$thisname]))
						{
							//occurs on form submission for fields at least
							$a = $data[$thisname];
						}
						else
						{
							//occurs when getting from the db
							$a = explode(GROUPSPLITTER, $data[$thisname]);
						}
						$value = JArrayHelper::getValue($a, $repeatCounter, $value);
					}
				}
				else
				{
					$value = !is_array($data) ? $data : JArrayHelper::getValue($data, $name, JArrayHelper::getValue($data, $rawname, $value));
				}
			}

			if (is_array($value) && !$this->isJoin())
			{
				$value = implode(',', $value);
			}
			// $$$ hugh - don't know what this is for, but was breaking empty fields in repeat
			// groups, by rendering the //..*..// seps.
			// if ($value === '') { //query string for joined data
			if ($value === '' && !$groupModel->canRepeat())
			{
				//query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			if (is_array($value) && !$this->isJoin())
			{
				$value = implode(',', $value);
			}
			//@TODO perhaps we should change this to $element->value and store $element->default as the actual default value
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
			{
				$formModel->getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * determines the label used for the browser title
	 * in the form/detail views
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string default value
	 */

	function getTitlePart($data, $repeatCounter = 0, $opts = array())
	{
		//$$$ rob set ths to label otherwise we get the value/key and not label
		$opts['valueFormat'] = 'label';
		return $this->getValue($data, $repeatCounter, $opts);
	}

	/**
	 * OPTIONAL FUNCTION
	 * code to create lists that are later used in the renderAdminSettings function
	 * @param array list of default values
	 * @param object element to apply lists to
	 */

	function _getAdminLists(&$lists)
	{
		$db 			=& JFactory::getDBO();
		$params 	=& $this->getParams();
		$oConn 		= JModel::getInstance('Connection', 'FabrikModel');
		$realCnns 	= $oConn->getConnections();
		$lists['connectionTables'] = $oConn->getConnectionTables($realCnns);
		$tableNames = $lists['connectionTables'][$params->get('join_conn_id', -1)];
		$lists['tablename'] = JHTML::_('select.genericlist', $tableNames, 'params[join_db_name]', 'class="inputbox" size="1"', 'value', 'text', $params->get('join_db_name', ''), 'join_db_name');
		if ($params->get('join_db_name', '') == "") {
			$tableNames[] = JHTML::_('select.option', '-1', JText::_('Select a connection first ....'));
		}

		$forms =& $this->getLinkedForms();
		$popupformid =  $params->get('fabrikdatabasejoin_popupform');
		if (empty($forms)) {
			$lists['popupform'] = JText::_('NO_POPUP_FORMS_AVAILABLE');
		} else {

			$lists['popupform'] = JHTML::_('select.genericlist', $forms, 'params[fabrikdatabasejoin_popupform]', 'class="inputbox" size="1" ', 'value', 'text', $popupformid);
		}
	}

	protected function getLinkedForms()
	{
		if (!isset($this->_linkedForms)) {
			$db =& JFactory::getDBO();
			$params =& $this->getParams();
			//forms for potential add record pop up form
			$db->setQuery("SELECT f.id AS value, f.label AS text, t.id AS tableid FROM
			#__fabrik_forms AS f LEFT JOIN #__fabrik_tables As t
			ON f.id = t.form_id
			WHERE f.state = '1' AND t.db_table_name = ".$db->Quote($params->get('join_db_name'))."
			ORDER BY f.label");
			$this->_linkedForms = $db->loadObjectList('value');

			// Check for a database error.
			if ($db->getErrorNum()) {
				JError::raiseError(500, $db->getErrorMsg());
			}
		}
		return $this->_linkedForms;
	}

	/**
	 * REQUIRED FUNCTION
	 * defines the type of database table field that is created to store the element's data
	 */

	function getFieldDescription()
	{
		$p =& $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		$table =& $this->getTableModel();
		$db =& $table->getDb();
		//if the element is in a non joined repeat group we should return varchar 255
		$group =& $this->getGroup();
		if (($group->isJoin() == 0 && $group->canRepeat()) || $this->isJoin()) {
			return "VARCHAR(255)";
		}
		//lets see if we can get the field type of the field we are joining to
		$join =& JTable::getInstance('Join', 'Table');
		$join->_tbl_key = "element_id";
		$join->load($this->_id);
		if($join->table_join == '') {
			return "VARCHAR(255)";
		}
		$db->setQuery("DESCRIBE $join->table_join");
		$fields = $db->loadObjectList();
		if (is_array($fields)) {
			foreach ($fields as $field) {
				if ($field->Field == $join->table_join_key) {
					return $field->Type;
				}
			}
		}
		//nope? oh well default to this:
		return "VARCHAR(255)";
	}

	/**
	 * REQUIRED FUNCTION
	 *
	 * @param array $lists
	 */

	function renderAdminSettings(&$lists)
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		$this->_getAdminLists( $lists);
		$element =& $this->getElement();
		$checked = $pluginParams->get('checked');
		$checked = ($checked == '1') ? ' checked="checked"' : '';
		?>
<script type="text/javascript">
			var connectiontables = new Array;
			<?php
			$i = 0;
			if (is_array($lists['connectionTables'])) {
				foreach ($lists['connectionTables'] as $k => $items) {
					foreach ($items as $v) {
						echo "connectiontables[".$i ++."] = new Array('$k','".addslashes($v->value)."','".addslashes($v->text)."');\n\t\t";
					}
				}
			}?>
		</script>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render('details');
	echo $pluginParams->render('params', 'intro');
	echo $pluginParams->render('params', 'simple'); ?> <?php
	echo $pluginParams->render('params', 'xtra');?>
<fieldset><legend><?php echo JText::_('FABRIKDATABASEJOIN_FRONTEND_ADD') ?></legend>
	<?php echo $pluginParams->render('params', 'frontend');?>


<table class="paramlist admintable">
	<tr>
		<td class="paramlist_key" width="40%"><?php echo JText::_('FABRIKDATABASEJOIN_PPOPUPFORM');?></td>
		<td class="paramlist_value"><?php echo $lists['popupform'];?></td>
	</tr>
</table>
</fieldset>

<fieldset><legend><?php echo JText::_('ADVANCED') ?></legend> <?php echo $pluginParams->render('params', 'advanced');?>
</fieldset>

</div>
	<?php
	}


	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		// $$$ hugh - FIXME - $data ??
		$tmp =& $this->_getOptions($data, $repeatCounter);
		//$tmp =& $this->_getOptions();
		if ($this->isJoin()) {
			$tmpvals = array();
			$values = array();
			foreach ($tmp as $t) {
				$tmpvals[$t->value] = $t->text;
			}
			$element =& $this->getElement();
			//$group =& $this->getGroup()->getGroup();
			$join =& $this->getJoinModel()->getJoin();
			$repeatName = $join->table_join.'___' . $element->name;
			foreach ($data['join'][$join->id][$repeatName] as $thing) {
				if (array_key_exists($thing, $tmpvals)) {
					$values[] = $this->renderTableData($tmpvals[$thing], new stdClass());
				}
			}
			$val = implode(',', $values);
		}
		else {
			if (is_array($value)){
				foreach ($value as &$v2) {
					foreach ($tmp as $v) {
						if ($v->value == $v2) {
							$v2 = $v->text;
							$continue;
						}
					}
					$v2 = $this->renderTableData($v2, new stdClass());
				}
				$val = $value;
			}else {
				foreach ($tmp as $v) {
					if ($v->value == $value) {
						$value = $v->text;
						continue;
					}
				}
				$val = $this->renderTableData($value, new stdClass());
			}
		}
		return $val;
	}

	/**
	 *
	 * child classes can then call this function with
	 * return parent::renderTableData($data, $oAllRowsData);
	 * to perform rendering that is applicable to all plugins
	 *
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();
		$groupModel =& $this->_group;
		$labeldata = array();
		if (!$groupModel->isJoin() && $groupModel->canRepeat()) {
			$opts =& $this->_getOptionVals();
			$name =$this->getFullName(false, true, false) ."_raw";
			//if coming from fabrikemail plugin oAllRowsdata is empty
			if (isset($oAllRowsData->$name)) {
				$data = $oAllRowsData->$name;
			}
			if (!is_array($data)) {
				$data = explode(GROUPSPLITTER, $data);
			}
			foreach ($data as $d) {
				foreach ($opts as $opt) {
					if ($opt->value == $d) {
						$labeldata[] = $opt->text;
						break;
					}
				}
			}
		} else {
			$labeldata[] = $data;
		}
		$data = implode(GROUPSPLITTER, $labeldata);
		// $$$ rob add links and icons done in parent::renderTableData();
		return parent::renderTableData($data, $oAllRowsData);
	}

	/**
	 * Get the table filter for the element
	 * @param int counter
	 * @param bol do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 * @return string filter html
	 */

	function getFilter($counter = true, $normal = true)
	{
		$params 			=& $this->getParams();
		$element 			=& $this->getElement();
		$table 				=& $this->getTableModel()->getTable();

		$elName 			= $this->getFilterFullName();
		$htmlid				= $this->getHTMLId() . 'value';
		// $$$ rob should be done in getFilterFullName if required
		//$elName 			= FabrikString::safeColName($elName);

		$v = 'fabrik___filter[table_'.$table->id.'][value]';
		$v .= ($normal) ? '['.$counter.']' : '[]';


		$return			= '';
		$default 		= $this->getDefaultFilterVal($normal, $counter);


		if (in_array($element->filter_type, array('range', 'dropdown', ''))) {
			$joinVal = $this->getJoinLabelColumn();
			$incJoin = (trim($params->get('join_val_column_concat')) == '' && trim($params->get('database_join_where_sql') == '')) ? false: true;
			$rows = $this->filterValueList($normal, null, $joinVal, '', $incJoin);
			if (!$rows) {
				// $$$ hugh - let's not raise a warning, as there are valid cases where a join may not yield results, see
				// http://fabrikar.com/forums/showthread.php?p=100466#post100466
				// JError::raiseWarning(500, 'database join filter query incorrect');
				// Moved warning to element model filterValueList_Exact(), with a test for $fabrikDb->getErrorNum()
				// So we'll just return an otherwise empty menu with just the 'select label'
				$rows = array();
				array_unshift($rows, JHTML::_('select.option',  '', $this->filterSelectLabel()));
				$return = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', "value", 'text', $default, $htmlid);
				return $return;
			}
			$this->unmergeFilterSplits($rows);
			$this->reapplyFilterLabels($rows);
			array_unshift($rows, JHTML::_('select.option',  '', $this->filterSelectLabel()));
		}

		$size = $params->get('filter_length', 20);

		switch ($element->filter_type) {

			case "dropdown":
			default:
			case '':
				$this->addSpaceToEmptyLabels($rows, 'text');
				$return = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', "value", 'text', $default, $htmlid);
				break;

			case "field":
				$return = "<input type=\"text\" class=\"inputbox fabrik_filter\" name=\"$v\" value=\"$default\" size=\"$size\" id=\"$htmlid\" />";
				$return .= $this->filterHiddenFields();
				break;

			case "auto-complete":
				$defaultLabel = $this->getLabelForValue($default);
				$return = "<input type=\"hidden\" name=\"$v\" class=\"inputbox fabrik_filter\" value=\"$default\" id=\"$htmlid\"  />";
				$return .= "<input type=\"text\" name=\"$element->id-auto-complete\" class=\"inputbox fabrik_filter autocomplete-trigger\" size=\"$size\" value=\"$defaultLabel\" id=\"$htmlid-auto-complete\"  />";
				$return .= $this->filterHiddenFields();
				FabrikHelperHTML::autoComplete($htmlid, $element->id, 'fabrikdatabasejoin');
				break;

		}
		if ($normal) {
			$return .= $this->getFilterHiddenFields($counter, $elName);
		} else {
			$return .= $this->getAdvancedFilterHiddenFields();
		}
		return $return;
	}

	protected function filterHiddenFields()
	{
		$params =& $this->getParams();
		$elName = $this->getFilterFullName();
		$return = "\n<input type=\"hidden\" name=\"".$elName . "[join_db_name]\" value=\"" . $params->get('join_db_name') . "\"/>";
		$return .= "\n<input type=\"hidden\" name=\"".$elName . "[join_key_column]\" value=\"" . $params->get('join_key_column') . "\"/>";
		$return .= "\n<input type=\"hidden\" name=\"".$elName . "[join_val_column]\" value=\"" . $params->get('join_val_column') . "\"/>";
		return $return;
	}

	protected function filterSelectLabel()
	{
		$params =& $this->getParams();
		$label = $params->get('database_join_noselectionlabel');
		if ($label == '') {
			$label = $params->get('filter_required') == 1 ? JText::_('COM_FABRIK_PLEASE_SELECT') : JText::_('FILTER_PLEASE_SELECT');
		}
		return $label;
	}

	/**
	 * used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - basedon filter_build_method
	 * @param bol do we render as a normal filter or as an advanced search filter
	 * @param string table name to use - defaults to element's current table
	 * @param string label field to use, defaults to element name
	 * @param string id field to use, defaults to element name
	 * @return array text/value objects
	 */

	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$params =& $this->getParams();
		$filter_build = $params->get('filter_build_method', 0);
		if ($filter_build == 0) {
			$filter_build = $usersConfig->get('filter_build_method');
		}
		if ($filter_build == 2) {
			return $this->filterValueList_All($normal, $tableName, $label, $id, $incjoin);
		} else {
			return $this->filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement::_buildFilterJoin()
	 */

	protected function _buildFilterJoin()
	{
		$params =& $this->getParams();
		$joinTable = FabrikString::safeColName($params->get('join_db_name'));
		$join =& $this->getJoin();
		$joinTableName = FabrikString::safeColName($join->table_join_alias);
		$joinKey = $this->getJoinValueColumn();
		$elName = FabrikString::safeColName($this->getFullName(false, true, false));
		return 'INNER JOIN '.$joinTable.' AS ' . $joinTableName . ' ON '.$joinKey.' = '.$elName;
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement::filterValueList_All()
	 */

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		/*
		 * list of all tables that have been joined to -
		 * if duplicated then we need to join using a table alias
		 */
		$tableModel 	=& $this->getTableModel();
		$table 				=& $tableModel->getTable();
		$origTable 		= $table->db_table_name;
		$fabrikDb 		=& $tableModel->getDb();
		$params 			=& $this->getParams();
		$joinTable 	= $params->get('join_db_name');
		$joinKey		= $this->getJoinValueColumn();
		$joinVal 		= $this->getJoinLabelColumn();

		$join =& $this->getJoin();
		$joinTableName = $join->table_join_alias;
		if ($joinTable == '') { $joinTable = $joinTableName;}
		// $$$ hugh - select all values for performance gain over selecting distinct records from recorded data
		$sql 	= "SELECT DISTINCT( $joinVal ) AS text, $joinKey AS value \n FROM  ".$fabrikDb->nameQuote($joinTable)." AS ".$fabrikDb->nameQuote($joinTableName)." \n ";
		$where = $this->_buildQueryWhere();

		//ensure table prefilter is applied to query
		$prefilterWhere = $tableModel->_buildQueryPrefilterWhere($this);
		$elementName =& FabrikString::safeColName($this->getFullName(false, false, false));
		// $$$ hugh - $joinKey is already in full `table`.`element` format from getJoinValueColumn()
		//$prefilterWhere = str_replace($elementName, "`$joinTableName`.`$joinKey`", $prefilterWhere);
		$prefilterWhere = str_replace($elementName, $joinKey, $prefilterWhere);
		if (trim($where) == '') {
			$prefilterWhere = str_replace('AND', 'WHERE', $prefilterWhere);
		}
		$where .= $prefilterWhere;

		$sql .= $where;
		if (!JString::stristr($where, 'order by')) {
			$order = $params->get('filter_groupby', 'text') == 'text' ? $joinKey : $joinVal;
			$sql .= " ORDER BY $order ASC ";
		}
		$sql = $tableModel->pluginQuery($sql);
		$fabrikDb->setQuery($sql);
		FabrikHelperHTML::debug($fabrikDb->getQuery(), 'fabrikdatabasejoin getFilter');
		return $fabrikDb->loadObjectList();
	}

	/**
	 * get the column name used for the value part of the db join element
	 * @return string
	 */

	function getJoinValueColumn()
	{
		$params 		=& $this->getParams();
		$join =& $this->getJoin();
		$db =& JFactory::getDBO();
		return $db->nameQuote($join->table_join_alias).'.'.$db->nameQuote($params->get('join_key_column'));
	}

	/**
	 * build the filter query for the given element.
	 * @param $key element name in format `tablename`.`elementname`
	 * @param $condition =/like etc
	 * @param $value search string - already quoted if specified in filter array options
	 * @param $originalValue - original filter value without quotes or %'s applied
	 * @param string filter type advanced/normal/prefilter/search/querystring/searchall
	 * @return string sql query part e,g, "key = value"
	 */

	function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal')
	{
		# $$$ rob $this->_rawFilter set in tableModel::getFilterArray()
		# used in prefilter dropdown in admin to allow users to prefilter on raw db join value

		$params =& $this->getParams();
		$db = JFactory::getDBO();
		if ($type == 'querystring') {
			//$key2 = FabrikString::safeColNameToArrayKey($key);
			// $$$ rob no matter whether you use elementname_raw or elementname in the querystring filter
			// by the time it gets here we have normalized to elementname. So we check if the original qs filter was looking at the raw
			// value if it was then we want to filter on the key and not the label
			//if (!array_key_exists($key2, JRequest::get('get'))) {
			if (!$this->_rawFilter) {
				$k = $db->nameQuote($params->get('join_db_name')).'.'.$db->nameQuote($params->get('join_val_column'));
			} else {
				$k = $key;
			}
			$this->encryptFieldName($k);
			return "$k $condition $value";
			//}
		}
		$this->encryptFieldName($key);
		if (!$this->_rawFilter && ($type == 'searchall' || $type == 'prefilter')) {
			//$$$rob wasnt working for 2nd+ db join element to same table (where key =  `countries_0`.`label`)
			//$k = '`'.$params->get('join_db_name'). "`.`" . $params->get('join_val_column').'`';
			$str = "$key $condition $value";
		} else {

			$group =& $this->getGroup();
			if (!$group->isJoin() && $group->canRepeat()) {

				$fval = $this->getElement()->filter_exact_match ? $originalValue : $value;

				$str = " ($key = $fval OR $key LIKE \"$originalValue',%\"".
				" OR $key LIKE \"%:'$originalValue',%\"".
				" OR $key LIKE \"%:'$originalValue'\"".
				" )";
			} else {
				$str = "$key $condition $value";
			}
		}
		return $str;
	}

	/**
	 * used for the name of the filter fields
	 * Over written here as we need to get the label field for field searches
	 *
	 * @return string element filter name
	 */

	function getFilterFullName()
	{
		$element 	=& $this->getElement();
		$params 	=& $this->getParams();
		$fields = array('auto-complete', 'field');
		if ($params->get('join_val_column_concat') !== '' && in_array($element->filter_type, $fields)) {

			$tableModel =& $this->getTableModel();
			$connection =& $tableModel->getConnection();
			if ($params->get('join_conn_id') == $connection->_id) {
				return htmlspecialchars($this->getJoinLabelColumn(), ENT_QUOTES);
			} else {
				// $$$ rob 24/02/11 if the element is not in the same db as the join table then we shouldn't
				// filter on the concat but simply on the element name.
				return parent::getFilterFullName();
			}
		} else {
			$join_db_name = $params->get('join_db_name');
			$tableModel 	=& $this->getTableModel();
			$joins 		=& $tableModel->getJoins();
			foreach ($joins as $join) {
				if ($join->element_id == $element->id) {
					$join_db_name = $join->table_join_alias;
				}
			}
			if ($element->filter_type == 'field') {
				$elName = $join_db_name . '___' . $params->get('join_val_column');
			} else {
				$elName = parent::getFilterFullName();
			}
		}
		return FabrikString::safeColName($elName);
	}

	function getFilterLabel($rawval)
	{
		$db =& $this->getDb();
		$params =& $this->getParams();
		$orig = $params->get('database_join_where_sql');

		$k = $params->get('join_key_column');
		$l = $params->get('join_val_column');
		$t = $params->get('join_db_name');
		if ($k != '' && $l != '' & $t != '' && $rawval != '') {
			$db->setQuery("SELECT $l FROM $t WHERE $k  = $rawval");
			return $db->loadResult();
		} else {
			return $rawval;
		}
	}

	/**
	 * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
	 * @param string data posted from form to check
	 * @return bol if data is considered empty then returns true
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		// $$$ hugh on validations (at least), we're getting arrays
		if (is_array($data))
		{
			return empty($data[0]);
		}
		if ($data == '' || $data == '-1') {
			return true;
		}
		return false;
	}

	/**
	 * load in the elements js class
	 *
	 */

	function formJavascriptClass()
	{
		FabrikModelElement::formJavascriptClass('javascript.js', 'components/com_fabrik/plugins/element/fabrikdatabasejoin/', true);
		//$$$rob if we allow the popup form we have to load in all the js files now -
		//same principal as for the calendar viz
		$params = $this->getParams();
		if ($params->get('fabrikdatabasejoin_frontend_add') == 1) {
			$this->getTableModel()->getPluginManager()->loadJs();
		}
	}

	/**
	 * create an instance of the elements js class
	 * @param int group repeat counter
	 * @return string js call
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);

		if ($this->getParams()->get('database_join_display_type') == 'auto-complete') {
			FabrikHelperHTML::autoComplete($id, $this->getElement()->id, 'fabrikdatabasejoin');
		}
		$opts = $this->elementJavascriptOpts($repeatCounter);
		return "new fbDatabasejoin('$id', $opts)";
	}

	function elementJavascriptOpts($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$params =& $this->getParams();
		$element =& $this->getElement();
		//$opts = $this->_getOptionVals();
		$data 			=& $this->_form->_data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$arVals = explode("|", $element->sub_values);
		$arTxt 	= explode("|", $element->sub_labels);

		$table 		= $params->get('join_db_name');
		$opts 		=& $this->getElementJSOptions($repeatCounter);
		$forms 		=& $this->getLinkedForms();
		$popupform = $params->get('fabrikdatabasejoin_popupform');
		$popuptableid = (empty($popupform) || !isset($forms[$popupform])) ? '' : $forms[$popupform]->tableid;
		$opts->mooversion 	= (FabrikWorker::getMooVersion() == 1 ) ? 1.2 : 1.1;
		$opts->liveSite 		= COM_FABRIK_LIVESITE;
		$opts->id 					= $this->_id;
		$opts->key 					= $table . "___" . $params->get('join_key_column');
		$opts->label 				= $table . "___" . $params->get('join_val_column');
		$opts->formid 			= $this->getForm()->getForm()->id;
		$opts->tableid 			= $popuptableid;
		$opts->value    		= $arSelected;
		$opts->defaultVal 	= $this->getDefaultValue($data);
		$opts->popupform  	= $popupform;
		$opts->popwiny      = $params->get('yoffset', 0);
		$opts->display_type = $params->get('database_join_display_type', 'dropdown');
		$opts->windowwidth = $params->get('join_popupwidth', 360);
		$opts->displayType 	= $params->get('database_join_display_type', 'dropdown');
		$opts->show_please_select = $params->get('database_join_show_please_select');
		$opts->showDesc 		= $params->get('join_desc_column') === '' ? false: true;
		$opts->autoCompleteOpts = $opts->display_type == 'auto-complete' ? FabrikHelperHTML::autoCompletOptions($id, $this->getElement()->id, 'fabrikdatabasejoin') : null;
		$opts->allowadd = $params->get('fabrikdatabasejoin_frontend_add', 0) == 0 ? false : true;
		if ($this->isJoin()) {
			$join =& $this->getJoin();
			$opts->elementName = $join->table_join;
			$opts->elementShortName = $element->name;
		}
		$opts = json_encode($opts);
		return $opts;
	}

	/**
	 * gets the options for the drop down - used in package when forms update
	 *
	 */

	function ajax_getOptions()
	{
		//needed for ajax update
		$formModel =& $this->getForm();
		$form =& $formModel->getForm();
		$formModel->_rowId = JRequest::getVar('rowid');
		$data = $formModel->getData();
		foreach ($data as $key => $val) {
			JRequest::setVar($key, $val);
		}
		echo json_encode($this->_getOptions(JRequest::get('request')));
	}

	/**
	 * called when the element is saved
	 */

	function onSave()
	{
		$params =& $this->getParams();
		if (!$this->isJoin()) {
			$params	= JRequest::getVar('params', array(), 'post', 'array');
			$details	= JRequest::getVar('details', array(), 'post', 'array');
			$element =& $this->getElement();
			//load join based on this element id
			$join =& JTable::getInstance('Join', 'Table');
			$origKey = $join->_tbl_key;
			$join->_tbl_key = "element_id";
			$join->load($this->_id);
			$join->_tbl_key = $origKey;
			$join->table_join = $params['join_db_name'];
			$join->join_type = 'left';
			$join->group_id = $details['group_id'];
			$join->table_key = str_replace('`', '', $element->name);
			//$join->table_key = $params['join_val_column'];
			$join->table_join_key = $params['join_key_column'];
			$join->join_from_table = '';
			$join->attribs = "join-label=" . $params['join_val_column'] . "\n";
			$join->store();
		}
		return parent::onSave();
	}

	function onAfterSave(&$row)
	{
		// $$$ hugh - see if we have any children, and if so, see if those children have
		// entries in the joins table.  If so, update them.  If not, create them.
		$orig_id = (int)$this->_id;
		$db =& JFactory::getDBO();
		$db->setQuery("
			SELECT e.id as cid, e.group_id as gid, j.id AS parent_jid
			FROM #__fabrik_elements AS e
			LEFT JOIN #__fabrik_joins AS j ON j.element_id = e.parent_id
			WHERE e.parent_id = $orig_id
		");
		$children = $db->loadObjectList();
		foreach ($children as $child) {
			$db->setQuery("SELECT id FROM #__fabrik_joins WHERE element_id = ".$db->Quote($child->cid));
			$join = $db->loadObject();
			if (empty($join)) {
				// no join table row for this child, so create one
				// just load the parent's join record, 0 the id and set element_id to be child's id
				// $$$ hugh - and set group_id!!!
				$joinTable =& JTable::getInstance('Join', 'Table');
				$joinTable->load($child->parent_jid);
				$joinTable->id = 0;
				$joinTable->element_id = $child->cid;
				$joinTable->group_id = $child->gid;
				if (!$joinTable->store()) {
					return JError::raiseWarning(500, $joinTable->getError());
				}
			}
			else {
				// join table row exists for this child, so update it
				// load the parent element's join row, and save it as the child's
				// (so just change id and element_id)
				$joinTable =& JTable::getInstance('Join', 'Table');
				$joinTable->load($child->parent_jid);
				$joinTable->id = $join->id;
				$joinTable->element_id = $child->cid;
				$joinTable->group_id = $child->gid;
				if (!$joinTable->store()) {
					return JError::raiseWarning(500, $joinTable->getError());
				}
			}
		}
		return true;
	}

	/**
	 * called before the element is saved
	 * @param object row that is going to be updated
	 */

	function beforeSave(&$row)
	{
		$element =& $this->getElement();
		$maskbits = 4;
		$post	= JRequest::get('post', $maskbits);
		if ($post['details']['plugin'] != 'fabrikdatabasejoin') {
			// $$$ hugh - I think we could just call $this->onRemove() here, following code is same-same
			$orig_id = (int)$row->id;
			if (!empty($orig_id)) {
				$db =& JFactory::getDBO();
				$db->setQuery("DELETE FROM #__fabrik_joins WHERE element_id = $orig_id");
				$db->query();
				$db->setQuery("
					SELECT j.id AS jid
					FROM #__fabrik_elements AS e
					LEFT JOIN #__fabrik_joins AS j ON j.element_id = e.id
					WHERE e.parent_id = ".$orig_id
				);
				$join_ids = $db->loadResultArray();
				if (!empty($join_ids)) {
					$db->setQuery("DELETE FROM #__fabrik_joins WHERE id IN (".implode(',', $join_ids).")");
					$db->query();
				}
			}
		}
	}

	function onRemove($drop = false)
	{
		$db =& JFactory::getDBO();
		$orig_id = (int)$this->_id;
		if (!empty($orig_id)) {
			$db->setQuery("DELETE FROM #__fabrik_joins WHERE element_id = ".$orig_id);
			$db->query();
			$db->setQuery("
				SELECT j.id AS jid
				FROM #__fabrik_elements AS e
				LEFT JOIN #__fabrik_joins AS j ON j.element_id = e.id
				WHERE e.parent_id = $orig_id
			");
			$join_ids = $db->loadResultArray();
			if (!empty($join_ids)) {
				$db->setQuery("DELETE FROM #__fabrik_joins WHERE id IN (" . implode(',', $join_ids) . ")");
			}
		}
		parent::onRemove($drop);
	}

	/**
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */

	function getValidationWatchElements($repeatCounter)
	{
		$params =& $this->getParams();
		$trigger = $params->get('database_join_display_type') == 'dropdown' ? 'change' : 'click';
		$id 			= $this->getHTMLId($repeatCounter);
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => $trigger
		);
		return array($ar);
	}

	/**
	 * used by elements with suboptions
	 *
	 * @param string value
	 * @param string default label
	 * @return string label
	 */

	public function getLabelForValue($v, $defaultLabel = '', $data = array())
	{
		$n = $this->getFullName(false, true, false);
		if (empty($data)) {
			$data = array($n => $v, $n . '_raw' => $v);
		}
		$tmp =& $this->_getOptions($data, 0, false);
		foreach ($tmp as $obj) {
			if ($obj->value == $v) {
				$defaultLabel = $obj->text;
			}
		}
		return $defaultLabel;
	}

	/**
	 * if no filter condition supplied (either via querystring or in posted filter data
	 * return the most appropriate filter option for the element.
	 * @return string default filter condition ('=', 'REGEXP' etc)
	 */

	function getDefaultFilterCondition()
	{
		return '=';
	}

	/**
	 * is the dropdowns cnn the same as the main Joomla db
	 * @return bool
	 */
	protected function inJDb()
	{
		$config =& JFactory::getConfig();
		$cnn =& $this->getTableModel()->getConnection()->getConnection();

		// if the table database is not the same as the joomla database then
		// we should simply return a hidden field with the user id in it.
		return $config->getValue('db') == $cnn->database;
	}

	/**
	 * ajax method to get a json array of value/text pairs of options for the
	 * auto-complete rendering of the element.
	 */

	public function autocomplete_options()
	{
		$db =& JFactory::getDBO();
		$c = $this->_getValColumn();
		if (!strstr($c, 'CONCAT')) {
			$c = FabrikString::safeColName($c);
		}
		$this->_autocomplete_where = $c.' LIKE '.$db->Quote('%'.JRequest::getVar('value').'%');
		// $$$ hugh - changed last arg (incwhere) to true, not sure why it was false
		$tmp =& $this->_getOptions(array(), 0, true);
		echo json_encode($tmp);
	}

	/**
	 * get the name of the field to order the table data by
	 * @return string column to order by tablename.elementname
	 */

	function getOrderByName()
	{
		$params =& $this->getParams();
		$join =& $this->getJoin();
		$joinTable = $join->table_join_alias;
		$joinVal = $this->_getValColumn();
		$return = !strstr($joinVal, 'CONCAT') ? "$joinTable.$joinVal" : $joinVal;
		if ($return == '.') {
			$return = parent::getOrderByName();
		}
		return $return;
	}

	public function selfDiagnose()
	{
		$retStr = parent::selfDiagnose();
		if ($this->_pluginName == 'fabrikdatabasejoin') {
			//Get the attributes as a parameter object:
			$params = $this->getParams();
			//Process the possible errors returning an error string:
			if (!$params->get('join_db_name')) {
				$retStr .= "\nMissing Table";
			}
			if (!$params->get('join_key_column')) {
				$retStr .= "\nMissing Key";
			}
			if ((!$params->get('join_val_column')) && (!$params->get('join_val_column_concat'))) {
				$retStr = "\nMissing Label";
			}
		}
		return $retStr;
	}

	/**
	 * does the element store its data in a join table (1:n)
	 * @return bool
	 */

	public function isJoin()
	{
		$params =& $this->getParams();
		if ($params->get('database_join_display_type') == 'checkbox') {
			return true;
		} else {
			return parent::isJoin();
		}
	}

	protected function _buildQueryElementConcat($jkey)
	{
		$join =& $this->getJoinModel()->getJoin();
		$jointable = $join->table_join;
		$params = $this->getParams();
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$table =& $this->getTableModel()->getTable();
		$jkey = $params->get('join_db_name').'.'.$params->get('join_val_column');
		$fullElName = $this->getFullName(false, true, false);
		$sql = "(SELECT GROUP_CONCAT(".$jkey." SEPARATOR '".GROUPSPLITTER."') FROM $jointable
		LEFT JOIN ".$params->get('join_db_name')." ON "
		.$params->get('join_db_name').".".$params->get('join_key_column')." = $jointable.".$this->_element->name." WHERE parent_id = " . $table->db_primary_key . ") AS $fullElName";
		return $sql;
	}

	protected function _buildQueryElementConcatId()
	{
		$str = parent::_buildQueryElementConcatId();
		$jointable = $this->getJoinModel()->getJoin()->table_join;
		$dbtable = $this->actualTableName();
		$db = JFactory::getDbo();
		$table =& $this->getTableModel()->getTable();
		$fullElName = $this->getFullName(false, true, false)."_id";
		$str .= ", (SELECT GROUP_CONCAT(".$this->_element->name." SEPARATOR '".GROUPSPLITTER."') FROM $jointable WHERE parent_id = " . $table->db_primary_key . ") AS $fullElName";
		return $str;
	}

/**
	 * @since 2.1.1
	 * used in form model setJoinData.
	 * @return array of element names to search data in to create join data array
	 * in this case append with the repeatnums data for checkboxes rendered in repeat groups
	 */

	public function getJoinDataNames()
	{
		$a = parent::getJoinDataNames();
		if ($this->isJoin()) {
			$element =& $this->getElement();
			$group =& $this->getGroup()->getGroup();
			 $join =& $this->getJoinModel()->getJoin();
			$repeatName = $join->table_join.'___repeatnum';
			$fvRepeatName = 'join['.$group->join_id.']['.$repeatName.']';
			$a[] =array($repeatName, $fvRepeatName);
		}
		return $a;
	}
}
?>