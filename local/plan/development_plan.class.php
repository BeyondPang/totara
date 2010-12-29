<?php

class development_plan {
    public static $permissions = array(
        'view' => false,
        'create' => false,
        'update' => false,
        'delete' => false,
        'confirm' => true,
        'signoff' => true
    );
    public $id, $templateid, $userid, $name, $description;
    public $startdate, $enddate, $status, $role, $settings;
    public $viewas;

    function __construct($id, $viewas=null) {
        global $USER, $CFG;

        // get plan db record
        $plan = get_record('dp_plan', 'id', $id);
        if(!$plan) {
            throw new PlanException(get_string('planidnotfound','local_plan', $id));
        }

        // get details about this plan
        $this->id = $id;
        $this->templateid = $plan->templateid;
        $this->userid = $plan->userid;
        $this->name = $plan->name;
        $this->description = $plan->description;
        $this->startdate = $plan->startdate;
        $this->enddate = $plan->enddate;
        $this->status = $plan->status;

        // default to viewing as the current user
        // if $viewas not set
        if(empty($viewas)) {
            $this->viewas = $USER->id;
        } else {
            $this->viewas  = $viewas;
        }

        // store role and component objects for easy access
        $this->load_roles();
        $this->load_components();

        // get the user's role in this plan
        $this->role = $this->get_user_role($this->viewas);

        // lazy-load settings from database when first needed
        $this->settings = null;
    }


    /**
     * Save an instance of each defined role to a property of this class
     *
     * This method creates a property $this->[role] for each entry in
     * $DP_AVAILABLE_ROLES, and fills it with an instance of that role.
     *
     */
    function load_roles() {

        global $CFG, $DP_AVAILABLE_ROLES;

        // loop through available roles
        foreach ($DP_AVAILABLE_ROLES as $role) {
            // include each class file
            $classfile = $CFG->dirroot .
                "/local/plan/roles/{$role}/{$role}.class.php";
            if(!is_readable($classfile)) {
                $string_params = new object();
                $string_params->classfile = $classfile;
                $string_params->role = $role;
                throw new PlanException(get_string('noclassfileforrole', 'local_plan', $string_params));
            }
            include_once($classfile);

            // check class exists
            $class = "dp_{$role}_role";
            if(!class_exists($class)) {
                $string_params = new object();
                $string_params->class = $class;
                $string_params->role = $role;
                throw new PlanException(get_string('noclassforrole', 'local_plan', $string_params));
            }

            $rolename = "role_$role";

            // create an instance and save as a property for easy access
            $this->$rolename = new $class($this);
        }

    }

    function get_role($role) {
        $rolename = "role_$role";
        return $this->$rolename;
    }


    /**
     * Save an instance of each defined component to a property of this class
     *
     * This method creates a property $this->[component] for each entry in
     * $DP_AVAILABLE_COMPONENTS, and fills it with an instance of that component.
     *
     */
    function load_components() {
        global $CFG, $DP_AVAILABLE_COMPONENTS;

        foreach($DP_AVAILABLE_COMPONENTS as $component) {
            // include each class file
            $classfile = $CFG->dirroot .
                "/local/plan/components/{$component}/{$component}.class.php";
            if(!is_readable($classfile)) {
                $string_params = new object();
                $string_params->classfile = $classfile;
                $string_params->component = $component;
                throw new PlanException(get_string('noclassfileforcomponent', 'local_plan', $string_params));
            }
            include_once($classfile);

            // check class exists
            $class = "dp_{$component}_component";
            if(!class_exists($class)) {
                $string_params = new object();
                $string_params->class = $class;
                $string_params->component = $component;
                throw new PlanException(get_string('noclassforcomponent', 'local_plan', $string_params));
            }

            $componentname = "component_$component";

            // create an instance and save as a property for easy access
            $this->$componentname = new $class($this);
        }
    }

    function get_component($component) {
        $componentname = "component_$component";
        return $this->$componentname;
    }


