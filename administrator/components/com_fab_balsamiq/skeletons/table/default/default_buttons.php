<?php if($this->showAdd) {?>
	<span class="addbutton" id="<?php echo $this->addRecordId;?>">
		<a href="<?php echo $this->addRecordLink;?>"><?php echo JText::_('ADD');?></a>
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

<?php if (array_key_exists('fabrik_delete', $this->headings)) {?>
	<span class="fabrik_delete">
		<?php echo $this->headings['fabrik_delete']?>
	</span>
<?php }?>