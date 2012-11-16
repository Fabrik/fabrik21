<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
//JHTML::_('behavior.mootools');
//JHTML::script('mootools-1.2-ext.js', 'components/com_fabrik/libs/mootools1.2/', true);
?>
<style>
#emailtable ul{
	list-style:none;
}

#emailtable li{
	background-image:none;
	margin-top:15px;
}
</style>
<div id="emailtable-content">
	<form method="post" enctype="multipart/form-data" action="<?php echo JURI::base();?>index.php" name="emailtable" id="emailtable">
		<p><?php echo JText::sprintf('EMAIL %s RECORDS', $this->recordcount) ?></p>
		<ul>
		<li>
			<label>
				<?php echo JText::_('TO') ?><br />
				<?php echo $this->fieldList ?>
			</label>
		</li>
		<?php if ($this->showSubject) { ?>
		<li>
			<label>
				<?php echo JText::_('SUBJECT') ?><br />
				<input class="inputbox fabrikinput" type="text" name="subject" id="subject" value="<?php echo $this->subject?>" size="20" />
			</label>
		</li>
		<?php } ?>
		<li>
			<label>
				<?php echo JText::_('MESSAGE') ?><br />
				<?php echo FabrikHelperHTML::getEditorArea('message', $this->message, 'message', 75, 10, 75, 10);?>
			</label>
		</li>
		<?php if ($this->allowAttachment) {?>
		<li class="attachement">
			<label>
				<?php echo JText::_('ATTACHMENT') ?><br />
				<input class="inputbox fabrikinput" name="attachement[]" type="file" id="attachement" />
			</label>
			<a href="#" class="addattachement">
				<img src="media/com_fabrik/images/add.png" alt="<?php echo JText::_('add');?>" />
			</a>
			<a href="#" class="delattachement">
				<img src="media/com_fabrik/images/del.png" alt="<?php echo JText::_('delete');?>" />
			</a>
		</li>
		<li>
		<?php }?>
			<input type="submit" id="submit" value="<?php echo JText::_('SEND') ?>" class="button" />
		</li>
	</ul>
		<?php if (!$this->showSubject) { ?>
		<input type="hidden" name="subject" value="<?php echo $this->subject; ?>" />
		<?php } ?>
		<input type="hidden" name="option" value="com_fabrik" />
		<input type="hidden" name="controller" value="table.emailtable" />
		<input type="hidden" name="task" value="doemail" />
		<input type="hidden" name="tmpl" value="component" />
		<input type="hidden" name="renderOrder" value="<?php echo $this->renderOrder?>" />
		<input type="hidden" name="id" value="<?php echo $this->tableid ?>" />
		<input type="hidden" name="recordids" value="<?php echo $this->recordids ?>" />
	</form>
</div>
<?php if ($this->allowAttachment) {?>
<script type="text/javascript"><!--

function watchAttachements() {
	document.getElements('.addattachement').removeEvents();
	document.getElements('.delattachement').removeEvents();

	document.getElements('.addattachement').addEvent('click', function(e) {
		e = new Event(e).stop();
		// $$$ hugh - for some reason, findUp() doesn't exist for this
		// var li = $(e.target).findUp('li');
		var li = $(e.target).getParent().getParent();
		li.clone().injectAfter(li);
		watchAttachements();
	});

	document.getElements('.delattachement').addEvent('click', function(e) {
		e = new Event(e).stop();
		if(document.getElements('.addattachement').length > 1) {
			// $(e.target).findUp('li').remove();
			$(e.target).getParent().getParent().remove();
		}
		watchAttachements();
	});
}

window.addEvent('load', function() {
	watchAttachements();
});
--></script>
<?php }?>