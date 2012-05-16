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
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage plan
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

class dp_course_component extends dp_base_component {

    public static $permissions = array(
        'updatecourse' => true,
        //'commenton' => false,
        'setpriority' => false,
        'setduedate' => false,
        'setcompletionstatus' => false,
        'deletemandatory' => false,
    );


    /**
     * Initialize settings for the component
     *
     * @access  public
     * @param   array   $settings
     * @return  void
     */
    public function initialize_settings(&$settings) {
        if ($coursesettings = get_record('dp_course_settings', 'templateid', $this->plan->templateid)) {
            $settings[$this->component.'_duedatemode'] = $coursesettings->duedatemode;
            $settings[$this->component.'_prioritymode'] = $coursesettings->prioritymode;
            $settings[$this->component.'_priorityscale'] = $coursesettings->priorityscale;
        }
    }


    /**
     * Get a single assignment
     *
     * @access  public
     * @param integer $assignmentid ID of the course assignment
     * @return  object|false
     */
    public function get_assignment($assignmentid) {
        global $CFG;

        $assignment = get_record_sql(
            "
            SELECT
                a.*,
                c.fullname
            FROM
                {$CFG->prefix}dp_plan_course_assign a
            INNER JOIN
                {$CFG->prefix}course c
             ON c.id = a.courseid
            WHERE
                a.planid = {$this->plan->id}
            AND a.id = {$assignmentid}
            "
        );

        return $assignment;
    }

    /**
     * Get a single assigned item
     *
     * @access  public
     * @param integer $itemid ID of a course that is assigned to this plan
     * @return  object|false
     */
    public function get_assigned_item($itemid) {
        global $CFG;

        $item = get_record_sql(
            "
            SELECT
                a.id,
                a.planid,
                a.courseid,
                a.id AS itemid,
                c.fullname,
                a.approved
            FROM
                {$CFG->prefix}dp_plan_course_assign a
            INNER JOIN
                {$CFG->prefix}course c
             ON c.id = a.courseid
            WHERE
                a.planid = {$this->plan->id}
            AND c.id = {$itemid}
            "
        );

        return $item;
    }


    /**
     * Get list of items assigned to plan
     *
     * Optionally, filtered by status
     *
     * @access  public
     * @param   mixed   $approved   (optional)
     * @param   string  $orderby    (optional)
     * @param   int     $limitfrom  (optional)
     * @param   int     $limitnum   (optional)
     * @return  array
     */
    public function get_assigned_items($approved = null, $orderby='', $limitfrom='', $limitnum='') {
        global $CFG;

        // Generate where clause
        $where = "c.visible = 1 AND a.planid = {$this->plan->id}";
        if ($approved !== null) {
            if (is_array($approved)) {
                $approved = implode(', ', $approved);
            }
            $where .= " AND a.approved IN ({$approved})";
        }
        // Generate order by clause
        if ($orderby) {
            $orderby = "ORDER BY $orderby";
        }

        if ($this->plan->is_complete()) {
            // Use the 'snapshot' status value
            $completion_field = 'a.completionstatus AS coursecompletion,';
            // save same value again with a new alias so the column
            // can be sorted
            $completion_field .= 'a.completionstatus AS progress,';
            $completion_joins = '';
        } else {
            // Use the 'live' status value
            $completion_field = 'cc.status AS coursecompletion,';
            // save same value again with a new alias so the column
            // can be sorted
            $completion_field .= 'cc.status AS progress,';
            $completion_joins = "LEFT JOIN
                {$CFG->prefix}course_completions cc
                ON ( cc.course = a.courseid
                AND cc.userid = {$this->plan->userid} )";
        }

        $assigned = get_records_sql(
            "
            SELECT
                a.*,
                $completion_field
                c.fullname,
                c.fullname AS name,
                c.icon,
                c.enablecompletion
            FROM
                {$CFG->prefix}dp_plan_course_assign a
                $completion_joins
            INNER JOIN
                {$CFG->prefix}course c
             ON c.id = a.courseid
            WHERE
                $where
                $orderby
            ",
            $limitfrom,
            $limitnum
        );

        if (!$assigned) {
            $assigned = array();
        }

        return $assigned;
    }


