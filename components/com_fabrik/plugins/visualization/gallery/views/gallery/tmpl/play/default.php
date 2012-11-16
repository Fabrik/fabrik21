<?php
defined('_JEXEC') or die('Restricted access');
?>
<div id="tableform_19">
<div class="gallery" id="<?php echo $this->containerId;?>"><?php echo $this->loadTemplate('filter'); ?>

<?php
$border = "<div style='margin-bottom:15px;clear:left;border-top:1px solid #9da4a9'></div>";
if (empty($this->images)) {
	echo "<div class='empty'>".JText::_('There are no images in this gallery') . "</div>";

} else {
	FabrikHelperHTML::slimbox();
	reset($this->images);
	$i = current($this->images[0]);
	$ratings = array();
	foreach($this->ratings as $r) {
		$ratings[$r->rowid]['html'] = $r->html;
		$ratings[$r->rowid]['id'] = $r->id;
	}

	//$t = strstr(JURI::base(), 'devplay') ? 'table_18' : 'table_19';
	$t = 'table_19';
	$filters = JArrayHelper::getValue($_POST, 'fabrik_filter', array());
	$fvals = empty($filters) ? array() : $filters[$t]['value'];
	if( is_array($fvals) && !empty($fvals)) {
		if(@$fvals[0][0] == '' && @$fvals[0][1] == '' && @$fvals[2] == '' && @$fvals[1] == '') {
			$nofilter = true;
		}else{
			$nofilter = false;
		}
	}else{
		$nofilter = true;
	}
	$class = '';
	?>

<div class="left">
<div class="mask">
<div class="thumbnails"><?php
//echo "<pre>";print_r($this->images);echo "</pre>";
$c = 1;
$curDate = '';
$now =& JFactory::getDate();
$unixNow = $now->toUnix();
$yesttodayimages = array('Yesterday' => 0, 'Today' => 0);

$dayImages = array();
foreach($this->images as $row) {

	foreach ($row as $img) {
		$opts = json_decode($img->jos_fabble_activity___params);
		//echo "<pre>";print_r($img);echo "</pre>";
		$d = JFactory::getDate($img->jos_fabble_activity___create_date_raw);

		if($opts->latest_player != 1) {
			//	$timeHeld = JFactory::getDate($opts->time_since_last_mms);
			$dateDiff = $opts->time_since_last_mms;
		} else {

			$now = JFactory::getDate();
			$dateDiff= $now->toUnix() - $d->toUnix();
			//$timeHeld = JFactory::getDate($now->toUnix() - $d->toUnix());
		}
		/*	$daysHeld = $timeHeld->toFormat('%d');
		 $sTimeHeld = '';
		 if ($daysHeld > 0) {
		 $sTimeHeld .= $daysHeld;
		 $sTimeHeld .= $daysHeld == 1 ? ' day' : ' days';
		 }
		 $sTimeHeld .= $timeHeld->toFormat(' %H hours %M minutes %S seconds');*/


		$fullDays = floor($dateDiff/(60*60*24));
		$fullHours = floor(($dateDiff-($fullDays*60*60*24))/(60*60));
		$fullMinutes = floor(($dateDiff-($fullDays*60*60*24)-($fullHours*60*60))/60);
		$sTimeHeld = "\n$fullDays days, $fullHours hours, $fullMinutes mins<br />";
		$unixD = $d->toUnix();
		$dateDiff = $unixNow - $unixD;
		$fullDays = floor($dateDiff/(60*60*24));
		switch($fullDays) {
			case 0:
				$d = 'Today';
				break;
			case 1:
				$d = 'Yesterday';
				break;
			default:
				$d = $d->toFormat('%d %B');
				break;
		}
		if($nofilter && ($d != 'Today' && $d !='Yesterday')) {
			continue;
		}
		$yesttodayimages[$d] = $yesttodayimages[$d] +1;

		if ($curDate != $img->jos_fabble_activity___create_date) {

			//if($curDate !== '') {
			//write out old days
			//echo $border;//"<div style='margin-bottom:15px;clear:left;border-top:1px solid #9da4a9'></div>";
			//}
			echo "<div class=\"imagerow\">".implode("\n", $dayImages ) . "</div>";
			$dayImages = array();

			$curDate = $img->jos_fabble_activity___create_date;
			echo $border."<h2 style=\"clear:left\">". $d . "</h2>\n";

		}
		$str = "<div class=\"imagecont $class\">\n";
		$str .= "<div class=\"imagediv\" style=\"width:{$this->maxImageWidth}px;height:{$this->maxImageHeight}px;margin:auto;\">\n";
		$str .= "<div style='margin:auto;width:{$img->width}px;height:{$this->maxImageHeight}px;position:relative;'>\n";
		$str .= str_replace('lightbox[]', 'lightbox['.$d.']', $img->image);
		$str .= "</div>";
		$str .= "</div>";
		$str .= "<div class='image_creator'>$img->jos_fabble_activity___user_id </div>\n";
		$str .= '<div class="fabrik_row fabrik_row___'.$ratings[$img->rowid]['id'].'" id="row_'.$img->rowid.'">';
		$str .= $ratings[$img->rowid]['html'];
		$str .= '</div>';

		$activePlayer = $opts->latest_player ? 'latestplayer': '';
		$str .= "<div class=\"$activePlayer\">active: $opts->latest_player;</div>\n";
		$str .= "<div class=\"timeheld\">$sTimeHeld</div>";
		$str .= "</div>";
		array_unshift($dayImages, $str);
	}
}
//	echo $border;//"<div style='margin-bottom:15px;clear:left;border-top:1px solid #9da4a9'></div>";

echo "<div class=\"imagerow\">".implode(' ', $dayImages ) . "</div>\n";
if($nofilter) {
	if($yesttodayimages['Today'] == 0) {
		echo $border . "<h2 style='clear:left'>Today</h2>";
		echo "<div class='empty'>No images</div>";
	}
	if($yesttodayimages['Yesterday'] == 0) {
		echo $border . "<h2 style='clear:left'>Yesterday</h2>";
		echo "<div class='empty'>No images</div>";
	}
}
?></div>
</div>

</div>

<div class="right" style="width:<?php echo $this->maxLargeImageWidth ?>px;height:<?php echo $this->maxLargeImageHeight ?>px;border:1px solid #eee;">
<div id="mainimage-container"><img id="mainimage"
	src="<?php echo $i->image_large ?>" alt="<?php echo $i->label?>" title="<?php echo $i->label?>" />
<div class="gallery-image-rating"><?php $style ='';
foreach($this->ratings as $r) {
	?>
<div style="<?php echo $style?>" class="fabrik_row fabrik_row___<?php echo $r->id?>" id="row_<?php echo $r->rowid?>">
	<?php $style = 'display:none';
	echo $r->html;?></div>
	<?php }?></div>
</div>
</div>
<div style="clear: left; padding-top: 20px;"><?php echo $this->loadTemplate('cart') ?>
</div>
	<?php } ?></div>
</div>
	<?php echo $this->loadTemplate('admin') ?>