    function get_component_setting($component, $action) {
        // we need the know the template to get settings
        if(!$this->templateid) {
            return false;
        }
        $role = $this->role;
        $templateid = $this->templateid;

        // only load settings when first needed
        if(!isset($this->settings)) {
            $this->initialize_settings();
        }

        // return false the setting if it exists
        if(array_key_exists($component.'_'.$action, $this->settings)) {
            return $this->settings[$component.'_'.$action];
        }

        // return the role specific setting if it exists
        if(array_key_exists($component.'_'.$action.'_'.$role, $this->settings)) {
            return $this->settings[$component.'_'.$action.'_'.$role];
        }

        // return null if nothing set
        print_error('error:settingdoesnotexist', 'local_plan', '', (object)array('component'=>$component, 'action'=>$action));
    }

    function get_setting($action) {
        return $this->get_component_setting('plan', $action);
    }

    function initialize_settings() {
        global $DP_AVAILABLE_COMPONENTS;
        // no need to initialize twice
        if(isset($this->settings)) {
            return true;
        }
        // can't initialize without a template id
        if(!$this->templateid) {
            return false;
        }

        // add role-based settings from permissions table
        if($results = get_records('dp_permissions', 'templateid',
            $this->templateid)) {

            foreach($results as $result) {
                $this->settings[$result->component.'_'.$result->action.'_'.$result->role] = $result->value;
            }
        }

        // add component-independent settings
        if ($components = get_records('dp_component_settings', 'templateid', $this->templateid, 'sortorder')) {
            foreach($components as $component) {
                // is this component enabled?
                $this->settings[$component->component.'_enabled'] = $component->enabled;

                // get the name from a config var, or use the default name if not set
                $configname = 'dp_'.$component->component;
                $name = get_config(null, $configname);
                $this->settings[$component->component.'_name'] = $name ? $name :
                    get_string($component->component.'_defaultname', 'local_plan');
            }
        }

        // also save the whole list together with sort order
        $this->settings['plan_components'] = $components;

        // add role-independent settings from individual component tables
        foreach($DP_AVAILABLE_COMPONENTS as $component) {
            // only include if the component is enabled
            if(!$this->get_component($component)->get_setting('enabled')) {
                continue;
            }
            $this->get_component($component)->initialize_settings($this->settings);
        }
    }


    function display_tabs($currenttab) {
        global $CFG;

        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        // overview tab
        $row[] = new  tabobject('plan', $CFG->wwwroot .
                '/local/plan/view.php?id=' .
                $this->id, get_string('overview','local_plan'));

        // get active components in correct order
        $components = $this->get_setting('components');

        if($components) {
            foreach($components as $component) {
                // don't show tabs for disabled components
                if(!$component->enabled) {
                    continue;
                }
                $componentname =
                    $this->get_component($component->component)->get_setting('name');

                $row[] = new tabobject($component->component, $CFG->wwwroot .
                    '/local/plan/components/' . $component->component .
                    '/index.php?id=' . $this->id, $componentname);
            }
        }

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }

    function display_summary_widget() {
        global $CFG;

        $out = '';
        $out .= "<div class=\"dp-summary-widget-title\"><a href=\"{$CFG->wwwroot}/local/plan/view.php?id={$this->id}\">{$this->name}</a></div>";

        $components = get_records_select('dp_component_settings', "templateid={$this->templateid} AND enabled=1", 'sortorder');
        $total = count($components);
        $out .= "<div class=\"dp-summary-widget-components\">";
        if ($components) {
            $count = 1;
            foreach ($components as $c) {
                $compname = $this->get_component($c->component)->get_setting('name');
                $class = ($count == $total) ? "dp-summary-widget-component-name-last" : "dp-summary-widget-component-name";
                $assignments = $this->get_component($c->component)->get_assigned_items();
                $assignments = !empty($assignments) ? '('.count($assignments).')' : '';

                $out .= "<span class=\"{$class}\">";
                $out .= "<a href=\"{$CFG->wwwroot}/local/plan/components/{$c->component}/index.php?id={$this->id}\">";
                $out .= $compname;

                if ($assignments) {
                    $out .= " $assignments";
                }

                $out .= "</a> </span>";
                $count++;
            }
        }
        $out .= "</div>";

        $out .= "<div class=\"dp-summary-widget-description\">{$this->description}</div>";

        return $out;
    }

