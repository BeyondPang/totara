<?php //$Id$

require_once($CFG->dirroot.'/lib/formslib.php');

class field_form extends moodleform {

    var $field;

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $datasent = $this->_customdata;

        require_once($CFG->dirroot.'/lib/customfield/field/'.$datasent['datatype'].'/define.class.php');
        $newfield = 'customfield_define_'.$datasent['datatype'];
        $this->field = new $newfield();

        $strrequired = get_string('required');

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'action', 'editfield');
        $mform->addElement('hidden', 'datatype', $datasent['datatype']);
        $mform->addElement('hidden', 'depthid', $datasent['depth']);

        $this->field->define_form($mform, $datasent['depth'], $datasent['tableprefix']);

        $this->add_action_buttons(true);
    }


/// alter definition based on existing or submitted data
    function definition_after_data () {
        $mform =& $this->_form;
        $this->field->define_after_data($mform);
    }


/// perform some moodle validation
    function validation($data, $files) {
        return $this->field->define_validate($data, $files);
    }
}

?>
