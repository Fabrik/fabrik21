<?php
/*
 * @package		Joomla.Framework
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @component Phoca Component
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License version 2 or later;
 */
defined('JPATH_BASE') or die();
jimport('joomla.filesystem.file');

class PhocaPDFRender extends JDocument
{
	function renderPDF( $document = '', $staticData = array()) {

		// LOADING OF HELPER FILES (extended TCPDF library), LISTENING TO Phoca PDF Plugins
		$option = JRequest::getCmd('option');
		$t 		= JString::ucfirst(str_replace('com_', '', $option));

		// Used abstract class of Phoca PDF (e.g. in VM)
		if ($option == 'com_phocapdf') {
			$type = JRequest::getCmd('type');
			switch($type) {

				case 'invoice':
				case 'receipt':
				case 'deliverynote':
					$option = 'com_virtuemart';
				break;

			}

		}

		// Static Data - if the function is called as static with defined parameters
		// and the pdf is not rendered as document
		if (isset($staticData['option']) && $staticData['option'] != '') {
			$option 		= $staticData['option'];
			$pdfFileName	= $staticData['filename'];
			$t				= '';
		}

		// Define - must be called before calling the plugin (because plugin includes definition file of tcpdf,
		// so it must be defined before
		if(!defined('K_TCPDF_EXTERNAL_CONFIG'))	{define('K_TCPDF_EXTERNAL_CONFIG', true);}
		if(!defined('K_PATH_MAIN'))	{define("K_PATH_MAIN", JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'assets'.DS.'tcpdf');}
		if(!defined('K_PATH_URL'))				{define("K_PATH_URL", JPATH_BASE);}// URL path
		if(!defined('K_PATH_FONTS'))	{define("K_PATH_FONTS", JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'fonts'.DS);}
		if(!defined('K_PATH_CACHE'))			{define("K_PATH_CACHE", K_PATH_MAIN.DS.'cache'.DS);}// Cache directory path
		$urlPath = JURI::base(true) . '/administrator/components/com_phocapdf/assets/tcpdf/';// Cache URL path
		if(!defined('K_PATH_URL_CACHE'))		{define("K_PATH_URL_CACHE", $urlPath.'cache/');}
		if(!defined('K_PATH_IMAGES'))			{define("K_PATH_IMAGES", K_PATH_MAIN.DS.'images'.DS);}// Images path
		if(!defined('K_BLANK_IMAGE'))			{define("K_BLANK_IMAGE", K_PATH_IMAGES.DS."_blank.png");}// Blank image path
		if(!defined('K_CELL_HEIGHT_RATIO'))		{define("K_CELL_HEIGHT_RATIO", 1.25);}// Cell height ratio
		if(!defined('K_TITLE_MAGNIFICATION'))	{define("K_TITLE_MAGNIFICATION", 1.3);}// Magnification scale for titles
		if(!defined('K_SMALL_RATIO'))			{define("K_SMALL_RATIO", 2/3);}// Reduction scale for small font
		if(!defined('HEAD_MAGNIFICATION'))		{define("HEAD_MAGNIFICATION", 1.1);}// Magnication scale for head
		if(!defined('PDF_PAGE_FORMAT'))			{define('PDF_PAGE_FORMAT', 'A4');}// page format
		if(!defined('PDF_PAGE_ORIENTATION'))	{define('PDF_PAGE_ORIENTATION', 'P');}// page orientation (P=portrait, L=landscape)
		if(!defined('PDF_CREATOR'))				{define('PDF_CREATOR', 'Phoca PDF');}// document creator
		if(!defined('PDF_AUTHOR'))				{define('PDF_AUTHOR', 'Phoca PDF');}// document author
		if(!defined('PDF_HEADER_TITLE'))		{define('PDF_HEADER_TITLE', 'Phoca PDF');}// header title
		if(!defined('PDF_HEADER_STRING'))		{define('PDF_HEADER_STRING', "Phoca PDF");}// header description string
		//define('PDF_HEADER_LOGO', 'tcpdf_logo.jpg');// image logo
		//define('PDF_HEADER_LOGO_WIDTH', 30);// header logo image width [mm]
		if(!defined('PDF_UNIT'))	{define('PDF_UNIT', 'mm');}// document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
		if(!defined('PDF_header_margin'))		{define('PDF_header_margin', 10);}// header margin
		if(!defined('PDF_footer_margin'))		{define('PDF_footer_margin', 10);}// footer margin
		if(!defined('PDF_MARGIN_TOP'))			{define('PDF_MARGIN_TOP', 33);}// top margin
		if(!defined('PDF_MARGIN_BOTTOM'))		{define('PDF_MARGIN_BOTTOM', 25);}// bottom margin
		if(!defined('PDF_MARGIN_LEFT'))			{define('PDF_MARGIN_LEFT', 15);}// left margin
		if(!defined('PDF_MARGIN_RIGHT'))		{define('PDF_MARGIN_RIGHT', 15);}// right margin
		if(!defined('PDF_FONT_NAME_MAIN'))		{define('PDF_FONT_NAME_MAIN', 'helvetica');}// main font name
		if(!defined('PDF_FONT_SIZE_MAIN'))		{define('PDF_FONT_SIZE_MAIN', 10);}// main font size
		if(!defined('PDF_FONT_NAME_DATA'))		{define('PDF_FONT_NAME_DATA', 'helvetica');}// data font name
		if(!defined('PDF_FONT_SIZE_DATA'))		{define('PDF_FONT_SIZE_DATA', 8);}// data font size
		if(!defined('PDF_IMAGE_SCALE_RATIO'))	{define('PDF_IMAGE_SCALE_RATIO', 4);}// Ratio used to scale the images
		if(!defined('K_TCPDF_CALLS_IN_HTML'))	{define('K_TCPDF_CALLS_IN_HTML', true);}


		switch ($option) {
			case 'com_content':

				$content 	= new JObject();
				// Get info from Phoca PDF Content Plugin
				$dispatcher	= &JDispatcher::getInstance();
				JPluginHelper::importPlugin('system');
				$results 	= $dispatcher->trigger('onBeforeCreatePDFContent', array (&$content));

				if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdfcontenttcpdf.php')) {
					require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdfcontenttcpdf.php');
					$pdf = new PhocaPDFContentTCPDF($content->page_orientation, 'mm', $content->page_format, true, 'UTF-8', $content->use_cache);
				} else {
					return JError::raiseError('PDF ERROR', 'Document cannot be created - Loading of Phoca PDF library (Content) failed');
				}
			break;

			case 'com_phocamenu':

				// Get info from Phoca PDF Restaurant Menu Plugin
				$content 	= new JObject();
				$dispatcher	= &JDispatcher::getInstance();
				JPluginHelper::importPlugin('phocapdf');

				$results 	= $dispatcher->trigger('onBeforeCreatePDFRestaurantMenu', array (&$content));

				if (JFile::exists(JPATH_SITE.DS.'plugins'.DS.'phocapdf'.DS.'restaurantmenu.php')) {
					require_once(JPATH_SITE.DS.'plugins'.DS.'phocapdf'.DS.'restaurantmenu.php');
					$pdf = new PhocaPDFRestaurantMenuTCPDF($content->page_orientation, 'mm', $content->page_format, true, 'UTF-8', $content->use_cache);
				} else {
					return JError::raiseError('PDF ERROR', 'Document cannot be created - Loading of Phoca PDF Plugin (Restaurant Menu) failed');
				}
			break;

			case 'com_virtuemart':

				// Get info from Phoca PDF VirtueMart Plugin
				$content 	= new JObject();
				$dispatcher	= &JDispatcher::getInstance();
				JPluginHelper::importPlugin('phocapdf');
				$results 	= $dispatcher->trigger('onBeforeCreatePDFVirtueMart', array (&$content, $staticData));

				if (JFile::exists(JPATH_SITE.DS.'plugins'.DS.'phocapdf'.DS.'virtuemart.php')) {
					require_once(JPATH_SITE.DS.'plugins'.DS.'phocapdf'.DS.'virtuemart.php');
					$pdf = new PhocaPDFVirtueMartTCPDF($content->page_orientation, 'mm', $content->page_format, true, 'UTF-8', $content->use_cache);
					$pdf->setStaticData($staticData);

				} else {
					return JError::raiseError('PDF ERROR', 'Document cannot be created - Loading of Phoca PDF Plugin (VirtueMart) failed');
				}
			break;

			default:
            require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdf.php');
            require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'assets'.DS.'tcpdf'.DS.'tcpdf.php');
            $content    = new JObject();
            // Get info from Phoca PDF Content Plugin
            $dispatcher   = &JDispatcher::getInstance();
            JPluginHelper::importPlugin('system');
            $results    = $dispatcher->trigger('onBeforeCreatePDF'.$t, array (&$content));
            $pdf = new TCPDF($content->page_orientation, 'mm', $content->page_format, true, 'UTF-8', $content->use_cache);

