<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
$lang =& JFactory::getLanguage();
$langfile = 'com_fabrik.plg.'.'table'.'.'.$this->getName();
$lang->load($langfile);
$lang->load($langfile, COM_FABRIK_BASE);
?>
<div>
	<form id="addmanyform" class="addmany" method="post" enctype="multipart/form-data" action="<?php echo JURI::base();?>index.php?tmpl=component">
		<ul>
		<li>
			<label>
				<?php echo $this->addmany_to_fk_label; ?>: <?php echo $this->addmany_to_fk_select; ?> 
			</label>
		</li>
		<li>
			<input type="button" value="<?php echo JText::_('SUBMIT') ?>" class="button" onclick="parent.oTable<?php echo $this->tableid;?>.plugins[<?php echo $this->renderOrder ?>].watchSubmit('<?php echo JText::_('PLG_TABLE_ADDMANY_PLEASE_SELECT')?>')");/>
		</li>
	</ul>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="controller" value="table.addmany" />
		<input type="hidden" name="task" value="doaddmany" />
		<input type="hidden" name="id" value="<?php echo $this->tableid ?>" />
		<input type="hidden" name="recordids" value="<?php echo $this->recordids ?>" />
		<input type="hidden" name="renderOrder" value="<?php echo $this->renderOrder ?>" />
	</form>
</div>
