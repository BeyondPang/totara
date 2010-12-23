<?php

require_once('../config.php');
require_once($CFG->dirroot.'/local/reportbuilder/lib.php');
require_once($CFG->dirroot.'/local/reportheading/lib.php');

$id = optional_param('id', null, PARAM_INT); // which user to show
$format = optional_param('format','', PARAM_TEXT); // export format

// default to current user
if(empty($id)) {
    $id = $USER->id;
}

if (! $user = get_record('user', 'id', $id)) {
    error('User not found');
}

$context = get_context_instance(CONTEXT_SYSTEM);
// users can only view their own and their staff's pages
if ($USER->id != $id && !totara_is_manager($id) && !has_capability('moodle/site:doanything', $context)) {
    error('You cannot view this page');
}
if ($USER->id != $id) {
    $strheading = get_string('coursecompletionsfor','local').fullname($user, true);
} else {
    $strheading = get_string('mycoursecompletions', 'local');
}

$shortname = 'course_completions';
$data = array(
    'userid' => $id,
);

$report = reportbuilder_get_embedded_report($shortname, $data);

if($format!='') {
    $report->export_data($format);
    die;
}
$report->include_js();

$fullname = $report->fullname;
$pagetitle = format_string(get_string('report','local').': '.$fullname);
$navlinks[] = array('name' => $fullname, 'link'=> '', 'type'=>'title');

$navigation = build_navigation($navlinks);

print_header_simple($strheading, $strheading, $navigation, '', null, true, null);

echo '<h1>'.$strheading.'</h1>';

// add heading block
$heading = new reportheading($id);
print $heading->display();

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

// tab bar
$currenttab = "course_completions";
include('learning_tabs.php');

// display heading including filtering stats
$heading = $report->print_result_count_string($countfiltered, $countall);
print_heading($heading);

print $report->print_description();

$report->display_search();

if($countfiltered>0) {
    print $report->showhide_button();
    $report->display_table();
    print $report->edit_button();
    // export button
    $report->export_select();
}

print_footer();

?>
