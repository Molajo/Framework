<?php
/**
 * @package     Molajo
 * @subpackage  Model
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

/**
 * Extensions
 *
 * @package     Molajo
 * @subpackage  Model
 * @since       1.0
 */
class MolajoExtensionsModel extends MolajoDisplayModel
{
    /**
     * __construct
     *
     * Constructor.
     *
     * @param  $id
     * @since  1.0
     */
    public function __construct($id = null)
    {
        $this->name = get_class($this);
        $this->table = '#__extensions';
        $this->primary_key = 'id';

        return parent::__construct($id);
    }
}
