<?php

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/plan/lib.php');
require_once($CFG->dirroot . '/local/js/lib/setup.php');


global $USER;

///
/// Load parameters
///
$id = required_param('id', PARAM_INT); // plan id
$submitted = optional_param('submitbutton', null, PARAM_TEXT); // form submitted
$action = optional_param('action', null, PARAM_ALPHANUM); // other actions
$delete = optional_param('d', 0, PARAM_INT); // course assignment id to delete
$confirm = optional_param('confirm', 0, PARAM_INT); // confirm delete


///
/// Load data
///
$plan = new development_plan($id);

if (!dp_can_view_users_plans($plan->userid)) {
    print_error('error:nopermissions', 'local_plan');
}

$componentname = 'course';
$component = $plan->get_component($componentname);
$currenturl = $CFG->wwwroot . '/local/plan/components/course/index.php?id='.$id;
$plancompleted = $plan->status == DP_PLAN_STATUS_COMPLETE;
$cansetduedate = ($component->get_setting('setduedate') == DP_PERMISSION_ALLOW);
$cansetpriority = ($component->get_setting('setpriority') == DP_PERMISSION_ALLOW);
$canapprovecourses = ($component->get_setting('updatecourse') == DP_PERMISSION_APPROVE);

if($submitted && confirm_sesskey()) {
    $component->process_course_settings_update();
} elseif ($action && confirm_sesskey()) {
    $component->process_action($action);
}

if ($delete && $confirm) {
    if (!confirm_sesskey()) {
        totara_set_notification(get_string('confirmsesskeybad', 'error'), $currenturl);
    }

    // Load item
    if (!$deleteitem = $component->get_assigned_item($delete)) {
        print_error('error:couldnotfindassigneditem', 'local_plan');
    }

    // Unassign item
    if ($component->unassign_item($deleteitem)) {
        totara_set_notification(get_string('canremoveitem','local_plan'), $currenturl, array('style' => 'notifysuccess'));

        $plan->set_status_unapproved_if_declined();
    } else {
        print_error('error:couldnotunassignitem', 'local_plan');
    }
}

$fullname = $plan->name;
$pagetitle = format_string(get_string('learningplan','local_plan').': '.$fullname);
$navlinks = array();
dp_get_plan_base_navlinks($navlinks, $plan->userid);
$navlinks[] = array('name' => $fullname, 'link'=> $CFG->wwwroot . '/local/plan/view.php?id='.$id, 'type'=>'title');
$navlinks[] = array('name' => $component->get_setting('name'), 'link' => '', 'type' => 'title');

$navigation = build_navigation($navlinks);

if ($delete) {
    print_header_simple($pagetitle, '', $navigation, '', null, true, '');
    notice_yesno(get_string('confirmitemdelete','local_plan'), $currenturl.'&amp;d='.$delete.'&amp;confirm=1&amp;sesskey='.sesskey(), $currenturl);
    print_footer();
    die();
}


///
/// Javascript stuff
///

// If we are showing dialog
if ($component->can_update_items()) {
    // Setup lightbox
    local_js(array(
        TOTARA_JS_DIALOG,
        TOTARA_JS_TREEVIEW
    ));

    // Get course picker
    require_js(array(
        $CFG->wwwroot.'/local/plan/components/course/find.js.php'
    ));
}

// Load datepicker JS
local_js(array(TOTARA_JS_DATEPICKER));


///
/// Display page
///
print_header_simple($pagetitle, '', $navigation, '', null, true, '');

// Plan menu
echo dp_display_plans_menu($plan->userid,$plan->id,$plan->role);

// Plan page content
print_container_start(false, '', 'dp-plan-content');

print $plan->display_plan_message_box();

print_heading($fullname);
print $plan->display_tabs($componentname);

$course_instructions = '<div class="instructional_text">';
if($plan->role == 'manager') {
    $course_instructions .= get_string('course_instructions_manager', 'local_plan');
} else {
    $course_instructions .= get_string('course_instructions_learner', 'local_plan');
}

$course_instructions .= get_string('course_instructions_detail', 'local_plan');

if ($component->get_setting('updatecourse') > DP_PERMISSION_REQUEST) {
    $course_instructions .= get_string('course_instructions_add', 'local_plan');
}
if ($component->get_setting('updatecourse') == DP_PERMISSION_REQUEST) {
    $course_instructions .= get_string('course_instructions_request', 'local_plan');
}

$course_instructions .= '</div>';

print $course_instructions;

print $component->display_picker();

print '<form id="dp-component-update" action="' . $currenturl . '" method="POST">';
print '<input type="hidden" id="sesskey" name="sesskey" value="'.sesskey().'" />';
print $component->display_course_list();

if(!$plancompleted && ($cansetduedate || $cansetpriority || $canapprovecourses) && ($component->get_assigned_items_count()>0)) {
    print '<br /><input type="submit" name="submitbutton" value="'.get_string('updatesettings', 'local_plan').'" />';
}

print '</form>';
print_container_end();
print_footer();
