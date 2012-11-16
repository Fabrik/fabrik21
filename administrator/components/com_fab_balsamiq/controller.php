<?php
/**
 * @version		$Id: controller.php 18615 2010-08-24 02:40:15Z ian $
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * Balsamiq master display controller.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_fab_balsamiq
 *
 */
class Fab_balsamiqController extends JController

{
	/** @var object default fabrik connection model */
	protected $connection = null;

	/** @var string db table name to make */
	protected $tbl = null;

	protected $typePrefix = 'com.balsamiq.mockups::';

	/** @var array maps balsamiq control types to fabrik element plugin types */
	protected $typeMap = array(
		'subtitle' => 'fabrikfield',
		'label' => 'fabrikfield',
		'textinput' => 'fabrikfield',
		'title' => 'fabrikfield',
		'paragraph' => 'fabriktextarea',
		'button' => 'fabrikbutton',
		'link' => 'fabriklink',
		'radiobutton' => 'fabrikradiobutton',
		'radiobuttongroup' => 'fabrikradiobutton',
		'image' => 'fabrikfileupload',
		'checkboxgroup' => 'fabrikcheckbox',
		'checkbox' => 'fabrikcheckbox',
		'colorpicker' => 'fabrikcolourpicker',
		'combobox' => 'fabrikdropdown',
		'map' => 'fabrikgooglemap',
		'textarea' => 'fabriktextarea',
		'datechooser' => 'fabrikdate',
		'calendar' => 'fabrikdate',
		'hslider' => 'fabrikslider',
		'numericstepper' => 'fabrikdropdown',
		'switch' => 'fabrikradiobutton',
		'videoplayer' => 'fabrikyoutube'
		);

		/** @var bool is search all enabled */
		protected $searchMode = null;

		protected $groups = array();

		/**
		 * Method to display a view.
		 *
		 * @param	boolean			If true, the view output will be cached
		 * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
		 *
		 * @return	JController		This object to support chaining.
		 * @since	1.5
		 */

		public function display($cachable = false, $urlparams = false)
		{
			require_once(JPATH_COMPONENT.DS.'views'.DS.'fab_balsamiq'.DS.'view.html.php');
			$params = new JParameter('', JPATH_COMPONENT.DS.'models'.DS.'import.xml');
			fab_balsamiqViewfab_balsamiq::display($params);
		}

		/**
		 * process the uploaded Balsamiq XML file
		 */

		public function save()
		{
			$this->includeFabrik();
			$post = JRequest::get('post');
			$params = $post['params'];

			$files = $_FILES['params']['tmp_name'];

			$allElements = array();
			$tableElements = array();
			$formElements = array();
			$detailsElements = array();
			foreach ($files as $tmplType => $file) {
				$bxml = JArrayHelper::getValue($params, $tmplType.'_txt');
				if (trim($bxml) == '') {
					if ($file == '') {
						continue;
					}
					$bxml = JFile::read($file);
				}
				$bxml = '<xml>'.$bxml.'</xml>';
				$imageMoved = false;
				$xmlDoc = & JFactory::getXMLParser('DOM', array('lite'=>false));
				$xmlDoc->resolveErrors(true);
				$ok =	$xmlDoc->parseXML($bxml, false);
				if ($ok) {
					switch ($tmplType) {
						case 'table_upload':
							$tableElements = $this->saveTable($xmlDoc);
							break;
						case 'form_upload':
							$formElements = $this->saveForm($xmlDoc, 'form');
							break;
						case 'details_upload':
							$detailsElements = $this->saveForm($xmlDoc, 'details');
							break;
					}
				} else {
					JError::raiseNotice(500, 'COM_FAB_BALSAMIQ_COULD_NOT_READ_XML_FILE');
					return;
				}
			}
			$allElements = $tableElements;

			$rest = array($formElements, $detailsElements);
			foreach ($rest as $els) {
				foreach ($els as $k => $v) {
					$allkeys = array_keys($allElements);
					if (array_key_exists($k, $allElements) === false) {
						$allElements[$k] = $v;
					} else {
						foreach ($v as $kk => $vv) {
							if (!array_key_exists($kk, ($allElements[$k]))) {
								$allElements[$k][$kk] = $vv;
							}
						}
					}
				}
			}
			// if a form template is specified then use that to create t
			$session =& JFactory::getSession();
			$session->clear('com_fabrik.list.create.groupmap');
			$session->clear('com_fabrik.list.create.elementmap');

			$session->set('com_fabrik.list.create.elementmap', $allElements);
			$session->set('com_fabrik.list.create.groupmap', $this->groups);

			$tmplName = $params['tmpl_name'];
			$this->prepareFabrikForm($params, $allElements, $tmplName);
			$model = $this->createFabrikTable($params, $allElements, $tmplName);
			$this->updateMetaSettings($model, $allElements);
			$session->clear('com_fabrik.list.create.groupmap');
			$session->clear('com_fabrik.list.create.elementmap');
			$this->setRedirect('index.php?option=com_fab_balsamiq', JText::_('COM_FAB_BALSAMIQ_EXPORTED'));
		}

		/**
		 * process the Balsamiq XML file as a Fabrik form
		 * @param object $xmlDoc DOMIT object
		 */

