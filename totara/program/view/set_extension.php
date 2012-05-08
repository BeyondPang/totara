<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/totara/program/lib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');

require_login();

?>

<div>
    <label for="extensiontime"><?php echo get_string('extenduntil', 'local_program'); ?></label>
    <input type="text" class="extensiontime" name="extensiontime" id="extensiontime" size="20" maxlength="10" placeholder="<?php echo get_string('datepickerplaceholder'); ?>" />
</div>
<br />
<div>
    <label for="extensionreason"><?php echo get_string('reasonforextension', 'local_program'); ?></label>
    <input type="text" class="extensionreason" name="extensionreason" id="extensionreason" size="80" maxlength="255" />
</div>
