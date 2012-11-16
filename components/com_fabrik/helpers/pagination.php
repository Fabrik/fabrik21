<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * makes the table navigation html to traverse the table data
 * @param int the total number of records in the table
 * @param int number of records to show per page
 * @param int which record number to start at
 */

jimport('joomla.html.pagination');

/**
 * extension to the normal pagenav functions
 * $total, $limitstart, $limit
 */

class FPagination extends JPagination{

	/** @var string post or ajax post method */
	var $_postMethod = 'post';

	/** "var string action url */
	var $url = null;

	var $_id = '';

	/** @bool show the total number of records found **/
	var $showTotal = false;

	var $showAllOption = false;

	/** @bool show the display num dropdown **/
	var $showDisplayNum = true;

	/** @array query string keys to ignore on nav links **/
	var $_ignoreKeys = array(
		'resetfilters',
		'clearordering',
		'clearfilters'
	);

	function setId($id )
	{
		$this->_id = $id;
	}

	/**
	 * Return the pagination footer
	 *
	 * @access	public
	 * @param int table id
	 * @param string table tmpl
	 * @return	string	Pagination footer
	 * @since	1.0
	 */
	function getListFooter($tableid = 0, $tmpl = 'default')
	{
		$app =& JFactory::getApplication();

		$list = array();
		$list['limit']			= $this->limit;
		$list['limitstart']		= $this->limitstart;
		$list['total']			= $this->total;
		$list['limitfield']		= $this->showDisplayNum ? $this->getLimitBox() : '';
		$list['pagescounter']	= $this->getPagesCounter();
		if ($this->showTotal) {
			$list['pagescounter'] .= ' ' . JText::_('TOTAL_RECORDS') . ': '. $list['total'];
		}
		$list['pageslinks']		= "<ul class=\"pagination\">".$this->getPagesLinks($tableid, $tmpl)."</ul>";

		$chromePath	= JPATH_THEMES.DS.$app->getTemplate().DS.'html'.DS.'pagination.php';

		if (file_exists($chromePath) && JRequest::getCmd('format') != 'raw')
		{
			require_once($chromePath);
			if (function_exists('pagination_list_footer')) {
				//cant allow for it to be overridden
				//return pagination_list_footer( $list);
			}
		}
		return $this->_list_footer($list);
	}

	/**
	 * Creates a dropdown box for selecting how many records to show per page
	 *
	 * @access	public
	 * @return	string	The html for the limit # input box
	 * @since	1.0
	 */
	function getLimitBox()
	{
		// Initialize variables
		$limits = array();

		// Make the option list
		for ($i = 5; $i <= 30; $i += 5) {
			$limits[] = JHTML::_('select.option', "$i");
		}
		$limits[] = JHTML::_('select.option', '50');
		$limits[] = JHTML::_('select.option', '100');
		if ($this->showAllOption == true) {
			$limits[] = JHTML::_('select.option', '0', JText::_('ALL'));
		}
		$selected = $this->_viewall ? 0 : $this->limit;
		$js = '';
		$html = JHTML::_('select.genericlist',  $limits, 'limit'.$this->_id, 'class="inputbox" size="1" onchange="'.$js.'"', 'value', 'text', $selected ) . "\n";
		return $html;
	}

	function _item_active(&$item)
	{
		$app 				=& JFactory::getApplication();
		$oTable = 'oTable'.$this->tableid;
		if ($app->isAdmin())
		{
			return "<li><a title=\"".$item->text."\" href=\"#\" onclick=\"".$oTable.".fabrikNav(" . $item->base . ");return false;\">".$item->text."</a></li>\n";
		} else {
			return "<li><a title=\"".$item->text."\" href=\"".$item->link."\" class=\"pagenav\">".$item->text."</a></li>\n";
		}
	}

function pagination_item_inactive(&$item) {
	return "<li>&nbsp;<span>".$item->text."</span>&nbsp;</li>";
}

	/**
	 * Create and return the pagination page list string, ie. Previous, Next, 1 2 3 ... x
	 *
	 * CANT ALLOW OVERRIDE IN TEMPLATES :s - AS THEY PRODUCE WRONG JS CODE/
	 * @access	public
	 * @return	string	Pagination page list string
	 * @since	1.0
	 */