		protected function saveForm($xmlDoc, $view = 'form')
		{
			$post =& JRequest::get('post');
			$params =& $post['params'];
			$controls =& $xmlDoc->getElementsByTagName('control');
			$clength =$controls->getLength();
			$fieldsets = $xmlDoc->getElementsByAttribute('controlTypeID', 'com.balsamiq.mockups::FieldSet');

			$buttons = $xmlDoc->selectNodes("//*[@controlTypeID='com.balsamiq.mockups::Button']");
			$blength = $buttons->getLength();
			$fbuttons = array();

			$allowedButtons = array(
				'fabrik_save' => 'Submit',
				'fabrik_copy' => 'Copy',
				'fabrik_go_back' => 'Goback',
				'fabrik_reset' => 'Reset',
				'fabrik_apply' => 'apply',
			'fabrik_delete' => 'delete');

			for ($i =0; $i < $blength; $i ++) {
				$button = $buttons->item($i);
				$name = strip_tags((string)$button->getElementsByPath('controlProperties/customID')->toString());
				$label = urldecode(strip_tags((string)$button->getElementsByPath('controlProperties/text')->toString()));
				if (in_array($name, array_keys($allowedButtons))) {
					switch ($name) {
						case 'fabrik_reset':
							$params['reset_button'] = 1;
							$params['reset_button_label'] = $label;
							break;
						case 'fabrik_go_back':
							$params['goback_button'] = 1;
							$params['goback_button_label'] = $label;
							break;
						case 'fabrik_copy':
							$params['copy_button'] = 1;
							$params['copy_button_label'] = $label;
							break;
						case 'fabrik_apply':
							$params['apply_button'] = 1;
							$params['apply_button_label'] = $label;
							break;
						case 'fabrik_save':
							JRequest::setVar('submit_button_label', $label);
							break;
						case 'fabrik_delete':
							$params['delete_button'] = 1;
							$params['delete_button_label'] = $label;
							//allow all to delete if this button is in the form
							$params['allow_delete'] = 0;
							break;
					}

					$fbuttons[] = array(
					'text' => $label,
						'name' => $name,
					'x' => $button->getAttribute('x'),
					'y' => $button->getAttribute('y'),
					'h' => $button->getAttribute('h'),
					'w' => $button->getAttribute('w')
					);
				}
			}
			$_POST['params'] = $params;

			$flength = $fieldsets->getLength();
			for ($i =0; $i < $flength; $i ++) {
				$fieldset = $fieldsets->item($i);

				$label = strip_tags((string)$fieldset->getElementsByPath('controlProperties/text')->toString());
				$name = strip_tags((string)$fieldset->getElementsByPath('controlProperties/customID')->toString());
				if ($name == '') {
					$name = $label;
				}
				$fieldSetStyle = $this->parseControlProperties($fieldset);
				$this->groups[] = array(
					'label' => $label,
					'name' => $name,
					'x' => $fieldset->getAttribute('x'),
					'y' => $fieldset->getAttribute('y'),
					'h' => $fieldset->getAttribute('h'),
					'w' => $fieldset->getAttribute('w'),
					'style' => $fieldSetStyle
				);
			}
			$html = array();
			list($elements, $minX, $minY, $maxH) = $this->parseElements($xmlDoc, 'form');

			foreach ($elements as &$element) {
				if (!array_key_exists('coords', $element)) {
					continue; //testing not sure this is right
				}
				$coords = $element['coords'];
				$ex = $coords['x'];
				$ey = $coords['y'];
				$ew = $coords['w'];
				$eh = $coords['h'];
				foreach ($this->groups as $gid => $g) {
					if ($ex >= $g['x'] && ($ex + $ew) <= ($g['x'] + $g['w']) && $ey >= $g['y'] && ($ey + $eh) <= ($g['y'] + $g['h'])) {
						$element['groupid'] = $gid;
						//position element relative to group/fieldset
						$element['coords']['x'] = $ex - $g['x'];
						$element['coords']['y'] = $ey - $g['y'];
						//and the label coords
						if (array_key_exists('labelcoords', $element)) {
							$element['labelcoords']['x'] = $element['labelcoords']['x'] - $g['x'];
							$element['labelcoords']['y'] = $element['labelcoords']['y'] - $g['y'];
						}
						continue;
					}
				}
			}
			//ensure pk field is in first group
			$elements[$this->tbl.'___id']['groupid'] = array_shift(array_keys($this->groups));

			$styles = array();

			$formNode = $this->getFormNode($xmlDoc);

			if ($formNode == false) {
				$relativeXOffest = 0;
				$relativeYOffset = 0;
				$formStyle = '';
				$formDims = array('w'=> 0, 'h' => 0);
			} else {
				$formStyle = $this->parseControlProperties($formNode);
				$coords = $this->getCoords($formNode);
				$relativeXOffest = $coords['x'];
				$relativeYOffset = $coords['y'];
				$formDims = array('w'=> $coords['w'], 'h' =>$coords['h']);

			}

			foreach ($this->groups as $group) {
				$gkey = "fabrikGroup".preg_replace("/[^A-Za-z0-9]/", "", $group['name']);
				/*$y = $group['y'];
				 $x = $group['x'];*/
				$w = $group['w'];
				$h = $group['h'];
				$x = $group['x'] - $relativeXOffest;
				$y = $group['y'] - $relativeYOffset;
				if ($formNode == false) {
					$formDims['h'] += $h;
					if ($w > $formDims['w']) {
						$formDims['w'] = $w;
					}
				}
				$styles[] = ".{$gkey}{ top:{$y}px;left:{$x}px;width:{$w}px;height:{$h}px;".$group['style']." }";
			}
			$styles[] = ".fabrikForm{ width:{$formDims['w']}px;height:{$formDims['h']}px;$formStyle }";

			foreach ($elements as $key => &$element) {
				if (!array_key_exists('coords', $element)) {
					continue; //testing not sure this is right
				}
				if (array_key_exists('labelcoords', $element)) {
					$ly = $element['labelcoords']['y'];
					$lx = $element['labelcoords']['x'];
					$lw = $element['labelcoords']['w'];
					$lh = $element['labelcoords']['h'];
					$labelStyle = JArrayHelper::getValue($element, 'labelstyle');
					$styles[] = "#fb_el_{$key}_text{ top:{$ly}px;left:{$lx}px;width:{$lw}px;height:{$lh}px;$labelStyle }";
					$styles[] = "#fb_el_{$key}_ro_text{ top:{$ly}px;left:{$lx}px;width:{$lw}px;height:{$lh}px;$labelStyle }";
				}
				$y = $element['coords']['y'];
				$x = $element['coords']['x'];
				$w = $element['coords']['w'];
				$h = $element['coords']['h'];
				$styles[] = ".{$key}{ top:{$y}px;left:{$x}px;width:{$w}px;height:{$h}px; }";
				$styles[] = ".{$key}_ro{ top:{$y}px;left:{$x}px;width:{$w}px;height:{$h}px; }";
			}

			//fabrik buttons
			foreach ($fbuttons as $fbutton) {
				$y = $fbutton['y'];
				$x = $fbutton['x'];
				$w = $fbutton['w'];
				$h = $fbutton['h'];
				$styles[] = "input[name=".$allowedButtons[$fbutton['name']]."]{ position:absolute;top:{$y}px;left:{$x}px;width:{$w}px;height:{$h}px; }";
			}
			//copy and write out tmpl
			$tmplName = $params['tmpl_name'];

			$tmpLoc = JPATH_SITE.DS.'tmp'.DS.$tmplName;

			if ($tmpLoc == '') {
				JError::raiseNotice(500, 'please supply a template name');
				return;
			}
			if (!JFile::exists(JPATH_COMPONENT.DS.'skeletons'.DS.'form'.DS.'default'.DS.'template.css')) {
				JError::raiseError(500, 'Form skeleton css file not found: ' . JPATH_COMPONENT.DS.'skeletons'.DS.'form'.DS.'default'.DS.'template.css');
			}
			$cssTmpl = JFile::read(JPATH_COMPONENT.DS.'skeletons'.DS.'form'.DS.'default'.DS.'template.css');
			$cssTmpl = str_replace('{css}', implode("\n", $styles), $cssTmpl);

			if (JFolder::exists($tmpLoc)) {
				JFolder::delete($tmpLoc);
			}
			JFolder::copy(JPATH_COMPONENT.DS.'skeletons'.DS.'form'.DS.'default', $tmpLoc);

			JFile::write($tmpLoc.DS.'template.css', $cssTmpl);

			if ($view == 'details') {
				$tmplName .= '_details';
			}
			$to = JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'views'.DS.'form'.DS.'tmpl'.DS.$tmplName;
			if (JFolder::exists($to)) {
				JFolder::delete($to);
			}
			JFolder::move($tmpLoc, $to);
			return $elements;
		}

