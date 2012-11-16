<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

class FabrikViewPlugin {

	/**
	 * set up the menu when viewing the list of  plugins
	 */

	function setPluginsToolbar()
	{
		JToolBarHelper::title(JText::_('PLUGINS'), 'plugin.png');
		JToolBarHelper::custom( 'uninstallPlugin', 'delete.png', 'delete_f2.png', 'Uninstall');
	}

	function installPlugin()
	{
		?>
<form enctype="multipart/form-data" action="index.php" method="post" name="filename">
<table class="adminheading">
	<tr>
		<th class="install"></th>
	</tr>
</table>
<table class="adminform">
	<tr>
		<th><?php echo JText::_('UPLOAD');?></th>
	</tr>
	<tr>
		<td align="left"><?php echo JText::_('PLUGIN');?>: <input
			class="text_area" name="userfile" type="file" size="70" /> <input
			class="button" type="submit"
			value="<?php echo JText::_('UPLOAD FILE AND INSTALL');?>" /></td>
	</tr>
</table>
<input type="hidden" name="option" value="com_fabrik" /> 
<input type="hidden" name="c" value="plugin" />
<input type="hidden" name="task" value="doimportPlugin" />
</form>
		<?php
}

/**
 * Display all available plugins
 * @param array array of plugin_rule objects
 * @param object page navigation
 */

function show($rows, $pageNav, $lists )
{
	FabrikViewPlugin::setPluginsToolbar();
	$user	  = &JFactory::getUser();
	$n=count($rows);
	FabrikViewPlugin::installPlugin();
	?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
<table class="adminform">
	<tr>
		<td><?php
		echo JText::_('TYPE');
		echo $lists['type'];
		?></td>
	</tr>
</table>

<table class="adminlist">
	<thead>
		<tr>
			<th width="1%"></th>
			<th width="1%">&nbsp;</th>
			<th width="28%"><?php echo JHTML::_('grid.sort',  'Name', 'name', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
			<th width="30%"><?php echo JHTML::_('grid.sort',  'Label', 'label', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
			<th width="35%"><?php echo JHTML::_('grid.sort',  'Type', 'type', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
			<th width="10%"><?php echo JHTML::_('grid.sort',  'Core', 'iscore', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
			<th width="5%"><?php echo JHTML::_('grid.sort',  'Published', 'state', @$lists['order_Dir'], @$lists['order']); ?>
			</th>
		</tr>
	</thead>
		<tfoot>
		<tr>
			<td colspan="7"><?php echo $pageNav->getListFooter(); ?></td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$k = 0;
	for ( $i = 0, $n = count($rows); $i < $n; $i ++) {
		$row = & $rows[$i];
		$link 	= JRoute::_('index.php?option=com_fabrik&c=plugin&task=edit&cid='. $row->id);
		$checked		= JHTML::_('grid.checkedout',   $row, $i);
		$row->published = $row->state;
		$published		= JHTML::_('grid.published', $row, $i);
			
		if ($row->iscore) {
			$row->cbd		= 'disabled';
			$row->style	= 'style="color:#999999;"';
			$row->img = 'images/tick.png';
		} else {
			$row->cbd		= null;
			$row->style	= null;
			$row->img = 'images/publish_x.png';
		}

		?>
	<tr class="<?php echo "row$k"; ?>" <?php echo $row->style ?>>
		<td width="1%"><?php echo $row->id; ?></td>
		<td><input type="radio" id="cb<?php echo $row->id;?>" name="eid"
			value="<?php echo $row->id; ?>" onclick="isChecked(this.checked);"
			<?php echo $row->cbd; ?> />
		</td>
		<td width="28%"><?php
		if ($row->checked_out && ( $row->checked_out != $user->get('id'))) {
			echo $row->name;
		} else {
			echo $row->name;
		}
		?></td>
		<td width="30%"><?php echo $row->label;?></td>
		<td width="20%"><?php echo $row->type;?></td>
		<td width="10%"><img src="<?php echo $row->img;?>"
			alt="<?php echo $row->iscore;?>" /></td>
		<td width="5%"><?php echo $published;?></td>
	</tr>
	<?php $k = 1 - $k;
} ?>
	</tbody>
</table>
<input type="hidden" name="option" value="com_fabrik" /> <input
	type="hidden" name="c" value="plugin" /> <input type="hidden"
	name="boxchecked" value="0" /> <input type="hidden" name="task"
	value="" /> <input type="hidden" name="filter_order"
	value="<?php echo $lists['order']; ?>" /> <input type="hidden"
	name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" /> <?php echo JHTML::_('form.token'); ?>
</form>
<?php }
}
?>