	function getPagesLinks($tableid = 0, $tmpl = 'default')
	{
		$app =& JFactory::getApplication();
		$this->tableid = $tableid;
		$lang =& JFactory::getLanguage();

		// Build the page navigation list
		$data = $this->_buildDataObject();

		$list = array();

		$itemOverride = false;
		$listOverride = false;

		$chromePath = COM_FABRIK_FRONTEND.DS.'views'.DS.'table'.DS.'tmpl'.DS.$tmpl.DS.'default_pagination.php';
		//$chromePath = JPATH_THEMES.DS.$app->getTemplate().DS.'html'.DS.'pagination.php';
		if (JFile::exists($chromePath))
		{
			require_once ($chromePath);
			if (function_exists('fabrik_pagination_item_active') && function_exists('fabrik_pagination_item_inactive')) {
				//cant allow this as the js code we use for the items is different
				$itemOverride = true;
			}
			if (function_exists('fabrik_pagination_list_render')) {
				$listOverride = true;
			}
		}

		// Build the select list
		if ($data->all->base !== null) {
			$list['all']['active'] = true;
			$list['all']['data'] = ($itemOverride) ? fabrik_pagination_item_active($data->all, $this->tableid) : $this->_item_active($data->all);
		} else {
			$list['all']['active'] = false;
			$list['all']['data'] = ($itemOverride) ? fabrik_pagination_item_inactive($data->all) : $this->_item_inactive($data->all);
		}

		if ($data->start->base !== null) {
			$list['start']['active'] = true;
			$list['start']['data'] = ($itemOverride) ? fabrik_pagination_item_active($data->start, $this->tableid) : $this->_item_active($data->start);
		} else {
			$list['start']['active'] = false;
			$list['start']['data'] = ($itemOverride) ? fabrik_pagination_item_inactive($data->start) : $this->_item_inactive($data->start);
		}
		if ($data->previous->base !== null) {
			$list['previous']['active'] = true;
			$list['previous']['data'] = ($itemOverride) ? fabrik_pagination_item_active($data->previous, $this->tableid) : $this->_item_active($data->previous);
		} else {
			$list['previous']['active'] = false;
			$list['previous']['data'] = ($itemOverride) ? fabrik_pagination_item_inactive($data->previous) : $this->_item_inactive($data->previous);
		}

		$list['pages'] = array(); //make sure it exists
		foreach ($data->pages as $i => $page)
		{
			if ($page->base !== null) {
				$list['pages'][$i]['active'] = true;
				$list['pages'][$i]['data'] = ($itemOverride) ? fabrik_pagination_item_active($page, $this->tableid) : $this->_item_active($page);
			} else {
				$list['pages'][$i]['active'] = false;
				$list['pages'][$i]['data'] = ($itemOverride) ? fabrik_pagination_item_inactive($page) : $this->_item_inactive($page);
			}
		}
		if ($data->next->base !== null) {
			$list['next']['active'] = true;
			$list['next']['data'] = ($itemOverride) ? fabrik_pagination_item_active($data->next, $this->tableid) : $this->_item_active($data->next);
		} else {
			$list['next']['active'] = false;
			$list['next']['data'] = ($itemOverride) ? fabrik_pagination_item_inactive($data->next) : $this->_item_inactive($data->next);
		}
		if ($data->end->base !== null) {
			$list['end']['active'] = true;
			$list['end']['data'] = ($itemOverride) ? fabrik_pagination_item_active($data->end, $this->tableid) : $this->_item_active($data->end);
		} else {
			$list['end']['active'] = false;
			$list['end']['data'] = ($itemOverride) ? fabrik_pagination_item_inactive($data->end) : $this->_item_inactive($data->end);
		}

		if($this->total > $this->limit) {
			return ($listOverride) ? fabrik_pagination_list_render($list) : $this->_list_render($list);
		}
		else{
			return '';
		}
	}