		/**
		 * prepare session and post data for form save
		 * @param array $params
		 * @param array $elements
		 * @param string $tmplName
		 * @param array groups
		 */

		protected function prepareFabrikForm($params, $elements, $tmplName)
		{
			if ($params['create_fabrik'] == 1) {
				$names = array_keys($elements);
				if (empty($names)) {
					return;
				}
				$connection = $this->getDefaultCnn();

				//set the plugin types so we can use those in fabriks table model::_createLinkedElements()
				// do the same for group data

				JRequest::setVar('label', $this->tbl);
				JRequest::setVar('connection_id', $connection->_id);
				JRequest::setVar('db_table_name', $this->tbl);
				JRequest::setVar('state', 1);
				JRequest::setVar('template', $tmplName);
				JRequest::setVar('view_only_template', $tmplName.'_details');
				JRequest::setVar('form_template', $tmplName);
				//$model->save();
			}
		}

		protected function parseElements($xmlDoc, $view = 'form')
		{
			$minX = null;
			$minY = null;
			$maxH = 0;
			$controls =& $xmlDoc->getElementsByTagName('control');
			$length =$controls->getLength();
			for ($i =0; $i < $length; $i++) {
				$control = $controls->item($i);

				$type = $control->getAttribute('controlTypeID');
				$type = strtolower(str_replace($this->typePrefix, '', $type));
				if (array_key_exists($type, $this->typeMap)) {


					$id = strip_tags((string)$control->getElementsByPath('controlProperties/customID')->toString());

					if (!strstr($id, '___') && substr($id, 0, 7) !== 'fabrik_') {
						JError::raiseNotice(500, 'skipped control '. $id . ' as it was not in the correct Fabrik full name format');
					}
					if ($id == '') {
						JError::raiseNotice(500, "a control of type '$type' was exported without a custom control id, no template placeholder created for this element");
					}
					//id had to be set and not a resevered fabrik_XXXX var
					if (substr($id, 0, 7) !== 'fabrik_' || $id === 'fabrik_row_delete') {
						if ($id === 'fabrik_row_delete') {
							$id = 'fabrik_delete';
						}
						$x = $control->getAttribute('x');

						if ($x < $minX || is_null($minX)) {
							$minX = $x;
						}

						$y = $control->getAttribute('y');
						if ($y < $minY || is_null($minY)) {
							$minY = $y;
						}

						$w = $control->getAttribute('w') == -1 ? $control->getAttribute('measuredW') : $control->getAttribute('w');
						$h = $control->getAttribute('h') == -1 ? $control->getAttribute('measuredH') : $control->getAttribute('h');

						if ($h > $maxH) {
							$maxH = $h;
						}
						$coords = array(
												'x' => $x,
												'y' => $y,
												'w' => $w,
												'h' => $h
						);
						$style = $this->parseControlProperties($control);
					
						//'label' => $label,
						$elements[$id] = array(
						'plugin' => $this->typeMap[$type],
						'coords' => $coords,
						'params' => array(),
						'style' => $style);

						if (substr($id, -2) == '/L') {
							$label = $this->getNodeText($control);
							$elements[$id]['label'] = $label;
						}
						$method = 'XML'.$type;
						//custom method to parse $control
						if (method_exists($this, $method)) {
							$this->$method($control, $elements[$id]);
						}
					} else {
						//JError::raiseNotice(500, "a control of type '$type', id '$id', was exported without a custom control id, no template placeholder created for this element");
					}
				} else {
					if (!in_array($type, array('fieldset', 'canvas', 'searchbox'))) {
						// fieldsets are special in form tmpls as they are converted to fabrik groups
						JError::raiseNotice(500, "could not match control type: $type, no template placeholder created for this element");
					}
				}
			}
			
			$names = array_keys($elements);
			if (empty($names)) {
				return array($elements, $minX, $minY, $maxH);
			}
			$this->tbl = array_shift(explode("___", $names[0]));
			//add in pk element
			$pluginManager = JModel::getInstance('pluginmanager', 'FabrikModel');
			if (!array_key_exists($this->tbl.'___id', $elements)) {
				$a = array();
				$a[$this->tbl.'___id'] = array(
						'plugin' => 'fabrikinternalid',
						'label' => 'id',
						'coords' => array('x'=>0,'y'=>0, 'w'=>0, 'h'=>0),
						'style' => 'display:none;'
						);
						$elements = array_merge($a, $elements);
			} else {
				//ensure it is a fabrikinternalid element
				$elements[$this->tbl.'___id']['plugin'] = 'fabrikinternalid';
			}

			//merge label elements
			foreach ($elements as $key => &$element) {
				if (substr($key, -2) == '/L') {
					$label = urldecode($element['label']);
					$fkey = substr($key, 0, strlen($key) - 2);
					if ($view == 'form') {
						$elements[$fkey]['label'] = $label;
					} else {
						$elements[$fkey]['params']['element_alt_table_heading'] = $label;
					}
					$elements[$fkey]['labelcoords'] = $elements[$key]['coords'];
					$elements[$fkey]['labelstyle']  = $elements[$key]['style'];
					unset($elements[$key]);
				}
			}

			return array($elements, $minX, $minY, $maxH);
		}

