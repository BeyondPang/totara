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
 * @author Peter Bulmer <peterb@catalyst.net.nz>
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/plan/components/objective/edit_form.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$id = required_param('id', PARAM_INT); // plan id
$caid = required_param('itemid', PARAM_INT); // objective assignment id
$action = optional_param('action', 'view', PARAM_TEXT);

require_login();
$plan = new development_plan($id);

//Permissions check
$systemcontext = get_system_context();
if(!has_capability('totara/plan:accessanyplan', $systemcontext) && ($plan->get_setting('view') < DP_PERMISSION_ALLOW)) {
        print_error('error:nopermissions', 'local_plan');
}

$plancompleted = $plan->status == DP_PLAN_STATUS_COMPLETE;
$componentname = 'objective';
$component = $plan->get_component($componentname);
$objectivename = get_string($componentname, 'local_plan');
$coursename = get_string('courseplural', 'local_plan');
$currenturl = $CFG->wwwroot . '/totara/plan/components/objective/view.php?id='.$id.'&amp;itemid='.$caid;
$canupdate = $component->can_update_items();

/// Javascript stuff
// If we are showing dialog
if ($canupdate) {
    // Setup lightbox
    local_js(array(
        TOTARA_JS_DIALOG,
        TOTARA_JS_TREEVIEW
    ));

    // Get course picker
    require_js(array(
        $CFG->wwwroot.'/totara/plan/components/objective/find-course.js.php'
    ));
}


// Check if we are performing an action
if ($data = data_submitted() && $canupdate) {
    if ($action === 'removelinkedcourses' && !$plan->is_complete()) {
        $deletions = array();

        // Load existing list of linked courses
        $fullidlist = $component->get_linked_components($caid, 'course');

        // Grab all linked items for deletion
        $course_assigns = optional_param('delete_linked_course_assign', array(), PARAM_BOOL);
        if ($course_assigns) {
            foreach ($course_assigns as $linkedid => $delete) {
                if (!$delete) {
                    continue;
                }

                $deletions[] = $linkedid;
            }

            if ($fullidlist && $deletions) {
                $newidlist = array_diff($fullidlist, $deletions);
                $component->update_linked_components($caid, 'course', $newidlist);
            }
        }

        if ($deletions) {
            totara_set_notification(get_string('selectedlinkedcoursesremovedfromobjective', 'local_plan'), $currenturl, array('class' => 'notifysuccess'));
        } else {
            redirect($currenturl);
        }
        die();
    }
}


$mform = $component->objective_form($caid, 'view');
if ($data = $mform->get_data()){
    if (isset($data->edit)){
        redirect("{$CFG->wwwroot}/totara/plan/components/objective/edit.php?id={$id}&itemid={$caid}");
    } elseif (isset($data->delete)){
        redirect("{$CFG->wwwroot}/totara/plan/components/objective/edit.php?id={$id}&itemid={$caid}&d=1");
    }
}
//$mform = new moodleform();

$fullname = $plan->name;
$pagetitle = format_string(get_string('learningplan','local_plan').': '.$fullname);
$navlinks = array();
dp_get_plan_base_navlinks($navlinks, $plan->userid);
$navlinks[] = array('name' => $fullname, 'link'=> $CFG->wwwroot . '/totara/plan/view.php?id='.$id, 'type'=>'title');
$navlinks[] = array('name' => get_string($component->component, 'local_plan'), 'link' => $component->get_url(), 'type' => 'title');
$navlinks[] = array('name' => get_string('viewitem','local_plan'), 'link' => '', 'type' => 'title');

$navigation = build_navigation($navlinks);

$plan->print_header($componentname, $navlinks, false);

print $component->display_back_to_index_link();
$component->display_objective_detail($caid, true);


if ($plan->get_component('course')->get_setting('enabled')) {
    print '<br />';
    print '<h3>' . get_string('linkedx', 'local_plan', $coursename) . '</h3>';
    print '<div id="dp-objective-courses-container">';
    if ($linkedcourses = $component->get_linked_components($caid, 'course')) {
        $formurl = $currenturl.'&action=removelinkedcourses';
        print '<form action="'.$formurl.'" method="post" />';
        print $plan->get_component('course')->display_linked_courses($linkedcourses);
        if ($canupdate) {
            print '<input type="submit" class="plan-remove-selected" value="'.get_string('removeselected', 'local_plan').'" />';
        }
        print '</form>';
    } else {
        print '<p class="noitems-assigncourses">' . get_string('nolinkedx', 'local_plan', strtolower($coursename)). '</p>';
    }
    print '</div>';

    if (!$plancompleted) {
        print $component->display_course_picker($caid);
    }
}

// Comments
require_once($CFG->dirroot.'/totara/comment/lib.php');
comment::init();
$options = new stdClass;
$options->area    = 'plan-objective-item';
$options->context = $systemcontext;
$options->itemid  = $caid;
$options->showcount = true;
$options->component = 'local_plan';
$options->autostart = true;
$options->notoggle = true;
$comment = new comment($options);
echo $comment->output(true);

print_container_end();

print_footer();


?>
