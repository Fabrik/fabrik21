<?php if (count($this->groupheadings ) > 1) { ?>
<tr class="fabrik___heading">
<?php
$t = 0;
  foreach ($this->groupheadings as $label=>$colspan) {
  $t += $colspan?>
	<th colspan="<?php echo $colspan;?>"><?php echo $label; ?></th>
	<?php }
	$t ++;
	if($t < count($this->headings)) {?>
	<th colspan="<?php echo count($this->headings) - count($this->groupheadings)?>"></th>
	<?php }?>
</tr>
<?php } ?>
<tr class="fabrik___heading">
<?php foreach ($this->headings as $key=>$heading) {?>
	<th <?php echo $this->headingClass[$key]?>><?php echo $heading; ?></th>
	<?php }?>
</tr>
<?php 	if ($this->showFilters) {?>
<tr class="fabrik___heading">
<?php
$this->found_filters = array();
foreach ($this->headings as $key=>$heading) {?>
	<th><?php $filter = JArrayHelper::getValue($this->filters, $key, null);
	if(!is_null($filter)) {
		$this->found_filters[] = $key;
		echo $filter->element;
	} ?></th>
	<?php }?>
</tr>
<?php } ?>