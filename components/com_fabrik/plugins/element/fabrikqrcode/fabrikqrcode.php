<?php
/**
 * Plugin element to render a google o meter viz
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php' );

class FabrikModelFabrikQRCode  extends FabrikModelElement {

	var $_pluginName = 'qrcode';

	var $qr_element = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render( $data, $repeatCounter = 0 )
	{

		//$name 		= $this->getHTMLName( $repeatCounter );
		//$id				= $this->getHTMLId( $repeatCounter );
		//$params 	=& $this->getParams();
		//$element 	=& $this->getElement();
		//$value 		= $this->getValue( $data, $repeatCounter );
		//$fullName = $this->getDataElementFullName();
		if (JRequest::getVar('view') == 'details') {
			$value = $data[$fullName];
			$value = $this->_getQRElement()->getQRValue($value, $data, $repeatCounter, 'details');
			$str = $this->_renderTableData( $value );
			return $str;
		} else {
		return '';
		}
	}

	/*
	private function getDataElementFullName()
	{
		$dataelement = $this->getDataElement();
		$fullName = $dataelement->getFullName();
		return $fullName;
	}

	private function getDataElement(){
		$params =& $this->getParams();
		$elementid = (int)$params->get('qrcode_element');
		$element =& JModel::getInstance( 'Element', 'FabrikModel' );
		$element->setId( $elementid );
		$element->getElement();
		return $element;
	}
	*/

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	 function renderTableData( $data, $oAllRowsData )
	{
		$fullName = $this->_getQRElement()->getFullName();
		$data = $oAllRowsData->$fullName;
		$data = $this->_getQRElement()->getQRValue($data, JArrayHelper::fromObject($oAllRowsData));
		$data = $this->_renderTableData( $data );
		return parent::renderTableData( $data, $oAllRowsData );
	}

	function _renderTableData( $data ){
		$options = array();
		$params =& $this->getParams();
		$options['chartsize'] = 'chs='.$params->get('qrcode_width', 150).'x'.$params->get('qrcode_height', 150);
		$options['charttype'] = 'cht=qr';
		$options['value'] = 'chl='.urlencode($data);
		$options['encode'] = 'choe='.$params->get('qrcode_encode', 'UTF-8');
		$options = implode('&amp;', $options);
		$str = '<img alt="Google QR Code" src="http://chart.apis.google.com/chart?'.$options.'"/>';
		return $str;
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */

	function getFieldDescription()
	{
		return "TINYINT(1)";
	}

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render();?></div>
		<?php
	}

	private function _getQRElement()
	{
		if (!isset($this->qr_element)) {
			$params 					=& $this->getParams();
			$elementid = (int)$params->get('qrcode_element');
			$tableModel 	=& $this->getTableModel();
			$formModel 		=& $tableModel->getForm();
			$groups 			=& $formModel->getGroupsHiarachy();
			foreach ($groups as $groupModel) {
				$elementModels =& $groupModel->getMyElements();
				foreach ($elementModels as $elementModel) {
					$element =& $elementModel->getElement();
					if ($elementid == $element->id) {
						$this->qr_element = $elementModel;
						continue 2;
					}
				}
			}
		}
		return $this->qr_element;
	}
}
?>