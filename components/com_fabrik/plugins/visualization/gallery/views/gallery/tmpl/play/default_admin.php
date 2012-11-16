<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->isOwner) { ?>
<div class="gallery-admin">
<h3><?php echo JText::_('Administration') ?></h3>
	<?php
	$user =& JFactory::getUser();
	  ?>
		<a class="button" href="index.php?option=com_fabrik&view=table&tableid=<?php echo $this->galleryTableId.'&'.$this->galleryOwnerField ?>=<?php echo $user->get('id') ?>&<?php echo $this->galleryKey ?>=_clear_">
			<?php echo JText::_('Manage Galleries');?>
		</a>
		<a class="button" href="index.php?option=com_fabrik&view=form&fabrik=<?php echo $this->galleryFormId ?>&tableid=<?php echo $this->galleryTableId ?>&rowid=<?php echo $this->gallery->__pk_val ?>">
			<?php echo JText::_('Edit gallery');?>
		</a>
				
			
		<?php foreach ($this->tables as $table) { ?>
			<h4><?php echo $table->label ?></h4>
			
			<a class="button" href="index.php?option=com_fabrik&view=table&tableid=<?php echo $table->image_table_id.'&'.$table->cat_id ?>=<?php echo $this->gallery->__pk_val ?>">
			<?php echo JText::_('Manage images');?>
			</a>
			
			<a class="button" href="index.php?option=com_fabrik&view=form&fabrik=<?php echo $table->image_form_id ?>&tableid=<?php echo $table->image_table_id.'&'.$table->cat_id ?>_raw=<?php echo $this->gallery->__pk_val ?>">
			<?php echo JText::_('Add Image');?>
			</a>
			
			
		<?php }?>	
		</div>
	<?php }?>
	
