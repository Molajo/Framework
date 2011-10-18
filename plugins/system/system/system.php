<?php
/**
 * @package     Molajo
 * @subpackage  Molajo System
 * @copyright   Copyright (C) 2011 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;


class plgMolajoSystem extends MolajoPlugin	{

    /**
     * plgMolajoSystem::MolajoOnAfterInitialise
     *
     * System Component Plugin activates tasks:
     *
     * 1: Cron
     * 2: Backups
     * 3: Bypass Offline Mode with Keyword
     *
     * @param	string		The context for the content passed to the plugin.
     * @param	object		The content object.
     * @param	object		The content params
     * @param	int		The 'page' number
     * @return	string
     * @since	1.6
     */
    function MolajoOnAfterInitialise ()
    {

        /** admin check **/
        $molajoSystemPlugin =& MolajoPluginHelper::getPlugin('system', 'molajo');
        $systemParams = new JParameter($molajoSystemPlugin->params);

        /** retrieve parameters for system plugin molajo library **/

        /** cron **/
        if (($systemParams->def('enable_cron', 0) == '1') && ($systemParams->def('cron_minutes', 1440) > 0)) {
            require_once dirname(__FILE__) . '/cron/driver.php';
            MolajoSystemCron::driver ();
        }

        /** backup **/
        if (($systemParams->def('enable_backup', 0) == '1') && ($systemParams->def('backup_days', 7) > 0)) {
            require_once dirname(__FILE__) . '/backup/driver.php';
            MolajoSystemBackup::driver ();
        }

        /** Offline Mode **/
        $config =& MolajoFactory::getConfig();

        if ($config->setValue('config.offline', 0) == 1) {
            if ($systemParams->def('enable_offline_bypass', '') == '') {
            } else {
                if (JRequest::getString('bypass', '') == '') {
                } else {
                    require_once dirname(__FILE__) . '/offline/driver.php';
                    MolajoSystemOffline::driver ();
                }
            }
        }

        /** Processing Complete **/
        return;
    }

    /**
     * plgMolajoSystem::MolajoOnAfterRender
     *
     * System Component Plugin that adds the Google Analytics Tracking to a Web page
     *
     * @param	none
     * @return	none
     * @since	1.6
     */
    function MolajoOnAfterRender()
    {
        /** admin check **/
        $app =& MolajoFactory::getApplication();
        if ($app->getName() == 'administrator') { return; }

        /** retrieve parameters for system plugin molajo library **/
        $molajoSystemPlugin =& MolajoPluginHelper::getPlugin('system', 'molajo');
        $systemParams = new JParameter($molajoSystemPlugin->params);

        /** meta - remove generated by meta **/
        if ($systemParams->def('remove_generator_tag', 0) == '1') {
            require_once dirname(__FILE__) . '/meta/driver.php';
            MolajoSystemMeta::driver ();
        }
    }
}