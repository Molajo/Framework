<?php
/**
 * @package     Joomla.Platform
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class working with menu select lists
 *
 * @package     Joomla.Platform
 * @subpackage  HTML
 * @since       11.1
 */
abstract class MolajoHtmlMenu
{
	/**
	 * Cached array of the menus.
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected static $menus = null;

	/**
	 * Cached array of the menus items.
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected static $items = null;

	/**
	 * Get a list of the available menus.
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	public static function menus()
	{
		if (empty(self::$menus))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('menutype AS value, title AS text');
			$query->from($db->quoteName('#__menu_types'));
			$query->order('title');
			$db->setQuery($query);
			self::$menus = $db->loadObjectList();
		}

		return self::$menus;
	}

	/**
	 * Returns an array of menu items grouped by menu.
	 *
	 * @param   array  $config  An array of configuration options.
	 *
	 * @return  array
	 */
	public static function menuitems($config = array())
	{
		if (empty(self::$items))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('menutype AS value, title AS text');
			$query->from($db->quoteName('#__menu_types'));
			$query->order('title');
			$db->setQuery($query);
			$menus = $db->loadObjectList();

			$query->clear();
			$query->select('a.id AS value, a.title AS text, a.level, a.menutype');
			$query->from('#__menu AS a');
			$query->where('a.parent_id > 0');
			$query->where('a.type <> ' . $db->quote('url'));
			$query->where('a.client_id = 0');

			// Filter on the published state
			if (isset($config['published']))
			{
				if (is_numeric($config['published']))
				{
					$query->where('a.published = ' . (int) $config['published']);
				}
				elseif ($config['published'] === '')
				{
					$query->where('a.published IN (0,1)');
				}
			}

			$query->order('a.lft');

			$db->setQuery($query);
			$items = $db->loadObjectList();

			// Collate menu items based on menutype
			$lookup = array();
			foreach ($items as &$item)
			{
				if (!isset($lookup[$item->menutype]))
				{
					$lookup[$item->menutype] = array();
				}
				$lookup[$item->menutype][] = &$item;

				$item->text = str_repeat('- ', $item->level) . $item->text;
			}
			self::$items = array();

			foreach ($menus as &$menu)
			{
				// Start group:
				self::$items[] = MolajoHTML::_('select.optgroup', $menu->text);

				// Special "Add to this Menu" option:
				self::$items[] = MolajoHTML::_('select.option', $menu->value . '.1', MolajoText::_('JLIB_HTML_ADD_TO_THIS_MENU'));

				// Menu items:
				if (isset($lookup[$menu->value]))
				{
					foreach ($lookup[$menu->value] as &$item)
					{
						self::$items[] = MolajoHTML::_('select.option', $menu->value . '.' . $item->value, $item->text);
					}
				}

				// Finish group:
				self::$items[] = MolajoHTML::_('select.optgroup', $menu->text);
			}
		}

