<?php
/**
 * @package     Molajo
 * @subpackage  Helper
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('MOLAJO') or die;

/**
 * Content
 *
 * @package     Molajo
 * @subpackage  Helper
 * @since       1.0
 */
abstract class MolajoContentHelper
{
    /**
     * get
     *
     * Get the content data for the id specified
     *
     * @return  mixed    An object containing an array of data
     * @since   1.0
     */
    static public function get($id, $content_table)
    {
        $m = new MolajoDisplayModel();

        $m->query->select('a.*');
        $m->query->from('#__content as a ');
        $m->query->where('a.' . $m->db->nq('id') . ' = ' . (int)$id);
        $m->query->where('a.' . $m->db->nq('status') .
            ' > ' . MOLAJO_STATUS_UNPUBLISHED);

        $m->query->where('(a.start_publishing_datetime = ' .
                $m->db->q($m->nullDate) .
                ' OR a.start_publishing_datetime <= ' .
                $m->db->q($m->now) . ')'
        );
        $m->query->where('(a.stop_publishing_datetime = ' .
                $m->db->q($m->nullDate) .
                ' OR a.stop_publishing_datetime >= ' .
                $m->db->q($m->now) . ')'
        );

        /** Assets Join and View Access Check */
        MolajoAccessService::setQueryViewAccess(
            $m->query,
            array('join_to_prefix' => 'a',
                'join_to_primary_key' => 'id',
                'asset_prefix' => 'b_assets',
                'select' => true
            )
        );

        //$m->db->setQuery($m->query->__toString());
        $rows = $m->runQuery();

        if (count($rows) == 0) {
            return array();
        }

        foreach ($rows as $row) {
        }

        return $row;
    }
}