    function display_add_plan_icon() {
        global $CFG;

        $out = '';
        $out .= "<a href=\"{$CFG->wwwroot}/local/plan/edit.php\"><img src=\"{$CFG->wwwtheme}/pix/t/add.gif\" title=\"".get_string('addplan', 'local_plan')."\" alt=\"".get_string('addplan', 'local_plan')."\"/></a>";

        return $out;
    }

    function display_enddate() {
        $cansetenddate = false; // @todo ($this->get_setting('setenddate') == DP_PERMISSION_ALLOW);

        $out = '';

        // only show a form if they have permission to change due dates
        if($cansetenddate) {
            $out .= $this->display_enddate_as_form($this->enddate, "enddate[{$this->id}]");
        } else {
            $out .= $this->display_enddate_as_text($this->enddate);
        }

        // highlight dates that are overdue or due soon
        $out .= $this->display_enddate_highlight_info($this->enddate);

        return $out;

    }

    function display_enddate_as_form($enddate, $name) {
        // @todo add date picker?
        global $CFG;
        $enddatestr = isset($enddate) ?
            userdate($enddate, '%d/%m/%y', $CFG->timezone, false) : '';
        return '<input type="text" name="'.$name.'" value="'. $enddatestr . '" size="8" maxlength="20"/>';
    }

    function display_enddate_as_text($enddate) {
        global $CFG;
        if(isset($enddate)) {
            return userdate($enddate, '%e %h %Y', $CFG->timezone, false);
        } else {
            return '';
        }
    }

    function display_enddate_highlight_info($enddate) {
        // @todo use a different class that doesn't add padding
        $out = '';
        $now = time();
        if(isset($enddate)) {
            if(($enddate < $now) && ($now - $enddate < 60*60*24)) {
                $out .= '<br /><span class="notifyproblem">' . get_string('duetoday', 'local_plan') . '</span>';
            } else if($enddate < $now) {
                $out .= '<br /><span class="notifyproblem">' . get_string('overdue', 'local_plan') . '</span>';
            } else if ($enddate - $now < 60*60*24*7) {
                $days = ceil(($enddate - $now)/(60*60*24));
                $out .= '<br /><span class="notifyproblem">' . get_string('dueinxdays', 'local_plan', $days) . '</span>';
            }
        }
        return $out;
    }

    /**
     * Determines and displays the progress of this plan.
     *
     * Progress is determined by course completion statuses.
     *
     * @access  public
     * @return  string
     */
    public function display_progress() {
        if ($this->status == DP_PLAN_STATUS_UNAPPROVED) {
            return get_string('awaitingapproval', 'local_plan');
        }
        if ($this->status == DP_PLAN_STATUS_DECLINED) {
            return get_string('declined', 'local_plan');
        }

        $completionsum = 0;
        $completedcount = 0;
        $inprogresscount = 0;
        $progress = 0;

        // Get all course completion info for the plan's user
        $completions = completion_info::get_all_courses($this->userid);

        // Get courses assigned to this plan
        if ($courses = $this->get_component('course')->get_assigned_items()) {
            foreach ($courses as $c) {
                if (!$c->approved) {
                    continue;
                }
                // Determine course completion
                $completionstatus = $this->get_component('course')->get_completion_status($c, $completions);
                if (empty($completionstatus)) {
                    continue;
                }
                switch ($completionstatus) {
                    case 'complete' :
                        $completionsum += 1;
                        $completedcount++;
                        break;
                    case 'inprogress' :
                    default:
                        $completionsum += 0.5;
                        $inprogresscount++;
                        break;

                }
            }

            // Calculate progress
            $progress = $completionsum / count($courses) * 100;
        }
        $tooltipstr = "{$completedcount}/".count($courses)." ".get_string('coursescomplete', 'local_plan').", {$inprogresscount} ".get_string('inprogress', 'local_plan')." ({$progress}%)";

        // Get relevant progress bar and return for display
        return local_display_progressbar($progress, 'medium', false, $tooltipstr);
    }

