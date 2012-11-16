<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.file');
$defines = JFile::exists(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php') ? JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php' : JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php';
require_once($defines);
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');//leave in as for some reason content plguin isnt loading the fabrikworker class
/**
 * Fabrik Component HTML Helper
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikHelperHTML
{

	function packageJS()
	{

		static $packagejsincluded;

		//if (JRequest::getVar('tmpl') == 'component') {
		if (FabrikHelperHTML::inAjaxLoadedPage()) {
			//was commented out? but its needed to not overwrite oPackage
			//in the calendar add event form
			//if there is another reason for us to load it here then we should
			//probably run a test for the existance of oPackage before initialising it

			//inside component tmpl - package should be loaded
			$packagejsincluded = true;
			return;
		}
		if (!isset($packagejsincluded)) {
			// $$$ rob - erm why force load mocha with the package - it may not be needed
			//FabrikHelperHTML::mocha();
			$document =& JFactory::getDocument();
			// Load the javascript
			FabrikHelperHTML::script('package.js', 'media/com_fabrik/js/', true);
			//dont have this inside an onload
			$opts = new stdClass();
			$opts->liveSite = COM_FABRIK_LIVESITE;
			$opts->mooversion = (FabrikWorker::getMooVersion() == 1 ) ? 1.2 : 1.1;
			$opts->loading = JText::_('LOADING');
			$opts = json_encode($opts);
			$script = "var oPackage = new fabrikPackage($opts);	";
			FabrikHelperHTML::addScriptDeclaration($script);
			$packagejsincluded = true;
		}
	}

	/**
   * load up mocha window code - should be run in ajax loaded pages as well
   * might be an issue in that we may be re-observing some links when loading in mocha - need to check
	 * @param string element select to auto create windows for  - was default = a.modal
	 */

	function mocha($selector='', $params = array())
	{
		static $modals;
		static $mocha;
		$script = '';

		$document =& JFactory::getDocument();
		$mooversion = FabrikWorker::getMooVersion();
		// Load the necessary files if they haven't yet been loaded
		if (!isset($mocha)) {

			// Load the javascript and css
			if (FabrikWorker::nativeMootools12()) {
				FabrikHelperHTML::script('excanvas_r43.js', 'components/com_fabrik/libs/mochaSVN/scripts/', true);
				//FabrikHelperHTML::script('excanvas.js', 'components/com_fabrik/libs/', true);

				$config = &JFactory::getConfig();
				$debug = $config->getValue('config.debug');
				if (!$debug) {
					FabrikHelperHTML::script('mocha.js', 'components/com_fabrik/libs/mochaui-0.9.7/scripts/', true);
				} else {
					FabrikHelperHTML::script('Core.js', 'components/com_fabrik/libs/mochaui-0.9.7/scripts/core/Core/', true);
					FabrikHelperHTML::script('Window.js', 'components/com_fabrik/libs/mochaui-0.9.7/scripts/core/Window/', true);
					FabrikHelperHTML::script('Modal.js', 'components/com_fabrik/libs/mochaui-0.9.7/scripts/core/Window/', true);
				}

				$theme = JRequest::getVar('mochatheme', 'default');

				JHTML::stylesheet('Core.css', 'components/com_fabrik/libs/mochaui-0.9.7/themes/'.$theme.'/css/');
				JHTML::stylesheet('Dock.css', 'components/com_fabrik/libs/mochaui-0.9.7/themes/'.$theme.'/css/');
				JHTML::stylesheet('Tabs.css', 'components/com_fabrik/libs/mochaui-0.9.7/themes/'.$theme.'/css/');
				JHTML::stylesheet('Window.css', 'components/com_fabrik/libs/mochaui-0.9.7/themes/'.$theme.'/css/');

			}else{
				switch ($mooversion) {
					case -1:
					case 0:
						FabrikHelperHTML::script('excanvas.js', 'components/com_fabrik/libs/', true);
						FabrikHelperHTML::script('mocha.js', 'components/com_fabrik/libs/', true);
						JHTML::stylesheet('mocha.css', 'components/com_fabrik/libs/mocha/css/');
						break;
					case 1:
						FabrikHelperHTML::script('excanvas_r43.js', 'components/com_fabrik/libs/mochaSVN/scripts/', true);
						FabrikHelperHTML::script('mocha.js', 'components/com_fabrik/libs/mochaSVN/scripts/', true);
						JHTML::stylesheet('Core.css', 'components/com_fabrik/libs/mochaSVN/themes/default/css/');
						JHTML::stylesheet('Window.css', 'components/com_fabrik/libs/mochaSVN/themes/default/css/');
						break;
				}
			}
		}

		if (!isset($modals)) {
			$modals = array();
		}

		$sig = md5(serialize(array($selector,$params)));
		if (isset($modals[$sig]) && ($modals[$sig])) {
			return;
		}
		// $$$ rob 11/07/2011 - get the js loading in popup windows
		$event = FabrikHelperHTML::inAjaxLoadedPage() ? 'domready' : 'load';
		$script .= "window.addEvent('$event', function() {";
		if (!isset($mocha)) {
			if (array_key_exists('dock', $params) && $params['dock']) {
				$script .= "\n  var dock = new Element('div', {'id':'mochaDock'}).adopt(".
				" [new Element('div', {id:'mochaDockPlacement'}),".
				" new Element('div', {'id':'mochaDockAutoHide'})]".
				");".
				"\ndock.injectInside(document.body);";
			}

			if (FabrikWorker::nativeMootools12()) {
				$mooversion = 1;
			}
			//@TODO in google chrome the desktop opton needs to be defined for MochaUI.Desktop
			switch ($mooversion) {
				case -1:
				case 0:
					$script .= "\n  document.mochaScreens = new MochaScreens();".
				"\n document.mochaDesktop = new MochaDesktop();";
					break;
				case 1:
					// $script .= "\n  MochaUI.Desktop = new MochaUI.Desktop();"; //no longer works or is needed in SVN mocha version?
					//"\n  MochaUI.Dock = new MochaUI.Dock();;";
					break;
			}
			$mocha = true;
		}
		if ($selector == '') {
			$script .= "\n})";
			FabrikHelperHTML::addScriptDeclaration($script);
			return;
		}

		// Setup options object
		$opt['ajaxOptions']	= (isset($params['ajaxOptions']) && (is_array($params['ajaxOptions']))) ? $params['ajaxOptions'] : null;
		$opt['size']		= (isset($params['size']) && (is_array($params['size']))) ? $params['size'] : null;
		$opt['onOpen']		= (isset($params['onOpen'])) ? $params['onOpen'] : null;
		$opt['onClose']		= (isset($params['onClose'])) ? $params['onClose'] : null;
		$opt['onUpdate']	= (isset($params['onUpdate'])) ? $params['onUpdate'] : null;
		$opt['onResize']	= (isset($params['onResize'])) ? $params['onResize'] : null;
		$opt['onMove']		= (isset($params['onMove'])) ? $params['onMove'] : null;
		$opt['onShow']		= (isset($params['onShow'])) ? $params['onShow'] : null;
		$opt['onHide']		= (isset($params['onHide'])) ? $params['onHide'] : null;

		$options = json_encode($opt);
		// Attach modal behavior to document
		//set default values which can be overwritten in <a>'s rel attribute

		$opts 							= new stdClass();
		$opts->id 					= 'mocha-advancedsearch';
		$opts->title 				= JText::_('ADVANCED SEARCH');
		$opts->loadMethod 	= 'xhr';
		$opts->minimizable 	= false;
		$opts->collapsible 	= true;
		$opts->width 				= 500;
		$opts->height 			= 150;
		$opts 							= FastJSON::encode($opts);
		// $$$ rob why load advanced-serach.js with every mocha window - not needed!
		//FabrikHelperHTML::script('advanced-search.js', 'media/com_fabrik/js/');
		if ($mooversion > 0) {

			$script .= <<<EOD

  $$('$selector').each(function(el) {
    el.addEvent('click', function(e) {
    	var opts = $opts;
    	new Event(e).stop();
      opts2 = Json.evaluate(el.get('rel'));
      \$extend(opts, opts2);
      opts.contentURL = el.href;

      opts.onContentLoaded = function() {
  			oPackage.resizeMocha(opts.id);
			};
      new MochaUI.Window(opts);
    });
  });
});
EOD;
		} else {
			$script .= <<<EOD

  $$('$selector').each(function(el) {
    el.addEvent('click', function(e) {
      var opts = $opts;
      new Event(e).stop();
      opts2 = Json.evaluate(el.getProperty('rel'));
      \$extend(opts, opts2);
      var lastWin = document.mochaDesktop.newWindowfromElement(el, opts);
    });
  });
});
EOD;
		}

		FabrikHelperHTML::addScriptDeclaration($script);

		// Set static array
		$modals[$sig] = true;
		return;
	}

	/**
	 * show form to allow users to email form to a friend
	 * @param object form
	 */

	function emailForm($formModel, $template='')
	{
		$document =& JFactory::getDocument();
		$form =& $formModel->getForm();
		$document->setTitle($form->label);
		$document->addStyleSheet("templates/'. $template .'/css/template_css.css");
		//$url = JRoute::_('index.php?option=com_fabrik&view=emailform&tmpl=component');
		?>
<form method="post" action="index.php" name="frontendForm">
<table>
	<tr>
		<td><label for="email"><?php echo JText::_('YOUR FRIENDS EMAIL') ?>:</label>
		</td>
		<td><input type="text" size="25" name="email" id="email" /></td>
	</tr>
	<tr>
		<td><label for="yourname"><?php echo JText::_('YOUR NAME'); ?>:</label>
		</td>
		<td><input type="text" size="25" name="yourname" id="yourname" /></td>
	</tr>
	<tr>
		<td><label for="youremail"><?php echo JText::_('YOUR EMAIL'); ?>:</label>
		</td>
		<td><input type="text" size="25" name="youremail" id="youremail" /></td>
	</tr>
	<tr>
		<td><label for="subject"><?php echo JText::_('MESSAGE SUBJECT'); ?>:</label>
		</td>
		<td><input type="text" size="40" maxlength="40" name="subject"
			id="subject" /></td>
	</tr>
	<tr>
		<td colspan="2"><input type="submit" name="submit" class="button"
			value="<?php echo JText::_('SEND EMAIL'); ?>" /> &nbsp;&nbsp; <input
			type="button" name="cancel" value="<?php echo JText::_('CANCEL'); ?>"
			class="button" onclick="window.close();" /></td>
	</tr>
</table>
<input name="referrer" value="<?php echo JRequest::getVar('referrer')?>"
	type="hidden" /> <input type="hidden" name="option" value="com_fabrik" />
<input type="hidden" name="view" value="emailform" /> <input
	type="hidden" name="tmpl" value="component" /> <?php echo JHTML::_('form.token'); ?></form>
		<?php
	}

	/**
	 * once email has been sent to a frind show this message
	 */

	function emailSent($to)
	{
		$config =& JFactory::getConfig();
		$document =& JFactory::getDocument();
		$document->setTitle($config->getValue('sitename'));
		?>
<span class="contentheading"><?php echo JText::_('THIS ITEM HAS BEEN SENT TO')." $to";?></span>
<br />
<br />
<br />
<a href='javascript:window.close();'> <span class="small"><?php echo JText::_('CLOSE WINDOW');?></span>
</a>
		<?php
	}

	/**
	 * writes a print icon
	 * @param object form
	 * @param object parameters
	 * @param int row id
	 * @return string print html icon/link
	 */

	function printIcon($formModel, $params, $rowid = '')
	{
		$app =& JFactory::getApplication();
		$config	=& JFactory::getConfig();
		$form =& $formModel->getForm();
		$table =& $formModel->getTable();
		if ($params->get('print')) {
			$status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=350,directories=no,location=no";
			$url = COM_FABRIK_LIVESITE."index.php?option=com_fabrik&tmpl=component&view=details&fabrik=". $form->id . "&tableid=" . $table->id . "&rowid=" . $rowid.'&iframe=1&print=1';
			// $$$ hugh - @TODO - FIXME - if they were using rowid=-1, we don't need this, as rowid has already been transmogrified
			// to the correct (PK based) rowid.  but how to tell if original rowid was -1???
			if (JRequest::getVar('usekey') !== null) {
				$url .= "&usekey=" . JRequest::getVar('usekey');
			}
			$link = JRoute::_($url);
			$link = str_replace('&', '&amp;', $link); // $$$ rob for some reason JRoute wasnt doing this ???
			if ($params->get('icons', true)) {

				if ($app->isAdmin()) {
					$image = "<img src=\"".COM_FABRIK_LIVESITE."images/M_images/printButton.png\" alt=\"".JText::_('PRINT')."\" />";
				} else {
					$attribs = array();
					$image = JHTML::_('image.site', 'printButton.png', '/images/M_images/', NULL, NULL, JText::_('PRINT'));
				}
			} else {
				$image = '&nbsp;'. JText::_('PRINT');
			}
			if ($params->get('popup', 1)) {
				$ahref = '<a href="javascript:void(0)" onclick="javascript:window.print(); return false" title="' . JText::_('PRINT') . '">';
			} else {
				$ahref = "<a href=\"#\" onclick=\"window.open('$link','win2','$status;');return false;\"  title=\"" .  JText::_('PRINT') . "\">";
			}
			$return = $ahref .
			$image .
			"</a>";
			return $return;
		}
	}

	/**
	 * Writes Email icon
	 * @param object form
	 * @param object parameters
	 * @return string email icon/link html
	 */

	function emailIcon($formModel, $params)
	{
		$app =& JFactory::getApplication();
		$config	=& JFactory::getConfig();
		$popup = $params->get('popup', 1);
		if ($params->get('email') && !$popup) {
			$status = "status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=400,height=250,directories=no,location=no";

			$url = COM_FABRIK_LIVESITE."index.php?option=com_fabrik&view=emailform&tmpl=component&fabrik=". $formModel->_id."&rowid=$formModel->_rowId";
			if (JRequest::getVar('usekey') !== null) {
				$url .= "&usekey=" . JRequest::getVar('usekey');
			}
			$url .= '&referrer='.urlencode(JFactory::getURI()->toString());
			$link = JRoute::_($url, true);
			$link = str_replace('&', '&amp;', $link); // $$$ rob for some reason JRoute wasnt doing this ???
			if ($params->get('icons', true)) {
				if ($app->isAdmin()) {
					$image = "<img src=\"".COM_FABRIK_LIVESITE."images/M_images/emailButton.png\" alt=\"".JText::_('EMAIL')."\" />";
				} else {
					$image = JHTML::_('image.site', 'emailButton.png', '/images/M_images/', NULL, NULL, JText::_('EMAIL'));
				}
			} else {
				$image = '&nbsp;'. JText::_('EMAIL');
			}
			return "<a href=\"#\" onclick=\"window.open('$link','win2','$status;');return false;\"  title=\"" .  JText::_('EMAIL') . "\">
			$image
			</a>\n";
		}
	}

	/**
	 * @param string selected join
	 */

	function joinTypeList($sel = '')
	{
		$joinTypes = array();
		$joinTypes[] = JHTML::_('select.option', 'inner', JText::_('INNER JOIN'));
		$joinTypes[] = JHTML::_('select.option', 'left', JText::_('LEFT JOIN'));
		$joinTypes[] = JHTML::_('select.option', 'right', JText::_('RIGHT JOIN'));
		return JHTML::_('select.genericlist',  $joinTypes, 'join_type[]', 'class="inputbox" size="1" ', 'value', 'text', $sel);
	}

	/**
	 * get a list of condition options - used in advanced search
	 * @param int table id
	 * @param string selected value
	 * @return string html select list
	 */

	function conditonList($tableid, $sel = '')
	{
		$conditions = array();
		$conditions[] = JHTML::_('select.option', 'AND', JText::_('AND'));
		$conditions[] = JHTML::_('select.option', 'OR', JText::_('OR'));
		return JHTML::_('select.genericlist', $conditions, 'fabrik___filter[table_'.$tableid.'][join][]', "class=\"inputbox\" size=\"1\" ", 'value', 'text', $sel);
	}

	/**
	 * yes no options for list with please select options
	 *
	 * @param string $sel
	 * @param string default label
	 */

	function yesNoOptions($sel = '', $default = '')
	{
		if ($default == '') {
			$default = JText::_('COM_FABRIK_PLEASE_SELECT');
		}
		$yesNoList[] = JHTML::_('select.option', "", $default);
		$yesNoList[] = JHTML::_('select.option', "1", JText::_('Yes'));
		$yesNoList[] = JHTML::_('select.option', "0", JText::_('No'));
		return $yesNoList;
	}

	function tableList($sel = '')
	{
		$db =& JFactory::getDBO();
		$db->setQuery("SELECT id, label FROM #__fabrik_tables WHERE state = '1'");
		$rows = $db->loadObjectList();
		return JHTML::_('select.genericlist', $rows, 'fabrik__swaptable', 'class="inputbox" size="1" ', 'id', 'label', $sel);
	}

	function loadCalendar()
	{
		static $calendarLoaded;

		// Only load once
		if ($calendarLoaded) {
			return;
		}

		$calendarLoaded = true;

		$document =& JFactory::getDocument();
		// $$$ hugh - if 'raw' and we output the JS stuff, it screws things up by echo'ing stuff ahead
		// of the raw view display() method's JSON echo
		if ($document->getType() == 'raw') {
			return;
		}

		$config = &JFactory::getConfig();
		$debug = $config->getValue('config.debug');

		FabrikHelperHTML::stylesheet('calendar-jos.css', 'media/system/css/', array(' title' => JText::_('green') ,' media' => 'all'));
		// $$$ hugh - need to just use JHTML::script() for these, to avoid recursion issues if anything else
		// includes these files, and Fabrik is using merged JS, which means page ends up with two copies,
		// causing a "too much recursion" error (calendar.js overrides some date object functions)
		//FabrikHelperHTML::script('calendar.js', 'media/system/js/');
		//FabrikHelperHTML::script('calendar-setup.js', 'media/system/js/');

		// $$$ rob as per what Hugh said above these should be JHTML, otherwise calendard viz popup form cant select date
		// gives too much recursion error. Any main view should therefore always call this method if there is a chance that
		// it will be loading a popup form.
		JHTML::script('calendar.js', 'media/system/js/');
		JHTML::script('calendar-setup.js', 'media/system/js/');
		$translation = FabrikHelperHTML::_calendartranslation();
		if ($translation) {
			FabrikHelperHTML::addScriptDeclaration($translation);
		}

	}

	/**
	 * fabrik script to load in a style sheet
	 * takes into account if you are viewing the page in raw format
	 * if so sends js code back to webpage to inject css file into document head
	 * If not raw format then apply standard J stylesheet
	 * @param $filename
	 * @param $path
	 * @param $attribs
	 * @return null
	 */

	function stylesheet($filename, $path = 'media/system/css/', $attribs = array())
	{

		if ((JRequest::getVar('format') == 'raw' || JRequest::getVar('tmpl') == 'component') && JRequest::getVar('print') != 1) {

			static $ajaxCssFiles;
			if (!is_array($ajaxCssFiles)) {
				$ajaxCssFiles = array();
			}
			$attribs = FastJSON::encode(JArrayHelper::toObject($attribs));
			// $$$rob TEST!!!! - this may mess up stuff
			//send an inline script back which will inject the css file into the doc head
			// note your ajax call must have 'evalScripts':true set in its properties
			if (!in_array($path.$filename, $ajaxCssFiles)) {
				// $$$ rob added COM_FABRIK_LIVESITE to make full path name other wise style sheets gave 404 error
				// when loading from site with sef urls.
				echo "<script type=\"text/javascript\">var v = new Asset.css('".COM_FABRIK_LIVESITE."{$path}{$filename}', {});</script>\n";
				$ajaxCssFiles[] = $path.$filename;
			}
		} else {
			JHTML::stylesheet($filename, $path, $attribs);
		}
	}

	/**
	 * check for a custom css file and include it if it exists
	 * @param string $path NOT including JPATH_SITE (so relative too root dir
	 * @return failse
	 */

	function stylesheetFromPath($path)
	{
		if (JFile::exists(JPATH_SITE.DS.$path)) {
			$parts = explode(DS, $path);
			$file = array_pop($parts);
			$path = implode('/', $parts) .'/';
			FabrikHelperHTML::stylesheet($file, $path);
		}
	}

	/**
	 * Internal method to translate the JavaScript Calendar
	 *
	 * @return	string	JavaScript that translates the object
	 * @since	1.5
	 */
	function _calendartranslation()
	{
		static $jsscript = 0;

		/*
		 * 		Calendar._TT["ABOUT"] =
		 "DHTML Date/Time Selector\n" +
		 "(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" +
		 "For latest version visit: http://www.dynarch.com/projects/calendar/\n" +
		 "Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details." +
		 "\n\n" +
		 "Date selection:\n" +
		 "- Use the \xab, \xbb buttons to select year\n" +
		 "- Use the " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " buttons to select month\n" +
		 "- Hold mouse button on any of the above buttons for faster selection.";
		 Calendar._TT["ABOUT_TIME"] = "\n\n" +
		 "Time selection:\n" +
		 "- Click on any of the time parts to increase it\n" +
		 "- or Shift-click to decrease it\n" +
		 "- or click and drag for faster selection.";
		 */
		if($jsscript == 0)
		{
			$return = 'Calendar._DN = new Array ("'.JText::_('Sunday').'", "'.JText::_('Monday').'", "'.JText::_('Tuesday').'", "'.JText::_('Wednesday').'", "'.JText::_('Thursday').'", "'.JText::_('Friday').'", "'.JText::_('Saturday').'", "'.JText::_('Sunday').'");Calendar._SDN = new Array ("'.JText::_('Sun').'", "'.JText::_('Mon').'", "'.JText::_('Tue').'", "'.JText::_('Wed').'", "'.JText::_('Thu').'", "'.JText::_('Fri').'", "'.JText::_('Sat').'", "'.JText::_('Sun').'"); Calendar._FD = 0;	Calendar._MN = new Array ("'.JText::_('January').'", "'.JText::_('February').'", "'.JText::_('March').'", "'.JText::_('April').'", "'.JText::_('May').'", "'.JText::_('June').'", "'.JText::_('July').'", "'.JText::_('August').'", "'.JText::_('September').'", "'.JText::_('October').'", "'.JText::_('November').'", "'.JText::_('December').'");	Calendar._SMN = new Array ("'.JText::_('January_short').'", "'.JText::_('February_short').'", "'.JText::_('March_short').'", "'.JText::_('April_short').'", "'.JText::_('May_short').'", "'.JText::_('June_short').'", "'.JText::_('July_short').'", "'.JText::_('August_short').'", "'.JText::_('September_short').'", "'.JText::_('October_short').'", "'.JText::_('November_short').'", "'.JText::_('December_short').'");Calendar._TT = {};Calendar._TT["INFO"] = "'.JText::_('About the calendar').'";
		Calendar._TT["PREV_YEAR"] = "'.JText::_('Prev. year (hold for menu)').'";Calendar._TT["PREV_MONTH"] = "'.JText::_('Prev. month (hold for menu)').'";	Calendar._TT["GO_TODAY"] = "'.JText::_('Go Today').'";Calendar._TT["NEXT_MONTH"] = "'.JText::_('Next month (hold for menu)').'";Calendar._TT["NEXT_YEAR"] = "'.JText::_('Next year (hold for menu)').'";Calendar._TT["SEL_DATE"] = "'.JText::_('Select date').'";Calendar._TT["DRAG_TO_MOVE"] = "'.JText::_('Drag to move').'";Calendar._TT["PART_TODAY"] = "'.JText::_('(Today)').'";Calendar._TT["DAY_FIRST"] = "'.JText::_('Display %s first').'";Calendar._TT["WEEKEND"] = "0,6";Calendar._TT["CLOSE"] = "'.JText::_('Close').'";Calendar._TT["TODAY"] = "'.JText::_('Today').'";Calendar._TT["TIME_PART"] = "'.JText::_('(Shift-)Click or drag to change value').'";Calendar._TT["DEF_DATE_FORMAT"] = "'.JText::_('%Y-%m-%d').'"; Calendar._TT["TT_DATE_FORMAT"] = "'.JText::_('%a, %b %e').'";Calendar._TT["WK"] = "'.JText::_('wk').'";Calendar._TT["TIME"] = "'.JText::_('Time:').'";';
			$jsscript = 1;
			return $return;
		} else {
			return false;
		}
	}

