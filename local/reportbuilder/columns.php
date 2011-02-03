<?php // $Id$

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/reportbuilder/lib.php');
require_once($CFG->dirroot.'/local/reportbuilder/report_forms.php');
require_once($CFG->dirroot.'/local/js/lib/setup.php');

global $USER;
$id = required_param('id',PARAM_INT); // report builder id
$d = optional_param('d', null, PARAM_TEXT); // delete
$m = optional_param('m', null, PARAM_TEXT); // move
$h = optional_param('h', null, PARAM_TEXT); // show/hide
$cid = optional_param('cid',null,PARAM_INT); //column id
$confirm = optional_param('confirm', 0, PARAM_INT); // confirm delete

admin_externalpage_setup('managereports');

$returnurl = $CFG->wwwroot."/local/reportbuilder/columns.php?id=$id";

$report = new reportbuilder($id);

// include jquery
local_js();
// include code to handle column headings
require_js(array($CFG->wwwroot . '/local/reportbuilder/columns.js'));

// toggle show/hide column
if ($h !== null && isset($cid)) {
    if($report->showhide_column($cid, $h)) {
        totara_set_notification(get_string('column_vis_updated','local_reportbuilder'), $returnurl, array('style' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('error:column_vis_not_updated','local_reportbuilder'), $returnurl);
    }
}

// delete column
if ($d and $confirm ) {
    if(!confirm_sesskey()) {
        totara_set_notification(get_string('error:bad_sesskey','local_reportbuilder'), $returnurl);
    }

    if(isset($cid)) {
        if($report->delete_column($cid)) {
            totara_set_notification(get_string('column_deleted','local_reportbuilder'), $returnurl, array('style' => 'notifysuccess'));
        } else {
            totara_set_notification(get_string('error:column_not_deleted','local_reportbuilder'), $returnurl);
        }
    }
}

// confirm deletion column
if ($d) {

    admin_externalpage_print_header();

    if(isset($cid)) {
        notice_yesno('Are you sure you want to delete this column?',"columns.php?d=1&amp;id=$id&amp;cid=$cid&amp;confirm=1&amp;sesskey=$USER->sesskey", $returnurl);
    }

    admin_externalpage_print_footer();
    die;
}

// move column
if($m && isset($cid)) {
    if($report->move_column($cid, $m)) {
        totara_set_notification(get_string('column_moved','local_reportbuilder'), $returnurl, array('style' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('error:column_not_moved','local_reportbuilder'), $returnurl);
    }
}

// form definition
$mform = new report_builder_edit_columns_form(null, compact('id','report'));

// form results check
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {

    if(empty($fromform->submitbutton)) {
        totara_set_notification(get_string('error:unknownbuttonclicked','local_reportbuilder'), $returnurl);
    }

    if(build_columns($id, $fromform)) {
        totara_set_notification(get_string('columns_updated','local_reportbuilder'), $returnurl, array('style' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('error:columns_not_updated','local_reportbuilder'), $returnurl);
    }

}

admin_externalpage_print_header();

print_container_start(true, 'reportbuilder-navbuttons');
print_single_button($CFG->wwwroot.'/local/reportbuilder/index.php', null, get_string('allreports','local_reportbuilder'));
print $report->view_button();
print_container_end();

print_heading(get_string('editreport','local_reportbuilder',$report->fullname));

$currenttab = 'columns';
include_once('tabs.php');

// display the form
$mform->display();

// include JS object to define the column headings
print '<script type="text/javascript">';
print "var rb_column_headings = {";
foreach($report->src->columnoptions as $option) {
    $key = $option->type . '-' . $option->value;
    // use defaultheading if set, otherwise name
    $value = ($option->defaultheading) ? $option->defaultheading :
        $option->name;
    print "'$key': '$value',";
}
print "};";
print '</script>';

admin_externalpage_print_footer();


/**
 * Update the report columns table with data from the submitted form
 *
 * @param integer $id Report ID to update
 * @param object $fromform Moodle form object containing the new column data
 *
 * @return boolean True if the columns could be updated successfully
 */
function build_columns($id, $fromform) {
    begin_sql();

    if ($oldcolumns = get_records('report_builder_columns', 'reportid', $id)) {
        // see if existing columns have changed
        foreach($oldcolumns as $cid => $oldcolumn) {
            $columnname = "column{$cid}";
            $headingname = "heading{$cid}";
            // update db only if column has changed
            if(isset($fromform->$columnname) &&
                ($fromform->$columnname != $oldcolumn->type.'-'.$oldcolumn->value ||
                $fromform->$headingname != $oldcolumn->heading)) {
                $todb = new object();
                $todb->id = $cid;
                $parts = explode('-', $fromform->$columnname);
                $todb->type = $parts[0];
                $todb->value = $parts[1];
                $todb->heading = $fromform->$headingname;
                if(!update_record('report_builder_columns', $todb)) {
                    rollback_sql();
                    return false;
                }
            }
        }
    }

    // add any new columns
    if(isset($fromform->newcolumns) && $fromform->newcolumns != '0') {
        $todb = new object();
        $todb->reportid = $id;
        $parts = explode('-',$fromform->newcolumns);
        $todb->type = $parts[0];
        $todb->value = $parts[1];
        $todb->heading = $fromform->newheading;
        $sortorder = get_field('report_builder_columns', 'MAX(sortorder) + 1', 'reportid', $id);
        if(!$sortorder) {
            $sortorder = 1;
        }
        $todb->sortorder = $sortorder;
        if(!insert_record('report_builder_columns', $todb)) {
            rollback_sql();
            return false;
        }
    }

    // update default column settings
    if(isset($fromform->defaultsortcolumn)) {
        $todb = new object();
        $todb->id = $id;
        $todb->defaultsortcolumn = $fromform->defaultsortcolumn;
        $todb->defaultsortorder = $fromform->defaultsortorder;
        if(!update_record('report_builder', $todb)) {
            rollback_sql();
            return false;
        }
    }

    commit_sql();
    return true;
}


?>
