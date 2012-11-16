<?php
/*
 * @package Joomla 1.5
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * @component Phoca Plugin
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 */
defined('_JEXEC') or die('Restricted access');
jimport( 'joomla.plugin.plugin' );

class plgSystemPhocaPDFFabrik extends JPlugin
{
	function plgSystemPhocaPDFFabrik(& $subject, $config) {
		parent :: __construct($subject, $config);
	}


	function onAfterRender() {

		$plugin       =& JPluginHelper::getPlugin('system', 'phocapdffabrik');
		$pluginP       = new JParameter( $plugin->params );
		$pdfDestination   = $pluginP->get('pdf_destination', 'S');

		/*global $mainframe;
		 if ($mainframe->isAdmin()) {
		 return;
		 }*/

		// IE 7 bug
		include_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdfbrowser.php');

		$document   = &JFactory::getDocument();
		$doctype   = $document->getType();
		if ($doctype == 'html') {
			$bodySite   = JResponse::getBody();
			if ($pdfDestination == 'I' || $pdfDestination == 'D') {
				// Remome OnClick
				$bodySite    = preg_replace_callback('/<a(.+)href="(.*)format=pdf(.*)"(.+)onclick="(.*)"/Ui', array('plgSystemPhocaPDFFabrik', 'phocaPDFCallbackOnClick'), $bodySite);
			} else {
				//$bodySite    = preg_replace_callback('/<a(.+)href="(.*)format=pdf(.*)"/Ui', array('plgSystemPhocaPDFFabrik', 'phocaPDFCallback'), $bodySite);
				// IE 7 bug
				$bodySite    = preg_replace_callback('/<a(.+)href="(.*)format=pdf(.*)"(.+)onclick="(.*)"/Ui', array('plgSystemPhocaPDFFabrik', 'phocaPDFCallbackOnClickIE'), $bodySite);
			}

			JResponse::setBody($bodySite);
		}
		return true;
	}

	function phocaPDFCallbackOnClick ($matches) {

		$a   = $matches[0];
		$b   = $matches[1];
		$c   = $matches[2];
		$d   = $matches[3];
		$e   = $matches[4];
		$f   = $matches[5];
		$replacement    = '<a ' . $b .  'href="'. $c . 'format=phocapdf' . $d .'"'.$e.'';
		return $replacement;
	}

	function phocaPDFCallbackOnClickIE ($matches) {

		$a   = $matches[0];
		$b   = $matches[1];
		$c   = $matches[2];
		$d   = $matches[3];
		$e   = $matches[4];// IE 7 bug
		$f   = $matches[5];// IE 7 bug

		// IE 7 bug
		$browser = PhocaPDFHelperBrowser::browserDetection('browser');
		if ($browser == 'msie7' || $browser == 'msie8') {
			$replacement    = '<a '. $b .  'href="'. $c . 'format=phocapdf' . $d .'"'.$e.'target="_blank" ';
		} else {
			$replacement    = '<a ' . $b .  'href="'. $c . 'format=phocapdf' .$d.'"'.$e.'onclick="'.$f.'"';
		}
		return $replacement;
	}
	/*
	 function phocaPDFCallback ($matches) {

	 $a   = $matches[0];
	 $b   = $matches[1];
	 $c   = $matches[2];
	 $d   = $matches[3];
	 $replacement    = '<a ' . $b .  'href="'. $c . 'format=phocapdf' . $d .'"';
	 return $replacement;
	 }*/

