<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class idp_priority_scale_value_edit_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'priorityscaleid');
        $mform->setType('priorityscaleid', PARAM_INT);
        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);

        /// Print the required moodle fields first
        $mform->addElement('header', 'moodle', get_string('general'));

        $mform->addElement('static', 'scalename', get_string('priorityscale', 'idp'));
        $mform->setHelpButton('scalename', array('priorityscaleassign', get_string('priorityscale', 'idp')), true);

        $mform->addElement('text', 'name', get_string('priorityscalevaluename', 'idp'), 'maxlength="100" size="20"');
        $mform->setHelpButton('name', array('priorityscalevaluename', get_string('priorityscalevaluename', 'idp')), true);
        $mform->addRule('name', get_string('missingpriorityscalevaluename', 'idp'), 'required', null, 'client');
        $mform->setType('name', PARAM_MULTILANG);

        $mform->addElement('text', 'idnumber', get_string('priorityscalevalueidnumber', 'idp'), 'maxlength="100"  size="10"');
        $mform->setHelpButton('idnumber', array('priorityscalevalueidnumber', get_string('priorityscalevalueidnumber', 'idp')), true);
        $mform->setType('idnumber', PARAM_RAW);

        $mform->addElement('text', 'numericscore', get_string('priorityscalevaluenumericalvalue', 'idp'), 'maxlength="100"  size="10"');
        $mform->setHelpButton('numericscore', array('priorityscalevaluenumeric', get_string('priorityscalevaluenumericalvalue', 'idp')), true);
        $mform->setType('numericscore', PARAM_RAW);

        $mform->addElement('htmleditor', 'description', get_string('description'));
        $mform->setHelpButton('description', array('text', get_string('helptext')), true);
        $mform->setType('description', PARAM_RAW);

        $this->add_action_buttons();
    }

    function validation($valuenew) {

        $err = array();
        $valuenew = (object)$valuenew;

        // Check the numericscore field was either empty or a number
        if (strlen($valuenew->numericscore)) {
            // Is a number
            if (is_numeric($valuenew->numericscore)) {
                $valuenew->numericscore = (float)$valuenew->numericscore;
            } else {
                $err['numericscore'] = get_string('invalidnumeric', 'idp');
                return $err;
            }
        } else {
            $valuenew->numericscore = null;
        }

        return true;
    }
}