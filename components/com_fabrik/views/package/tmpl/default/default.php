<?php
defined('_JEXEC') or die('Restricted access');
?>

<div class="fbPackageStatus"><img src="media/com_fabrik/images/ajax-loader.gif" alt="loading"/><span><?php echo JText::_('LOADING') ?></span></div>
<dl id="fabrik_package">
<?php
FabrikHelperHTML::script('tabs.js');
$i = 0;
foreach($this->blocks as $key => $block) {
	if( $i % 2  == 0) {
		echo "<dt>$key</dt><dd>";
	}
	echo "<div class='fabrik_block fabrik_block_col" . $i % 2 . "'>";
	echo $block;
	echo "</div>";
	if( $i % 2  != 0) {
		echo "<br style='clear:left' / ></dd>";
	}
	$i ++;
}
?>
</dl>

<script type="text/javascript">
window.addEvent('domready', function() {
 new JTabs('fabrik_package');
});
</script>
