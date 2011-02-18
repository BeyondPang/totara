<?php

/**
 * Page containing list of saved searches for this report
 *
 * @copyright Catalyst IT Limited
 * @author Simon Coggins
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 * @subpackage reportbuilder
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/local/reportbuilder/lib.php');
require_once('report_forms.php');

require_login();

define('REPORT_BUILDER_SAVED_SEARCHES_CONFIRM_DELETE', 1);
define('REPORT_BUILDER_SAVED_SEARCHES_FAILED_DELETE', 2);

$id = optional_param('id',null,PARAM_INT); // id for report
$sid = optional_param('sid',null,PARAM_INT); // id for saved search
$d = optional_param('d',false, PARAM_BOOL); // delete saved search?
$confirm = optional_param('confirm', false, PARAM_BOOL); // confirm delete

$returnurl = $CFG->wwwroot.'/local/reportbuilder/savedsearches.php?id='.$id;

$report = new reportbuilder($id);
if(!$report->is_capable($id)) {
    error(get_string('nopermission','local_reportbuilder'));
}

if($d && $confirm) {
    // delete an existing saved search
    if(!confirm_sesskey()) {
        totara_set_notification(get_string('error:bad_sesskey','local_reportbuilder'), $returnurl);
    }
    if(delete_records('report_builder_saved', 'id', $sid)) {
        totara_set_notification(get_string('savedsearchdeleted','local_reportbuilder'), $returnurl);
    } else {
        totara_set_notification(get_string('error:savedsearchnotdeleted','local_reportbuilder'), $returnurl, array('style' => 'notifysuccess'));
    }
} else if($d) {
    $fullname = $report->fullname;
    $pagetitle = format_string(get_string('savesearch','local_reportbuilder').': '.$fullname);
    $navlinks[] = array('name' => get_string('report','local_reportbuilder'), 'link'=> '', 'type'=>'title');
    $navlinks[] = array('name' => $fullname, 'link'=> '', 'type'=>'title');
    $navlinks[] = array('name' => get_string('savedsearches','local_reportbuilder'), 'link'=> '', 'type'=>'title');

    $navigation = build_navigation($navlinks);
    print_header_simple($pagetitle, '', $navigation, '', null, true, $report->edit_button());

    print_heading(get_string('savedsearches','local_reportbuilder'));
    // prompt to delete
    notice_yesno(get_string('savedsearchconfirmdelete','local_reportbuilder'),
        "savedsearches.php?id={$id}&amp;sid={$sid}&amp;d=1&amp;confirm=1&amp;" .
        "sesskey={$USER->sesskey}", $returnurl);

    print_footer();
    die;
}



$fullname = $report->fullname;
$pagetitle = format_string(get_string('savesearch','local_reportbuilder').': '.$fullname);
$navlinks[] = array('name' => get_string('report','local_reportbuilder'), 'link'=> '', 'type'=>'title');
$navlinks[] = array('name' => $fullname, 'link'=> '', 'type'=>'title');
$navlinks[] = array('name' => get_string('savedsearches','local_reportbuilder'), 'link'=> '', 'type'=>'title');

$navigation = build_navigation($navlinks);
print_header_simple($pagetitle, '', $navigation, '', null, true, $report->edit_button());

print $report->view_button();
print_heading(get_string('savedsearches','local_reportbuilder'));

if($searches = get_records_select('report_builder_saved', 'userid='.$USER->id.' AND reportid='.$id, 'name')) {
    $tableheader = array(get_string('name','local_reportbuilder'),
                         get_string('options','local_reportbuilder'));
    $data = array();

    foreach($searches as $search) {
        $row = array();
        $strdelete = get_string('delete','local_reportbuilder');

        $row[] = '<a href="' . $CFG->wwwroot . '/local/reportbuilder/report.php?id=' . $id .
            '&amp;sid='.$search->id.'">' . $search->name . '</a>';

        $delete = '<a href="' . $CFG->wwwroot .
            '/local/reportbuilder/savedsearches.php?d=1&amp;id=' . $id . '&amp;sid=' .
            $search->id . '" title="' . $strdelete . '">' .
            '<img src="' . $CFG->pixpath . '/t/delete.gif" alt="' .
            $strdelete . '"></a>';
        $row[] = $delete;
        $data[] = $row;
    }
    $table = new object();
    $table->summary = '';
    $table->head = $tableheader;
    $table->data = $data;
    print_table($table);

} else {
    print_error('error:nosavedsearches','local_reportbuilder');
}

print_footer();


?>
