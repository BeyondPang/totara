<?php
class dp_objective_component extends dp_base_component {
    public static $permissions = array(
        'updateobjective' => true,
        //'commenton' => false,
        'setpriority' => false,
        'setduedate' => false,
        'setproficiency' => false
    );

    function __construct($plan) {
        $this->component = 'objective';
        $this->defaultname = get_string('objectives', 'local_plan');

        parent::__construct($plan);
    }

    function initialize_settings(&$settings) {
        if($objectivesettings = get_record('dp_objective_settings', 'templateid', $this->plan->templateid)) {
            $settings[$this->component.'_duedatemode'] = $objectivesettings->duedatemode;
            $settings[$this->component.'_prioritymode'] = $objectivesettings->prioritymode;
            $settings[$this->component.'_priorityscale'] = $objectivesettings->priorityscale;
            $settings[$this->component.'_objectivescale'] = $objectivesettings->objectivescale;
        }
    }

    /**
     * Can the logged in user update items in this plan
     *
     * Returns false if they cannot, or a constant detailing their
     * exact permissions if they can
     *
     * @access  public
     * @return  false|int
     */
    public function can_update_items() {
        // Get permissions
        $updateitem = $this->get_setting('updateobjective');
        if ( $updateitem == DP_PERMISSION_DENY ){
            return false;
        } else {
            return $updateitem;
        }
    }


    /**
     * Return markup for javascript course picker
     *
     * @access public
     * @global object $CFG
     * @return string
     */
    public function display_picker() {
        global $CFG;

        if (!$permission = $this->can_update_items()) {
            return '';
        }

        // Decide on button text
        if ($permission == DP_PERMISSION_ALLOW) {
            $btntext = get_string('addnewobjective', 'local_plan');
        } else {
            $btntext = get_string('requestednewobjective', 'local_plan');
        }

        $html = '<div class="buttons">';
        $html .= print_single_button("{$CFG->wwwroot}/local/plan/components/objective/edit.php", array('id'=>$this->plan->id), $btntext, 'get', '_SELF', true);
        $html .= '</div>';

        return $html;
    }


    /**
     * Get list of items assigned to plan
     *
     * @access  public
     * @return  array
     */
    public function get_assigned_items() {
        global $CFG;

        $assigned = get_records_sql(
            "
            SELECT
                obj.id,
                obj.planid,
                obj.fullname
            FROM
                {$CFG->prefix}dp_plan_objective obj
            WHERE
                obj.planid = {$this->plan->id}
            "
        );

        if (!$assigned) {
            $assigned = array();
        }

        return $assigned;
    }


    /**
     * Get list of items assigned to plan
     *
     * @access  public
     * @return  array
     */
    public function get_assigned_items_count() {
        global $CFG;

        $count = count_records('dp_plan_objective', 'planid', $this->plan->id);

        if (!$count) {
            $count = 0;
        }

        return $count;
    }


