<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/totara/plan/lib.php');
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

// Load parameters
$plan_id = required_param('planid', PARAM_INT);
$component_name = required_param('component', PARAM_ALPHA);
$view_as = optional_param('viewas', null, PARAM_INT);

require_login();

// Load plan and component
$plan = new development_plan($plan_id, $view_as);
$component = $plan->get_component($component_name);

$sesskey = sesskey();

?>

// Add hooks to learning plan component form elements
$(function() {

    // Update when form elements change
    $('table.dp-plan-component-items input, table.dp-plan-component-items select').live('change', function() {
        var data = {
            submitbutton: "1",
            ajax: "1",
            sesskey: "<?php echo $sesskey; ?>"
        };

        // Get current value
        data[$(this).attr('name')] = $(this).val();

        $.post(
            '<?php echo "{$CFG->wwwroot}/totara/plan/component.php?id={$plan->id}&c={$component_name}"; ?>',
            data,
            totara_local_plan_update
        );
    });
});


// Create handler for the dialog
totaraDialog_handler_preRequisite = function() {
    // Base url
    var baseurl = '';
}

totaraDialog_handler_preRequisite.prototype = new totaraDialog_handler_treeview_multiselect();

/**
 * Add a row to a table on the calling page
 * Also hides the dialog and any no item notice
 *
 * @param string    HTML response
 * @return void
 */
totaraDialog_handler_preRequisite.prototype._update = function(response) {
    // Hide dialog
    this._dialog.hide();

    // Update table
    totara_local_plan_update(response);
}


/**
 * Update the table on the calling page, and remove/add no items notices
 *
 * @param   string  HTML response
 * @return  void
 */
var totara_local_plan_update = function(response) {

    // Remove no item warning (if exists)
    $('.noitems-assign<?php echo $component_name; ?>').remove();

    // Split response into table and div
    var new_table = $(response).filter('table');
    var new_planbox = $(response).filter('.plan_box');

    // Grab table
    var table = $('div#content form#dp-component-update table.dp-plan-component-items');

    // Check for no items msg
    var noitems = $(response).filter('span.noitems-assign<?php echo $component_name; ?>');

    // Define update setting button div
    var updatesettings = $('div#content div#dp-component-update-submit');

    if (noitems.size()) {
        // If no items, just display message
        $('div#content form#dp-component-update div#dp-component-update-table').append(noitems);
        // Replace table with nothing
        table.empty();
        // Hide update setting button when there are no items
        updatesettings.hide();
    } else if (table.size()) {
        // If table found
        table.replaceWith(new_table);
        updatesettings.show();
    } else {
        // Add new table
        $('div#content form#dp-component-update div#dp-component-update-table').append(new_table);
        // Show update setting button there are now rows
        updatesettings.show();
    }

    // Replace plan message box
    $('div.plan_box').replaceWith(new_planbox);

    // Add duedate datepicker
<?php echo build_datepicker_js("[id^=duedate_{$component_name}]", false); ?>
}