	function onBeforeCreatePDFFabrik(&$content) {
		$content->content = '';
		// load plugin params info
		$plugin  = &JPluginHelper::getPlugin('system', 'phocapdffabrik');
		$pluginP = new JParameter( $plugin->params );

		$content->margin_top      = $pluginP->get('margin_top', 27);
		$content->margin_left      = $pluginP->get('margin_left', 15);
		$content->margin_right      = $pluginP->get('margin_right', 15);
		$content->margin_bottom      = $pluginP->get('margin_bottom', 25);
		$content->site_font_color   = $pluginP->get('site_font_color', '#000000');
		$content->site_cell_height   = $pluginP->get('site_cell_height', 1.2);

		$content->font_type         = $pluginP->get('font_type', 'freemono');
		$content->page_format      = $pluginP->get('page_format', 'A4');
		$content->page_orientation   = $pluginP->get('page_orientation', 'L');

		$content->header_data      = $pluginP->get('header_data', '');
		$content->header_font_type   = $pluginP->get('header_font_type', 'freemono');
		$content->header_font_style   = $pluginP->get('header_font_style', '');
		$content->header_font_size   = $pluginP->get('header_font_size', 10);
		$content->header_margin      = $pluginP->get('header_margin', 5);

		$content->footer_font_type   = $pluginP->get('footer_font_type', 'freemono');
		$content->footer_font_style   = $pluginP->get('footer_font_style', '');
		$content->footer_font_size   = $pluginP->get('footer_font_size', 10);
		$content->footer_margin      = $pluginP->get('footer_margin', 15);

		$content->pdf_name         = $pluginP->get('pdf_name', 'Phoca PDF');
		$content->pdf_destination   = $pluginP->get('pdf_destination', 'S');
		$content->image_scale      = $pluginP->get('image_scale', 4);
		$content->display_plugin   = $pluginP->get('display_plugin', 0);
		$content->display_image      = $pluginP->get('display_image', 1);
		$content->use_cache         = $pluginP->get('use_cache', 0);


		//Extra values
		if ((int)$content->site_cell_height > 3) {
			$content->site_cell_height = 3;
		}
		if ((int)$content->margin_top > 200) {
			$content->margin_top = 200;
		}
		if ((int)$content->margin_left > 50) {
			$content->margin_left = 50;
		}
		if ((int)$content->margin_right > 50) {
			$content->margin_right = 50;
		}
		if ((int)$content->margin_bottom > 150) {
			$content->margin_bottom = 150;
		}
		if ((int)$content->header_font_size > 30) {
			$content->header_font_size = 30;
		}
		if ((int)$content->footer_font_size > 30) {
			$content->footer_font_size = 30;
		}
		if ((int)$content->header_margin > 50) {
			$content->header_margin = 50;
		}
		if ((int)$content->footer_margin > 50) {
			$content->footer_margin = 50;
		}
		if ((int)$content->image_scale < 0.5) {
			$content->image_scale = 0.5;
		}
		return true;
	}


	function onBeforeDisplayPDFFabrik(&$pdf, &$content, &$document) {
		$pdf->SetTitle($document->getTitle());
		$pdf->SetSubject($document->getDescription());
		$pdf->SetKeywords($document->getMetaData('keywords'));

		// - - - - - - - - - - - - - - - -
				/*
		 * Specific Plugin code for Header
		 * Header is set here in system plugin (Phoca PDF Content Plugin) because we need title and header data
		 * Footer is set in helper of system plugin (Phoca PDF Component) because we need TCPDF data (pagination)
		 */
		if ($content->header_data != '') {
			// Plugin code
			$content->header_data = str_replace('{phocapdftitle}', $document->title, $content->header_data);
			$content->header_data = str_replace('{phocapdfheader}', $document->_header, $content->header_data);
			$pdf->setHeaderData('' , 0, '', $content->header_data);
		} else {
			$pdf->setHeaderData('' , 0, $document->getTitle(), $document->getHeader());
		}
		$pdf->setHeaderFont(array($content->header_font_type, $content->header_font_style, $content->header_font_size));
		/*// HEADER
		if ($content->header_data != '') {
			$pdf->setHeaderData('' , 0, '', $content->header_data);
		} else {
			$pdf->setHeaderData('' , 0, $document->getTitle(), $document->getHeader());
		}
		$pdf->setHeaderFont(array($content->header_font_type, $content->header_font_style, $content->header_font_size));
*/


		$lang = &JFactory::getLanguage();
		$font = $content->font_type;
		$pdf->setRTL($lang->isRTL());


		$pdf->setFooterFont(array($content->footer_font_type, $content->footer_font_style, $content->footer_font_size));
		// Initialize PDF Document
		$pdf->AliasNbPages();
		$pdf->AddPage();

		$documentOutput = $document->getBuffer();
		if ($content->display_plugin == 0) {
			$documentOutput    = preg_replace_callback('/\{(.*)\}/Ui', array('plgSystemPhocaPDFFabrik', 'phocaPDFCallbackPlugin'), $documentOutput);
		}

		if ($content->display_image == 0) {
			$documentOutput    = preg_replace_callback('/<img(.*)>/Ui', array('plgSystemPhocaPDFFabrik', 'phocaPDFCallbackImage'), $documentOutput);
		}

		// Build the PDF Document string from the document buffer
		//echo "<Pre>";print_r($content);echo $documentOutput;exit;
		$pdf->writeHTML($documentOutput , true);

		return true;
	}

	function phocaPDFCallbackPlugin ($matches) {

		// Don't display Plugin Code in PDF
		$a   = $matches[0];
		$replacement    = '';
		return $replacement;
	}

	function phocaPDFCallbackImage ($matches) {

		// Don't display Images in PDF
		$a   = $matches[0];
		$replacement    = '';
		return $replacement;
	}

}
?>