    /**
     * Generates a flexibletable listing all the objectives in the current plan.
     *
     * @global object $CFG
     * @return string
     */
    function display_objective_list() {
        global $CFG;

        $showduedates = ($this->get_setting('duedatemode') == DP_DUEDATES_OPTIONAL ||
            $this->get_setting('duedatemode') == DP_DUEDATES_REQUIRED);
        $showpriorities =
            ($this->get_setting('prioritymode') == DP_PRIORITY_OPTIONAL ||
            $this->get_setting('prioritymode') == DP_PRIORITY_REQUIRED);
        $priorityscaleid = ($this->get_setting('priorityscale')) ? $this->get_setting('priorityscale') : -1;
        $plancompleted = $this->plan->status == DP_PLAN_STATUS_COMPLETE;
        $cansetprofs = !$plancompleted && $this->get_setting('setproficiency') == DP_PERMISSION_ALLOW;
        $canapproveobjectives = !$plancompleted && $this->get_setting('updateobjective') == DP_PERMISSION_APPROVE;
        $canremoveobjectives = !$plancompleted && (
                $this->get_setting('updateobjective') == DP_PERMISSION_ALLOW
                || $this->get_setting('updateobjective') == DP_PERMISSION_APPROVE
        );
        $coursesenabled = $this->plan->get_component('course')->get_setting('enabled');

        $as = sql_as();
        $count = 'SELECT COUNT(*) ';
        $select = "SELECT o.id, o.planid, o.fullname {$as} objname, o.duedate, o.approved, o.scalevalueid ";
        $select .= ", psv.id as priority, psv.name {$as} priorityname ";
        $select .= ", osv.achieved ";
        if ( $coursesenabled ){
            $select .= ", (select count(*) from {$CFG->prefix}dp_plan_component_relation pcr where pcr.component1='course' and pcr.component2='objective' and pcr.itemid2=o.id) {$as} numcourses ";
        }
        // todo: Add evidence support
//        $select .= ", (select count(*) from {$CFG->prefix}dp_plan_relation pr where pr.itemtype1='evidence' and pr.itemtype2='objective' and pr.itemid2=o.id) {$as} numevidences ";

        // get objectives assigned to this plan
        $from = "FROM {$CFG->prefix}dp_plan_objective o ";
        $from .= "LEFT JOIN {$CFG->prefix}dp_objective_scale_value osv
                ON o.scalevalueid = osv.id ";
        $from .= "LEFT JOIN {$CFG->prefix}dp_priority_scale_value psv
                ON (o.priority = psv.id
                AND psv.priorityscaleid = {$priorityscaleid}) ";

        $where = "WHERE o.planid = {$this->plan->id} ";

        $count = count_records_sql($count.$from.$where);
        if (!$count) {
            return '<div class="noitems-assignobjectives">'.get_string('noobjectives', 'local_plan').'</div>';
        }

        $tableheaders = array(get_string('name', 'local_plan'));
        $tablecolumns = array('objname');

        if ( $coursesenabled ){
            $tableheaders[] = $this->plan->get_component('course')->get_setting('name');
            $tablecolumns[] = 'numcourses';
        }

        if($showpriorities) {
            $tableheaders[] = get_string('priority', 'local_plan');
            $tablecolumns[] = 'o.priority';
        }

        if($showduedates) {
            $tableheaders[] = get_string('duedate', 'local_plan');
            $tablecolumns[] = 'o.duedate';
        }

        $tableheaders[] = get_string('proficiency', 'local');
        $tablecolumns[] = 'o.scalevalueid';

        if(!$plancompleted) {
            $tableheaders[] = get_string('status','local_plan');
            $tablecolumns[] = 'status';
        }

        if($canremoveobjectives) {
            $tableheaders[] = get_string('actions', 'local_plan');
            $tablecolumns[] = 'actions';
        }

        $table = new flexible_table('objectivelist');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);

        $table->set_attribute('class', 'logtable generalbox dp-plan-component-items');
        $table->sortable(true);
        $table->no_sorting('status');
        $table->no_sorting('actions');
        $table->setup();
        $table->pagesize(20, $count);
        $sort = $table->get_sql_sort();
        $sort = ($sort=='') ? '' : ' ORDER BY ' . $sort;

        // get all course completions for this plan's user
        $completions = completion_info::get_all_courses($this->plan->userid);

        // get the scale values used for objectives in this plan
        if ($showpriorities){
            $priorityvalues = get_records('dp_priority_scale_value',
                'priorityscaleid', $priorityscaleid, 'sortorder', 'id,name,sortorder');
        }

        // Get the proficiency values for this plan
        $proficiencyvalues = get_records('dp_objective_scale_value', 'objscaleid', $this->get_setting('objectivescale'), 'sortorder','id,name,achieved');

