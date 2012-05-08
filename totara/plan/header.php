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
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package totara
 * @subpackage plan
 */

/**
 * Generate header including plan details
 *
 * Only included via development_plan::print_header()
 *
 * The following variables will be set:
 *
 * - $this              Plan instance
 * - $CFG               Config global
 * - $currenttab        Current tab
 * - $navlinks          Additional breadcrumbs (optional)
 */

(defined('MOODLE_INTERNAL') && isset($this)) || die();
require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

// Check if this is a component
if (array_key_exists($currenttab, $this->get_components())) {
    $component = $this->get_component($currenttab);
    $is_component = true;
}
else {
    $is_component = false;
}

$fullname = $this->name;
$pagetitle = format_string(get_string('learningplan', 'local_plan').': '.$fullname);
$breadcrumbs = array();

dp_get_plan_base_navlinks($breadcrumbs, $this->userid);

$breadcrumbs[] = array('name' => $fullname, 'link'=> '', 'type'=> 'title');

if (!empty($navlinks)) {
    $breadcrumbs += $navlinks;
}

$navigation = build_navigation($breadcrumbs);

//Javascript include
local_js(array(
    TOTARA_JS_DATEPICKER,
    TOTARA_JS_PLACEHOLDER
));


print_header_simple($pagetitle, '', $navigation, '', null, true, '');


// Run post header hook (if this is a component)
if ($is_component) {
    $component->post_header_hook();
}


// Plan menu
echo dp_display_plans_menu($this->userid, $this->id, $this->role);

// Plan page content
print_container_start(false, '', 'dp-plan-content');

echo $this->display_plan_message_box();

print_heading('<span class="dp-plan-prefix">'.get_string('plan','local_plan') . ':</span> ' . $fullname);

print $this->display_tabs($currenttab);

if ($printinstructions) {
    //
    // Display instructions
    //
    $instructions = '<div class="instructional_text">';
    if ($this->role == 'manager') {
        $instructions .= get_string($currenttab.'_instructions_manager', 'local_plan') . ' ';
    } else {
        $instructions .= get_string($currenttab.'_instructions_learner', 'local_plan') . ' ';
    }

    // If this a component
    if ($is_component) {
        $instructions .= get_string($currenttab.'_instructions_detail', 'local_plan') . ' ';

        if (!$this->is_active() || $component->get_setting('update'.$currenttab) > DP_PERMISSION_REQUEST) {
            $instructions .= get_string($currenttab.'_instructions_add11', 'local_plan') . ' ';
        }
        if ($this->is_active() && $component->get_setting('update'.$currenttab) == DP_PERMISSION_REQUEST) {
            $instructions .= get_string($currenttab.'_instructions_request', 'local_plan') . ' ';
        }
    }

    $instructions .= '</div>';

    print $instructions;
}
