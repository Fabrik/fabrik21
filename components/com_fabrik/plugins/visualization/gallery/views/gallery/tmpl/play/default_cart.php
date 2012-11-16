<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php 
if ($this->useCart) {
	$pp = $this->paypal ?>

 	
 	<form action="<?php echo $this->paypalurl;?>" method="post" id="cart-form">
	 	<div id="price-table">
	 	
	 	</div><!--
	 	<table id="price-table" style="width:100%">
		 	<thead>
		 		<tr>
		 			<th><?php echo JText::_('Title') ?></th>
		 			<th><?php echo JText::_('Price') ?></th>
		 			<th><?php echo JText::_('In stock') ?></th>
		 			<th><?php echo JText::_('Quantity') ?></th>
		 		</tr>
		 	</thead>
		 	<tbody>
		 	</tbody>
	 	</table>

	--><input type="hidden" name="cmd" value="_cart" />
	<input type="hidden" name="upload" value="1">
	
	<input type="hidden" name="currency_code" value="<?php echo $pp->currencyCode ?>" />
	<input type="hidden" name="return" value="<?php echo $pp->return ?>" />
	<input type="hidden" name="business" value="<?php echo $pp->businessemail ?>" />
	
	<input type="hidden" name="shipping" value="" />
	<!--  additional shipping costs per second item onwards -->
	<input type="hidden" name="shipping2" value="" />
	<input type="hidden" name="notify_url" value="<?php echo JRoute::_(JURI::base().'index.php?option=com_fabrik&view=visualization&subview=gallery&controller=visualization.gallery&task=ipn'); ?>" />
	<input type="image" name="submit" border="0" style="float:right"
	src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" 
	alt="PayPal - The safer, easier way to pay online" />
	
	</form>



<?php }else{ ?>
<div class='gallery-image-template'></div>
<?php }?>