	/**
	 * THIS SEEMS GOOFY TO HAVE TO OVERRIDE DEFAULT FUNCTION - BUT!
	 * THE ORIGINAL SETS THE PAGE TO EMPTY IF ITS 0 - APPARENTTLY TO DO WITH
	 * ROUTING - THIS HAS BEEN REMOVED HERE
	 *
	 * PERHAPS THE FABRIK ROUTING ISNT RIGHT?
	 *
	 * oCCURRS EVEN WITHOUT SEF URLS ON THOUGH? :s
	 *
	 * Create and return the pagination data object
	 *
	 * @access	public
	 * @return	object	Pagination data object
	 * @since	1.5
	 */
	function _buildDataObject()
	{
		$app 				=& JFactory::getApplication();
		$admin = $app->isAdmin();
 		// Initialize variables
		$data = new stdClass();
		// $$$ rob all of this moved to tablemodel::getTableAction()
		/*if (is_null($this->url)) {
		// $$$ hugh - need to rebuild URL from first principles rather than using $this->url
		// when SEF'ed so we can remove unwanted query string vars like 'resetfilters'
		$router = &$app->getRouter();
		if ((int)$router->getMode() === (int)JROUTER_MODE_SEF) {
			$queryvars = $router->parse(JFactory::getURI());
			$this->url = "index.php?";
			$qs = array();
			foreach ($queryvars as $key => $val) {
				if (in_array($key, $this->_ignoreKeys)) {
					continue;
				}
				$qs[] = "$key=$val";
			}
			$this->url 	=  $this->url . implode("&amp;",$qs);
			$this->url 	= JRoute::_($this->url);
		}
		$this->url = preg_replace( "/limitstart{$this->_id}=(.*)?(&|)/", "", $this->url);
		$this->url = FabrikString::rtrimword( $this->url, "&");
		}*/
		// $$$ hugh - need to work out if we need & or ?
		$sepchar = strstr($this->url,'?') ? '&amp;' : '?';
		//$sepchar = '&';
		$data->all	= new JPaginationObject(JText::_('VIEW ALL'));
		if (!$this->_viewall) {
			$data->all->base	= '0';
			$data->all->link	= $admin ? "{$sepchar}limitstart=" : JRoute::_("{$sepchar}limitstart=");
		}

		// Set the start and previous data objects
		$data->start	= new JPaginationObject(JText::_('START'));
		$data->previous	= new JPaginationObject(JText::_('PREV'));

		if ($this->get('pages.current') > 1)
		{
			$page = ($this->get('pages.current') -2) * $this->limit;

			//$page = $page == 0 ? '' : $page; //set the empty for removal from route
			$data->start->base	= '0';
			$data->start->link	= $admin ? "{$sepchar}limitstart{$this->_id}=0" : JRoute::_($this->url."{$sepchar}limitstart{$this->_id}=0");

			$data->previous->base	= $page;
			$data->previous->link	= $admin ? "{$sepchar}limitstart{$this->_id}=".$page : JRoute::_($this->url."{$sepchar}limitstart{$this->_id}=".$page);

			$data->start->link = str_replace('resetfilters=1', '', $data->start->link);
			$data->previous->link = str_replace('resetfilters=1', '', $data->previous->link);
			$data->start->link = str_replace('clearordering=1', '', $data->start->link);
			$data->previous->link = str_replace('clearordering=1', '', $data->previous->link);
		}

		// Set the next and end data objects
		$data->next	= new JPaginationObject(JText::_('NEXT'));
		$data->end	= new JPaginationObject(JText::_('END'));

		if ($this->get('pages.current') < $this->get('pages.total'))
		{
			$next = $this->get('pages.current') * $this->limit;
			$end  = ($this->get('pages.total') -1) * $this->limit;

			$data->next->base	= $next;
			$data->next->link	= $admin ? "{$sepchar}limitstart{$this->_id}=".$next : JRoute::_($this->url."{$sepchar}limitstart{$this->_id}=".$next);
			$data->end->base	= $end;
			$data->end->link	= $admin ? "{$sepchar}limitstart{$this->_id}=".$end : JRoute::_($this->url."{$sepchar}limitstart{$this->_id}=".$end);

			$data->next->link = str_replace('resetfilters=1', '', $data->next->link);
			$data->end->link = str_replace('resetfilters=1', '', $data->end->link);
			$data->next->link = str_replace('clearordering=1', '', $data->next->link);
			$data->end->link = str_replace('clearordering=1', '', $data->end->link);
		}

		$data->pages = array();
		$stop = $this->get('pages.stop');
		for ($i = $this->get('pages.start'); $i <= $stop; $i ++)
		{
			$offset = ($i -1) * $this->limit;

			//$offset = $offset == 0 ? '' : $offset;  //set the empty for removal from route

			$data->pages[$i] = new JPaginationObject($i);
			if ($i != $this->get('pages.current') || $this->_viewall)
			{
				$data->pages[$i]->base	= $offset;
				$data->pages[$i]->link	= $admin ? "{$sepchar}limitstart{$this->_id}=".$offset : JRoute::_($this->url."{$sepchar}limitstart{$this->_id}=".$offset);
				$data->pages[$i]->link = str_replace('resetfilters=1', '', $data->pages[$i]->link);
				$data->pages[$i]->link = str_replace('clearordering=1', '', $data->pages[$i]->link);
			}
		}
		return $data;
	}

	function _list_footer($list)
	{

		// Initialize variables
		$html = "<div class=\"list-footer\">\n";
		if ($this->showDisplayNum) {
			$html .= "\n<div class=\"limit\">".JText::_('Display Num').$list['limitfield']."</div>";
		}
		$html .= $list['pageslinks'];
		$html .= "\n<div class=\"counter\">".$list['pagescounter']."</div>";

		$html .= "\n<input type=\"hidden\" name=\"limitstart{$this->_id}\" id=\"limitstart{$this->_id}\" value=\"".$list['limitstart']."\" />";
		$html .= "\n</div>";

		return $html;
	}

	function _item_inactive(&$item)
	{
		$app =& JFactory::getApplication();
		if ($app->isAdmin()) {
			return "<li><span>".$item->text."</span></li>";
		} else {
			return "<li><span class=\"pagenav\">".$item->text."</span></li>";
		}
	}

function _list_render($list)
	{
		// Initialize variables
		$html = null;

		// Reverse output rendering for right-to-left display
		$html .= '<li>&lt;&lt;</li>';
		$html .= $list['start']['data'];
		$html .= '<li>&lt;</li>';
		$html .= $list['previous']['data'];
		foreach( $list['pages'] as $page ) {
			$html .= ' '.$page['data'];
		}
		$html .= ' '. $list['next']['data'];
		$html .= '<li>&gt;</li>';
		$html .= ' '. $list['end']['data'];
		$html .= '<li>&gt;&gt;</li>';

		return $html;
	}

}

?>