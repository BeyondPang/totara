<?php

require_once('../config.php');

require_once($CFG->dirroot.'/local/reportbuilder/lib.php');

$userid = optional_param('userid', $USER->id, PARAM_INT); // which user to show
$format = optional_param('format','', PARAM_TEXT); // export format

if (! $user = get_record('user', 'id', $userid)) {
    error('User not found');
}

// users can only view their own and their staff's pages
if ($USER->id != $userid && !totara_is_manager($userid)) {
    error('You cannot view this page');
}
if ($USER->id != $userid) {
    $strheading = get_string('pastbookingsfor','local').fullname($user, true);
} else {
    $strheading = get_string('mypastbookings', 'local');
}

$shortname = 'pastbookings';
$embed->source = 'facetoface_sessions';
$embed->fullname = $strheading;
$embed->filters = array(); // hide filter block
$embed->columns = array(
    array(
        'type' => 'course',
        'value' => 'courselink',
        'heading' => 'Course Name',
    ),
    array(
        'type' => 'facetoface',
        'value' => 'name',
        'heading' => 'Session Name',
    ),
    array(
        'type' => 'date',
        'value' => 'timestart',
        'heading' => 'Session Start',
    ),
    array(
        'type' => 'date',
        'value' => 'timefinish',
        'heading' => 'Session Finish',
    ),
);
// only add facilitator column if role exists
if(get_field('role','id','shortname','facilitator')) {
    $embed->columns[] = array(
        'type' => 'role',
        'value' => 'facilitator',
        'heading' => 'Facilitator',
    );
}
// only show past bookings
$embed->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;
$embed->contentsettings = array(
    'date' => array(
        'enable' => 1,
        'when' => 'past',
    ),
);
// also limited to single user by embedded params
$embed->embeddedparams = array(
    'userid' => $userid,
);

$report = new reportbuilder(null, $shortname, $embed);

if($format!='') {
    $report->export_data($format);
    die;
}
$report->include_js();

$fullname = $report->fullname;
$pagetitle = format_string(get_string('report','local').': '.$fullname);
$navlinks[] = array('name' => $fullname, 'link'=> '', 'type'=>'title');

$navigation = build_navigation($navlinks);

print_header_simple($pagetitle, '', $navigation, '', null, true, null);

$currenttab = "pastbookings";
include('booking_tabs.php');

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

// display heading including filtering stats
$heading = $strheading . ': ' .
    $report->print_result_count_string($countfiltered, $countall);
print_heading($heading);

print $report->print_description();

$report->display_search();

print '<br />';

if($countfiltered>0) {
    print $report->showhide_button();
    $report->display_table();
    print $report->edit_button();
    // export button
    $report->export_select();
}

print_footer();

?>
