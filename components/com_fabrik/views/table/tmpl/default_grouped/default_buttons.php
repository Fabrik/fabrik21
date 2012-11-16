<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php if($this->showAdd) {?>
	<span class="addbutton" id="<?php echo $this->addRecordId;?>">
		<a href="<?php echo $this->addRecordLink;?>"><?php echo $this->addRecordLabel;?></a>
	</span>
<?php }?>

<?php if($this->showCSV) {?>
	<span class="csvExportButton">
		<a href="#"><?php echo JText::_('EXPORT TO CSV');?></a>
	</span>
<?php }?>

<?php if($this->showCSVImport) {?>
	<span class="csvImportButton">
		<a href="<?php echo $this->csvImportLink;?>"><?php echo JText::_('IMPORT FROM CSV');?></a>
	</span>
<?php }?>

<?php if($this->showRSS) {?>
	<span class="feedButton">
		<a href="<?php echo $this->rssLink;?>"><?php echo JText::_('SUBSCRIBE RSS');?></a>
	</span>
<?php }?>

<?php if($this->showPDF) {
echo $this->pdfLink;
}?>
