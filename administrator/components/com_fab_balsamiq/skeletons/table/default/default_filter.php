<!-- replace this in component code -->
<?php $use = array({filters});?>
<div class="fabrikFilterContainer">
	<div class="filtertable fabrikTable">
		<div class="fabrik_clear">
			<?php echo $this->clearFliterLink;?>
		</div>
		<div class="fabrik_search_all">
			<?php if (array_key_exists('all', $this->filters)) {
			echo $this->filters['all']->element;
			}?>
		</div>

		<div class="fabrik_search">
			<?php if ($this->filter_action != 'onchange') {?>
				<input type="button" class="fabrik_filter_submit button" value="<?php echo JText::_('GO');?>" name="filter" />
			<?php }?>
		</div>

	<?php
	foreach ($this->filters as $key => $filter) {
		if (in_array($key, $use)) {
			$class = $filter->required == 1 ? ' notempty' : '';
		?>
		<div class="<?php echo $class . ' filter_' . $key.'_label'?> ">
			<?php echo $filter->label;?>
		</div>
		<div class="<?php echo $class . ' filter_' . $key?>">
			<?php echo $filter->element;?>
		</div>
	<?php }
	} ?>
	</div>
</div>