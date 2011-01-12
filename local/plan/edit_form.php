<?php
/**
 * Plan related form definitions
 *
 * @copyright Catalyst IT Limited
 * @author Eugene Venter
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 * @subpackage plan
 */

require_once("{$CFG->libdir}/formslib.php");

class plan_edit_form extends moodleform {

    function definition() {
        global $CFG, $USER;

        $mform =& $this->_form;
        $action = $this->_customdata['action'];

        if (isset($this->_customdata['plan'])) {
            $plan = $this->_customdata['plan'];
        }

        // Add some hidden fields
        if ($action != 'add') {
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
        }
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);
        $template = dp_get_first_template();
        $mform->addElement('hidden', 'templateid', $template->id);  //@todo: HACK! we will always use the first template for now
        $mform->setType('templateid', PARAM_INT);
        $mform->addElement('hidden', 'status', 0);
        $mform->setType('status', PARAM_INT);
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);

        if ($action == 'delete') {
            // Only show delete confirmation
            $mform->addElement('html', get_string('checkplandelete', 'local_plan', $plan->name));
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'deleteyes', get_string('yes'));
            $buttonarray[] = $mform->createElement('submit', 'deleteno', get_string('no'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');

            return;
        }
        if ($action == 'signoff') {
            // Only show complete plan confirmation
            $mform->addElement('html', get_string('checkplancomplete', 'local_plan', $plan->name));
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'signoffyes', get_string('yes'));
            $buttonarray[] = $mform->createElement('submit', 'signoffno', get_string('no'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');

            return;
        }

        $mform->addElement('date_selector', 'startdate', get_string('datecreated', 'local_plan'));
        $mform->hardFreeze('startdate');

        $mform->addElement('text', 'name', get_string('planname', 'local_plan'), array('size'=>50));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('err_required', 'form'), 'required', '', 'client', false, false);
        $mform->setDefault('name', $template->fullname);
        $mform->addElement('textarea', 'description', get_string('plandescription', 'local_plan'), array('rows'=>5, 'cols'=>50));
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', get_string('err_required', 'form'), 'required', '', 'client', false, false);
        $mform->addElement('text', 'enddate', get_string('completiondate', 'local_plan'));
        $mform->addRule('enddate', get_string('err_required', 'form'), 'required', '', 'client', false, false);
        $mform->setDefault('enddate', userdate($template->enddate, '%d/%m/%Y', $CFG->timezone, false));

        if ($action == 'view') {
            $mform->hardFreeze(array('name', 'description', 'enddate'));
            $buttonarray = array();
            if ($plan->get_setting('update') == DP_PERMISSION_ALLOW && $plan->status != DP_PLAN_STATUS_COMPLETE) {;
                $buttonarray[] = $mform->createElement('submit', 'edit', get_string('editdetails', 'local_plan'));
            }
            if ($plan->get_setting('delete') == DP_PERMISSION_ALLOW) {
                $buttonarray[] = $mform->createElement('submit', 'delete', get_string('deleteplan', 'local_plan'));
            }
            if ($plan->get_setting('signoff') >= DP_PERMISSION_ALLOW && $plan->status == DP_PLAN_STATUS_APPROVED) {
                $buttonarray[] = $mform->createElement('submit', 'signoff', get_string('plancomplete', 'local_plan'));
            }

            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        } else {
            switch ($action) {
            case 'add':
                $actionstr = 'createplan';
                break;
            case 'edit':
                $actionstr = 'updateplan';
                break;
            default:
                $actionstr = null;
            }
            $this->add_action_buttons(true, get_string($actionstr,'local_plan'));
        }
    }

    function validation($data) {
        $mform =& $this->_form;
        $result = array();

        $startdate = isset($data['startdate']) ? $data['startdate'] : '';
        $enddate = isset($data['enddate']) ? $data['enddate'] : '';

        $datepattern = '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/(\d{4})$/';
        if (preg_match($datepattern, $enddate, $matches) == 0) {
            $errstr = get_string('error:dateformat','local_plan');
            $result['enddate'] = $errstr;
            unset($errstr);
        } elseif ( $startdate > dp_convert_userdate($enddate) && $startdate !== false && $enddate !== false ) {
            // Enforce start date before finish date
            $errstr = get_string('error:creationaftercompletion','local_plan');
            $result['enddate'] = $errstr;
            unset($errstr);
        }

        return $result;
    }

}