		protected function getNodeText($node)
		{
			return strip_tags((string)$node->getElementsByPath('controlProperties/text')->toString());
		}

		/**
		 * Import table xml file
		 * @param object $xmlDoc
		 */

		protected function saveTable($xmlDoc)
		{
			$post = JRequest::get('post');
			$params = $post['params'];
			$filterPlaceholders = array();
			$filterStyles = array();
			list($elements, $minX, $minY, $maxH) = $this->parseElements($xmlDoc, 'table');

			//merge filter elements
			foreach ($elements as $key => &$element) {
				if (substr($key, -2) == '/F') {
					$fkey = substr($key, 0, strlen($key) - 2);

					$elements[$fkey]['filter_type'] = $element['plugin'] = 'fabrikdropdown' ? 'dropdown' : 'field';
					$filterPlaceholders[] = "'$fkey'";
					$coords = $elements[$key]['coords'];
					$filterStyles[] = ".filter_".$fkey."{position:absolute;top:{$coords['y']}px;left:{$coords['x']}px;width:{$coords['w']}px;height:{$coords['h']}px;}";
					unset($elements[$key]);
				}


				if (substr($key, -3) == '/FL') {
					$fkey = substr($key, 0, strlen($key) - 3);
					$elements[$fkey]['filter_label'] = '';
					$coords = $elements[$key]['coords'];
					$filterStyles[] = ".filter_".$fkey."_label{position:absolute;top:{$coords['y']}px;left:{$coords['x']}px;width:{$coords['w']}px;height:{$coords['h']}px;}";
					unset($elements[$key]);
				}
			}

			//get custom filter tags
			$controls =& $xmlDoc->getElementsByTagName('control');
			$filterSpecials = array('fabrik_search', 'fabrik_clear', 'fabrik_search_all');
			$length =$controls->getLength();
			$this->searchMode = 0;
			for ($i =0; $i < $length; $i++) {
				$control = $controls->item($i);
				$id = strip_tags((string)$control->getElementsByPath('controlProperties/customID')->toString());
				if ($id == 'fabrik_search') {
					$this->searchMode = 1;
				}
				if (in_array($id, $filterSpecials)){
					$coords = $this->getCoords($control);
					$filterStyles[] = ".$id{position:absolute;top:{$coords['y']}px;left:{$coords['x']}px;width:{$coords['w']}px;height:{$coords['h']}px;}";
				}
			}

			//button locations
			$buttons = array('fabrik_csv_export' => 'csvExportButton', 'fabrik_csv_import' => 'csvImportButton', 'fabrik_add' => 'addbutton', 'fabrik_delete_all' => 'fabrik_delete');
			foreach ($buttons as $placeholder => $class) {
				$node = $this->getControllerNode($xmlDoc, 'controlProperties/customID', $placeholder);
				if ($node !== false) {
					$coords = $this->getCoords($node);
					$filterStyles[] = ".$class{position:absolute;display:block;top:{$coords['y']}px;left:{$coords['x']}px;width:{$coords['w']}px;heght:{$coords['h']}px}";
				}
			}

			$tmplName = $params['tmpl_name'];
			$html = array();
			$styles = array();
			$lstyles = array();
			$styles[] = $this->getHeaderStyle($xmlDoc);
			$styles[] = $this->getTableTitleStyle($xmlDoc);

			// as the row coords are relative to the containing div we want to remove the smallest x/y offsets from each
			// elements coordinates.
			$row = $this->getRowNode($xmlDoc);
			if ($row == false) {
				$relativeXOffest = 0;
				$relativeYOffset = 0;
			} else {
				$coords = $this->getCoords($row);
				if ($coords['h'] > $maxH) {
					$maxH = $coords['h'];
				}
				$relativeXOffest = $coords['x'];
				$minX = 0;
				$minY =0;
				$relativeYOffset = $coords['y'];

			}

			foreach ($elements as $key => $el) {
				if (!array_key_exists('coords', $el)) {
					continue; //testing not sure this is right
				}
				$x = $el['coords']['x'] - $minX - $relativeXOffest;
				$y = $el['coords']['y'] - $minY - $relativeYOffset;
				$w = $el['coords']['w'];
				$h = $el['coords']['h'];

				$elStyle = JArrayHelper::getValue($el, 'style');
				$styles[] = ".fabrik_row___$key{ position:absolute;top:{$y}px;left:{$x}px;width:{$w}px;height:{$h}px;$elStyle}";
				$html[] = "\t<div <?php echo @\$this->cellClass['".$key."']; ?>>";
				if (isset($el['tag']) && is_array($el['tag'])) {
					$html[] = "\t\t".$el['tag'][0];
				}
				$html[] = "\t\t".'<?php echo @$this->_row->data->'.$key.';?>';
				if (isset($el['tag']) && is_array($el['tag'])) {
					$html[] = "\t\t".$el['tag'][1];
					$styles[] =  ".fabrik_row___$key ".str_replace(array("<", ">"), '', $el['tag'][0]) . "{padding:0;margin:0}";
				}

				$html[] = "\t</div>";
				//add labels
				if (array_key_exists('labelcoords', $el)) {
					$label = JArrayHelper::getValue($el['params'], 'element_alt_table_heading', JArrayHelper::getValue($el, 'label'));
					$lcoords = JArrayHelper::getValue($el, 'labelcoords');
					$x = $lcoords['x']- $minX - $relativeXOffest;;
					$y = $lcoords['y']- $minY - $relativeYOffset;
					$w = $lcoords['w'];
					$h = $lcoords['h'];
					$labelstyle = JArrayHelper::getValue($el, 'labelstyle');
					$html[] = "<div class=\"{$key}_blabel\">$label</div>";
					$lstyles[] = ".{$key}_blabel{ position:absolute;top:{$y}px;left:{$x}px;width:{$w}px;height:{$h}px;$labelstyle}";
				}
			}
			// $$$ rob 15/09/2011 create a default group otherwise its not added into fabrik
			$this->groups[] = array(
				'label' => 'details',
				'name' => $tmplName
			);
			
			$rowStyle =  ($row != false) ? $this->parseControlProperties($row) : '';
			$rowStyle .= 'height:'.$maxH.'px;';
			$rowStyle .= ($row != false) ? "width:".$row->getAttribute('w')."px;" : '';
			$styles[] = ".oddRow0{".$rowStyle."}";
			$styles[] = ".oddRow1{".$rowStyle."}";

			$styles = array_merge($styles, $filterStyles, $lstyles);

			$tmpLoc = JPATH_SITE.DS.'tmp'.DS.$tmplName;

			if (!JFile::exists(JPATH_COMPONENT.DS.'skeletons'.DS.'table'.DS.'default'.DS.'template.css')) {
				JError::raiseError(500, 'Table skeleton css file not found: ' . JPATH_COMPONENT.DS.'skeletons'.DS.'form'.DS.'default'.DS.'template.css');
			}
			$cssTmpl = JFile::read(JPATH_COMPONENT.DS.'skeletons'.DS.'table'.DS.'default'.DS.'template.css');
			$rowTmpl = JFile::read(JPATH_COMPONENT.DS.'skeletons'.DS.'table'.DS.'default'.DS.'default_row.php');
			$filterTmpl = JFile::read(JPATH_COMPONENT.DS.'skeletons'.DS.'table'.DS.'default'.DS.'default_filter.php');

			$rowTmpl = str_replace('{content}', implode("\n", $html), $rowTmpl);
			$cssTmpl = str_replace('{cells}', implode("\n", $styles), $cssTmpl);
			$filterTmpl = str_replace('{filters}', implode(",", $filterPlaceholders), $filterTmpl);

			if (JFolder::exists($tmpLoc)) {
				JFolder::delete($tmpLoc);
			}
			JFolder::copy(JPATH_COMPONENT.DS.'skeletons'.DS.'table'.DS.'default', $tmpLoc);

			JFile::write($tmpLoc.DS.'default_row.php', $rowTmpl);
			JFile::write($tmpLoc.DS.'default_filter.php', $filterTmpl);
			JFile::write($tmpLoc.DS.'template.css', $cssTmpl);

			$to = JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'views'.DS.'table'.DS.'tmpl'.DS.$tmplName;
			if (JFolder::exists($to)) {
				JFolder::delete($to);
			}
			JFolder::move($tmpLoc, $to);
			return $elements;
		}