    /**
     * Process an action
     *
     * General component actions can come in here
     *
     * @access  public
     * @return  void
     */
    public function process_action_hook() {
        global $USER;

        $delete = optional_param('d', 0, PARAM_INT); // course assignment id to delete
        $confirm = optional_param('confirm', 0, PARAM_INT); // confirm delete

        $currenturl = $this->get_url();

        if ($delete && $confirm) {
            if (!confirm_sesskey()) {
                totara_set_notification(get_string('confirmsesskeybad', 'error'), $currenturl);
            }

            // Load item
            if (!$deleteitem = $this->get_assignment($delete)) {
                print_error('error:couldnotfindassigneditem', 'local_plan');
            }

            // Check mandatory permissions
            if (!$this->can_delete_item($deleteitem)) {
                print_error('error:nopermissiondeletemandatorycourse', 'local_plan');
            }

            // Unassign item
            if ($this->unassign_item($deleteitem)) {
                add_to_log(SITEID, 'plan', 'removed course', "component.php?id={$this->plan->id}&amp;c=course", "{$deleteitem->fullname} (ID:{$deleteitem->id})");
                dp_plan_check_plan_complete(array($this->plan->id));
                totara_set_notification(get_string('canremoveitem','local_plan'), $currenturl, array('class' => 'notifysuccess'));

            } else {
                print_error('error:couldnotunassignitem', 'local_plan');
            }
        }
    }


    /**
     * Code to load the JS for the picker
     *
     * @access  public
     * @return  void
     */
    public function setup_picker() {
        global $CFG;

        // If we are showing dialog
        if ($this->can_update_items()) {
            // Setup lightbox
            local_js(array(
                TOTARA_JS_DIALOG,
                TOTARA_JS_TREEVIEW
            ));

            // Get course picker
            require_js(array(
                $CFG->wwwroot.'/totara/plan/component.js.php?planid='.$this->plan->id.'&amp;component=course&amp;viewas='.$this->plan->viewas,
                $CFG->wwwroot.'/totara/plan/components/course/find.js.php'
            ));
        }
    }


    /**
     * Code to run after page header is display
     *
     * @access  public
     * @return  void
     */
    public function post_header_hook() {

        $delete = optional_param('d', 0, PARAM_INT); // course assignment id to delete
        $currenturl = $this->get_url();

        if ($delete) {
            notice_yesno(get_string('confirmitemdelete','local_plan'), $currenturl.'&amp;d='.$delete.'&amp;confirm=1&amp;sesskey='.sesskey(), $currenturl);
            print_footer();
            die();
        }
    }


    /**
     * Assign a new item to this component of the plan
     *
     * @access  public
     * @param   integer $itemid
     * @param   boolean $checkpermissions If false user permission checks are skipped (optional)
     * @param   boolean $manual Was this assignment created manually by a user? (optional)
     * @return  object  Inserted record
     */
    public function assign_new_item($itemid, $checkpermissions = true, $manual = true) {

        // Get approval value for new item if required
        if ($checkpermissions) {
            if (!$permission = $this->can_update_items()) {
                print_error('error:cannotupdatecourses', 'local_plan');
            }
        } else {
            $permission = DP_PERMISSION_ALLOW;
        }

        $item = new object();
        $item->planid = $this->plan->id;
        $item->courseid = $itemid;
        $item->priority = null;
        $item->duedate = null;
        $item->completionstatus = null;
        $item->grade = null;
        $item->manual = (int) $manual;

        // Check required values for priority/due data
        if ($this->get_setting('prioritymode') == DP_PRIORITY_REQUIRED) {
            $item->priority = $this->get_default_priority();
        }

        if ($this->get_setting('duedatemode') == DP_DUEDATES_REQUIRED) {
            $item->duedate = $this->plan->enddate;
        }

        // Set approved status
        if ( $permission >= DP_PERMISSION_ALLOW ) {
            $item->approved = DP_APPROVAL_APPROVED;
        }
        else { # $permission == DP_PERMISSION_REQUEST
            $item->approved = DP_APPROVAL_UNAPPROVED;
        }

        // Load fullname of item
        $item->fullname = get_field('course', 'fullname', 'id', $itemid);

        add_to_log(SITEID, 'plan', 'added course', "component.php?id={$this->plan->id}&amp;c=course", "Course ID: {$itemid}");

        if ($result = insert_record('dp_plan_course_assign', $item)) {
            $item->id = $result;
        }

        return $result ? $item : $result;
    }