		return self::$items;
	}

	/**
	 * Displays an HTML select list of menu items.
	 *
	 * @param   string  $name      The name of the control.
	 * @param   string  $selected  The value of the selected option.
	 * @param   string  $attribs   Attributes for the control.
	 * @param   array   $config    An array of options for the control.
	 *
	 * @return  string
	 */
	public static function menuitemlist($name, $selected = null, $attribs = null, $config = array())
	{
		static $count;

		$options = self::menuitems($config);

		return MolajoHTML::_(
			'select.genericlist', $options, $name,
			array(
				'id' => isset($config['id']) ? $config['id'] : 'assetgroups_' . ++$count,
				'list.attr' => (is_null($attribs) ? 'class="inputbox" size="1"' : $attribs),
				'list.select' => (int) $selected,
				'list.translate' => false
			)
		);
	}

	/**
	 * Build the select list for Menu Ordering
	 *
	 * @param   object   &$row  The row object
	 * @param   integer  $id    The id for the row. Must exist to enable menu ordering
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	public static function ordering(&$row, $id)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		if ($id)
		{
			$query->select('ordering AS value, title AS text');
			$query->from($db->quoteName('#__menu'));
			$query->where($db->quoteName('menutype').' = ' . $db->quote($row->menutype));
			$query->where($db->quoteName('parent_id').' = ' . (int) $row->parent_id);
			$query->where($db->quoteName('published').' != -2');
			$query->order('ordering');
			$order = MolajoHTML::_('list.genericordering', $query);
			$ordering = MolajoHTML::_(
				'select.genericlist', $order, 'ordering',
				array('list.attr' => 'class="inputbox" size="1"', 'list.select' => intval($row->ordering))
			);
		}
		else
		{
			$ordering = '<input type="hidden" name="ordering" value="' . $row->ordering . '" />' . MolajoText::_('JGLOBAL_NEWITEMSLAST_DESC');
		}

		return $ordering;
	}

	/**
	 * Build the multiple select list for Menu Links/Pages
	 *
	 * @param   boolean  $all         True if all can be selected
	 * @param   boolean  $unassigned  True if unassigned can be selected
	 *
	 * @return  string
	 *
	 * @since   11.1
	 */
	public static function linkoptions($all = false, $unassigned = false)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// get a list of the menu items
		$query->select('m.id, m.parent_id, m.title, m.menutype');
		$query->from($db->quoteName('#__menu').' AS m');
		$query->where($db->quoteName('mpublished').' = 1');
		$query->order('m.menutype, m.parent_id, m.ordering');
		$db->setQuery($query);

		$mitems = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			MolajoError::raiseNotice(500, $db->getErrorMsg());
		}

		if (!$mitems)
		{
			$mitems = array();
		}

		$mitems_temp = $mitems;

		// Establish the hierarchy of the menu
		$children = array();
		// First pass - collect children
		foreach ($mitems as $v)
		{
			$id = $v->id;
			$pt = $v->parent_id;
			$list = @$children[$pt] ? $children[$pt] : array();
			array_push($list, $v);
			$children[$pt] = $list;
		}
		// Second pass - get an indent list of the items
		$list = MolajoHTMLMenu::TreeRecurse(intval($mitems[0]->parent_id), '', array(), $children, 9999, 0, 0);

		// Code that adds menu name to Display of Page(s)
		$mitems_spacer = $mitems_temp[0]->menutype;

		$mitems = array();
		if ($all | $unassigned)
		{
			$mitems[] = MolajoHTML::_('select.option', '<OPTGROUP>', MolajoText::_('JOPTION_MENUS'));

			if ($all)
			{
				$mitems[] = MolajoHTML::_('select.option', 0, MolajoText::_('JALL'));
			}
			if ($unassigned)
			{
				$mitems[] = MolajoHTML::_('select.option', -1, MolajoText::_('JOPTION_UNASSIGNED'));
			}

			$mitems[] = MolajoHTML::_('select.option', '</OPTGROUP>');
		}

		$lastMenuType = null;
		$tmpMenuType = null;
		foreach ($list as $list_a)
		{
			if ($list_a->menutype != $lastMenuType)
			{
				if ($tmpMenuType)
				{
					$mitems[] = MolajoHTML::_('select.option', '</OPTGROUP>');
				}
				$mitems[] = MolajoHTML::_('select.option', '<OPTGROUP>', $list_a->menutype);
				$lastMenuType = $list_a->menutype;
				$tmpMenuType = $list_a->menutype;
			}

			$mitems[] = MolajoHTML::_('select.option', $list_a->id, $list_a->title);
		}
		if ($lastMenuType !== null)
		{
			$mitems[] = MolajoHTML::_('select.option', '</OPTGROUP>');
		}

		return $mitems;
	}

	/**
	 * Build the list representing the menu tree
	 *
	 * @param   integer  $id         Id of the menu item
	 * @param   string   $indent     The indentation string
	 * @param   array    $list       The list to process
	 * @param   array    &$children  The children of the current item
	 * @param   integer  $maxlevel   The maximum number of levels in the tree
	 * @param   integer  $level      The starting level
	 * @param   string   $type       Type of link: component, URL, alias, separator
	 *
	 * @return  array
	 *
	 * @since   11.1
	 */
	public static function treerecurse($id, $indent, $list, &$children, $maxlevel = 9999, $level = 0, $type = 1)
	{
		if (@$children[$id] && $level <= $maxlevel)
		{
			foreach ($children[$id] as $v)
			{
				$id = $v->id;

				if ($type)
				{
					$pre = '<sup>|_</sup>&#160;';
					$spacer = '.&#160;&#160;&#160;&#160;&#160;&#160;';
				}
				else
				{
					$pre = '- ';
					$spacer = '&#160;&#160;';
				}

				if ($v->parent_id == 0)
				{
					$txt = $v->title;
				}
				else
				{
					$txt = $pre . $v->title;
				}
				$pt = $v->parent_id;
				$list[$id] = $v;
				$list[$id]->treename = "$indent$txt";
				$list[$id]->children = count(@$children[$id]);
				$list = MolajoHTMLMenu::TreeRecurse($id, $indent . $spacer, $list, $children, $maxlevel, $level + 1, $type);
			}
		}
		return $list;
	}
}
