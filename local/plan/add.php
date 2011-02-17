<?php
/**
 * Page for adding a plan
 *
 * @copyright Catalyst IT Limited
 * @author Eugene Venter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/plan/lib.php');
require_once($CFG->dirroot . '/local/plan/edit_form.php');
require_once($CFG->dirroot . '/local/js/lib/setup.php');

require_login();

$userid = required_param('userid', PARAM_INT); // user id

///
/// Permission checks
///
if (!dp_can_view_users_plans($userid)) {
    print_error('error:nopermissions', 'local_plan');
}


// START PERMISSION HACK
if ($userid != $USER->id) {
    // Make sure user is manager
    if (totara_is_manager($userid) || isadmin()) {
        $role = 'manager';
    } else {
        print_error('error:nopermissions', 'local_plan');
    }
} else {
    $role = 'learner';
}

if (!$template = dp_get_first_template()) {
    print_error('notemplatesetup', 'local_plan');
}

if (dp_get_template_permission($template->id, 'plan', 'create', $role) != DP_PERMISSION_ALLOW) {
    print_error('error:nopermissions', 'local_plan');
}
// END HACK


///
/// Data and actions
///
$currenturl = qualified_me();
$allplansurl = "{$CFG->wwwroot}/local/plan/index.php?userid={$userid}";

$form = new plan_edit_form($currenturl, array('action'=>'add'));

if ($form->is_cancelled()) {
    redirect($allplansurl);
}

// Handle form submit
if ($data = $form->get_data()) {
    if (isset($data->submitbutton)) {
        begin_sql();

        $data->enddate = dp_convert_userdate($data->enddate);  // convert to timestamp

        // Set up the plan
        if (!$newid = insert_record('dp_plan', $data)) {
            rollback_sql();
            totara_set_notification(get_string('plancreatefail', 'local_plan', get_string('couldnotinsertnewrecord', 'local_plan')), $currenturl);
        }
        $plan = new development_plan($newid);

        // Update plan status and plan history
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED);

        if ($plan->get_component('competency')->get_setting('enabled')) {
            // Auto-assign competencies
            $competencycomponent = $plan->get_component('competency');
            if ($competencycomponent->get_setting('autoassignorg')) {
                // From organisation
                if (!$competencycomponent->assign_from_org()) {
                    rollback_sql();
                    totara_set_notification(get_string('plancreatefail', 'local_plan', get_string('unabletoassigncompsfromorg', 'local_plan')), $currenturl);
                }
            }
            if ($competencycomponent->get_setting('autoassignpos')) {
                // From position
                if (!$competencycomponent->assign_from_pos()) {
                    rollback_sql();
                    totara_set_notification(get_string('plancreatefail', 'local_plan', get_string('unabletoassigncompsfrompos', 'local_plan')), $currenturl);
                }
            }
            unset($competencycomponent);
        }

        commit_sql();

        // Send out a notification?
        if ($plan->is_active()) {
            if ( $role == 'manager' ) {
                $plan->send_alert(true,'learningplan-update.png','plan-add-learner-short','plan-add-learner-long');
            }
        }

        $viewurl = "{$CFG->wwwroot}/local/plan/view.php?id={$newid}";
        totara_set_notification(get_string('plancreatesuccess', 'local_plan'), $viewurl, array('style' => 'notifysuccess'));
    }
}


///
/// Display
///
$heading = get_string('createnewlearningplan', 'local_plan');
$pagetitle = format_string(get_string('learningplan','local_plan').': '.$heading);
$navlinks = array();
dp_get_plan_base_navlinks($navlinks, $userid);
$navlinks[] = array('name' => $heading, 'link'=> '', 'type'=>'title');

$navigation = build_navigation($navlinks);

//Javascript include
local_js(array(
    TOTARA_JS_DATEPICKER
));
require_js(array(
    $CFG->wwwroot.'/local/js/plan.form.datepick.js'
));

print_header_simple($pagetitle, '', $navigation, '', null, true, '');

// Plan menu
echo dp_display_plans_menu($userid);

// Plan page content
print_container_start(false, '', 'dp-plan-content');

if ($USER->id != $userid) {
    echo dp_display_user_message_box($userid);
}

print_heading($heading);

print '<p>' . get_string('createplan_instructions', 'local_plan') . '</p>';

$form->set_data((object)array('userid'=>$userid));
$form->display();

print_container_end();

print <<<HEREDOC
<script type="text/javascript">

    $(function() {
        $('input[name="enddate"]').datepicker(
            {
                dateFormat: 'dd/mm/yy',
                showOn: 'both',
                buttonImage: '{$CFG->wwwroot}/local/js/images/calendar.gif',
                buttonImageOnly: true,
                constrainInput: true
            }
        );
    });
</script>
HEREDOC;


print_footer();