    /**
     * Displays a list of linked courses
     *
     * @param   array   $list           The list of linked courses
     * @return  false|string  $out  the table to display
     */
    function display_linked_courses($list) {
        global $CFG;

        if (!is_array($list) || count($list) == 0) {
            return false;
        }

        $showduedates = ($this->get_setting('duedatemode') == DP_DUEDATES_OPTIONAL ||
            $this->get_setting('duedatemode') == DP_DUEDATES_REQUIRED);
        $showpriorities =
            ($this->get_setting('prioritymode') == DP_PRIORITY_OPTIONAL ||
            $this->get_setting('prioritymode') == DP_PRIORITY_REQUIRED);
        $priorityscaleid = ($this->get_setting('priorityscale')) ? $this->get_setting('priorityscale') : -1;

        if ($this->plan->is_complete()) {
            // Use the 'snapshot' status value
            $completion_field = 'ca.completionstatus AS coursecompletion,';
            // save same value again with a new alias so the column
            // can be sorted
            $completion_field .= 'ca.completionstatus AS progress ';
            $completion_joins = '';
        } else {
            // Use the 'live' status value
            $completion_field = 'cc.status AS coursecompletion,';
            // save same value again with a new alias so the column
            // can be sorted
            $completion_field .= 'ca.completionstatus AS progress ';
            $completion_joins = "LEFT JOIN
                {$CFG->prefix}course_completions cc
                ON ( cc.course = ca.courseid
                AND cc.userid = {$this->plan->userid} )";
        }

        $select = 'SELECT ca.*, c.fullname, c.icon, psv.name AS priorityname, '. $completion_field;

        // get courses assigned to this plan
        // and related details
        $from = "
            FROM
                {$CFG->prefix}dp_plan_course_assign ca
            LEFT JOIN
                {$CFG->prefix}course c
             ON c.id = ca.courseid
            LEFT JOIN
                {$CFG->prefix}dp_priority_scale_value psv
            ON  (ca.priority = psv.id
            AND psv.priorityscaleid = $priorityscaleid)
                {$completion_joins}
        ";

        $where = " WHERE ca.id IN (" . implode(',', $list) . ")
            AND ca.approved = ".DP_APPROVAL_APPROVED;

        $sort = " ORDER BY c.fullname";


        $tableheaders = array(
            get_string('coursename','local_plan'),
        );
        $tablecolumns = array(
            'fullname',
        );

        if ($showpriorities) {
            $tableheaders[] = get_string('priority', 'local_plan');
            $tablecolumns[] = 'priority';
        }

        if ($showduedates) {
            $tableheaders[] = get_string('duedate', 'local_plan');
            $tablecolumns[] = 'duedate';
        }

        $tableheaders[] = get_string('progress','local_plan');
        $tablecolumns[] = 'progress';

        if (!$this->plan->is_complete() && $this->can_update_items()) {
            $tableheaders[] = get_string('remove', 'local_plan');
            $tablecolumns[] = 'remove';
        }

        $table = new flexible_table('linkedcourselist');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);

        $table->set_attribute('class', 'logtable generalbox dp-plan-component-items');
        $table->setup();

