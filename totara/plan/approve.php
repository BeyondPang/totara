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
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package totara
 * @subpackage plan
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/plan/lib.php');
require_once($CFG->dirroot . '/local/js/lib/setup.php');

require_login();

///
/// Load parameters
///
$id = required_param('id', PARAM_INT); // plan id
$submitted = optional_param('submitbutton', null, PARAM_TEXT); // form submitted


///
/// Load data
///
$currenturl = qualified_me();
$planurl = "{$CFG->wwwroot}/local/plan/view.php?id={$id}";
$plan = new development_plan($id);

if (!dp_can_view_users_plans($plan->userid)) {
    print_error('error:nopermissions', 'local_plan');
}


// Redirect if plan complete
if ($plan->status == DP_PLAN_STATUS_COMPLETE) {
    totara_set_notification(
        get_string('plancomplete', 'local_plan'),
        $planurl
    );
}


// Get all components
$components = $plan->get_components();

// Get items the current user can approve
$requested_items = $plan->has_pending_items(null, true, true);

// If no items
if (!$requested_items) {
    totara_set_notification(
        get_string('noitemsrequiringapproval', 'local_plan'),
        $planurl
    );
}


// Flag this page as the review page
$plan->reviewing_pending = true;


///
/// Process data
///
if ($submitted && confirm_sesskey()) {

    // Loop through components
    $errors = 0;
    foreach ($components as $componentname => $component) {

        // Update settings
        $result = $component->process_settings_update();

        if ($result === false) {
            $errors += 1;
        }
    }

    if ($errors) {
        totara_set_notification(get_string('error:problemapproving', 'local_plan'));
    }

    redirect($plan->get_display_url());
}


$fullname = $plan->name;
$pagetitle = format_string(get_string('learningplan', 'local_plan').': '.$fullname);
$navlinks = array();
dp_get_plan_base_navlinks($navlinks, $plan->userid);
$navlinks[] = array('name' => $fullname, 'link' => "{$CFG->wwwroot}/local/plan/view.php?id={$id}", 'type' => 'title');
$navlinks[] = array('name' => get_string('pendingitems', 'local_plan'), 'link' => '', 'type' => 'title');

$navigation = build_navigation($navlinks);


///
/// Javascript stuff
///
local_js(array(TOTARA_JS_DATEPICKER, TOTARA_JS_PLACEHOLDER));


///
/// Display page
///

print_header_simple($pagetitle, '', $navigation, '', null, true, '');

// Plan menu
echo dp_display_plans_menu($plan->userid, $plan->id, $plan->role);

// Plan page content
print_container_start(false, '', 'dp-plan-content');

print $plan->display_plan_message_box();

print_heading($fullname);
print $plan->display_tabs('pendingitems');

print '<form id="dp-component-update" action="' . $currenturl . '" method="POST">';
print '<input type="hidden" id="sesskey" name="sesskey" value="'.sesskey().'" />';

foreach ($components as $componentname => $component) {
    // Check if there are any items requiring approval
    if (empty($requested_items[$componentname])) {
        continue;
    }

    print_heading(get_string($component->component.'plural', 'local_plan'));

    print $component->display_approval_list($requested_items[$componentname]);
}

print '<br /><input type="submit" name="submitbutton" value="'.get_string('updatesettings', 'local_plan').'" />';

print '</form>';
print_container_end();
print_footer();
