<?php

class dp_managerdriven_workflow extends dp_base_workflow {

    function __construct() {
        global $CFG;
        require_once($CFG->dirroot.'/local/plan/objectivescales/lib.php');
        require_once($CFG->dirroot.'/local/plan/priorityscales/lib.php');
        $defaultpriority = dp_priority_default_scale_id();
        $defaultobjective = dp_objective_default_scale_id();

        $this->classname = 'managerdriven';

        // workflow settings

        // course specific settings
        $this->cfg_course_duedatemode = DP_DUEDATES_NONE;
        $this->cfg_course_prioritymode = DP_PRIORITY_OPTIONAL;
        $this->cfg_course_priorityscale = $defaultpriority;

        // competency specific settings
        $this->cfg_competency_autoassignpos = 0;
        $this->cfg_competency_autoassignorg = 0;
        $this->cfg_competency_autoassigncourses = 0;
        $this->cfg_competency_duedatemode = DP_DUEDATES_NONE;
        $this->cfg_competency_prioritymode = DP_PRIORITY_OPTIONAL;
        $this->cfg_competency_priorityscale = $defaultpriority;

        // objective specific settings
        $this->cfg_objective_duedatemode = DP_DUEDATES_NONE;
        $this->cfg_objective_prioritymode = DP_PRIORITY_OPTIONAL;
        $this->cfg_objective_priorityscale = $defaultpriority;
        $this->cfg_objective_objectivescale = $defaultobjective;

        // plan permission settings
        $this->perm_plan_view_learner = DP_PERMISSION_ALLOW;
        $this->perm_plan_view_manager = DP_PERMISSION_ALLOW;
        $this->perm_plan_create_learner = DP_PERMISSION_ALLOW;
        $this->perm_plan_create_manager = DP_PERMISSION_ALLOW;
        $this->perm_plan_update_learner = DP_PERMISSION_ALLOW;
        $this->perm_plan_update_manager = DP_PERMISSION_ALLOW;
        $this->perm_plan_delete_learner = DP_PERMISSION_DENY;
        $this->perm_plan_delete_manager = DP_PERMISSION_ALLOW;
        $this->perm_plan_approve_learner = DP_PERMISSION_DENY;
        $this->perm_plan_approve_manager = DP_PERMISSION_ALLOW;
        $this->perm_plan_complete_learner = DP_PERMISSION_REQUEST;
        $this->perm_plan_complete_manager = DP_PERMISSION_APPROVE;

        // course permission settings
        $this->perm_course_updatecourse_learner = DP_PERMISSION_REQUEST;
        $this->perm_course_updatecourse_manager = DP_PERMISSION_APPROVE;
        $this->perm_course_commenton_learner = DP_PERMISSION_ALLOW;
        $this->perm_course_commenton_manager = DP_PERMISSION_ALLOW;
        $this->perm_course_setpriority_learner = DP_PERMISSION_ALLOW;
        $this->perm_course_setpriority_manager = DP_PERMISSION_ALLOW;
        $this->perm_course_setduedate_learner = DP_PERMISSION_ALLOW;
        $this->perm_course_setduedate_manager = DP_PERMISSION_ALLOW;
        $this->perm_course_setcompletionstatus_learner = DP_PERMISSION_REQUEST;
        $this->perm_course_setcompletionstatus_manager = DP_PERMISSION_APPROVE;

        //competency permission settings
        $this->perm_competency_updatecompetency_learner = DP_PERMISSION_REQUEST;
        $this->perm_competency_updatecompetency_manager = DP_PERMISSION_APPROVE;
        $this->perm_competency_commenton_learner = DP_PERMISSION_ALLOW;
        $this->perm_competency_commenton_manager = DP_PERMISSION_ALLOW;
        $this->perm_competency_setpriority_learner = DP_PERMISSION_ALLOW;
        $this->perm_competency_setpriority_manager = DP_PERMISSION_ALLOW;
        $this->perm_competency_setduedate_learner = DP_PERMISSION_ALLOW;
        $this->perm_competency_setduedate_manager = DP_PERMISSION_ALLOW;
        $this->perm_competency_setproficiency_learner = DP_PERMISSION_REQUEST;
        $this->perm_competency_setproficiency_manager = DP_PERMISSION_APPROVE;

        //objective permission settings
        $this->perm_objective_updateobjective_learner = DP_PERMISSION_REQUEST;
        $this->perm_objective_updateobjective_manager = DP_PERMISSION_APPROVE;
        $this->perm_objective_commenton_learner = DP_PERMISSION_ALLOW;
        $this->perm_objective_commenton_manager = DP_PERMISSION_ALLOW;
        $this->perm_objective_setpriority_learner = DP_PERMISSION_ALLOW;
        $this->perm_objective_setpriority_manager = DP_PERMISSION_ALLOW;
        $this->perm_objective_setduedate_learner = DP_PERMISSION_ALLOW;
        $this->perm_objective_setduedate_manager = DP_PERMISSION_ALLOW;
        $this->perm_objective_setproficiency_learner = DP_PERMISSION_REQUEST;
        $this->perm_objective_setproficiency_manager = DP_PERMISSION_APPROVE;

        parent::__construct();
    }
}
