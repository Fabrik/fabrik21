<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * fabrik plugin installer adaptor
 */
class JInstallerFabrikPlugin extends JObject{

	/**
	 * Constructor
	 *
	 * @access	protected
	 * @param	object	$parent	Parent object [JInstaller instance]
	 * @return	void
	 * @since	1.5
	 */
	function __construct(&$parent)
	{
		$this->parent =& $parent;
	}

	function install() {

		// Get a database connector object
		$db =& $this->parent->getDBO();

		// Get the extension manifest object
		$manifest =& $this->parent->getManifest();
		$this->manifest =& $manifest->document;
		if (!is_object($manifest)) {
			return false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Manifest Document Setup Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// Set the component name
		$name =& $this->manifest->getElementByPath('name');

		//$files =& $this->manifest->getElementByPath('files');

		$this->set('label', $name->data());

		// Get the component description
		$description = & $this->manifest->getElementByPath('description');

		if (is_a($description, 'JSimpleXMLElement')) {
			$this->parent->set('message', $this->get('name').'<p>'.$description->data().'</p>');
		} else {
			$this->parent->set('message', $this->get('name'));
		}

		/*
		 * Backward Compatability
		 * @todo Deprecate in future version
		 */
		$type = $this->manifest->attributes('type');

		// Set the installation path
		$element =& $this->manifest->getElementByPath('files');
		if (is_a($element, 'JSimpleXMLElement') && count($element->children())) {
			$files =& $element->children();
			foreach ($files as $file) {
				if ($file->attributes($type)) {
					$pname = $file->attributes($type);
					$this->set('name', $pname);
					break;
				}
			}
		}
		$group = $this->manifest->attributes('group');

		$path = JPATH_ROOT.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.$group.DS.$this->get('name');

		if (!empty ($pname) && !empty($group)) {
			$this->parent->setPath('extension_root', $path);
		} else {
			$this->parent->abort('Plugin Install: '.JText::_('NO PLUGIN FILE SPECIFIED'));
			return false;
		}

		/*
		 * Let's run the install queries for the component
		 *	If backward compatibility is required - run queries in xml file
		 *	If Joomla 1.5 compatible, with discreet sql files - execute appropriate
		 *	file for utf-8 support or non-utf-8 support
		 */
		//test
		$this->parent->setPath('extension_administrator',  $this->parent->getPath('source'));
		$result = $this->parent->parseQueries($this->manifest->getElementByPath('install/queries'));
		if ($result === false) {
			// Install failed, rollback changes
			$this->parent->abort(JText::_('Plugin Install: ').': '.JText::_('SQL Error')." ".$db->stderr(true));
			return false;
		} elseif ($result === 0) {
			// no backward compatibility queries found - try for Joomla 1.5 type queries
			// second argument is the utf compatible version attribute
			$utfresult = $this->parent->parseSQLFiles($this->manifest->getElementByPath('install/sql'));
			if ($utfresult === false) {
				// Install failed, rollback changes
				$this->parent->abort(JText::_('Plugin Install: ').': '.JText::_('SQLERRORORFILE')." ".$db->stderr(true));
				return false;
			}
		}


		/**
		 * ---------------------------------------------------------------------------------------------
		 * Filesystem Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// If the plugin directory does not exist, lets create it

		$created = false;
		if (!file_exists($this->parent->getPath('extension_root')) && !$this->parent->getOverwrite()) {
			if (!$created = JFolder::create($this->parent->getPath('extension_root'))) {
				$this->parent->abort('Plugin Install: '.JText::_('FAILED TO CREATE DIRECTORY').': "'.$this->parent->getPath('extension_root').'"');
				return false;
			}
		}

		/*
		 * If we created the plugin directory and will want to remove it if we
		 * have to roll back the installation, lets add it to the installation
		 * step stack
		 */
		if ($created) {
			$this->parent->pushStep(array ('type' => 'folder', 'path' => $this->parent->getPath('extension_root')));
		}


		// Copy all necessary files
		if ($this->parent->parseFiles($element, -1) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		// Parse optional tags -- media and language files for plugins go in admin app
		$this->parent->parseMedia($this->manifest->getElementByPath('media'), 1);

		$this->parent->parseLanguages($this->manifest->getElementByPath('administration/languages'), 1);

		$this->parent->parseLanguages($this->manifest->getElementByPath('languages'));


		/**
		 * ---------------------------------------------------------------------------------------------
		 * Database Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */



		// Check to see if a plugin by the same name is already installed
		$query = 'SELECT `id`' .
				' FROM `#__fabrik_plugins`' .
				' WHERE type = '.$db->Quote($group) .
				' AND name = '.$db->Quote($pname);
		$db->setQuery($query);

		if (!$db->Query()) {
			// Install failed, roll back changes
			$this->parent->abort('Plugin Install: '.$db->stderr(true));
			return false;
		}
		$id = $db->loadResult();

		//

		// Was there a module already installed with the same name?
		if ($id && !$this->parent->getOverwrite()) {
			// Install failed, roll back changes
			$this->parent->abort('Plugin Install: '.JText::_('PLUGIN').' "'.$pname.'" '.JText::_('ALREADY EXISTS'));
			return false;
		} else {
			$row = JTable::getInstance('Plugin', 'Table');
			$row->id = $id;
			$row->name = $pname;
			$row->type = $group;
			$row->label = $this->get('label');

			$row->state = 1;
			$row->iscore = 0;

			if (!$row->store()) {
				// Install failed, roll back changes
				$this->parent->abort('Plugin Install: '.$db->stderr(true));
				return false;
			}

			// Since we have created a plugin item, we add it to the installation step stack
			// so that if we have to rollback the changes we can undo it.
			$this->parent->pushStep(array ('type' => 'plugin', 'id' => $row->id));
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Finalization and Cleanup Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// Lastly, we will copy the manifest file to its appropriate place.
		if (!$this->parent->copyManifest(-1)) {
			// Install failed, rollback changes
			$this->parent->abort('Plugin Install: '.JText::_('COULD NOT COPY SETUP FILE'));
			return false;
		}
		return true;
	}

	/**
	 * Custom uninstall method for components
	 *
	 * @access	public
	 * @param	int		$cid	The id of the component to uninstall
	 * @param	int		$clientId	The id of the client (unused)
	 * @return	mixed	Return value for uninstall method in component uninstall file
	 * @since	1.0
	 */
	function uninstall($id, $clientId)
	{
		// Initialize variables
		$db =& $this->parent->getDBO();
		$row	= null;
		$retval	= true;



		// First order of business will be to load the plugin object table from the database.
		// This should give us the necessary information to proceed.
		$row = JTable::getInstance('plugin', 'Table');
	  if (!$row->load((int) $id)) {
			JError::raiseWarning(100, JText::_('ERRORUNKOWNEXTENSION'));
			return false;
		}

		// Get the extension manifest object
		// where to look for xml files
		$this->parent->setPath('source', JPath::clean(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.$row->type.DS.$row->name));

		$manifest =& $this->parent->getManifest();
		$this->manifest =& $manifest->document;

		/*
		 * Let's run the uninstall queries for the component
		 *	If backward compatibility is required - run queries in xml file
		 *	If Joomla 1.5 compatible, with discreet sql files - execute appropriate
		 *	file for utf-8 support or non-utf support
		 */

		$result = $this->parent->parseQueries($this->manifest->getElementByPath('installation/queries'));

		if ($result === false) {
			// Install failed, rollback changes
			JError::raiseWarning(100, JText::_('Plugin Install: ').': '.JText::_('SQL Error')." ".$db->stderr(true));
			$retval = false;
		} elseif ($result === 0) {
			$this->parent->setPath('extension_administrator',  $this->parent->getPath('source'));
			// no backward compatibility queries found - try for Joomla 1.5 type queries
			// second argument is the utf compatible version attribute
			$utfresult = $this->parent->parseSQLFiles($this->manifest->getElementByPath('uninstall/sql'));
			if ($utfresult === false) {
				// Install failed, rollback changes
				JError::raiseWarning(100, JText::_('Plugin Install: ').': '.JText::_('SQLERRORORFILE')." ".$db->stderr(true));
				$retval = false;
			}
		}

		// Is the component we are trying to uninstall a core one?
		// Because that is not a good idea...
		if ($row->iscore) {
			JError::raiseWarning(100, JText::_('Component').' '.JText::_('Uninstall').': '.JText::sprintf('WARNCORECOMPONENT', $row->name)."<br />".JText::_('WARNCORECOMPONENT2'));
			return false;
		}
		// Get the admin and site paths for the component
		$this->parent->setPath('extension_site', JPath::clean(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.$row->type.DS.$row->name));

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Manifest Document Setup Section
		 * ---------------------------------------------------------------------------------------------
		 */
		JFolder::delete($this->parent->getPath('extension_site'));
		// Remove all languages and media as well
		$this->parent->removeFiles($this->manifest->getElementByPath('media'));
		$this->parent->removeFiles($this->manifest->getElementByPath('languages'));
		$this->parent->removeFiles($this->manifest->getElementByPath('administration/languages'), 1);

		$row->delete();

		// Remove the menu
		//@TODO remove visualziation menus

		return true;
	}
}
?>