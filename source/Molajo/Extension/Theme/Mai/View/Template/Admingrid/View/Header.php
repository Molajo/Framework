<?php
use Molajo\Service\Services;
/**
 * @package    Molajo
 * @copyright  2012 Amy Stephen. All rights reserved.
 * @license    GNU GPL v 2, or later and MIT, see License folder
 */
defined('MOLAJO') or die;

$pageUri = $_SERVER['REQUEST_URI'];

$nowrap = '';
$checked = '';
$rowCount = Services::Registry()->get('Triggerdata', 'AdminGridTableRows');
$columnArray = Services::Registry()->get('Triggerdata', 'AdminGridTableColumns');
$numCols = count($columnArray);
?>
            <h1>Articles</h1>

            <include:template name=Admingridpagination value=AdminGridPagination/>

<?php // This needs to be a template ?>
            <dl id="table_config">
                <dt><a href="<?php echo $pageUri ?>#table_config"><i>a</i><span>Configure Table Columns</span></a></dt>
                <dd>
                    <a href="<?php echo $pageUri ?>#articles" class="dismiss"><i>g</i><span>Close</span></a>
                    <table>
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Show</th>
                                <th>Use as Filter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($columnArray as $column => $data): ?>
                            <tr>
                                <td><?php echo $data ?></td>
                                <td><select><option value="1"<?php if($data['show']): ?> selected="selected"<?php endif ?>>Yes</option><option value="0"<?php if(!$data['show']): ?> selected="selected"<?php endif ?>>No</option></select></td>
                                <td><select><option value="1"<?php if($data['filter']): ?> selected="selected"<?php endif ?>>Yes</option><option value="0"<?php if(!$data['filter']): ?> selected="selected"<?php endif ?>>No</option></select></td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                    <button>Apply</button>
                </dd>
            </dl>

<table class="responsive">
    <thead>
        <tr>
            <?php
            $count = 1;
            foreach ($columnArray as $column) {
                $extraClass = '';
                if ($count == 1) {
                    $extraClass .= 'first';
                }
                if ($count == count($columnArray)) {
                    $extraClass .= 'last';
                }
                if ($extraClass == '') {
                } else {
                    $extraClass = ' class="' . trim($extraClass) . '"';
                }
                ?>
                <th<?php echo $extraClass . ' ' .  $nowrap; ?>><?php echo Services::Language()->translate('GRID_' . strtoupper($column) . '_COLUMN_HEADING'); ?></th>
                <?php
                $count++;
            } ?>
            <th width="1%">
                <input type="checkbox" class="checkall"><?php echo Services::Language()->translate('GRID_CHECK_ALL'); ?>
            </th>
        </tr>
        <tr id="batch-actions">
            <th colspan="<?php echo $numCols + 1 ?>">
                With selected: <select id="batch-options"><option>Enable</option><option>Disable</option><option>Archive</option><option>Delete</option><option value="more">More options...</option></select>
                <a href="<?php echo $pageUri ?>#articles" class="dismiss"><i>g</i><span>Close</span></a>
                <dl>
                    <dt><a href="#"><?php echo Services::Language()->translate('Filters'); ?></a></dt>
                    <dd><include:template name=Admingridfilters/></dd>
                    <dt><a href="#"><?php echo Services::Language()->translate('Batch'); ?></a></dt>
                    <dd><include:template name=Admingridbatch/></dd>
                    <dt><a href="#"><?php echo Services::Language()->translate('View'); ?></a></dt>
                    <dd><include:template name=Admingridview/></dd>
                    <dt><a href="#"><?php echo Services::Language()->translate('Options'); ?></a></dt>
                    <dd><include:template name=Admingridoptions/></dd>
                </dl>
            </th>
        </tr>
    </thead>
    <tbody>