		/**
		 * figure out the styling for a give control object
		 * @param unknown_type $row
		 * @return string css formatting
		 */

		protected function parseControlProperties($row)
		{
			$rowStyle = '';
			if (!is_object($row)) {
				return '';
			}
			//for img showBorder options
			$borderStyle = strip_tags((string)$row->getElementsByPath('controlProperties/borderStyle')->toString());
			$rowBorder = strip_tags((string)$row->getElementsByPath('controlProperties/borderColor')->toString());
			if ($rowBorder !== '' || $borderStyle !== '') {
				if ($rowBorder == '') {
					$rowBorder = '0';
				}
				$borderType = 'solid';
				if ($borderStyle == 'none') {
					$borderType = 'none';
				}
				if ($borderStyle == 'roundedSolid' || $borderStyle == 'roundedDotted') {
					$rowStyle .= "border-radius: 15px;-moz-border-radius: 15px;";
				}
				if ($borderStyle == 'roundedDotted') {
					$borderType = 'dotted';
				}
				$rowBorder = $this->toColor($rowBorder);
				$rowStyle .= "border:1px $borderType ".$rowBorder.";";
			}
			$opacity = strip_tags((string)$row->getElementsByPath('controlProperties/backgroundAlpha')->toString());

			$type = $row->getAttribute('controlTypeID');
			$cssProperty = in_array($type, array('com.balsamiq.mockups::Canvas', 'com.balsamiq.mockups::FieldSet')) ? 'background' : 'color';

			$rowBg = strip_tags((string)$row->getElementsByPath('controlProperties/color')->toString());
			if ($rowBg !== '') {
				if ($opacity !== '') {
					$hex = $this->toColor($rowBg);
					$rgb = $this->hex2RGB($hex);
					$rgba = $rgb;
					$rgba[] = $opacity;
					$hex = "rgba(".implode(",", $rgba).")";

					$ieOpacity = dechex($opacity * 255);
					$rgb[] = $ieOpacity;
					$rgba = implode('', $rgb);
					$rowStyle."filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#$rgba, endColorstr=#$rgba);";

				} else {
					$hex = $this->toColor($rowBg);
				}
				$rowStyle .= "$cssProperty:".$hex.";";
			}


			/*if ($opacity !== '') {
				$ieopacity = (float)$opacity * 100;
				$rowStyle .= "opacity:$opacity;filter:alpha(opacity=".$ieopacity.")";
				}*/

			$italic = strip_tags((string)$row->getElementsByPath('controlProperties/italic')->toString());
			if ($italic == 'true') {
				$rowStyle .= "font-style:italic;";
			}
			$bold = strip_tags((string)$row->getElementsByPath('controlProperties/bold')->toString());
			if ($bold == 'true') {
				$rowStyle .= "font-weight:bold;";
			}

			$fontSize = strip_tags((string)$row->getElementsByPath('controlProperties/size')->toString());
			if ($fontSize !== '') {
				$rowStyle .= "font-size:{$fontSize}px;";
			}
			$underline = strip_tags((string)$row->getElementsByPath('controlProperties/underline')->toString());
			if ($underline == 'true') {
				$rowStyle .= "text-decoration:underline;";
			}
			$align = strip_tags((string)$row->getElementsByPath('controlProperties/align')->toString());
			if ($align !== '') {
				$rowStyle .= "text-align:$align !important;";
			}
			return $rowStyle;
		}