        $records = get_recordset_sql(
                $select.$from.$where.$sort,
                $table->get_page_start(),
                $table->get_page_size()
        );
        if ( $records ){

            while($objective = rs_fetch_next_record($records)) {

                $objapproved = dp_is_approved($objective->approved);

                $row = array();
                $row[] = $this->display_objective_name($objective);
                if ( $coursesenabled ){
                    $row[] = $objective->numcourses;
                }
//                $row[] = $objective->numevidences;

                if($showpriorities) {
                    $row[] = $this->display_priority($objective, $priorityvalues);
                }

                if($showduedates) {
                    $row[] = $this->display_duedate($objective->id, $objective->duedate, null);
                }

                // Proficiency
                $row[] = $this->display_proficiency($objective, $proficiencyvalues);

                if(!$plancompleted) {
                    $status = '';
                    if($objapproved) {
                        if(!$objective->achieved) {
                            $status = $this->display_duedate_highlight_info($objective->duedate);
                        }
                    } else {
                        $status = $this->display_approval($objective, $canapproveobjectives);
                    }
                    $row[] = $status;
                }

                if($canremoveobjectives) {
                    $deleteurl = $CFG->wwwroot
                        . '/local/plan/components/objective/edit.php?id='
                        . $this->plan->id
                        . '&itemid='
                        . $objective->id
                        . '&d=1';
                    $strdelete = get_string('delete', 'local_plan');
                    $row[] = '<a href="'.$deleteurl.'" title="'.$strdelete.'"><img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall" alt="'.$strdelete.'" /></a>';
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
     * Generates a flexibletable of details for all the courses linked to the
     * objective
     *
     * @global object $CFG
     * @param int $objectiveid
     * @return string
     */
    function display_linked_courses($objectiveid) {
        global $CFG;

        $coursename = $this->plan->get_component('course')->get_setting('name');
        $tableheaders = array(
            get_string('linkedx', 'local_plan', $coursename),
        );
        $tablecolumns = array(
            'fullname',
        );

        $table = new flexible_table('linkedcourselist');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);

        $table->set_attribute('class', 'logtable generalbox dp-plan-component-items');
        $table->setup();

        $list = $this->get_linked_components($objectiveid, 'course');
        if(is_array($list) && count($list) > 0) {
            $sql = "
                select c.*
                from
                    {$CFG->prefix}course c
                    inner join {$CFG->prefix}dp_plan_course_assign ca
                    on c.id = ca.courseid
                where ca.id in (".implode(',', $list).") order by c.fullname
                ";
            //$sql = "select * from {$CFG->prefix}course c where c.id in (" . implode(',', $list) . ") order by c.fullname";
            $records = get_recordset_sql($sql);
            if ($records){

                while($ca = rs_fetch_next_record($records)) {

                    $row = array();
                    ob_start();
                    print_course($ca);
                    $row[] = ob_get_contents();
                    ob_end_clean();
                    $table->add_data($row);
                }

                rs_close($records);

            }
        } else {
            $table->add_data(array(get_string('nolinkedx', 'local_plan', $coursename)));
        }
        // return instead of outputing table contents
        ob_start();
        $table->print_html();
        $out = ob_get_contents();
        ob_end_clean();

        return $out;
    }

    /**
     * Generates a flexibletable of details for all the courses linked to the
     * objective
     *
     * @global object $CFG
     * @param int $objectiveid
     * @return string
     */
    function display_linked_objectives($list) {
        global $CFG;

        $objectivename = $this->get_setting('name');
        $tableheaders = array(
            get_string('linkedx', 'local_plan', $objectivename),
        );
        $tablecolumns = array(
            'fullname',
        );

        $table = new flexible_table('linkedobjectivelist');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);

        $table->set_attribute('class', 'logtable generalbox dp-plan-component-items');
        $table->setup();

        if ( count($list)>0 ){
            $records = get_records_select('dp_plan_objective', 'id in ('.  implode(',',$list) .')', 'fullname', 'id, fullname, planid');
            if ($records){

                foreach ( $records as $ca) {

                    $row = array();
                    $row[] = "<a href=\"{$CFG->wwwroot}/local/plan/components/objective/view.php?id={$ca->planid}&itemid={$ca->id}\">{$ca->fullname}</a>";
                    $table->add_data($row);
                }
            }
        } else {
            $table->add_data(array(get_string('nolinkedx', 'local_plan', $objectivename)));
        }
        // return instead of outputing table contents
        ob_start();
        $table->print_html();
        $out = ob_get_contents();
        ob_end_clean();

        return $out;
    }


    function display_objective_name($objective) {
        global $CFG;

        return '<a href="'.$CFG->wwwroot.'/local/plan/components/' .
            $this->component . '/view.php?id=' . $this->plan->id .
            '&amp;itemid=' . $objective->id . '">' . $objective->objname . '</a>';
    }


    /**
     * Create a form object for the data in an objective
     * @global object $CFG
     * @param int $objectiveid
     * @return plan_objective_edit_form
     */
    function objective_form($objectiveid=null) {
        global $CFG;
        require_once($CFG->dirroot.'/local/plan/components/objective/edit_form.php');
        $customdata = array(
            'plan' => $this->plan,
            'objective' => $this
        );
        if ( empty($objectiveid) ){
            return new plan_objective_edit_form( null, $customdata );
        } else {

            if (!$objective = get_record('dp_plan_objective', 'id', $objectiveid)){
                error(get_string('error:objectiveidincorrect', 'local_plan'));
            }
            $objective->itemid = $objective->id;
            $objective->id = $objective->planid;
            unset($objective->planid);

            $mform = new plan_objective_edit_form(
                    null,
                    array(
                        'plan'=>$this->plan,
                        'objective'=>$this,
                        'objectiveid'=>$objectiveid
                    )
            );
            $mform->set_data($objective);
            return $mform;
        }
    }


    /**
     * Display approval options for objectives
     *
     * Overwrite base display_approval_options() method to show links instead of
     * pulldown menu. This is necessary because each objective must be
     * individually approved (to set evidence/assessor etc)
     *
     * @param stdClass $obj The assignment object
     * @param integer $approvalstatus The currently selected approval status
     * @return $out string an html string
     */
    function display_approval_options($obj, $approvalstatus) {
        global $CFG;
        // @todo link to relevant pages
        // @todo add icons
        return '<a href="' . $CFG->wwwroot . '/local/plan/components/' .
            $this->component . '/approval.php?id=' . $obj->planid . '&amp;itemid=' .
            $obj->id . '&amp;action=approve">' . get_string('approve','local_plan') . '</a> ' .
            '<a href="' . $CFG->wwwroot . '/local/plan/components/' .
            $this->component . '/approval.php?id=' . $obj->planid . '&amp;itemid=' .
            $obj->id . '&amp;action=decline">' . get_string('decline','local_plan') . '</a> ';
    }

    /**
     * Display a proficiency (or the dropdown menu for it)
     * @param object $ca The current objective
     * @param array $proficiencyvalues A list of all the proficiencies in the objective scale for this objective
     * @return string
     */
    function display_proficiency($ca, $proficiencyvalues) {
        $plancompleted = ($this->plan->status == DP_PLAN_STATUS_COMPLETE);
        $cansetprof = $this->get_setting('setproficiency') == DP_PERMISSION_ALLOW;
        $out = '';

        $selected = $ca->scalevalueid;

        if ( !$plancompleted && $cansetprof ){
            // Show the menu
            $options = array();
            foreach( $proficiencyvalues as $id => $val){
                $options[$id] = $val->name;
            }
            return choose_from_menu($options, "proficiencies[{$ca->id}]", $selected, null, '', null, true);

        } else {
            // They can't change the setting, so show it as-is
            $out = format_string($proficiencyvalues[$selected]->name);
            if ( $proficiencyvalues[$selected]->achieved ){
                $out = '<b>'.$out.'</b>';
            }
            return $out;
        }
    }

    function process_objective_settings_update() {
        if (!confirm_sesskey()) {
            return 0;
        }
        // @todo validation notices, including preventing empty due dates
        // if duedatemode is required
        $cansetduedates = ($this->get_setting('setduedate') == DP_PERMISSION_ALLOW);
        $cansetpriorities = ($this->get_setting('setpriority') == DP_PERMISSION_ALLOW);
        $cansetprofs = ($this->get_setting('setproficiency') == DP_PERMISSION_ALLOW);
        $canapprovecomps = ($this->get_setting('updateobjective') == DP_PERMISSION_APPROVE);
        $duedates = optional_param('duedate', array(), PARAM_TEXT);
        $priorities = optional_param('priorities', array(), PARAM_INT);
        $proficiencies = optional_param('proficiencies', array(), PARAM_INT);
        $approvals = optional_param('approve', array(), PARAM_INT);
        $currenturl = qualified_me();
        $stored_records = array();

        $status = true;
        if(!empty($duedates) && $cansetduedates) {
            // Update duedates
            foreach($duedates as $id => $duedate) {
                // allow empty due dates
                if($duedate == '' || $duedate == 'dd/mm/yy') {
                    $duedateout = null;
                } else {
                    $datepattern = '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/(\d{2})$/';
                    if (preg_match($datepattern, $duedate, $matches) == 0) {
                        // skip badly formatted date strings
                        continue;
                    }
                    $day = $matches[1];
                    $mon = $matches[2];
                    $year = $matches[3];

                    $duedateout = mktime(0, 0, 0, $mon, $day, $year);
                }

                $todb = new object();
                $todb->id = $id;
                $todb->duedate = $duedateout;
                $stored_records[$id] = $todb;
            }
        }

        if(!empty($priorities) && $cansetpriorities) {
            foreach($priorities as $id => $priority) {
                $priority = (int) $priority;
                if(array_key_exists($id, $stored_records)) {
                    // add to the existing update object
                    $stored_records[$id]->priority = $priority;
                } else {
                    // create a new update object
                    $todb = new object();
                    $todb->id = $id;
                    $todb->priority = $priority;
                    $stored_records[$id] = $todb;
                }
            }
        }

        if (!empty($proficiencies) && $cansetprofs) {
            foreach( $proficiencies as $id => $proficiency){
                $proficiency = (int) $proficiency;
                if ( array_key_exists($id, $stored_records) ){
                    $stored_records[$id]->scalevalueid = $proficiency;
                } else {
                    // Create a new update object
                    $todb = new stdClass();
                    $todb->id = $id;
                    $todb->scalevalueid = $proficiency;
                    $stored_records[$id] = $todb;
                }
            }
        }

        if (!empty($approvals) && $canapprovecomps) {
            // Update approvals
            foreach ($approvals as $id => $approval) {
                if(array_key_exists($id, $stored_records)) {
                    // add to the existing update object
                    $stored_records[$id]->approved = $approval;
                } else {
                    // create a new update object
                    $todb = new object();
                    $todb->id = $id;
                    $todb->approved = $approved;
                    $stored_records[$id] = $todb;
                }
            }
        }
        $status = true;

        // save before snapshot of objectives
        $orig_objectives = get_records_list('dp_plan_objective', 'id', implode(',', array_keys($stored_records)));

        if (!empty($stored_records)) {
            begin_sql();
            foreach($stored_records as $itemid => $record) {
                $status = $status & update_record('dp_plan_objective', $record);
            }
            if($status) {
                commit_sql();
                $this->plan->set_status_unapproved_if_declined();
                // process update notifications
                foreach($stored_records as $itemid => $record) {
                    // priority may have been updated
                    if (isset($record->priority) && array_key_exists($itemid, $orig_objectives) && $record->priority != $orig_objectives[$itemid]->priority) {
                        $this->send_edit_notification($orig_objectives[$itemid], 'priority');
                    }
                    // proficiency may have been updated
                    if (isset($record->scalevalueid) && array_key_exists($itemid, $orig_objectives) && $record->scalevalueid != $orig_objectives[$itemid]->scalevalueid) {
                        $orig_objectives[$itemid]->scalevalueid = $record->scalevalueid;
                        $this->send_status_notification($orig_objectives[$itemid]);
                    }
                }
                totara_set_notification(get_string('objectivesupdated','local_plan'), $currenturl, array('style'=>'notifysuccess'));
            } else {
                rollback_sql();
                totara_set_notification(get_string('objectivesnotupdated','local_plan'), $currenturl);
            }
        }

        redirect($currenturl);
    }


    /**
     * Completely delete an objective
     * @param int $caid
     * @return boolean success or failure
     */
    function delete_objective($caid) {
        // need permission to remove this objective
        if(!$this->can_update_items()) {
            return false;
        }

        // store objective details for notifications
        $objective = get_record('dp_plan_objective', 'id', $caid);

        begin_sql();
        $result = delete_records('dp_plan_objective', 'id', $caid);
        $result = $result && delete_records('dp_plan_component_relation', 'component1', 'objective', 'itemid1', $caid);
        $result = $result && delete_records('dp_plan_component_relation', 'component2', 'objective', 'itemid2', $caid);
        commit_sql();

        // are we OK? then send the notifications
        if ($result) {
            $this->send_deletion_notification($objective);
        }

        return $result;
    }

    /**
     * Create a new objective. (Does not check for permissions)
     * @param string $fullname
     * @param string $shortname
     * @param string $description
     * @param int $priority
     * @param int $duedate
     * @return boolean
     */
    public function create_objective($fullname, $shortname=null, $description=null, $priority=null, $duedate=null) {
        if ( !$this->can_update_items() ){
            return false;
        }

        $rec = new stdClass();
        $rec->planid = $this->plan->id;
        $rec->fullname = $fullname;
        $rec->shortname = $shortname;
        $rec->description = $description;
        $rec->priority = $priority;
        $rec->duedate = $duedate;
        $rec->scalevalueid = get_field('dp_objective_scale', 'defaultid', 'id', $this->get_setting('objectivescale'));
        $rec->approved = $this->approval_status_after_update();

        $result = insert_record('dp_plan_objective', $rec);

        // are we OK? then send the notifications
        if ($result) {
            $this->send_creation_notification($result, $shortname);
        }
        return $result;
    }

    /**
     * Construct the link for the current user
     * @return string user link
     */
    function current_user() {
        global $USER, $CFG;

        $userfrom_link = $CFG->wwwroot.'/user/view.php?id='.$USER->id;
        $fromname = fullname($USER);
        return "<a href=\"{$userfrom_link}\" title=\"$fromname\">$fromname</a> ";
    }

    /**
     * send objective deletion notification
     * @param object $objective Objective details
     * @return nothing
     */
    function send_deletion_notification($objective) {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

        $event = new stdClass;
        $userfrom = get_record('user', 'id', $USER->id);
        $event->userfrom = $userfrom;
        $event->contexturl = "{$CFG->wwwroot}/local/plan/view.php?id={$this->plan->id}";
        $event->icon = 'objective-remove.png';
        $a = new stdClass;
        $a->objective = $objective->shortname;
        $a->plan = "<a href=\"{$event->contexturl}\" title=\"{$this->plan->name}\">{$this->plan->name}</a>";

        // did they delete it themselves?
        if ($USER->id == $this->plan->userid) {
            // notify their manager
            if ($manager = totara_get_manager($this->plan->userid)) {
                $event->userto = $manager;
                $event->subject = get_string('objectivedeleteshortmanager', 'local_plan', $this->current_user());
                $event->fullmessage = get_string('objectivedeletelongmanager', 'local_plan', $a);
                $event->roleid = get_field('role','id', 'shortname', 'manager');
                tm_notification_send($event);
            }
        }
        // notify user that someone else did it
        else {
            $userto = get_record('user', 'id', $this->plan->userid);
            $event->userto = $userto;
            $event->subject = get_string('objectivedeleteshortlearner', 'local_plan', $a->objective);
            $event->fullmessage = get_string('objectivedeletelonglearner', 'local_plan', $a);
            tm_notification_send($event);
        }
    }

    /**
     * send objective creation notification
     * @param int $objid Objective Id
     * @param string $shortname the shortname of the objective
     * @return nothing
     */
    function send_creation_notification($objid, $shortname) {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

        $event = new stdClass;
        $userfrom = get_record('user', 'id', $USER->id);
        $event->userfrom = $userfrom;
        $event->contexturl = "{$CFG->wwwroot}/local/plan/components/objective/view.php?id={$this->plan->id}&itemid={$objid}";
        $event->icon = 'objective-add.png';
        $a = new stdClass;
        $a->objective = "<a href=\"{$event->contexturl}\" title=\"$shortname\">$shortname</a>";
        $a->plan = "<a href=\"{$CFG->wwwroot}/local/plan/view.php?id={$this->plan->id}\" title=\"{$this->plan->name}\">{$this->plan->name}</a>";

        // did they create it themselves?
        if ($USER->id == $this->plan->userid) {
            // notify their manager
            if ($manager = totara_get_manager($this->plan->userid)) {
                $event->userto = $manager;
                $event->subject = get_string('objectivenewshortmanager', 'local_plan', $this->current_user());
                $event->fullmessage = get_string('objectivenewlongmanager', 'local_plan', $a);
                $event->roleid = get_field('role','id', 'shortname', 'manager');
                tm_notification_send($event);
            }
        }
        // notify user that someone else did it
        else {
            $userto = get_record('user', 'id', $this->plan->userid);
            $event->userto = $userto;
            $event->subject = get_string('objectivenewshortlearner', 'local_plan', $shortname);
            $event->fullmessage = get_string('objectivenewlonglearner', 'local_plan', $a);
            tm_notification_send($event);
        }
    }


    /**
     * send objective edit notification
     * @param object $objective Objective record
     * @param string $field field updated
     * @return nothing
     */
    function send_edit_notification($objective, $field) {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

        $event = new stdClass;
        $userfrom = get_record('user', 'id', $USER->id);
        $event->userfrom = $userfrom;
        $event->contexturl = "{$CFG->wwwroot}/local/plan/components/objective/view.php?id={$this->plan->id}&itemid={$objective->id}";
        $event->icon = 'objective-update.png';
        $a = new stdClass;
        $a->objective = "<a href=\"{$event->contexturl}\" title=\"{$objective->shortname}\">{$objective->shortname}</a>";
        $a->plan = "<a href=\"{$CFG->wwwroot}/local/plan/view.php?id={$this->plan->id}\" title=\"{$this->plan->name}\">{$this->plan->name}</a>";
        $a->field = get_string('objective'.$field, 'local_plan');

        // did they edit it themselves?
        if ($USER->id == $this->plan->userid) {
            // notify their manager
            if ($manager = totara_get_manager($this->plan->userid)) {
                $event->userto = $manager;
                $event->subject = get_string('objectiveeditshortmanager', 'local_plan', $this->current_user());
                $event->fullmessage = get_string('objectiveeditlongmanager', 'local_plan', $a);
                $event->roleid = get_field('role','id', 'shortname', 'manager');
                tm_notification_send($event);
            }
        }
        // notify user that someone else did it
        else {
            $userto = get_record('user', 'id', $this->plan->userid);
            $event->userto = $userto;
            $event->subject = get_string('objectiveeditshortlearner', 'local_plan', $a->objective);
            $event->fullmessage = get_string('objectiveeditlonglearner', 'local_plan', $a);
            tm_notification_send($event);
        }
    }

    /**
     * send objective status notification
     *
     * handles both complete and incomplete
     *
     * @param object $objective Objective record
     * @return nothing
     */
    function send_status_notification($objective) {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

        // determined achieved/non-achieved status
        $achieved = get_field('dp_objective_scale_value', 'achieved', 'id', $objective->scalevalueid);
        $status = ($achieved ? 'complete' : 'incomplete');

        // build event message
        $event = new stdClass;
        $userfrom = get_record('user', 'id', $USER->id);
        $event->userfrom = $userfrom;
        $event->contexturl = "{$CFG->wwwroot}/local/plan/components/objective/view.php?id={$this->plan->id}&itemid={$objective->id}";
        $event->icon = 'objective-'.($status == 'complete' ? 'complete' : 'fail').'.png';
        $a = new stdClass;
        $a->objective = "<a href=\"{$event->contexturl}\" title=\"{$objective->shortname}\">{$objective->shortname}</a>";
        $a->plan = "<a href=\"{$CFG->wwwroot}/local/plan/view.php?id={$this->plan->id}\" title=\"{$this->plan->name}\">{$this->plan->name}</a>";

        // did they complete it themselves?
        if ($USER->id == $this->plan->userid) {
            // notify their manager
            if ($manager = totara_get_manager($this->plan->userid)) {
                $event->userto = $manager;
                $event->subject = get_string('objective'.$status.'shortmanager', 'local_plan', $this->current_user());
                $event->fullmessage = get_string('objective'.$status.'longmanager', 'local_plan', $a);
                $event->roleid = get_field('role','id', 'shortname', 'manager');
                tm_notification_send($event);
            }
        }
        // notify user that someone else did it
        else {
            $userto = get_record('user', 'id', $this->plan->userid);
            $event->userto = $userto;
            $event->subject = get_string('objective'.$status.'shortlearner', 'local_plan', $a->objective);
            $event->fullmessage = get_string('objective'.$status.'longlearner', 'local_plan', $a);
            tm_notification_send($event);
        }
    }

    /**
     * Update instances of $componentupdatetype linked to the specified compoent,
     * delete links in db which aren't needed, and add links missing from db
     * which are needed
     *
     * specialised from super class to allow the hooking of notifications
     *
     * @param integer $thiscompoentid Identifies the component on one end of the link
     * @param string $componentupdatetype: the type of components on the other end of the links
     * @param array $componentids array of component ids that should be on the other end of the links in db
     *
     * @return void
     */
    function update_linked_components($thiscomponentid, $componentupdatetype, $componentids) {

        parent::update_linked_components($thiscomponentid, $componentupdatetype, $componentids);

        if ($componentupdatetype == 'course') {
            $objective = get_record('dp_plan_objective', 'id', $thiscomponentid);
            $this->send_edit_notification($objective, 'course');
        }

    }

    /**
     * Print details about an objective
     * @global object $CFG
     * @param int $objectiveid
     * @return void
     */
    public function print_objective_detail($objectiveid){
        global $CFG;

        $priorityscaleid = ($this->get_setting('priorityscale')) ? $this->get_setting('priorityscale') : -1;
        $objectivescaleid = $this->get_setting('objectivescale');
        $priorityenabled = $this->get_setting('prioritymode') != DP_PRIORITY_NONE;
        $duedateenabled = $this->get_setting('duedatemode') != DP_DUEDATES_NONE;
        $requiresapproval = $this->get_setting('updateobjective') == DP_PERMISSION_REQUEST;

        $as = sql_as();
        $sql = <<<SQL
            select
                o.id,
                o.fullname,
                o.shortname,
                o.description,
                o.approved,
                o.duedate,
                o.priority,
                psv.name {$as} priorityname,
                osv.name {$as} profname,
                osv.achieved
            from
                {$CFG->prefix}dp_plan_objective o
                left join {$CFG->prefix}dp_objective_scale_value osv on (o.scalevalueid=osv.id and osv.objscaleid={$objectivescaleid})
                left join {$CFG->prefix}dp_priority_scale_value psv on (o.priority=psv.id and psv.priorityscaleid={$priorityscaleid})
            where
                o.id={$objectiveid}
SQL;
        $item = get_record_sql($sql);

        if(!$item) {
            return get_string('error:objectivenotfound','local_plan');
        }

        $out = '';

        // get the priority values used for competencies in this plan
        $priorityvalues = get_records('dp_priority_scale_value',
            'priorityscaleid', $priorityscaleid, 'sortorder', 'id,name,sortorder');

        // @todo add competency icon
        $out .= "<h3>" . get_string('fullname') . ": {$item->fullname}</h3>\n";
        $out .= "<table border=\"0\">\n";
        $out .= "<tr>\n";
        $out .= "  <th>" . get_string('shortname') .":</th>\n";
        $out .= "  <td>{$item->shortname}</td>\n";
        $out .= "</tr>\n";
        $out .= "<tr>\n";
        $out .= "  <th>" . get_string('description') .":</th>\n";
        $out .= "  <td>{$item->description}</td>\n";
        $out .= "</tr>\n";

        if($priorityenabled) {
            $out .= '<tr><th>';
            $out .= get_string('priority', 'local_plan') . ':';
            $out .= '</th><td>';
            $out .= $this->display_priority_as_text($item->priority,
                $item->priorityname, $priorityvalues);
            $out .= '</td></tr>';
        }
        if($duedateenabled) {
            $out .= '<tr><th>';
            $out .= get_string('duedate', 'local_plan') . ':';
            $out .= '</th><td>';
            $out .= $this->display_duedate_as_text($item->duedate);
            if ( !$item->achieved ){
                $out .= '<br />';
                $out .= $this->display_duedate_highlight_info($item->duedate);
            }
            $out .= '</td></tr>';
        }
        $out .= "<tr>\n";
        $out .= "  <th>Proficiency:</th>\n";
        $out .= "  <td>$item->profname</td>\n";
        $out .= "</tr>\n";
        if ($requiresapproval){
            $out .= "<tr>\n";
            $out .= "  <th>" . get_string('status') .":</th>\n";
            $out .= "  <td>".$this->display_approval($item, false, false)."</td>\n";
            $out .= "</tr>\n";
        }
        $out .= '</table>';

        print $out;
    }

    /**
     * Return just the "approval" field for an objective
     * @param int $caid
     * return int
     */
    public function get_approval($caid){
        return get_field('dp_plan_objective', 'approved', 'id', $caid);
    }

    /**
     * Indicates what the objective's approval status should be if the approval
     * is updated.
     * @return int (or false on failure)
     */
    public function approval_status_after_update( ){
        $perm = $this->can_update_items();
        if ( $perm == DP_PERMISSION_REQUEST ){
            return DP_APPROVAL_UNAPPROVED;
        }
        if ( in_array( $perm, array( DP_PERMISSION_ALLOW, DP_PERMISSION_APPROVE ) ) ){
            return DP_APPROVAL_APPROVED;
        }

        // In case something went wrong, fall back to unapproved status
        return DP_APPROVAL_UNAPPROVED;
    }

    /*
     * Return markup for javascript course picker
     * objectiveid integer - the id of the objective for which selected& available courses should be displayed
     * @access  public
     * @return  string
     */
    public function display_course_picker($objectiveid) {

        if (!$permission = $this->can_update_items()) {
            return '';
        }

        $coursename = $this->plan->get_component('course')->get_setting('name');
        $btntext = get_string('updatelinkedx', 'local_plan', $coursename);

        $html  = '<div class="buttons">';
        $html .= '<div class="singlebutton dp-plan-assign-button">';
        $html .= '<div>';
        $html .= '<script type="text/javascript">var objective_id = ' . $objectiveid . ';';
        $html .= 'var plan_id = ' . $this->plan->id . ';</script>';
        $html .= '<input type="submit" id="show-course-dialog" value="' . $btntext . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Indicates whether an update will revoke the "approved" status of the
     * component
     * @param <type> $caid
     * @return boolean
     */
    public function will_an_update_revoke_approval( $caid ){
        // If the resource is already approved, and the user has only REQUEST
        // permission, then it will revoke the approved status. Otherwise,
        // no change.
        if (
                $this->can_update_items() == DP_PERMISSION_REQUEST
                && $this->get_approval($caid) != DP_APPROVAL_UNAPPROVED
        ){
            return true;
        } else {
            return false;
        }
    }
}
