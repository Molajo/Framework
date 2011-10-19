<?php
/**
 * @package     Molajo
 * @subpackage  Layouts
 * @copyright   Copyright (C) 2011 Chris Rault. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;
var_dump($this->setup);
?>
<div class="inner">
    <h2>Site Information</h2>
    <p>Enter your site information. All fields marked with a are required.</p>

    <form action="<?php echo JUri::current() ?>" method="post">

        <input type="hidden" name="language"       value="<?php echo $this->setup['language'] ?>">
        <input type="hidden" name="sitename"       value="<?php echo $this->setup['sitename'] ?>">
        <input type="hidden" name="name"           value="<?php echo $this->setup['name'] ?>">
        <input type="hidden" name="admin_email"    value="<?php echo $this->setup['admin_email'] ?>">
        <input type="hidden" name="admin_password" value="<?php echo $this->setup['admin_password'] ?>">
        <input type="hidden" name="hostname"       value="<?php echo $this->setup['hostname'] ?>">
        <input type="hidden" name="db_scheme"      value="<?php echo $this->setup['db_scheme'] ?>">
        <input type="hidden" name="db_username"    value="<?php echo $this->setup['db_username'] ?>">
        <input type="hidden" name="db_password"    value="<?php echo $this->setup['db_password'] ?>">
        <input type="hidden" name="db_prefix"      value="<?php echo $this->setup['db_prefix'] ?>">
        <input type="hidden" name="db_type"        value="<?php echo $this->setup['db_type'] ?>">
        <input type="hidden" name="remove_tables"  value="<?php echo $this->setup['remove_tables'] ?>">
        <input type="hidden" name="install_sample" value="<?php echo $this->setup['install_sample'] ?>">

        <ol class="list-reset forms">
            <li>
                <span class="inner-wrap">
                    <label for="sitename" class="inlined">Site name</label>
                    <input type="text" class="input-text" id="sitename" name="sitename" title="Site name" value="<?php echo $this->setup['sitename'] ?>" />
                    <span class="note">Your site name.</span>
                </span>
            </li>
            <li>
                <span class="inner-wrap">
                    <label for="name" class="inlined">Your real name</label>
                    <input type="text" class="input-text" id="name" name="name" title="Your real name" value="<?php echo $this->setup['name'] ?>" />
                    <span class="note">Enter a valid email address. This is where your login info will be sent.</span>
                </span>
            </li>
            <li>
                <span class="inner-wrap">
                    <label for="admin_email" class="inlined">Your email address</label>
                    <input type="text" class="input-text" id="admin_email" name="admin_email" title="Your email address" value="<?php echo $this->setup['admin_email'] ?>" />
                    <span class="note">Enter a valid email address. This is where your login info will be sent.</span>
                </span>
            </li>
            <li>
                <span class="inner-wrap">
                    <label for="email_confirm" class="inlined">Confirm your email address</label>
                    <input type="text" class="input-text" id="email_confirm" name="email_confirm" />
                    <span class="note">Confirm your email address.</span>
                </span>
            </li>
            <li>
                <span class="inner-wrap">
                    <label for="admin_password" class="inlined">Password</label>
                    <input type="password" class="password" id="admin_password" name="admin_password" value="<?php echo $this->setup['admin_password'] ?>" />
                    <span class="note">Enter your admin password.</span>
                </span>
            </li>
            <li>
                <span class="inner-wrap">
                    <label for="password_confirm" class="inlined">Confirm password</label>
                    <input type="password" class="password" id="password_confirm" name="password_confirm" />
                    <span class="note">Confirm your admin password.</span>
                </span>
            </li>
        </ol>
        
        <div id="actions">
            <!--a href="<?php echo JURI::base(); ?>index.php?option=com_installer&view=display&layout=step1" class="btn-secondary">&laquo; <strong>P</strong>revious</a-->
            <!--a href="<?php echo JURI::base(); ?>index.php?option=com_installer&view=display&layout=step3" class="btn-primary"><strong>N</strong>ext &raquo;</a-->
            <button type="submit" class="btn-secondary" name="layout" value="step1"><?php echo MolajoText::_('Previous') ?></button>
            <button type="submit" class="btn-primary" name="layout" value="step3"><?php echo MolajoText::_('Next') ?></button>
        </div>

    </form>

</div>