<?php
use Molajo\Service\Services;

/**
 * @package    Molajo
 * @copyright  2012 Individual Molajo Contributors. All rights reserved.
 * @license    GNU GPL v 2, or later and MIT, see License folder
 */
defined('MOLAJO') or die; ?>
<label for="<?php echo $this->row->field_id; ?>"><?php echo $this->row->label; ?></label>
 <select id="<?php echo $this->row->field_id; ?>" name="<?php echo $this->row->name; ?>"<?php echo $this->row->multiple; ?> <?php echo $this->row->required; ?> <?php echo $this->row->disabled; ?> <?php echo $this->row->size; ?>>
<?php if ($this->row->multiple == '') { ?>
	<option value=""><?php echo Services::Language()->translate('SELECT_' . strtoupper($this->row->datalist)); ?></option>
	<?php } else { ?>
	<option value=""><?php echo Services::Language()->translate('No selection'); ?></option>
<?php }