		function toColor($n)
		{
			return("#".substr("000000".dechex($n),-6));
		}

		function hex2RGB($hexStr, $returnAsString = false, $seperator = ',')
		{
			$hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
			$rgbArray = array();
			if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
				$colorVal = hexdec($hexStr);
				$rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
				$rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
				$rgbArray['blue'] = 0xFF & $colorVal;
			} elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
				$rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
				$rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
				$rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
			} else {
				return false; //Invalid hex color code
			}
			return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
		}

		protected function updateMetaSettings($model, $tmpldata = array())
		{
			$db =& JFactory::getDBO();
			if ($model === false) {

				$db->setQuery("select id from #__fabrik_tables where db_table_name = ".$db->Quote($this->tbl));
				$tid = $db->loadResult();
				$model = JModel::getInstance('table', 'FabrikModel');
				$model->setId($tid);
			}
			$elements = $model->getForm()->getFormGroups();

			foreach ($tmpldata as $fullElName => $data) {
				$shortName = array_pop(explode("___", ($fullElName)));
				$filterType = JArrayHelper::getValue($data, 'filter_type');
				foreach ($elements as $el) {
					if ($el->name == $shortName) {
						$db->setQuery("update #__elements set filter_type = " . $db->Quote($filterType) . " where id = ".(int)$el->id);
						$db->Query();
					}
				}
			}
			//set the search type
			$table = $model->getTable();
			$attribs = $table->attribs;
			$params = new fabrikParams($attribs, JPATH_COMPONENT.DS.'xml'.DS.'table.xml');
			$aparams = $params->toArray();
			$aparams['search-mode'] = $this->searchMode ? 'OR' : 'AND';
			$params->empty_data_msg = 'No records found';
			$table->attribs = $params->updateAttribsFromParams($aparams);
			$table->store();
		}

		protected function getTableTitleStyle($xmlDoc)
		{
			$header = $xmlDoc->selectNodes("//*[@controlTypeID='com.balsamiq.mockups::Title']", 0);
			if ($header->getLength() == 0) {
				return;
			}
			$header = $header->item(0);
			$coords = $this->getCoords($header);
			return ".fabrikTableHeading{position:absolute;top:{$coords['y']}px;left:{$coords['x']}px;width:{$coords['w']}px;height:{$coords['h']}px; }";
		}

		/**
		 * in the tbl tmpl get the row container canvas, used to work out offsets
		 * @param object $xmlDoc
		 */

		protected function getRowNode($xmlDoc)
		{
			return $this->getCanvasControl($xmlDoc, 'fabrik_row');
		}

		protected function getFormNode($xmlDoc)
		{
			return $this->getCanvasControl($xmlDoc, 'fabrik_form');
		}

		/**
		 * get a canvas element with a specifed id
		 * @param object $xmlDoc
		 * @param string $customId
		 */

		protected function getCanvasControl($xmlDoc, $customId)
		{
			$canvases = $xmlDoc->selectNodes("//*[@controlTypeID='com.balsamiq.mockups::Canvas']", 0);
			if ($canvases->getLength() == 0) {
				return false;
			}
			for ($i=0; $i < $canvases->getLength(); $i++) {
				$canvas = $canvases->item($i);
				$id = strip_tags((string)$canvas->getElementsByPath('controlProperties/customID')->toString());
				if ($id == $customId) {
					return $canvas;
				}
			}
			return false;
		}

		protected function getControllerNode($xmlDoc, $node = 'controlProperties/customID', $value)
		{
			$controls =& $xmlDoc->getElementsByTagName('control');
			$clength =$controls->getLength();
			for ($i = 0; $i < $clength; $i++) {
				$control = $controls->item($i);
				$tmpVal = strip_tags((string)$control->getElementsByPath($node)->toString());
				if ($tmpVal == $value){
					return $control;
				}
			}
			return false;
		}

		protected function getHeaderStyle($xmlDoc)
		{
			$canvases = $xmlDoc->selectNodes("//*[@controlTypeID='com.balsamiq.mockups::Canvas']", 0);
			if ($canvases->getLength() == 0) {
				return;
			}
			for ($i=0; $i < $canvases->getLength(); $i++) {
				$canvas = $canvases->item($i);
				$id = strip_tags((string)$canvas->getElementsByPath('controlProperties/customID')->toString());
				if ($id == 'fabrik_header') {
					$coords = $this->getCoords($canvas);
					return ".fabrik_header{margin-top:{$coords['y']}px;margin-left:{$coords['x']}px;width:{$coords['w']}px;height:{$coords['h']}px; }";
				}
			}
		}

		protected function getCoords($node)
		{
			$x = $node->getAttribute('x');
			$y = $node->getAttribute('y');
			$w = $node->getAttribute('w') == -1 ? $node->getAttribute('measuredW') : $node->getAttribute('w');
			$h = $node->getAttribute('h') == -1 ? $node->getAttribute('measuredH') : $node->getAttribute('h');
			return array('x'=>$x, 'y'=> $y, 'w'=> $w, 'h'=>$h);
		}

		private function includeFabrik()
		{
			require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php');
			require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'fabrik.php');
			require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'parent.php');
			require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'parent.php');
			require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'params.php');
			require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'html.php');
			JModel::addIncludePath(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models');
			JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');

			//set some default params for the table
			$params = JArrayHelper::getValue($_POST, 'params');
			$params['allow_add'] =0;
			$params['access'] = 0;
			$params['allow_view_details'] = 0;
			$params['allow_delete'] = 20;
			$params['allow_drop'] = 26;

			$_POST['params'] = $params;

			// Load common language files
			$lang =& JFactory::getLanguage();
			$lang->load('com_fabrik');
			$lang->load('com_fabrik', JPATH_ROOT);
		}

		/**
		 * Load fabrik's default connection
		 */

		protected function getDefaultCnn()
		{
			if (!isset($this->connection)) {
				$this->connection = JModel::getInstance('connection', 'FabrikModel');
				$this->connection->_id = -1;
				$this->connection->getConnection();
			}
			return $this->connection;
		}

		/**
		 * Create the mySQL database table in the default Fabrik connection
		 * @param array $elements
		 * @return mixed false or table model
		 */

		protected function createDbTable($elements, $drop = false)
		{
			$names = array_keys($elements);
			if (empty($names)) {
				return false;
			}
			$model = JModel::getInstance('Table', 'FabrikModel');
			$connection = $this->getDefaultCnn();
			$db = $connection->getDb();

			if ($model->databaseTableExists($this->tbl, $db)) {
				if ($drop) {
					$db->setQuery("DROP " . $db->nameQuote($this->tbl));
					$db->query();
				} else {
					JError::raiseNotice(500, 'db table already exists, not altering its structure');
					return false;
				}
			}

			$pluginManager = JModel::getInstance('pluginmanager', 'FabrikModel');

			$sql = "CREATE TABLE IF NOT EXISTS ".$db->nameQuote($this->tbl)." (";
			$fields = array();
			foreach ($elements as $name => $element) {
				if (array_key_exists('plugin', $element)) {
					$plugin = $pluginManager->getPlugIn($element['plugin'], 'element');
					if (strstr($name, $this->tbl)) {
						$fields[] = $db->nameQuote(array_pop(explode("___", $name))) . " " . $plugin->getFieldDescription() ;
					}
				}
			}
			$sql .= implode(",", $fields);
			$sql .= ", PRIMARY KEY (id)) ENGINE = MYISAM ";
			$db->setQuery($sql);
			$db->query();
			return $model;
		}

		/**
		 * create a fabrik table and assoc db table
		 * @param array $params
		 * @param array $elements
		 * @param string $tmplName
		 * @return mixed false or table model
		 */

		protected function createFabrikTable($params, $elements, $tmplName)
		{
			$model = false;
			if ($params['create_fabrik'] == 1) {
				$names = array_keys($elements);
				if (empty($names)) {
					JError::raiseNotice(500, 'createFabriktable: no elements given!');
					return false;
				}
				$connection = $this->getDefaultCnn();

				//create the database
				$drop = $params['drop_table'] == 1 ? true : false;
				$model = $this->createDbTable($elements, $drop);
				if ($model !== false) {
					JRequest::setVar('label', $this->tbl);
					JRequest::setVar('connection_id', $connection->_id);
					JRequest::setVar('db_table_name', $this->tbl);
					JRequest::setVar('state', 1);
					JRequest::setVar('template', $tmplName);

					$params = JRequest::getVar('params', array());
					$params['search-mode'] = $this->searchMode ? 'OR' : 'AND';
					$model->save();
				} else {
					JError::raiseNotice(500, 'createFabriktable: model not created');
				}
			}
			return $model;
		}

		/**
		 * format a combo box
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */

		protected function XMLcombobox($control, &$return)
		{
			$this->getSubElements($control, $return);
		}
		
		/**
		* Enter description here ...
		* @param unknown_type $control
		* @param unknown_type $return
		*/
		
		protected function XMLcheckbox($control, &$return)
		{
			$return['label'] = $control->getElementsByTagName('text', 0)->toString();
			$this->getSubElements($control, $return);
		}

		/**
		 *
		 * format checkbox group
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */

		protected function XMLcheckboxgroup($control, &$return)
		{
			$this->getSubElements($control, $return);
		}

		/**
		 *
		 * get the sub elements from the xml file
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */

		protected function getSubElements($control, &$return)
		{
			$opts = strip_tags($control->getElementsByTagName('text', 0)->toString());
			$opts = explode("\n", urldecode($opts));

			foreach ($opts as &$opt) {
				$opt = str_replace(array('[ ] ', '[x] ', '-[ ] ', '-[x] ', '(o) ', '( ) '), '', $opt);
			}
			$output = implode("|", $opts);
			$return['sub_values'] = $output;
			$return['sub_labels'] = $output;
		}

		/**
		 * Enter description here ...
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */

		protected function XMLradiobutton($control, &$return)
		{
			$this->getSubElements($control, $return);
		}

		/**
		 *
		 * Format radio button group
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */
		protected function XMLradiobuttongroup($control, &$return)
		{
			$this->getSubElements($control, $return);
		}

		/**
		 *
		 * format numeric stepper
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */
		protected function XMLnumericstepper($control, &$return)
		{
			$this->getSubElements($control, $return);
		}

		/**
		 *
		 * format switch - basically set its sub options
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */
		protected function XMLswitch($control, &$return)
		{
			$return['sub_values'] = "0|1";
			$return['sub_labels'] = "no|yes";
		}

		protected function XMLbutton($control, &$return)
		{
			//@todo 'TwitterIcon'
			$icon = urldecode(strip_tags($control->getElementsByTagName('icon', 0)->toString()));
			$icon = array_shift(explode("|", $icon));
			switch ($icon) {
				case 'FacebookIcon':
					$return['plugin'] = 'fblike';

					//element params - testing not sure if this works
					$return['params']['fblike_layout'] = 'button_count';
					break;
				case 'HeartIcon':
					$return['plugin'] = 'fabrikdigg';
					break;
				case 'ThumbsUpIcon':
					$return['plugin'] = 'fabrikthumbs';
					break;
			}
		}

		protected function XMLImage($control, &$return)
		{
			$return['params']['upload_allow_folderselect'] = 0;
			$return['params']['fileupload_crop'] = 1;
			$return['params']['fileupload_crop_dir']= '/images/stories/crop/';
			$return['style'] .= 'overflow:hidden;';
		}

		/**
		 *
		 * format a subtitle - wrap it iin <h3> tags
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */
		protected function XMLsubtitle($control, &$return)
		{
			$return['tag'] = array("<h3>", "</h3>");
		}

		/**
		 *
		 * format a title - wrap it iin <h2> tags
		 * @param unknown_type $control
		 * @param unknown_type $return
		 */
		protected function XMLtitle($control, &$return)
		{
			$return['tag'] = array("<h2>", "</h2>");
		}

		/**
		 * show a notice if they don't have fabrik installed
		 */

		public function getfabrik()
		{
			echo "<h1>Shucks, Looks like you forgot to install Fabrik!</h1>";
			echo "<p>Fabrik is The Joomla! Application builder</p><p>Using it in conjunction with 'Balsamiq 2 Fabrik' you can quickly and effortlessly convert your Balsamiq mockups into working Joomla applications</p>";
			echo "<p><a href=\"http://fabrikar.com/subscribe\">Download Fabrik</a></p>";
		}
}
