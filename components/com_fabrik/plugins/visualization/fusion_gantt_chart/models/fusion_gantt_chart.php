<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'visualization.php');

class fabrikModelFusion_gantt_chart extends FabrikModelVisualization {


	function getChart()
	{
		// Include PHP Class
		include(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.'visualization'.DS.'fusion_gantt_chart'.DS.'FCclass'.DS.'FusionCharts_Gen.php');
		// Add JS to page header
		$document =& JFactory::getDocument();
		$document->addScript($this->srcBase."fusion_gantt_chart/FCCharts/FusionCharts.js");

		$params =& $this->getParams();
		$w = $params->get('fusion_gantt_chart_width');
		$h = $params->get('fusion_gantt_chart_height');

		// Create new chart
		$FC = new FusionCharts("GANTT","$w","$h");

		// Define path to FC's SWF
		$FC->setSWFPath($this->srcBase."fusion_gantt_chart/FCCharts/");


		$FC->setChartParam('dateFormat', 'yyyy-mm-dd');
		$FC->setChartParam('showTaskNames', 1);

		$FC->setChartParams('ganttWidthPercent=70;gridBorderAlpha=100;canvasBorderColor=333333;canvasBorderThickness=0;hoverCapBgColor=FFFFFF;hoverCapBorderColor=333333;extendcategoryBg=0;ganttLineColor=99cc00;ganttLineAlpha=20;baseFontColor=333333;gridBorderColor=99cc00');
		// ------------------- Setting Param string


		$tableid 	= $params->get('fusion_gantt_chart_table');
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		$tableModel->setId($tableid);
		$db =& $tableModel->getDB();
		$table = $tableModel->getTable()->db_table_name;
		$process = $params->get('fusion_gantt_chart_process');
		$processraw = $process.'_raw';
		$start = $params->get('fusion_gantt_chart_startdate');
		$startraw = $start .'_raw';
		$end = $params->get('fusion_gantt_chart_enddate');
		$endraw = $end . '_raw';
		$label = $params->get('fusion_gantt_chart_label');
		$hover = $params->get('fusion_gantt_chart_hover');
		$milestone = $params->get('fusion_gantt_chart_milestone');
		$milestoneraw = $milestone."_raw";
		$connector = $params->get('fusion_gantt_chart_connector');
		$connectorraw = $connector."_raw";
		$fields = array();

		$fields[] = FabrikString::safeColName($process)." AS " . $db->nameQuote($process);
		$fields[] = FabrikString::safeColName($start)." AS " . $db->nameQuote($start);
		$fields[] = FabrikString::safeColName($end)." AS " . $db->nameQuote($end);
		$fields[] = FabrikString::safeColName($label)." AS " . $db->nameQuote($label);
		if ($hover !== '') {
			$fields[] = FabrikString::safeColName($hover)." AS " . $db->nameQuote($hover);
		}
		if ($milestone !== '') {
			$fields[] = FabrikString::safeColName($milestone)." AS " . $db->nameQuote($milestone);
		}
		if ($connector !== '') {
			$fields[] = FabrikString::safeColName($connector)." AS " . $db->nameQuote($connector);
		}

		$tableModel->asfields = $fields;
		$nav =& $tableModel->getPagination(0, 0, 0);
		$data = $tableModel->getData();
		$data = (array)$data[0];

		$mindate = null;
		$maxdate = null;
		$usedProcesses = array();
		$milestones = array();
		$connectors = array();
		$processLabel = $params->get('fusion_gantt_chart_process_label');
		$FC->setGanttProcessesParams("positionInGrid=right;align=center;headerText=$processLabel;fontColor=333333;fontSize=11;isBold=1;isAnimated=1;bgColor=99cc00;headerbgColor=333333;headerFontColor=99cc00;headerFontSize=16;bgAlpha=40");

		foreach ($data as $d) {
			$hovertext = $hover == '' ? '' : $d->$hover;
			$processid = $d->$processraw;
			$startdate = JFactory::getDate($d->$startraw);
			$enddate = JFactory::getDate($d->$endraw);
			$strParam = "start=".$startdate->toFormat('%Y/%m/%d').";end=".$enddate->toFormat('%Y/%m/%d').";processId={$processid};id={$d->__pk_val};color=99cc00;alpha=60;topPadding=19;hoverText={$hovertext};";
			$strParam .="link={$d->fabrik_view_url};";
			$FC->addGanttTask($FC->encodeSpecialChars($d->$label), $strParam);

			if ($milestone !== '' && $d->$milestoneraw == 1) {
				$FC->addGanttMilestone($d->__pk_val, "date=".$enddate->toFormat('%Y/%m/%d').";radius=10;color=333333;shape=Star;numSides=5;borderThickness=1");
			}

			if ($connector !== '' && $d->$connectorraw !== '') {
				$FC->addGanttConnector($d->$connectorraw, $d->__pk_val, "color=99cc00;thickness=2;");
			}
			#apply processes
			if (!in_array($processid, $usedProcesses)) {
				$usedProcesses[] = $processid;
				$FC->addGanttProcess($FC->encodeSpecialChars($d->$process), "id={$processid};");
			}
			#increaes max/min date range
			if (is_null($mindate)) {
				$mindate = $startdate;
				$maxdate = $enddate;
			} else {
				if (JFactory::getDate($d->$startraw)->toUnix() < $mindate->toUnix()) {
					$mindate = JFactory::getDate($d->$startraw);
				}

				if (JFactory::getDate($d->$endraw)->toUnix() > $maxdate->toUnix()) {
					$maxdate = JFactory::getDate($d->$endraw);
				}
			}
		}

		$startyear = $mindate ? $mindate->toFormat('%Y') : date('Y');
		$endyear = $maxdate ? $maxdate->toFormat('%Y') : 0;

		$monthdisplay = $params->get('fusion_gannt_chart_monthdisplay');
		$FC->addGanttCategorySet("bgColor=333333;fontColor=99cc00;isBold=1;fontSize=14");
		for ($y = $startyear; $y <= $endyear; $y++) {
			$firstmonth = ($y == $startyear) ? (int)$mindate->toFormat('%m') : 1;
			$lastmonth = ($y == $endyear) ? $maxdate->toFormat('%m')+1 : 13;

			$start = date('Y/m/d', mktime(0, 0, 0, $firstmonth, 1, $y));
			$end = date('Y/m/d', mktime(0, 0, 0, $lastmonth, 0, $y));

			$strParam = "start=". $start.";end=".$end.";";
			$FC->addGanttCategory($y, $strParam);
		}
		$FC->addGanttCategorySet("bgColor=99cc00;fontColor=333333;isBold=1;fontSize=10;bgAlpha=40;align=center");
		for ($y = $startyear; $y <= $endyear; $y++) {
			$lastmonth = ($y == $endyear) ? $maxdate->toFormat('%m')+1 : 13;
			$firstmonth = ($y == $startyear) ? (int)$mindate->toFormat('%m') : 1;
			for ($m = $firstmonth; $m < $lastmonth; $m++) {
				$starttime = mktime(0, 0, 0, $m, 1, $y);
				$start = date('Y/m/d', $starttime);
				$end = date('Y/m/d', mktime(0, 0, 0, $m+1, 0, $y));//use day = 0 to load last day of next month
				$m2 = $monthdisplay == 'str' ? JText::_(date('M', $starttime)) : $m;
				$FC->addGanttCategory($m2, "start=".$start.";end=".$end.";");
			}
		}

		# Render Chart
		return $FC->renderChart(false, false);
	}

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none"><?php

	$c = count($pluginParams->get('fusion_gantt_chart_table'));
	$pluginParams->_duplicate = true;
	echo $pluginParams->render('params', 'connection');

	for ($x=0; $x<$c; $x++) {
		echo $pluginParams->render('params', '_default', true, $x);
	}

	$pluginParams->_duplicate = false;
	?>
<fieldset><legend><?php echo JText::_('PROCESSES'); ?></legend> <?php echo $pluginParams->render('params', 'process');?>
</fieldset>

<fieldset><legend><?php echo JText::_('TASKS'); ?></legend> <?php echo $pluginParams->render('params', 'tasks');?>
</fieldset>

<fieldset><legend><?php echo JText::_('GRAPH_ATTRIBUTES'); ?></legend>
	<?php echo $pluginParams->render('params', 'graph_attributes');?></fieldset>
	<?php echo $pluginParams->render('params', 'rest');?>
	</div>
	<?php
	}

	function setTableIds()
	{
		if (!isset($this->tableids)) {
			$params =& $this->getParams();
			$this->tableids = $params->get('fusion_gantt_chart_table', array(), '_default', 'array');
		}
	}

}
?>