            break;
			break;
		}

		//krumo("exit");exit;
		$pdf->SetMargins($content->margin_left, $content->margin_top, $content->margin_right);

		$pdf->SetAutoPageBreak(TRUE, $content->margin_bottom);
		$pdf->setCellHeightRatio($content->site_cell_height);
		$pdf->setFont($content->font_type);

		$siteFontColor = $pdf->convertHTMLColorToDec($content->site_font_color);
		$pdf->SetTextColor($siteFontColor['R'], $siteFontColor['G'], $siteFontColor['B']);

		//$pdf->setPageFormat($content->page_format, $content->page_orientation);
		$pdf->SetHeaderMargin($content->header_margin);
		$pdf->SetFooterMargin($content->footer_margin);
		$pdf->setImageScale($content->image_scale);


		// PDF Metadata
		$pdf->SetCreator(PDF_CREATOR);


		// Content
		switch ($option) {
			 default:
            $results = $dispatcher->trigger('onBeforeDisplayPDF'.$t, array (&$pdf, &$content, &$this));
            break;
			case 'com_content':

				$results = $dispatcher->trigger('onBeforeDisplayPDFContent', array (&$pdf, &$content, &$document));

			break;

			case 'com_phocamenu':

				$results = $dispatcher->trigger('onBeforeDisplayPDFRestaurantMenu', array (&$pdf, &$content, &$document));

			break;

			case 'com_virtuemart':

				$results = $dispatcher->trigger('onBeforeDisplayPDFVirtueMart', array (&$pdf, &$content, &$document, $staticData));

			break;
		}


		if (isset($staticData['file']) && $staticData['file'] != '') {

			$pdf->Output($staticData['file'], 'F');
			return true;

		}

		// Called from administrator area (administrator calls frontend view, but it still administrator area)
		$adminView	= JRequest::getVar('admin', 0, '', 'int');
		if ($adminView == 1) {
			$content->pdf_destination = 'S';
		}


		if ($content->pdf_destination == 'D' || $content->pdf_destination == 'I') {
			$pdf->Output($content->pdf_name, $content->pdf_destination);
			return true;
		}

		$data = $pdf->Output($content->pdf_name, $content->pdf_destination);

		return $data;

	}
}