/**
	 * Generates an HTML checkbox list
	 * @param array An array of objects
	 * @param string The value of the HTML name attribute
	 * @param string Additional HTML attributes for the <select> tag
	 * @param mixed The key that is selected
	 * @param string The name of the object variable for the option value
	 * @param string The name of the object variable for the option text
	 * @param int number of options to show per row @since 2.0.5
	 * @returns string HTML for the select list
	 */

	function checkboxList(&$arr, $tag_name, $tag_attribs, $selected=null, $key='value', $text='text', $options_per_row = 0)
	{
		return FabrikHelperHTML::aList('checkbox', $arr, $tag_name, $tag_attribs, $selected, $key, $text, $options_per_row);
	}

	/**
	 * Generates an HTML radio list
	 * @param array An array of objects
	 * @param string The value of the HTML name attribute
	 * @param string Additional HTML attributes for the <select> tag
	 * @param mixed The key that is selected
	 * @param string The name of the object variable for the option value
	 * @param string The name of the object variable for the option text
	 * @param int number of options to show per row @since 2.0.5
	 * @returns string HTML for the select list
	 */

	function radioList(&$arr, $tag_name, $tag_attribs, $selected=null, $key='value', $text='text', $options_per_row = 0)
	{
		return FabrikHelperHTML::aList('radio', $arr, $tag_name, $tag_attribs, $selected, $key, $text, $options_per_row);
	}

		/**
	 * Generates an HTML radio OR checkbox list
	 * @param    string  $type         radio or checkbox
	 * @param    array   &$arr         An array of objects
	 * @param    string  $tag_name     The value of the HTML name attribute
	 * @param    string  $tag_attribs  Additional HTML attributes for the <select> tag
	 * @param    mixed   $selected     The key that is selected
	 * @param string The name of the object variable for the option value
	 * @param string The name of the object variable for the option text
	 * @param int number of options to show per row @since 2.0.5
	 * @param bool is the list editable or not @since 2.1.1
	 * @returns string HTML for the select list
	 */

	public function aList($type, &$arr, $tag_name, $tag_attribs, $selected=null, $key='value', $text='text', $options_per_row = 0, $editable=true)
	{
		reset($arr);
		if ($options_per_row > 0) {
			$percentageWidth = floor(floatval(100) / $options_per_row) - 2;
			$div = "<div class=\"fabrik_subelement\" style=\"float:left;width:" . $percentageWidth . "%\">\n";
		} else {
			$div = '<div class="fabrik_subelement">';
		}
		$html = "";
		if ($editable) {
			$selectText = $type == 'checkbox' ? " checked=\"checked\"" : " selected=\"selected\"";
		} else {
			$selectText = '';
		}
		for ($i=0, $n=count($arr); $i < $n; $i++) {

			$k = $arr[$i]->$key;
			$t = $arr[$i]->$text;
			$id = isset($arr[$i]->id) ? @$arr[$i]->id : null;

			$extra = '';
			$extra .= $id ? " id=\"" . $arr[$i]->id . "\"" : '';
			$found = false;
			if (is_array($selected)) {
				foreach ($selected as $obj) {
						if (is_object($obj)) {
						$k2 = $obj->$key;
						if ($k == $k2) {
							$found = true;
							$extra .= $selected;
							break;
						}
					} else {
						if ($k == $obj) { //checkbox from db join
							$extra .= $selectText;
							$found = true;
							break;
						}
					}
				}
			} else {
				$extra .= $k == $selected ? " checked=\"checked\"" : '';
			}
			$html .= $div;

			if ($editable) {
				$html .= '<label>';
				$html .= '<input type="'.$type.'" value="'.$k.'" name="'.$tag_name.'" class="fabrikinput" ' . $extra. '/>';
			}
			if ($editable || $found) {
				$html .= '<span>'.$t.'</span>';
			}
			if ($editable) {
				$html .= '</label>';
			}
			$html .= '</div>';
		}
		$html .= "\n";
		return $html;
	}
	/**
	 * hack to get the editior code without it being written out to the page straight away
	 * think this returns a simple text field
	 */

	function getEditorArea($name, $content, $hiddenField, $width, $height, $col, $row)
	{
		$editor =& FabrikHelperHTML::getEditor();
		return $editor->display($name, $content, $width, $height, $col, $row, false);
	}

	/**
	 * Get an editor object
	 *
	 * @access public
	 * @param string $editor The editor to load, depends on the editor plugins that are installed
	 * @return object JEditor
	 */

	function &getEditor($editor = null)
	{
		jimport('joomla.html.editor');

		//get the editor configuration setting
		if (is_null($editor))
		{
			$conf =& JFactory::getConfig();
			$editor = $conf->getValue('config.editor');
		}
		$instance =& FEditor::getInstance($editor);
		return $instance;
	}

	/**
	 *
	 */

	function PdfIcon($model, $params, $rowId = 0, $attribs = array())
	{
		$app =& JFactory::getApplication();
		$url = '';
		$text	= '';
		// $$$ rob changed from looks at the view as if rendering the table as a module when rendering a form
		// view was form, but $Model is a table
		$modelClass = get_class($model);
		$task = JRequest::getVar('task');
		if ($task == 'form' || $modelClass == 'FabrikModelForm') {
			$form = $model->getForm();
			//$table = $model->_table->getTable();
			$table = $model->getTable();
			$user =& JFactory::getUser();
			$url = COM_FABRIK_LIVESITE."index.php?option=com_fabrik&amp;view=details&amp;format=pdf&amp;fabrik=". $form->id . "&amp;tableid=" . $table->id . "&amp;rowid=" . $rowId;
		} else {
			$table = $model->getTable();
			$url = COM_FABRIK_LIVESITE."index.php?option=com_fabrik&amp;view=table&amp;format=pdf&amp;tableid=" . $table->id;
		}
		if (JRequest::getVar('usekey') !== null) {
			$url .= "&amp;usekey=" . JRequest::getVar('usekey');
		}
		$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

		// checks template image directory for image, if non found default are loaded
		if ($app->isAdmin()) {
			$text = "<img src=\"".COM_FABRIK_LIVESITE."images/M_images/pdf_button.png\" alt=\"".JText::_('PDF')."\" />\n";
		} else {
			$text = JHTML::_('image.site', 'pdf_button.png', '/images/M_images/', NULL, NULL, JText::_('PDF'));
		}
		$attribs['title']	= JText::_('PDF');
		$attribs['onclick'] = "window.open(this.href,'win2','".$status."'); return false;";
		$attribs['rel']     = 'nofollow';
		$url = JRoute::_($url);
		$output = JHTML::_('link', $url, $text, $attribs) . "\n";
		return $output;
	}

	/**
	 * should be called JUST before view is rendered (unless in admin when it should be called at end of view
	 * ensures that incompatible versions of mootools are removed
	 * and that if there is a combined js file it is loaded
	 */

	function cleanMootools()
	{

		$version = new JVersion();
		global $combine;
		FabrikHelperHTML::_getCombine();
		$app 				=& JFactory::getApplication();
		$fbConfig 	=& JComponentHelper::getParams('com_fabrik');
		if ($fbConfig->get('merge_js', false)) {
			$file = $combine->getCacheFile();
			$combine->output();
			$p = FabrikString::ltrimword(str_replace("\\", "/", str_replace(COM_FABRIK_BASE, '', $combine->outputFolder() ) ), "/" ) . "/";
			// $$$ rob DONT use FabrikHelper::Script here - this is the ONE place where we HAVE to use JHTML::Script
			JHTML::script($file, $p);
		}
		if ($version->RELEASE == '1.5' && $version->DEV_LEVEL >= 19) {
			if (JPluginHelper::isEnabled( 'system', 'mtupgrade')) {
				return;
			}
		}
		if (FabrikWorker::getMooVersion() == 1) {
			$document =& JFactory::getDocument();
			$found = false;

			//new order for scripts
			$scripts = array();
			//array of scripts to place first
			$aM2scripts = array(
				'/js/archive/',
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2.js',
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-uncompressed.js',
				'/components/com_fabrik/libs/mootools1.2/compat.js',
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-ext.js',
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-more.js',
				'/components/com_fabrik/libs/mootools1.2/mootools-1.2-more-uncompressed.js',
				'/components/com_fabrik/libs/mootools1.2/tips.js');

			$docscripts = FabrikHelperHTML::getDocumentHeadPart( 'scripts');
			foreach ($docscripts as $script=> $type) {
				foreach ($aM2scripts as $ms) {
					if (strstr($script, $ms)) {
						$found = true;
						$scripts[$script] = $type;
					}
				}
			}
			if ($found) {
				$removescripts = array();
				// $$$ hugh @TODO - need to fix this for >.5.19 with built in moo, otherwise
				// we are removing J!'s moo!  So added the media/system/js/ path prefix.
				foreach ($docscripts as $script=>$type) {
					if (strstr($script, 'media/system/js/mootools.js') || strstr($script, 'media/system/js/mootools-uncompressed.js') || strstr($script, 'mootools-release-1.11.js') || strstr($script, 'mootools.v1.1.js')) {
						$removescripts[$script] = $type;
					}
				}

				// $rob test shifting all the mootools1.2 scripts to the top of the doc head
				// fixes issue with jceutilities js script
				$first = array();
				foreach($document->_scripts as $script => $type) {
					foreach ($aM2scripts as $ms) {
						if (strstr($script, $ms)) {
							unset($document->_scripts[$script]);
							$first[$script] = $type;
						}
					}
				}
				$document->_scripts = $first + $document->_scripts;

				FabrikHelperHTML::removeDocumentHeadPart( 'scripts', $removescripts);
			}
		}
	}

	/**
	 * if we have Koowa installed we can get the required part of the head data
	 * without reverting to accessing private properties
	 * @param string part of docuement head to retrieve
	 * @return array docuemnt head parts
	 */

	public function getDocumentHeadPart( $type )
	{
		$document =& JFactory::getDocument();
		if (!defined('KOOWA')) {
			$type = '_' . $type;
			$docscripts = $document->$type;
		} else {
			$docscripts = $document->getHeadData();
			$docscripts = $docscripts[$type];
		}
		return $docscripts;
	}

	/**
	 * remove an array of files from a document head part
	 * @param string head part type script/style etc
	 * @param array of scripts to remove
	 * @return null
	 */

	public function removeDocumentHeadPart( $type, $remove )
	{
		$document =& JFactory::getDocument();
		$docscripts =& FabrikHelperHTML::getDocumentHeadPart( 'scripts');
		$keep = FArrayHelper::array_key_diff( $docscripts, $remove);
		if (!defined('KOOWA')) {
			$type = '_' . $type;
			$document->$type = null;
			$document->$type = $keep;
		} else {
			$document->setHeadData(array($type => $keep));
		}
	}

	/**
	 * Keep session alive, for example, while editing or creating an article.
	 */

	function keepalive()
	{
		//test since 2.0b3 dont do anything if loading from mocha win
		if (JRequest::getVar('tmpl') == 'component') {
			return;
		}
		//end test
		// Include mootools framework
		FabrikHelperHTML::mootools();

		$config 	 =& JFactory::getConfig();
		$lifetime 	 = ($config->getValue('lifetime') * 60000);
		$refreshTime =  ( $lifetime <= 60000 ) ? 30000 : $lifetime - 60000;
		//refresh time is 1 minute less than the liftime assined in the configuration.php file

		$document =& JFactory::getDocument();
		$script  = '';
		$script .= 'function keepAlive() {';
		$script .=  '	var myAjax = new Ajax( "index.php", { method: "get" }).request();';
		$script .=  '}';
		$script .= 	' window.addEvent("domready", function()';
		$script .= 	'{ keepAlive.periodical('.$refreshTime.'); }';
		$script .=  ');';
		FabrikHelperHTML::addScriptDeclaration($script);
		return;
	}

	/**
	 * overwrite standard J mootools file with mootools 1.2
	 * this isnt really going to work out - too much incompatibilty between the two code bases
	 * even with "compatibility mode" on will try again when final 1.2 is out
	 */

	function mootools()
	{
		static $mootools;

		if (!isset($mootools)) {
			$mootools = true;

			if (FabrikWorker::nativeMootools12()) {
				//new standard J mt 1.2.4 pluign is enabled
				// $$$ hugh - because we set $mootools false in our script() method, before
				// we call the JHTML::script(), their method then doesn't include moo automagically.
				// So let's make darn sure it gets loaded!
				JHTML::_('behavior.mootools');

				FabrikHelperHTML::script('j1.5.20_mootools-1.2-ext.js', 'components/com_fabrik/libs/mootools1.2/', true);
				//FabrikHelperHTML::script('compat12.js', 		'components/com_fabrik/libs/mootools1.2/', false);
				return;
			}
			$mooversion = FabrikWorker::getMooVersion();
			if ($mooversion == -1) {
				FabrikHelperHTML::script('mootools-ext.js', 	'components/com_fabrik/libs/', true);
				return;
			}
			if ($mooversion == 1) {
				$document =& JFactory::getDocument();

				$docscripts =& FabrikHelperHTML::getDocumentHeadPart( 'scripts');
				$newscripts = array();
				foreach ($docscripts as $script=>$type) {
					if (strstr($script, '/media/system/js/mootools.js') || strstr($script, '/fabrik2.0.x/media/system/js/mootools-uncompressed.js')) {
						$newscripts[$script] = $type;
					}
				}
				FabrikHelperHTML::removeDocumentHeadPart( 'scripts', $newscripts);

				$config = &JFactory::getConfig();
				$debug = $config->getValue('config.debug');

				// TODO NOTE: Here we are checking for Konqueror - If they fix thier issue with compressed, we will need to update this
				$konkcheck = strpos (strtolower($_SERVER['HTTP_USER_AGENT']), "konqueror");

				if ($debug || $konkcheck) {
					FabrikHelperHTML::script('mootools-1.2-uncompressed.js', 			'components/com_fabrik/libs/mootools1.2/', false);
					FabrikHelperHTML::script('mootools-1.2-more-uncompressed.js', 	'components/com_fabrik/libs/mootools1.2/', false);
				} else {
					FabrikHelperHTML::script('mootools-1.2.js', 			'components/com_fabrik/libs/mootools1.2/', false);
					FabrikHelperHTML::script('mootools-1.2-more.js', 'components/com_fabrik/libs/mootools1.2/', false);
				}
				FabrikHelperHTML::script('compat.js', 'components/com_fabrik/libs/mootools1.2/', false);
				FabrikHelperHTML::script('compat12.js', 'components/com_fabrik/libs/mootools1.2/', false);
				FabrikHelperHTML::script('tips.js', 'components/com_fabrik/libs/mootools1.2/', false);
				FabrikHelperHTML::script('mootools-1.2-ext.js', 'components/com_fabrik/libs/mootools1.2/', true);
			} else {
				FabrikHelperHTML::script('mootools-ext.js', 'components/com_fabrik/libs/', true);
			}
		}
	}

	function _getCombine()
	{
		static $bcombine;
		global $combine;
		if (!isset($bcombine)) {
			$bcombine = true;
			//combine js test
			require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'combine-js.php');
			global $combine;
			$combine = new combineJS();
			//end test
		}
	}

	/**
	 * if in tmpl view or raw view delay the loading of the script
	 * @param string js $script
	 * @return null
	 */

	function addScriptDeclaration($script)
	{
		$document =& JFactory::getDocument();
		if (FabrikHelperHTML::inAjaxLoadedPage()) {
			$script = "(function() {".$script."}).delay(2000);";
		}
		if (FabrikHelperHTML::inAjaxLoadedPage()) {
			echo "<script type='text/javascript'>$script</script>\n";

		} else {
			$script = "/* <![CDATA[ */ \n" . $script . "\n /* ]]> */";
			$document->addScriptDeclaration($script);
		}
	}

	/**
	 * sometimes you want to load a page in an iframe and want to use tmpl=component - in this case
	 * append iframe=1 to the url to ensure that we dont try to add the scripts via FBAsset()
	 */

	function inAjaxLoadedPage()
	{
		if (class_exists('JSite')) {
			$menus	= &JSite::getMenu();
			$menu	= $menus->getActive();
			//popup menu item so not in ajax loaded page even if tmpl=component
			// $$$ hugh - nope, browserNav of '1' is not a popup, just a new tab, see ...
			// http://fabrikar.com/forums/showthread.php?p=111771#post111771
			// if (is_object($menu) && ($menu->browserNav == 2 || $menu->browserNav == 1)) {
			if (is_object($menu) && ($menu->browserNav == 2)) {
				return false;
			}
		}
		return JRequest::getVar('format') == 'raw' || (JRequest::getVar('tmpl') == 'component' && JRequest::getInt('iframe') != 1);
	}

	/**
	 * wrapper for JHTML::Script()
	 */

	function script($filename, $path = 'media/system/js/', $mootools = true)
	{
		static $rawmootools;
		global $combine;
		$app 				=& JFactory::getApplication();
		if (FabrikHelperHTML::inAjaxLoadedPage()) {

			// $$$rob TEST!!!! - this may mess up stuff

			//no need to reload mootools as if this is an ajax request it has already been loaded
			//FabrikHelperHTML::mootools();

			static $ajaxJsFiles;
			if (!is_array($ajaxJsFiles)) {
				$ajaxJsFiles = array();
			}

			// $$$rob TEST!!!! - this may mess up stuff
			//send an inline script back which will inject the javascript file into the doc head
			// note your ajax call must have 'evalScripts':true set in its properties
			// $$$ hugh - yeah, it messes up :) "Fatal error: Class 'FastJSON' not found"
			// for instance when creating a calendar viz event ... added require_once
			if (!in_array($path.$filename, $ajaxJsFiles)) {
				$attribs = FastJSON::encode(JArrayHelper::toObject($attribs));
				$file = $path.$filename;
				if (substr($file, 0, 4) !== 'http') {
					$file = COM_FABRIK_LIVESITE.$file;
				}
				echo "<script type=\"text/javascript\">var v = new Asset.javascript('".$file."', {});</script>\n";
				$ajaxJsFiles[] = $path.$filename;
			}

			return;
		}

		// $$$ - hugh - something, somewhere is trying to add a blank filename
		// with '/' as the directory, which ends up trying to load the main page
		// as a JS file.  So some defensive coding to avoid this!
		// @TODO - really need to find out who is doing it tho!
		if (empty($filename)) {
			return;
		}
		FabrikHelperHTML::_getCombine();
		$fbConfig =& JComponentHelper::getParams('com_fabrik');
		if ($mootools) {
			FabrikHelperHTML::mootools();
		}

		if (FabrikWorker::getMooVersion() == 1) {
			$mootools = false;
		}

		if ($fbConfig->get('merge_js', false) && !$app->isAdmin()) {
			$combine->addFile($path.$filename);
		}else{
			JHTML::script($filename, $path, $mootools);
		}
	}

	function slimbox()
	{
		$mooversion = FabrikWorker::getMooVersion();
		$fbConfig =& JComponentHelper::getParams('com_fabrik');
		if ($fbConfig->get('include_lightbox_js', 1) == 0) {
			return;
		}
		if ($fbConfig->get('use_mediabox', false) && FabrikWorker::nativeMootools12()) {
			// $$$ hugh - 4/24/2011 - upgraded Mediabox to (almost) latest, needs Moo >= 1.2.4
			// So only include latest (mediaboxAdv) if Moo Upgrade
			$folder = 'components/com_fabrik/libs/mediabox_advanced/';
			JHTML::stylesheet('mediaboxAdvBlack.css', $folder . 'css/');
			FabrikHelperHTML::script('mediaboxAdv.js', $folder, true);
		}
		else if ($fbConfig->get('use_mediabox', false) && $mooversion == 1) {
			$folder = 'components/com_fabrik/libs/mediabox/';
			JHTML::stylesheet('mediabox.css', $folder . 'css/');
			FabrikHelperHTML::script('mediabox.js', $folder, true);
		}
		else {
			if ($mooversion == 1) {
				//$folder = 'components/com_fabrik/libs/slimbox1.64/js/';
				$folder = 'components/com_fabrik/libs/slimbox1.71a/';
				$folder .= JDEBUG ? 'src/' : 'js/';
				JHTML::stylesheet('slimbox.css', 'components/com_fabrik/libs/slimbox1.71a/css/');
			} else {
				JHTML::stylesheet('slimbox.css', 'components/com_fabrik/css/slimbox/');
				$folder = 'components/com_fabrik/libs/';
			}
			FabrikHelperHTML::script('slimbox.js', $folder, true);
		}
	}

	/**
	 *
	 * @param $selector string class name of tips
	 * @param $params array paramters
	 * @param $selectorPrefix limit the tips selection to those contained within an id
	 * @return unknown_type
	 */

	function tips($selector='.hasTip', $params = array(), $selectorPrefix = 'document')
	{
		static $tips;

		if (!isset($tips)) {
			$tips = array();
		}

		// Include mootools framework
		FabrikHelperHTML::mootools();

		$sig = md5(serialize(array($selector,$params)));
		if (isset($tips[$sig]) && ($tips[$sig])) {
			return;
		}

		// Setup options object
		$opt['maxTitleChars']	= (isset($params['maxTitleChars']) && ($params['maxTitleChars'])) ? (int)$params['maxTitleChars'] : 50;
		if (isset($params['offsets'])) {
			$opt['offsets']	= (int)$params['offsets'];
		}
		if (isset($params['showDelay'])) {
			$opt['showDelay'] = (int)$params['showDelay'];
		}
		if (isset($params['hideDelay'])) {
			$opt['hideDelay'] = (int)$params['hideDelay'];
		}
		if (isset($params['className'])) {
			$opt['className']	= $params['className'];
		}
		$opt['fixed']			= (isset($params['fixed']) && ($params['fixed'])) ? true : false;
		if (isset($params['onShow'])) {
			$opt['onShow'] = $params['onShow'];
		}
		if (isset($params['onHide'])) {
			$opt['onHide'] = $params['onHide'];
		}

		$options = json_encode($opt);

		// Attach tooltips to document
		$document =& JFactory::getDocument();
		//force the zindex to 9999 so that it appears above the popup window.
		$event = (JRequest::getVar('tmpl') == 'component') ? 'load' : 'domready';
		$tooltipInit = '		window.addEvent(\''.$event.'\', function() {if($type('.$selectorPrefix.') !== false && '.$selectorPrefix.'.getElements(\''.$selector.'\').length !== 0) {window.JTooltips = new Tips('.$selectorPrefix.'.getElements(\''.$selector.'\'), '.$options.');$$(".tool-tip").setStyle("z-index", 999999);}});';
		FabrikHelperHTML::addScriptDeclaration($tooltipInit);

		// Set static array
		$tips[$sig] = true;
		return;

	}

	/**
	 * Internal method to get a JavaScript object notation string from an array
	 *
	 * @param	array	$array	The array to convert to JavaScript object notation
	 * @return	string	JavaScript object notation representation of the array
	 * @since	1.5
	 */

	function _getJSObject($array=array())
	{
		// Initialize variables
		$object = '{';

		// Iterate over array to build objects
		foreach ((array)$array as $k => $v)
		{
			if (is_null($v)) {
				continue;
			}
			if (!is_array($v) && !is_object($v)) {
				$object .= ' '.$k.': ';
				$object .= (is_numeric($v) || strpos($v, '\\') === 0) ? (is_numeric($v)) ? $v : substr($v, 1) : "'".$v."'";
				$object .= ',';
			} else {
				$object .= ' '.$k.': '.FabrikHelperHTML::_getJSObject($v).',';
			}
		}
		if (substr($object, -1) == ',') {
			$object = substr($object, 0, -1);
		}
		$object .= '}';

		return $object;
	}

	/**
	 * add a debug out put section
	 * @param mixed string/object $content
	 * @param string $title
	 */

	function debug($content, $title = 'output:')
	{
		$config =& JComponentHelper::getParams('com_fabrik');
		if ($config->get('use_fabrikdebug') == 0) {
			return;
		}
		if (FabrikWorker::getMooVersion() == -1) {
			return;
		}
		if (JRequest::getBool( 'fabrikdebug', 0, 'request') != 1) {
			return;
		}
		if (JRequest::getVar('format') == 'raw') {
			return;
		}
		echo "<div class=\"fabrikDebugOutputTitle\">$title</div>";
		echo "<div class=\"fabrikDebugOutput fabrikDebugHidden\">";
		if (is_object($content) || is_array($content)) {
			echo "<pre>";print_r($content);echo "</pre>";
		} else {
			echo $content;
		}
		echo "</div>";
		static $debug;

		if (!isset($debug)) {
			$debug = true;
			$document =& JFactory::getDocument();
			$style = ".fabrikDebugOutputTitle{padding:5px;background:#efefef;color:#333;border:1px solid #999;cursor:pointer}";
			$style .= ".fabrikDebugOutput{padding:5px;background:#efefef;color:#999;}";
			$style .= ".fabrikDebugOutput pre{padding:5px;background:#efefef;color:#999;}";
			$style .= ".fabrikDebugHidden{display:none}";
			$document->addStyleDeclaration($style);
			$script = "window.addEvent('domready', function() {
			$$('.fabrikDebugOutputTitle').each(function(title) {
				title.addEvent('click', function(e) {
					title.getNext().toggleClass('fabrikDebugHidden');
				});
			});
			})";
			FabrikHelperHTML::addScriptDeclaration($script);
		}
	}

	/**
	 * create html for ajax folder browser (used by fileupload and image elements)
	 * @param array folders
	 * @param string start path
	 * @return string html snippet
	 */

	function folderAjaxSelect($folders, $path = 'images')
	{
		$str = array();
		$path = explode(DS, FabrikString::rtrimword($path, DS));
		$str[] = "<a href=\"#\" class=\"toggle\" title=\"".JText::_('BROWSE_FOLDERS')."\">";
		$str[] = "<img src=\"".COM_FABRIK_LIVESITE."/media/com_fabrik/images/control_play.png\" alt=\"".JText::_('BROWSE_FOLDERS')."\"/>";
		$str[] = "</a>";
		$str[] = "<div class=\"folderselect-container\">";
		$str[] = "<span class=\"breadcrumbs\"><a href=\"#\">" . JText::_('HOME') . "</a><span> / </span>";
		$i = 1;
		foreach ($path as $p) {
			$str[] = "<a href=\"#\" class=\"crumb".$i."\">" . $p . "</a><span> / </span>";
			$i ++;
		}
		$str[] = "</span>";
		$str[] = "<ul class=\"folderselect\">";
		settype($folders, 'array');
		foreach ($folders as $folder) {
			if (trim($folder) != '') {
				$str[] = "<li class=\"fileupload_folder\"><a href=\"#\">$folder</a></li>";
			}
		}
		//for html validation
		if (empty($folder)) {
			$str[] =  "<li></li>";
		}
		$str[] = "</ul></div>";
		return implode("\n", $str);
	}

	/**
	 * Add autocomplete JS code to head
	 * @param string $htmlid of element to turn into autocomplete
	 * @param int $elementid
	 * @param string $plugin
	 * @param array $opts (currently only takes 'onSelection')
	 */

	public function autoComplete($htmlid, $elementid, $plugin = 'fabrikfield', $opts = array())
	{
		FabrikHelperHTML::autoCompleteScript();
		$json = FabrikHelperHTML::autoCompletOptions($htmlid, $elementid, $plugin, $opts);
		$str = FastJSON::encode($json);
		FabrikHelperHTML::addScriptDeclaration(
		"window.addEvent('domready', function() { new FabAutocomplete('$htmlid', $str); });"
		);
	}

	/**
	 * Gets auto complete js options (needed separate from autoComplete as db js class needs these values for repeat group duplication)
	 * @param string $htmlid of element to turn into autocomplete
	 * @param int $elementid
	 * @param string $plugin
	 * @param array $opts (currently only takes 'onSelection')
	 * @return array autocomplete options (needed for elements so when duplicated we can create a new FabAutocomplete object
	 */

	public function autoCompletOptions($htmlid, $elementid, $plugin = 'fabrikfield', $opts = array())
	{
		$json = new stdClass();
		$json->url = COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&element_id='.$elementid.'&plugin='.$plugin.'&method=autocomplete_options';
		$c = JArrayHelper::getValue($opts, 'onSelection');
		if ($c != '') {
			$json->onSelections = $c;
		}
		$json->container = JArrayHelper::getValue($opts, 'container', 'fabrikElementContainer');
		return $json;
	}

	/**
	 *Load the autocomplete script once
	 */

	public function autoCompleteScript() {
		static $autocomplete;
		if (!isset($autocomplete)) {
			$autocomplete = true;
			FabrikHelperHTML::script('autocomplete.js', 'media/com_fabrik/js/');
		}
	}

	public function facebookGraphAPI($appid, $locale = 'en_US', $meta = array())
	{
		static $facebookgraphapi;
		if (!isset($facebookgraphapi)) {
			$facebookgraphapi = true;
			return "<div id=\"fb-root\"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({appId: '$appid', status: true, cookie: true,
             xfbml: true});
  };
  (function() {
    var e = document.createElement('script'); e.async = true;
    e.src = document.location.protocol +
      '//connect.facebook.net/$locale/all.js';
    document.getElementById('fb-root').appendChild(e);
  }());