    function display_completeddate() {
        global $CFG;

        // Ensure plan is currently completed
        if ($this->status != DP_PLAN_STATUS_COMPLETE) {
            return get_string('notcompleted', 'local_plan');
        }

        // Get the last modification and make sure that it has DP_PLAN_STATUS_COMPLETE status
        $history = $this->get_history('id DESC');
        $latestmodification = reset($history);

        return ($latestmodification->status != DP_PLAN_STATUS_COMPLETE) ? get_string('notcompleted', 'local_plan') : userdate($latestmodification->timemodified, '%d/%m/%y', $CFG->timezone, false);
    }

    // Displays icons of current actions the user can perform on the plan
    function display_actions() {
        global $CFG;

        // @todo: USE NICE ICONS

        ob_start();

        // Approval
        if ($this->status == DP_PLAN_STATUS_UNAPPROVED) {
            // Approval request
            if ($this->get_setting('confirm') == DP_PERMISSION_REQUEST) {
                echo '<a href="'.$CFG->wwwroot.'/local/plan/action.php?id='.$this->id.'&approvalrequest=1&sesskey='.sesskey().'" title="'.get_string('sendapprovalrequest', 'local_plan').'">
                    <img src="'.$CFG->pixpath.'/t/feedback_add.gif" alt="'.get_string('sendapprovalrequest', 'local_plan').'" />
                    </a>';
            }

            // Approve/Decline
            if (in_array($this->get_setting('confirm'), array(DP_PERMISSION_ALLOW, DP_PERMISSION_APPROVE))) {
                echo '<a href="'.$CFG->wwwroot.'/local/plan/action.php?id='.$this->id.'&approve=1&sesskey='.sesskey().'" title="'.get_string('approve', 'local_plan').'">
                    <img src="'.$CFG->pixpath.'/t/go.gif" alt="'.get_string('approve', 'local_plan').'" />
                    </a>';
                echo '<a href="'.$CFG->wwwroot.'/local/plan/action.php?id='.$this->id.'&decline=1&sesskey='.sesskey().'" title="'.get_string('decline', 'local_plan').'">
                    <img src="'.$CFG->pixpath.'/t/stop.gif" alt="'.get_string('decline', 'local_plan').'" />
                    </a>';
            }
        }

        // Complete
        if ($this->status == DP_PLAN_STATUS_APPROVED && $this->get_setting('signoff') == DP_PERMISSION_ALLOW) {
            echo '<a href="'.$CFG->wwwroot.'/local/plan/action.php?id='.$this->id.'&signoff=1&sesskey='.sesskey().'" title="'.get_string('plancomplete', 'local_plan').'">
                <img src="'.$CFG->pixpath.'/t/favourite_on.gif" alt="'.get_string('plancomplete', 'local_plan').'" />
                </a>';
        }

        // Delete
        if ($this->get_setting('delete') == DP_PERMISSION_ALLOW) {
            echo '<a href="'.$CFG->wwwroot.'/local/plan/action.php?id='.$this->id.'&delete=1&sesskey='.sesskey().'" title="'.get_string('delete').'">
                <img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.get_string('delete').'" />
                </a>';
        }

        $out = ob_get_contents();

        ob_end_clean();


        return $out;
    }

    function get_history($orderby='timemodified') {
        return get_records('dp_plan_history', 'planid', $this->id, $orderby);
    }

    /**
     * Return a string containing the specified user's role in this plan
     *
     * This currently returns the first role that the user has, although
     * it would be easy to modify to return an array of all matched roles.
     *
     * @param integer $userid ID of the user to find the role of
     *                        If null uses current user's ID
     * @return string Name of the user's role within the current plan
     */
    function get_user_role($userid=null) {

        global $DP_AVAILABLE_ROLES;

        // loop through available roles
        foreach ($DP_AVAILABLE_ROLES as $role) {
            // call a method on each one to determine if user has that role
            if ($hasrole = $this->get_role($role)->user_has_role($userid)) {
                // return the name of the first role to match
                // could change to return an array of all matches
                return $hasrole;
            }
        }

        // no roles matched
        return false;
    }


