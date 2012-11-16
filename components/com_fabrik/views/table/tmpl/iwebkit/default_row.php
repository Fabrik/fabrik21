<?php
defined('_JEXEC') or die('Restricted access');
?>

<li id="<?php echo $this->_row->id;?>"
	class="withimage store <?php echo $this->_row->class;?>"><a
	href="<?php echo $this->_row->data->fabrik_view_url;?>"
	class="noeffect"> <?php foreach ($this->headings as $heading=>$label) {
		$d =$this->_row->data->$heading;
		if (JString::stristr($d, 'src='))	{
			$rawheading = $heading . "_raw";
			if (!in_array($this->_row->data->$rawheading, array('0', '1'))) {
				//get the thumbnails url - cant use rawheading as that contains the path to the
				// full size image
				$bgimg = FabrikString::ltrimword(str_replace("\\", "/", $this->_row->data->$rawheading), '/');
				$str = "<xml>".$this->_row->data->$heading."</xml>";
				$xmlDoc = & JFactory::getXMLParser( 'DOM', array('lite'=>false));
				$xmlDoc->resolveErrors(true);
				$ok =	$xmlDoc->parseXML($str, false);
				if ($ok) {
					$imgs =& $xmlDoc->getElementsByTagName('img');
					$bgimg = str_replace(COM_FABRIK_LIVESITE, '', $imgs->item(0)->getAttribute('src'));
				}
				?>
				<span class="image" style="background-image: url(<?php echo $bgimg?>);"></span>
				<?php break;
			}
		}
	}?> <?php foreach ($this->headings as $heading=>$label) {
		$d = $this->_row->data->$heading;
		if (JString::stristr($d, 'href='))	{
			$heading.= "_raw";?> <span class="name"><?php echo $this->_row->data->$heading;?></span>
			<?php
			break;
		}
	}?> <span class="arrow"></span> </a></li>