</script>";
		}
		$document =& JFactory::getDocument();
		$data = array('custom'=>array());
		$typeFound = false;
		foreach ($meta as $k => $v) {
			$v = strip_tags($v);
			//og:type required
			if ($k == 'og:type') {
				$typeFound = true;
				if ($v == '') {
					$v = 'article';
				}
			}
			$data['custom'][] = "<meta property=\"$k\" content=\"$v\"/>";

		}
		if (!$typeFound) {
				$data['custom'][] = "<meta property=\"og:type\" content=\"article\"/>";
			}
		$document->setHeadData($data);
	}

	/**
	 * Returns basic info about browser agent being used
	 * NOTE - this is NOT an exhaustive test, and is only designed to identify the Major League browsers,
	 * specifically ie, ff, chrome, safari, opera and netscape.  Anything else comes back as 'unknown'.
	 *
	 * @param bool if true just return short browser name as string, if false return full array of detailed info
	 * @param string if specified, use this string as the user agent string instead of current HTTP_USER_AGENT
	 * @return mixed browser info
	 */
	public function getBrowser($just_name = true, $use_agent = null)
	{
		$u_agent = $use_agent ? $use_agent : $_SERVER['HTTP_USER_AGENT'];
		$bname = 'Unknown';
		$short_bname = 'unknown';
		$platform = 'Unknown';
		$version= "";

		//First get the platform?
		if (preg_match('/linux/i', $u_agent)) {
			$platform = 'linux';
		}
		elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		}
		elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}

		// Next get the name of the useragent yes seperately and for good reason
		// (because the agent string will often contain multiple matches, for instance Chrome also says Safari)
		// (so be CAREFUL if you add extra tests!)
		if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
		{
			$bname = 'Internet Explorer';
			$short_bname = 'ie';
			$ub = "MSIE";
		}
		elseif(preg_match('/Firefox/i',$u_agent))
		{
			$bname = 'Mozilla Firefox';
			$short_bname = 'firefox';
			$ub = "Firefox";
		}
		elseif(preg_match('/Chrome/i',$u_agent))
		{
			$bname = 'Google Chrome';
			$short_bname = 'chrome';
			$ub = "Chrome";
		}
		elseif(preg_match('/Safari/i',$u_agent))
		{
			$bname = 'Apple Safari';
			$short_bname = 'safari';
			$ub = "Safari";
		}
		elseif(preg_match('/Opera/i',$u_agent))
		{
			$short_bname = 'opera';
			$ub = "Opera";
		}
		elseif(preg_match('/Netscape/i',$u_agent))
		{
			$bname = 'Netscape';
			$short_bname = 'netscape';
			$ub = "Netscape";
		}

		if ($just_name) {
			return $short_bname;
		}

		// finally get the correct version number
		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) .
	    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}

		// see how many we have
		$i = count($matches['browser']);
		if ($i != 1) {
			//we will have two since we are not using 'other' argument yet
			//see if version is before or after the name
			if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
				$version= $matches['version'][0];
			}
			else {
				$version= $matches['version'][1];
			}
		}
		else {
			$version= $matches['version'][0];
		}

		// check if we have a number
		if ($version==null || $version=="") {$version="?";}

		return array(
                'userAgent' => $u_agent,
                'name'      => $bname,
        		'short_name' => $short_bname,
                'version'   => $version,
                'platform'  => $platform,
                'pattern'    => $pattern
		);
	}

	/**
	 * Workaround for bug in IE / Mootools, where 'domready' doesn't work in iframes (it fires before DOM is loaded)
	 * This function will return 'domready' unless browser being used is IE and iframe=1 qs arg is set
	 *
	 * @return string event to use
	 */

	public function useLoadEvent() {
		if (JRequest::getVar('iframe', '') == '1') {
			if (FabrikHelperHTML::getBrowser() == 'ie') {
				return 'load';
			}
		}
		return 'domready';
	}

}
?>
