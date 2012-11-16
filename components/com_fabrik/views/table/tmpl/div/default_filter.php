<div class="fabrikFilterContainer">
<dl class="filtertable fabrikTable">
		<dt style="text-align:left"><?php echo JText::_('SEARCH');?>:</dt>
		<dd style="text-align:right"><?php echo $this->clearFliterLink;?></dd>
	<?php
	$c = 0;
	foreach ($this->filters as $filter) {
		$class = 'fabrik_row oddRow' . ($c % 2);
		$class .= $filter->required == 1 ? ' notempty' : '';?>
		<dt class='<?php echo $class ?>'><?php echo $filter->label;?></dt>
		<dd class='<?php echo $class ?>' style="text-align:right;"><?php echo $filter->element;?></dd>
	<?php $c ++;
	} ?>
	<?php if ($this->filter_action != 'onchange') {?>
	<dt  class="fabrik_row oddRow<?php echo $c % 2;?>"></dt>
		<dd style="text-align:right;">
		<input type="button" class="fabrik_filter_submit button" value="<?php echo JText::_('GO');?>"
			name="filter" />
		</dd>
	<?php }?>
</dl>
</div>