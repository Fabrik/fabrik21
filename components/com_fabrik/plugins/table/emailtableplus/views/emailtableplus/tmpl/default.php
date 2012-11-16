<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
$lang =& JFactory::getLanguage();
$langfile = 'com_fabrik.plg.'.'table'.'.'.$this->getName();
$lang->load($langfile);
$lang->load($langfile, COM_FABRIK_BASE);
?>
<div>
	<form class="emailtableplus" method="post" enctype="multipart/form-data" action="<?php echo JURI::base();?>index.php?tmpl=component">
	<p><?php echo JText::sprintf(JText::_('EMAIL N RECORDS'), $this->recordcount) ?></p>
		<input type="hidden" name="MAX_FILE_SIZE" value="524288" />
		<ul>
		<li>
			<label>
				<?php echo JText::_('SUBJECT') ?><br />
				<input class="inputbox fabrikinput" type="text" name="subject" id="subject" value="" size="20" />
			</label>
		</li>
		<li>
			<label>
				<?php echo JText::_('MESSAGE') ?><br />
				<textarea name="message" id="message" cols="60" rows="10"></textarea>
			</label>
		</li>
		<li>
			<input type="button" value="<?php echo JText::_('SEND') ?>" class="button" onclick="parent.oTable<?php echo $this->tableid;?>.plugins[<?php echo $this->renderOrder ?>].watchSend('<?php echo JText::_('PLEASE ENTER A SUBJECT')?>')");/>
		</li>
		<li>
			<input type="button" value="<?php echo JText::_('ATTACH FILE') ?>" class="button" onclick="parent.oTable<?php echo $this->tableid;?>.plugins[<?php echo $this->renderOrder ?>].watchAttach()");/>
		</li>
	</ul>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="controller" value="table.emailtableplus" />
		<input type="hidden" name="task" value="doemail" />
		<input type="hidden" name="id" value="<?php echo $this->tableid ?>" />
		<input type="hidden" name="recordids" value="<?php echo $this->recordids ?>" />
		<input type="hidden" name="renderOrder" value="<?php echo $this->renderOrder ?>" />
	</form>
</div>
