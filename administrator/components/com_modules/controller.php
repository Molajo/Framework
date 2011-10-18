<?php
/**
 * @version		$Id: controller.php 20196 2011-01-09 02:40:25Z ian $
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * Modules manager master display controller.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_modules
 * * * @since		1.0
 */
class ModulesController extends JController
{
	/**
	 * Method to display a view.
	 *
	 * @param	boolean			If true, the view output will be cached
	 * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/modules.php';

		// Load the submenu.
		ModulesHelper::addSubmenu(JRequest::getCmd('view', 'modules'));

		$view		= JRequest::getCmd('view', 'modules');
		$layout 	= JRequest::getCmd('layout', 'default');
		$id			= JRequest::getInt('id');

		// Check for edit form.
		if ($view == 'module' && $layout == 'edit' && !$this->checkEditId('com_modules.edit.module', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(MolajoText::sprintf('MOLAJO_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(MolajoRoute::_('index.php?option=com_modules&view=modules', false));

			return false;
		}

		parent::display();
	}
}