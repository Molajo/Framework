<?php
use Molajo\Service\Services;

/**
 *
 * @package    Molajo
 * @copyright  2012 Individual Molajo Contributors. All rights reserved.
 * @license    GNU GPL v 2, or later and MIT, see License folder
 */
defined('MOLAJO') or die;
$action = Services::Registry()->get('Plugindata', 'page_url'); ?>
<div class="grid-filters">
    <ol class="batch">
        <li><include:template name=formselectlist datalist=groups/></li>
        <li><input id="create" type="radio" value="create" name="permission"><label for="create"><?php echo Services::Language()->translate('Create'); ?></label></li>
        <li><input id="read" type="radio" value="read" name="permission"><label for="read"><?php echo Services::Language()->translate('Read'); ?></label></li>
        <li><input id="update" type="radio" value="update" name="permission"><label for="update"><?php echo Services::Language()->translate('Update'); ?></label></li>
        <li><input id="publish" type="radio" value="publish" name="permission"><label for="publish"><?php echo Services::Language()->translate('Publish'); ?></label></li>
        <li><input id="delete" type="radio" value="delete" name="permission"><label for="delete"><?php echo Services::Language()->translate('Delete'); ?></label></li>
        <li><input type="submit" class="submit button small" name="submit" id="AssignPermissions" value="Assign"></li>
        <li><input type="submit" class="submit button small" name="submit" id="RemovePermissions" value="Remove"></li>
    </ol>
</div>