    /**
     * Return true if the user of this plan has the specified role
     *
     * Typically the user of the plan is the current user, unless a different
     * userid was specified via the viewas parameter when the plan instance
     * was created.
     *
     * This method makes use of $this->role, which is populated by
     * {@link development_plan::get_user_role()} when a new plan is instantiated
     *
     * @param string $role Name of the role to check for
     * @return boolean True if the user has the role specified
     */
    function user_has_role($role) {
        // support array of roles in case we want to allow
        // a user to have multiple roles at some point
        if(is_array($this->role)) {
            if(in_array($role, $this->role)) {
                return true;
            }
        } else {
            if($role == $this->role) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if this plan contains any pending items
     */
    function get_pending_items() {
        $out = array();

        if($this->get_component('course')->get_setting('enabled')) {
            // any pending courses?
            $courses = get_records_select('dp_plan_course_assign', 'planid=' .
                $this->id . ' AND approved=0');
            $out['course'] = $courses;
        }
        if($this->get_component('competency')->get_setting('enabled')) {
            $competencies = get_records_select('dp_plan_competency_assign', 'planid=' .
                $this->id . ' AND approved=0');
            $out['competency'] = $competencies;
        }
        if($this->get_component('objective')->get_setting('enabled')) {
            global $CFG;
            $objectives = get_records_select('dp_plan_objective', 'planid=' . $this->id . ' and approved=0');
            $out['objective'] = $objectives;
        }

        // @todo add evidence when tables exist

        return $out;
    }


    function has_pending_items($pendinglist=null, $onlyapprovable=false) {

        $canapprovecourses = ($this->get_component('course')->get_setting('updatecourse')
            == DP_PERMISSION_APPROVE);
        $canapprovecompetencies = ($this->get_component('competency')->get_setting('updatecompetency')
            == DP_PERMISSION_APPROVE);
        $canapproveobjectives = ($this->get_component('objective')->get_setting('updateobjective')
            == DP_PERMISSION_APPROVE);

        // get the pending items, if it hasn't been passed to the method
        if(!isset($pendinglist)) {
            $pendinglist = $this->get_pending_items();
        }

        // see if any component has any pending items

        // check if there are pending course
        if(array_key_exists('course', $pendinglist) &&
            $pendinglist['course']) {
            // there are pending courses

            if(!$onlyapprovable) {
                // don't need to know if user can approve
                return true;
            } else if ($canapprovecourses) {
                // only count it if the user can approve
                return true;
            }
        }

        // check if there are pending competencies
        if(array_key_exists('competency', $pendinglist) &&
            $pendinglist['competency']) {

            if(!$onlyapprovable) {
                // don't need to know if user can approve
                return true;
            } else if ($canapprovecompetencies) {
                // only count it if the user can approve
                return true;
            }
        }

        // check if there are pending competencies
        if(array_key_exists('objective', $pendinglist) &&
            $pendinglist['objective']) {

            if(!$onlyapprovable) {
                // don't need to know if user can approve
                return true;
            } else if ($canapproveobjectives) {
                // only count it if the user can approve
                return true;
            }
        }

        return false;
    }

    function display_plan_message_box() {
        $unapproved = ($this->status == DP_PLAN_STATUS_UNAPPROVED);
        $declined = ($this->status == DP_PLAN_STATUS_DECLINED);
        $completed = ($this->status == DP_PLAN_STATUS_COMPLETE);
        $viewingasmanager = $this->role == 'manager';
        $pending = $this->get_pending_items();
        $haspendingitems = $this->has_pending_items($pending);
        $canapprovepending = $this->has_pending_items($pending, true);

        // @todo check permission name
        $canapproveplan = (in_array($this->get_setting('confirm'), array(DP_PERMISSION_APPROVE, DP_PERMISSION_ALLOW)));

        $message = '';
        if($viewingasmanager) {
            $message .= $this->display_viewing_users_plan($this->userid);
        }

        if($completed) {
            $message .= $this->display_completed_plan_message();
            $style = 'plan_box_completed';
        } elseif ($declined) {
            $message .= $this->display_declined_plan_message();
            $style = 'plan_box_action';
        } else {
            if($canapprovepending || $canapproveplan) {
                $style = 'plan_box_action';
            } else {
                $style = 'plan_box_plain';
            }
            if($unapproved) {
                if($haspendingitems) {
                    if($canapprovepending) {
                        $message .= $this->display_pending_items($pending);
                    } else if ($canapproveplan) {
                        $message .= $this->display_unapproved_plan_message();
                    } else {
                        $message .= $this->display_pending_items($pending);
                    }
                } else {
                    $message .= $this->display_unapproved_plan_message();
                }
            } else {
                if($haspendingitems) {
                    $message .= $this->display_pending_items($pending);
                } else {
                    // nothing to report (no message)
                }
            }
        }

        if($message == '') {
            return '';
        }
        return '<div class="plan_box '. $style . '">' . $message . '</div>';
    }

    function display_completed_plan_message() {
        return '<p>' . get_string('plancompleted', 'local_plan') . '</p>';
    }

    function display_declined_plan_message() {
        return '<p>' . get_string('plandeclinedtryagain', 'local_plan') . '</p>';
    }

    function display_unapproved_plan_message() {
        global $CFG;

        $canapproveplan = (in_array($this->get_setting('confirm'),  array(DP_PERMISSION_APPROVE, DP_PERMISSION_ALLOW)));
        $canrequestapproval = ($this->get_setting('confirm') == DP_PERMISSION_REQUEST);
        $out = '';

        // @todo fill in action and process
        $out .= "<form action=\"{$CFG->wwwroot}/local/plan/action.php\" method=\"POST\">";
        $out .= "<input type=\"hidden\" name=\"id\" value=\"{$this->id}\"/>";
        $out .= "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\"/>";
        $out .= '<table width="100%" border="0"><tr>';
        $out .= '<td>' . get_string('plannotapproved', 'local_plan') .
            // @todo add reminder request if available
            '</td>';
        if($canapproveplan) {
            $out .= '<td><input type="submit" name="approve" value="' . get_string('approve', 'local_plan') . '" /> &nbsp; ';

            $out .= '<input type="submit" name="decline" value="' . get_string('decline', 'local_plan') . '" /></td>';
        } elseif ($canrequestapproval) {
            $out .= '<td><input type="submit" name="approvalrequest" value="' . get_string('sendapprovalrequest', 'local_plan') . '" /> &nbsp; ';
        }
        $out .= '</tr></table></form>';

        return $out;
    }

    function display_pending_items($pendinglist=null) {
        global $CFG;

        $canapprovecourses = ($this->get_component('course')->get_setting('updatecourse')
            == DP_PERMISSION_APPROVE);
        $canapprovecompetencies = ($this->get_component('competency')->get_setting('updatecompetency')
            == DP_PERMISSION_APPROVE);
        $canapproveobjectives = ($this->get_component('objective')->get_setting('updateobjective')
            == DP_PERMISSION_APPROVE);
        $coursesenabled = $this->get_component('course')->get_setting('enabled');
        $competenciesenabled = $this->get_component('competency')->get_setting('enabled');
        $objectivesenabled = $this->get_component('objective')->get_setting('enabled');

        // get the pending items, if it hasn't been passed to the method
        if(!isset($pendinglist)) {
            $pendinglist = $this->get_pending_items();
        }

        // @todo fix pluralization issues for 1 item
        // @todo check permission names are correct
        $list = '';
        $listcount = 0;
        if($coursesenabled && $pendinglist['course']) {
            $a = new object();
            $a->planid = $this->id;
            $a->number = count($pendinglist['course']);
            $a->name = $this->get_component('course')->get_setting('name');
            $a->component = 'course';
            $a->site = $CFG->wwwroot;
            $list .= '<li>' . get_string('xitemspending', 'local_plan', $a) . '</li>';
            $listcount++;
        }

        if($competenciesenabled && $pendinglist['competency']) {
            $a = new object();
            $a->planid = $this->id;
            $a->number = count($pendinglist['competency']);
            $a->name = $this->get_component('competency')->get_setting('name');
            $a->component = 'competency';
            $a->site = $CFG->wwwroot;
            $list .= '<li>' . get_string('xitemspending', 'local_plan', $a) . '</li>';
            $listcount++;
        }

        if($objectivesenabled && $pendinglist['objective']) {
            $a = new object();
            $a->planid = $this->id;
            $a->number = count($pendinglist['objective']);
            $a->name = $this->get_component('objective')->get_setting('name');
            $a->component = 'objective';
            $a->site = $CFG->wwwroot;
            $list .= '<li>' . get_string('xitemspending', 'local_plan', $a) . '</li>';
            $listcount++;
        }
        // @todo add evidence when tables exist

        $descriptor = ($canapprovecourses || $canapprovecompetencies || $canapproveobjectives) ?
        'thefollowingitemsrequireyourapproval' : 'thefollowingitemsarepending';
        // only print if there are pending items
        $out = '';
        if($listcount) {
            $out .= '<p>' . get_string($descriptor, 'local_plan'). '</p>';
            $out .= '<ul>' . $list . '</ul>';
        }

        return $out;

    }

    static public function display_viewing_users_plan($userid) {
        global $CFG;
        $user = get_record('user', 'id', $userid);
        if(!$user) {
            return '';
        }
        $out = '';
        $out .= '<table border="0" width="100%"><tr><td width="50">';
        $out .= print_user_picture($user, 1, null, 0, true);
        $out .= '</td><td>';
        $a = new object();
        $a->name = fullname($user);
        $a->userid = $userid;
        $a->site = $CFG->wwwroot;
        $out .= get_string('youareviewingxsplan', 'local_plan', $a);
        $out .= '</td></tr></table>';
        return $out;
    }

    // Delete the plan and all of its relevant data
    function delete() {
        global $CFG, $DP_AVAILABLE_COMPONENTS;

        require_once("{$CFG->libdir}/ddllib.php");

        begin_sql();

        // Delete plan
        if (!delete_records('dp_plan', 'id', $this->id)) {
            rollback_sql();
            return false;
        }
        //Delete plan history
        if (!delete_records('dp_plan_history', 'planid', $this->id)) {
            rollback_sql();
            return false;
        }
        // Delete related components
        foreach ($DP_AVAILABLE_COMPONENTS as $c) {
            $itemids = array();
            $table = new XMLDBTable("dp_plan_{$c}");
            if (table_exists($table)) {
                $field = new XMLDBField('planid');
                if (field_exists($table, $field)) {
                    // Get record ids for later use in deletion of assign tables
                    $ids = get_records($table->name, 'planid', $this->id, '', 'id');
                    if (!delete_records($table->name, 'planid', $this->id)) {
                        rollback_sql();
                        return false;
                    }
                    $table = new XMLDBTable("dp_plan_{$c}_assign");
                    if (table_exists($table)) {
                        foreach ($ids as $i) {
                            $itemids = array_merge($itemids, get_records($table->name, "{$c}id", $i, '', 'id'));
                            if (!delete_records($table->name, "{$c}id", $i)) {
                                rollback_sql();
                                return false;
                            }
                        }
                    }
                }
            } else {
                $table = new XMLDBTable("dp_plan_{$c}_assign");
                if (table_exists($table)) {
                    $itemids = get_records($table->name, 'planid', $this->id, '', 'id');
                    if (!delete_records($table->name, 'planid', $this->id)) {
                        rollback_sql();
                        return false;
                    }
                }
            }
            if (!empty($itemids)) {
                // Delete component relations
                foreach ($itemids as $id=>$value) {
                    if (!delete_records('dp_plan_component_relation', 'itemid1', $id) || !delete_records('dp_plan_component_relation', 'itemid2', $id)) {
                        rollback_sql();
                        return false;
                    }
                }
            }
        }

        //rollback_sql();
        commit_sql();
        return true;
    }

    function set_status($status) {
        global $USER;

        $todb = new stdClass;
        $todb->id = $this->id;
        $todb->status = $status;
        if ($status == DP_PLAN_STATUS_APPROVED) {
            // Set the plan startdate to the approval time
            $todb->startdate = time();
        }

        if (update_record('dp_plan', $todb)) {
            // Update plan history
            $todb = new stdClass;
            $todb->planid = $this->id;
            $todb->status = $status;
            $todb->timemodified = time();
            $todb->usermodified = $USER->id;

            if (!insert_record('dp_plan_history', $todb)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Sets the plan's status to true if the plan was in "Declined" status
     * @return boolean True if the plan's status was changed
     */
    function set_status_unapproved_if_declined() {
        if ($this->status == DP_PLAN_STATUS_DECLINED) {
            $this->set_status(DP_PLAN_STATUS_UNAPPROVED);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Send a task to the manager when a learner requests a new plan
     * @global <type> $USER
     * @global object $CFG
     */
    function send_manager_task_plan_request() {
        global $USER, $CFG;

        $manager = totara_get_manager($this->userid);
        $learner = get_record('user','id',$this->userid);
        if ($manager && $learner) {
            require_once( $CFG->dirroot . '/local/totara_msg/eventdata.class.php' );
            require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

            // do the IDP Plan workflow event
            $data = new stdClass();
            $data->userid = $this->userid;
            $data->planid = $this->id;

            $event = new tm_task_eventdata($manager, 'plan', $data, $data);
            $event->userfrom = $learner;
            $event->contexturl = $this->get_display_url();
            $event->contexturlname = $this->name;
            $event->roleid = get_field('role','id', 'shortname', 'manager');
            $event->icon = 'learningplan-request.png';

            $a = new stdClass;
            $a->learner = fullname($learner);
            $a->plan = s($this->name);
            $event->subject = get_string('plan-request-manager-short', 'local_plan', $a);
            $event->fullmessage = get_string('plan-request-manager-long', 'local_plan', $a);

            tm_reminder_send($event);
        }
    }

    /**
     * Send an alert to the user when they have a new plan assigned to them
     */
    function send_learner_alert_plan_assigned() {
        global $USER;
        global $CFG;
        // Send notification to user
        $learner = get_record('user', 'id', $this->userid);
        if ( $learner ){
            require_once( $CFG->dirroot . '/local/totara_msg/eventdata.class.php' );
            require_once( $CFG->dirroot . '/local/totara_msg/messagelib.php' );
            $manager = $USER;
            $event = new tm_alert_eventdata($learner);
            $event->userfrom = $manager;
            $event->contexturl = $this->get_display_url();
            $event->contexturlname = $this->name;
            $event->roleid = get_field('role','id','shortname','student');
            $event->icon = 'learningplan-update.png';

            $a = new stdClass();
            $a->plan = $this->name;
            $a->manager = fullname($manager);
            $event->subject = get_string('plan-add-learner-short','local_plan',$a);
            $event->fullmessage = get_string('plan-add-learner-long', 'local_plan', $a);

            tm_notification_send($event);
        }
    }

    function send_approved_notification() {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

        $userto = get_record('user', 'id', $this->userid);
        $userfrom = get_record('user', 'id', $USER->id);

        $event = new stdClass;
        $event->userfrom = $userfrom;
        $event->userto = $userto;
        $event->fullmessage = get_string('planapproved', 'local_plan', $this->name);
        tm_notification_send($event);
    }

    function send_declined_notification() {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

        $userto = get_record('user', 'id', $this->userid);
        $userfrom = get_record('user', 'id', $USER->id);

        $event = new stdClass;
        $event->userfrom = $userfrom;
        $event->userto = $userto;
        $event->fullmessage = format_string(get_string('plandeclined', 'local_plan', $this->name));
        tm_notification_send($event);
    }

    function send_completion_notification() {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/local/totara_msg/messagelib.php');

        // Send notification to manager
        if ($manager = totara_get_manager($this->userid)) {
            $userto = $manager;
            $event = new stdClass;
            $event->userto = $userto;
            $event->fullmessage = format_text(get_string('plancompletesuccess', 'local_plan', $this->name));
            $event->roleid = get_field('role','id', 'shortname', 'manager');
            tm_notification_send($event);
        }

        // Send notification to user
        $userto = get_record('user', 'id', $this->userid);
        $event = new stdClass;
        $event->userto = $userto;
        $event->fullmessage = format_text(get_string('plancompletesuccess', 'local_plan', $this->name));
        tm_notification_send($event);
    }

    /**
     * Returns the URL for the page to view this plan
     * @global object $CFG
     * @return string
     */
    public function get_display_url(){
        global $CFG;
        return "{$CFG->wwwroot}/local/plan/view.php?id={$this->id}";
    }
}


class PlanException extends Exception { }