        if ($records = get_recordset_sql($select.$from.$where.$sort)) {
            // get the scale values used for competencies in this plan
            $priorityvalues = get_records('dp_priority_scale_value',
                'priorityscaleid', $priorityscaleid, 'sortorder', 'id,name,sortorder');

            while ($ca = rs_fetch_next_record($records)) {
                $row = array();
                $row[] = $this->display_item_name($ca);

                if ($showpriorities) {
                    $row[] = $this->display_priority_as_text($ca->priority, $ca->priorityname, $priorityvalues);
                }

                if ($showduedates) {
                    $row[] = $this->display_duedate_as_text($ca->duedate);
                }

                $row[] = $this->display_status_as_progress_bar($ca);

                if (!$this->plan->is_complete() && $this->can_update_items()) {
                    $row[] = '<input type="checkbox" value="1" name="delete_linked_course_assign['.$ca->id.']" />';
                }

                $table->add_data($row);
            }

            rs_close($records);

            // return instead of outputing table contents
            ob_start();
            $table->print_html();
            $out = ob_get_contents();
            ob_end_clean();

            return $out;
        }

    }


    /**
     * Display item's name
     *
     * @access  public
     * @param   object  $item
     * @return  string
     */
    public function display_item_name($item) {
        global $CFG;
        $approved = $this->is_item_approved($item->approved);

        if ($approved) {
            $class = '';
            $launch = '<div class="plan-launch-course-button">' .
                '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $item->courseid . '">'. get_string('launchcourse', 'local_plan') .'</a>' .
                '</div>';
        } else {
            $class = ' class="dimmed"';
            $launch = '';
        }
        return '<img class="course_icon" src="' .
            $CFG->wwwroot . '/local/icon/icon.php?icon=' . $item->icon .
            '&amp;id=' . $item->courseid .
            '&amp;size=small&amp;type=course" alt="' . format_string($item->fullname).
            '" /><a' . $class .' href="' . $CFG->wwwroot .
            '/totara/plan/components/' . $this->component.'/view.php?id=' .
            $this->plan->id . '&amp;itemid=' . $item->id . '">' . format_string($item->fullname) .
            '</a>'. $launch;
    }


    /**
     * Display details for a single course
     *
     * @param integer $caid ID of the course assignment (not the course id)
     * @return string HTML string to display the course information
     */
    function display_course_detail($caid) {
        global $CFG;

        $priorityscaleid = ($this->get_setting('priorityscale')) ? $this->get_setting('priorityscale') : -1;
        $priorityenabled = $this->get_setting('prioritymode') != DP_PRIORITY_NONE;
        $duedateenabled = $this->get_setting('duedatemode') != DP_DUEDATES_NONE;

        if ($this->plan->is_complete()) {
            $completion_field = 'ca.completionstatus AS coursecompletion';

            $completion_joins = '';
        } else {
            $completion_field = 'cc.status AS coursecompletion';

            $completion_joins = "LEFT JOIN {$CFG->prefix}course_completions cc
                    ON (cc.course = ca.courseid
                    AND cc.userid = {$this->plan->userid})";
        }

        $sql = "SELECT ca.*, course.*, psv.name AS priorityname, {$completion_field}
            FROM {$CFG->prefix}dp_plan_course_assign ca
                LEFT JOIN {$CFG->prefix}dp_priority_scale_value psv
                    ON (ca.priority = psv.id
                    AND psv.priorityscaleid = {$priorityscaleid})
                LEFT JOIN {$CFG->prefix}course course
                    ON course.id = ca.courseid
                {$completion_joins}
            WHERE ca.id = $caid";
        $item = get_record_sql($sql);

        if (!$item) {
            return get_string('coursenotfound', 'local_plan');
        }

        $out = '';

        // get the priority values used for competencies in this plan
        $priorityvalues = get_records('dp_priority_scale_value',
            'priorityscaleid', $priorityscaleid, 'sortorder', 'id,name,sortorder');

        if ($this->is_item_approved($item->approved)) {
            $out =  '<div class="plan-launch-course-button">' .
                '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $item->courseid . '">'. get_string('launchcourse', 'local_plan') .'</a>' .
                '</div>';
        }

        $icon = "<img class=\"course_icon\" src=\"{$CFG->wwwroot}/local/icon/icon.php?icon={$item->icon}&amp;id={$item->courseid}&amp;size=small&amp;type=course\" alt=\"" . format_string($item->fullname) . "\">";
        $out .= '<h3>' . $icon . format_string($item->fullname) . '</h3>';
        $out .= '<table border="0" class="planiteminfobox">';
        $out .= "<tr>";
        if ($priorityenabled && !empty($item->priority)) {
            $out .= '<td>';
            $out .= get_string('priority', 'local_plan') . ': ';
            $out .= $this->display_priority_as_text($item->priority,
                $item->priorityname, $priorityvalues);
            $out .= '</td>';
        }
        if ($duedateenabled && !empty($item->duedate)) {
            $out .= '<td>';
            $out .= get_string('duedate', 'local_plan') . ': ';
            $out .= $this->display_duedate_as_text($item->duedate);
            $out .= '<br />';
            $out .= $this->display_duedate_highlight_info($item->duedate);
            $out .= '</td>';
        }
        if ($progressbar = $this->display_status_as_progress_bar($item)) {
            unset($completionstatus);
            $out .= '<td><table border="0"><tr><td style="border:0px;">';
            $out .= get_string('progress', 'local_plan').': </td><td style="border:0px;">'.$progressbar;
            $out .= '</td></tr></table></td>';
        }
        $out .= "</tr>";
        $out .= '</table>';
        $out .= '<p>' . format_string($item->summary) . '</p>';

        return $out;
    }


    /**
     * Displays an items status as a progress bar
     *
     * @param object $item the item to check
     * @return string $out display markup
     */
    function display_status_as_progress_bar($item) {
        return totara_display_course_progress_icon($this->plan->userid, $item->courseid, $item->coursecompletion);
    }


    /**
     * Check if an item is complete
     *
     * @access  protected
     * @param   object  $item
     * @return  boolean
     */
    protected function is_item_complete($item) {
        return in_array($item->coursecompletion, array(COMPLETION_STATUS_COMPLETE, COMPLETION_STATUS_COMPLETEVIARPL));
    }


    /**
     * Process component's settings update
     *
     * @access  public
     * @param   bool    $ajax   Is an AJAX request (optional)
     * @return  void
     */
    public function process_settings_update($ajax = false) {
        // @todo validation notices, including preventing empty due dates
        // if duedatemode is required
        // @todo consider handling differently - currently all updates must
        // work or nothing is changed - is that the best way?
        global $CFG;

        if (!confirm_sesskey()) {
            return 0;
        }
        $cansetduedates = ($this->get_setting('setduedate') == DP_PERMISSION_ALLOW);
        $cansetpriorities = ($this->get_setting('setpriority') == DP_PERMISSION_ALLOW);
        $canapprovecourses = ($this->get_setting('updatecourse') == DP_PERMISSION_APPROVE);
        $duedates = optional_param('duedate_course', array(), PARAM_TEXT);
        $priorities = optional_param('priorities_course', array(), PARAM_TEXT);
        $approvals = optional_param('approve_course', array(), PARAM_INT);
        $currenturl = qualified_me();
        $stored_records = array();

        if (!empty($duedates) && $cansetduedates) {
            $badduedates = array();  // Record naughty duedates
            foreach ($duedates as $id => $duedate) {
                // allow empty due dates
                if ($duedate == '' || $duedate == get_string('datepickerplaceholder', 'totara_core')) {
                    // set all empty due dates to the plan due date
                    // if they are required
                    if ($this->get_setting('duedatemode') == DP_DUEDATES_REQUIRED) {
                        $duedateout = $this->plan->enddate;
                        $badduedates[] = $id;
                    } else {
                        $duedateout = null;
                    }
                } else {
                    $datepattern = get_string('datepickerregexphp', 'totara_core');
                    if (preg_match($datepattern, $duedate) == 0) {
                        // skip badly formatted date strings
                        $badduedates[] = $id;
                        continue;
                    }
                    $duedateout = totara_date_parse_from_format(get_string('datepickerparseformat', 'totara_core'), $duedate);
                }

                $todb = new object();
                $todb->id = $id;
                $todb->duedate = $duedateout;
                $stored_records[$id] = $todb;
            }
        }

        if (!empty($priorities)) {
            foreach ($priorities as $pid => $priority) {
                $priority = (int) $priority;
                if (array_key_exists($pid, $stored_records)) {
                    // add to the existing update object
                    $stored_records[$pid]->priority = $priority;
                } else {
                    // create a new update object
                    $todb = new object();
                    $todb->id = $pid;
                    $todb->priority = $priority;
                    $stored_records[$pid] = $todb;
                }
            }
        }
        if (!empty($approvals) && $canapprovecourses) {
            // Update approvals
            foreach ($approvals as $id => $approval) {
                if (!$approval) {
                    continue;
                }
                $approval = (int) $approval;
                if (array_key_exists($id, $stored_records)) {
                    // add to the existing update object
                    $stored_records[$id]->approved = $approval;
                } else {
                    // create a new update object
                    $todb = new object();
                    $todb->id = $id;
                    $todb->approved = $approval;
                    $stored_records[$id] = $todb;
                }
            }
        }

        $status = true;
        if (!empty($stored_records)) {
            $oldrecords = get_records_list('dp_plan_course_assign', 'id', implode(',', array_keys($stored_records)));

            $updates = '';
            $approvals = array();
            begin_sql();
            foreach ($stored_records as $itemid => $record) {
                // Update the record
                $status = $status & update_record('dp_plan_course_assign', $record);
            }

            if ($status) {
                commit_sql();

                // Process update alerts
                foreach ($stored_records as $itemid => $record) {
                    // Record the updates for later use
                    $course = get_record('course', 'id', $oldrecords[$itemid]->courseid);
                    $courseheader = '<p><strong>'.format_string($course->fullname).": </strong><br>";
                    $courseprinted = false;
                    if (!empty($record->priority) && $oldrecords[$itemid]->priority != $record->priority) {
                        $oldpriority = get_field('dp_priority_scale_value', 'name', 'id', $oldrecords[$itemid]->priority);
                        $newpriority = get_field('dp_priority_scale_value', 'name', 'id', $record->priority);
                        $updates .= $courseheader;
                        $courseprinted = true;
                        $updates .= get_string('priority', 'local_plan').' - '.
                            get_string('changedfromxtoy', 'local_plan',
                                (object)array('before'=>$oldpriority, 'after'=>$newpriority))."<br>";
                    }
                    if (!empty($record->duedate) && $oldrecords[$itemid]->duedate != $record->duedate) {
                        $updates .= $courseprinted ? '' : $courseheader;
                        $courseprinted = true;
                        $updates .= get_string('duedate', 'local_plan').' - '.
                            get_string('changedfromxtoy', 'local_plan',
                            (object)array('before'=>empty($oldrecords[$itemid]->duedate) ? '' :
                                userdate($oldrecords[$itemid]->duedate, '%e %h %Y', $CFG->timezone, false),
                            'after'=>userdate($record->duedate, '%e %h %Y', $CFG->timezone, false)))."<br>";
                    }
                    if (!empty($record->approved) && $oldrecords[$itemid]->approved != $record->approved) {
                        $approval = new object();
                        $text = $courseheader;
                        $text .= get_string('approval', 'local_plan').' - '.
                            get_string('changedfromxtoy', 'local_plan',
                            (object)array('before'=>dp_get_approval_status_from_code($oldrecords[$itemid]->approved),
                            'after'=>dp_get_approval_status_from_code($record->approved)))."<br>";
                        $approval->text = $text;
                        $approval->itemname = $course->fullname;
                        $approval->before = $oldrecords[$itemid]->approved;
                        $approval->after = $record->approved;
                        $approvals[] = $approval;

                    }
                    $updates .= $courseprinted ? '</p>' : '';
                }  // foreach

                if ($this->plan->status != DP_PLAN_STATUS_UNAPPROVED && count($approvals)>0) {
                    foreach ($approvals as $approval) {
                        $this->send_component_approval_alert($approval);

                        $action = ($approval->after == DP_APPROVAL_APPROVED) ? 'approved' : 'declined';
                        add_to_log(SITEID, 'plan', "{$action} course", "component.php?id={$this->plan->id}&amp;c=course", $approval->itemname);
                    }
                }

                // Send update alert
                if ($this->plan->status != DP_PLAN_STATUS_UNAPPROVED && strlen($updates)) {
                    $this->send_component_update_alert($updates);
                }

            } else {
                rollback_sql();
            }

            $currenturl = new moodle_url($currenturl);
            $currenturl->remove_params('badduedates');
            if (!empty($badduedates)) {
                $currenturl->params(array('badduedates'=>implode(',', $badduedates)));
            }
            $currenturl = $currenturl->out();

            if ($this->plan->reviewing_pending) {
                return $status;
            }
            else {
                if ($status) {
                    $issuesnotification = '';
                    if (!empty($badduedates)) {
                        $issuesnotification .= $this->get_setting('duedatemode') == DP_DUEDATES_REQUIRED ?
                            '<br>'.get_string('noteduedateswrongformatorrequired', 'local_plan') : '<br>'.get_string('noteduedateswrongformat', 'local_plan');
                    }

                    // Do not create notification or redirect if ajax request
                    if (!$ajax) {
                        totara_set_notification(get_string('coursesupdated','local_plan').$issuesnotification, $currenturl, array('class' => 'notifysuccess'));
                    }
                } else {
                    // Do not create notification or redirect if ajax request
                    if (!$ajax) {
                        totara_set_notification(get_string('coursesnotupdated','local_plan'), $currenturl);
                    }
                }
            }
        }

        if ($this->plan->reviewing_pending) {
            return null;
        }

        // Do not redirect if ajax request
        if (!$ajax) {
            redirect($currenturl);
        }
    }


    /**
     * Returns true if any courses use the scale given
     *
     * @param integer $scaleid
     * return boolean
     */
    public static function is_priority_scale_used($scaleid) {
        global $CFG;
        $sql = "
            SELECT ca.id
            FROM {$CFG->prefix}dp_plan_course_assign ca
            LEFT JOIN
                {$CFG->prefix}dp_priority_scale_value psv
            ON ca.priority = psv.id
            WHERE psv.priorityscaleid = {$scaleid}";
        return record_exists_sql($sql);
    }


    /**
     * Get headers for a list
     *
     * @return array $headers
     */
    function get_list_headers() {
        $headers = parent::get_list_headers();

        foreach ($headers->headers as $i=>$h) {
            if ($h == get_string('status', 'local_plan')) {
                // Replace 'Status' header with 'Progress'
                $headers->headers[$i] = get_string('progress', 'local_plan');
                break;
            }
        }

        return $headers;
    }


    /**
     * Display progress for an item in a list
     *
     * @access protected
     * @param object $item the item to check
     * @return string the item status
     */
    protected function display_list_item_progress($item) {
        return $this->is_item_approved($item->approved) ? $this->display_status_as_progress_bar($item) : '';
    }


    /**
     * Display an items available actions
     *
     * @access protected
     * @param object $item the item being checked
     * @return string $markup the display markup
     */
    protected function display_list_item_actions($item) {
        global $CFG;

        $markup = '';

        // Get permissions
        $cansetcompletion = !$this->plan->is_complete() && $this->get_setting('setcompletionstatus') >= DP_PERMISSION_ALLOW;

        // Check course has completion enabled
        $course = new object();
        $course->id = $item->courseid;
        $course->enablecompletion = $item->enablecompletion;
        $cinfo = new completion_info($course);

        // Only allow setting an RPL if completion is enabled for the site and course
        $cansetcompletion = $cansetcompletion && $cinfo->is_enabled();

        $approved = $this->is_item_approved($item->approved);

        // Actions
        if ($this->can_delete_item($item)) {
            $currenturl = $this->get_url();
            $strdelete = get_string('delete', 'local_plan');
            $delete = '<a href="'.$currenturl.'&amp;d='.$item->id.'" title="'.$strdelete.'"><img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'.$strdelete.'" /></a>';
            $markup .= $delete;
        }

        if ($cansetcompletion && $approved && $CFG->enablecourserpl) {
            $strrpl = get_string('addrpl', 'local_plan');
            $proficient = '<a href="'.$CFG->wwwroot.'/totara/plan/components/course/rpl.php?id='.$this->plan->id.'&courseid='.$item->courseid.'" title="'.$strrpl.'">
                <img src="'.$CFG->pixpath.'/t/ranges.gif" class="iconsmall" alt="'.$strrpl.'" /></a>';
            $markup .= $proficient;
        }

        return $markup;
    }

    /*
     * Return data about course progress within this plan
     *
     * @return mixed Object containing stats, or false if no progress stats available
     *
     * Object should contain the following properties:
     *    $progress->complete => Integer count of number of items completed
     *    $progress->total => Integer count of total number of items in this plan
     *    $progress->text => String description of completion (for use in tooltip)
     */
    public function progress_stats() {

        $completedcount = 0;
        $completionsum = 0;
        $inprogresscount = 0;
        // Get courses assigned to this plan
        if ($courses = $this->get_assigned_items()) {
            foreach ($courses as $c) {
                if ($c->approved != DP_APPROVAL_APPROVED) {
                    continue;
                }
                // Determine course completion
                if (empty($c->coursecompletion)) {
                    continue;
                }
                switch ($c->coursecompletion) {
                    case COMPLETION_STATUS_COMPLETE:
                    case COMPLETION_STATUS_COMPLETEVIARPL:
                        $completionsum += 1;
                        $completedcount++;
                        break;
                    case COMPLETION_STATUS_INPROGRESS:
                        $inprogresscount++;
                        break;
                    default:
                }
            }
        }

        $progress_str = "{$completedcount}/" . count($courses) . " " .
            get_string('coursescomplete', 'local_plan') . ", {$inprogresscount} " .
            get_string('inprogress', 'local_plan') . "\n";

        $progress = new object();
        $progress->complete = $completionsum;
        $progress->total = count($courses);
        $progress->text = $progress_str;

        return $progress;
    }


    /**
     * Reactivates course when re-activating a plan
     *
     * @return bool
     */
    public function reactivate_items() {
        global $CFG;
        $sql = "UPDATE {$CFG->prefix}dp_plan_course_assign SET completionstatus=null WHERE planid={$this->plan->id}";
        if (!execute_sql($sql, false)) {
            return false;
        }
        return true;
    }


    /**
     * Gets all plans containing specified course
     *
     * @param int $courseid
     * @param int $userid
     * @return array|false $plans ids of plans with specified course
     */
    public static function get_plans_containing_item($courseid, $userid) {
        global $CFG;
        $sql = "SELECT DISTINCT
                planid
            FROM
                {$CFG->prefix}dp_plan_course_assign ca
            JOIN
                {$CFG->prefix}dp_plan p
              ON
                ca.planid = p.id
            WHERE
                ca.courseid = {$courseid}
            AND
                p.userid = {$userid}";

        if (!$plans = get_records_sql($sql)) {
            // There are no plans with this course
            return false;
        }

        return array_keys($plans);
    }

    /*
     * Display the competency picker
     *
     * @access  public
     * @param   int $competencyid the id of the competency for which selected & available courses should be displayed
     * @return  string markup for javascript course picker
     */
    public function display_competency_picker($courseid) {

        if (!$permission = $this->can_update_items()) {
            return '';
        }

        $btntext = get_string('addlinkedcompetencies', 'local_plan');

        $html  = '<div class="buttons">';
        $html .= '<div class="singlebutton dp-plan-assign-button">';
        $html .= '<div>';
        $html .= '<script type="text/javascript">var course_id = ' . $courseid . ';';
        $html .= 'var plan_id = ' . $this->plan->id . ';</script>';
        $html .= '<input type="submit" id="show-competency-dialog" value="' . $btntext . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }


    /**
     * Check to see if the course can be deleted
     *
     * @access  public
     * @param   object  $item
     * @return  bool
     */
    public function can_delete_item($item) {

        // Check whether this course is a mandatory relation
        if ($this->is_mandatory_relation($item->id)) {
            if ($this->get_setting('deletemandatory') <= DP_PERMISSION_DENY) {
                return false;
            }
        }

        return parent::can_delete_item($item);
    }
}
