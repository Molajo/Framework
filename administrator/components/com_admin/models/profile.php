<?php
/**
 * @version		$Id: profile.php 21097 2011-04-07 15:38:03Z dextercowley $
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_users/models/user.php';

/**
 * User model.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_admin
 * * * @since		1.0
 */
class AdminModelProfile extends UsersModelUser
{
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = MolajoFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_admin.profile', 'profile', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = MolajoFactory::getApplication()->getUserState('com_users.edit.user.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}

		// TODO: Maybe this can go into the parent model somehow?
		// Get the dispatcher and load the users plugins.
		$dispatcher	= JDispatcher::getInstance();
		MolajoPluginHelper::importPlugin('user');

		// Trigger the data preparation event.
		$results = $dispatcher->trigger('onContentPrepareData', array('com_admin.profile', $data));

		// Check for errors encountered while preparing the data.
		if (count($results) && in_array(false, $results, true)) {
			$this->setError($dispatcher->getError());
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @return	mixed	Object on success, false on failure.
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		$user = MolajoFactory::getUser();

		return parent::getItem($user->get('id'));
	}

	/**
	 * Method to save the form data.
	 *
	 * @param	array	$data	The form data.
	 *
	 * @return	boolean	True on success.
	 * @since	1.6
	 */
	public function save($data)
	{
		// Initialise variables;
		$user = MolajoFactory::getUser();

		unset($data['id']);
		unset($data['groups']);
		unset($data['sendEmail']);
		unset($data['block']);

		// Bind the data.
		if (!$user->bind($data)) {
			$this->setError($user->getError());
			return false;
		}

		$user->groups = null;

		// Store the data.
		if (!$user->save()) {
			$this->setError($user->getError());
			return false;
		}

		$this->setState('user.id', $user->id);

		return true;
	}
}