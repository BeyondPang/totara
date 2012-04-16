<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010-2012 Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_customfield
 */

/**
 * Base class for the custom fields.
 */
class customfield_base {

    /// These 2 variables are really what we're interested in.
    /// Everything else can be extracted from them
    var $fieldid;
    var $itemid;
    var $prefix;
    var $tableprefix;
    var $field;
    var $inputname;
    var $data;

    /**
     * Constructor method.
     * @param   integer   field id from the _info_field table
     * @param   integer   id using the data
     */
    function customfield_base($fieldid=0, $itemid=0, $prefix, $tableprefix) {
        $this->set_fieldid($fieldid);
        $this->set_itemid($itemid);
        $this->load_data($itemid, $prefix, $tableprefix);
    }

    /**
     * Display the data for this field
     */
    function display_data() {
        // call the static method belonging to this object's class
        // or the one below if not re-defined by child class
        return $this->display_item_data($this->data);
    }


/***** The following methods must be overwritten by child classes *****/

    /**
     * Abstract method: Adds the custom field to the moodle form class
     * @param  form  instance of the moodleform class
     */
    function edit_field_add(&$mform) {
        print_error('error:abstractmethod', 'totara_customfield');
    }


/***** The following methods may be overwritten by child classes *****/

    static function display_item_data($data) {
        $options->para = false;
        return format_text($data, FORMAT_MOODLE, $options);
    }
    /**
     * Print out the form field in the edit page
     * @param   object   instance of the moodleform class
     * $return  boolean
     */
    function edit_field(&$mform) {

        if ($this->field->hidden == false) {
            $this->edit_field_add($mform);
            $this->edit_field_set_default($mform);
            $this->edit_field_set_required($mform);
            return true;
        }
        return false;
    }

    /**
     * Tweaks the edit form
     * @param   object   instance of the moodleform class
     * $return  boolean
     */
    function edit_after_data(&$mform) {

        if ($this->field->hidden == false) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        return false;
    }

    /**
     * Saves the data coming from form
     * @param   mixed   data coming from the form
     * @param   string  name of the prefix (ie, competency)
     * @return  mixed   returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    function edit_save_data($itemnew, $prefix, $tableprefix) {
        global $DB;

        if (!isset($itemnew->{$this->inputname})) {
            // field not present in form, probably locked and invisible - skip it
            return;
        }
        $itemnew->{$this->inputname} = $this->edit_save_data_preprocess($itemnew->{$this->inputname});

        $data = new stdClass();
        $data->{$prefix.'id'} = $itemnew->id;
        $data->fieldid      = $this->field->id;
        $data->data         = $itemnew->{$this->inputname};

        if ($dataid = $DB->get_field($tableprefix.'_info_data', 'id', array($prefix.'id' => $itemnew->id, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
            if (!$DB->update_record($tableprefix.'_info_data', $data)) {
                print_error('error:updatecustomfield', 'totara_customfield');
            }
        } else {
            $DB->insert_record($tableprefix.'_info_data', $data);
        }
    }

    /**
     * Validate the form field from edit page
     * @return  string  contains error message otherwise NULL
     **/
    function edit_validate_field($itemnew, $prefix, $tableprefix) {
        global $DB;

        $errors = array();
        /// Check for uniqueness of data if required
        if ($this->is_unique()) {
            if ($prefix == 'course') {
                // anywhere across the site
                $data = $itemnew->{$this->inputname};
                // check value, not key for menu items
                if ($this->field->datatype == 'menu') {
                    $data = $this->options[$data];
                }
                if ($data != '' && $DB->record_exists_select($tableprefix.'_info_data',
                    "fieldid = ? AND " .
                    "data = ? AND " .
                    "courseid != ?", array($this->field->id, $data, $itemnew->id))) {


                    $errors["{$this->inputname}"] = get_string('valuealreadyused');
                }
            } else {
                // within same depth level
                if ($itemid = $DB->get_field($tableprefix.'_info_data', $prefix.'id', array('fieldid' => $this->field->id, 'data' => $itemnew->{$this->inputname}))) {
                    if ($itemid != $itemnew->id) {
                        $errors["{$this->inputname}"] = get_string('valuealreadyused');
                    }
                }
            }

        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_default(&$mform) {
        if (!empty($this->field->defaultdata)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }
    }

    /**
     * Sets the required flag for the field in the form object
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_required(&$mform) {
        if ($this->is_required()) {
            $mform->addRule($this->inputname, get_string('customfieldrequired', 'totara_customfield'), 'required', null, 'client');
        }
    }

    /**
     * HardFreeze the field if locked.
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_locked(&$mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked()) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param   mixed
     * @return  mixed
     */
    function edit_save_data_preprocess($data) {
        return $data;
    }

    /**
     * Loads an object with data for this field ready for the edit form
     * form
     * @param   object a object
     */
    function edit_load_item_data(&$item) {
        if ($this->data !== NULL) {
            $item->{$this->inputname} = $this->data;
        }
    }

    /**
     * Check if the field data should be loaded into the object
     * By default it is, but for field prefixes where the data may be potentially
     * large, the child class should override this and return false
     * @return boolean
     */
    function is_object_data() {
        return true;
    }


/***** The following methods generally should not be overwritten by child classes *****/
    /**
     * Accessor method: set the itemid for this instance
     * @param   integer   id from the prefix (competency etc) table
     */
    function set_itemid($itemid) {
        $this->itemid = $itemid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @param   integer   id from the _info_field table
     */
    function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Accessor method: Load the field record and prefix data and tableprefix associated with the prefix
     * object's fieldid and itemid
     */
    function load_data($itemid, $prefix, $tableprefix) {
        global $DB;

        /// Load the field object
        if (($this->fieldid == 0) || (!($field = $DB->get_record($tableprefix.'_info_field', array('id' => $this->fieldid))))) {
            $this->field = NULL;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'customfield_'.$field->shortname;
        }
        if (!empty($this->field)) {
            if ($datafield = $DB->get_field($tableprefix.'_info_data', 'data', array($prefix.'id' => $this->itemid, 'fieldid' => $this->fieldid))) {
                $this->data = $datafield;
            } else {
                $this->data = $this->field->defaultdata;
            }
        } else {
            $this->data = NULL;
        }
    }

    /**
     * Check if the field data is hidden to the current item 
     * @return  boolean
     */
    function is_hidden() {

        if($this->field->hidden) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the field data is considered empty
     * return boolean
     */
    function is_empty() {
        return ( ($this->data != '0') and empty($this->data));
    }

    /**
     * Check if the field is required on the edit page
     * @return   boolean
     */
    function is_required() {
        return (boolean)$this->field->required;
    }

    /**
     * Check if the field is locked on the edit page
     * @return   boolean
     */
    function is_locked() {
        return (boolean)$this->field->locked;
    }

    /**
     * Check if the field data should be unique
     * @return   boolean
     */
    function is_unique() {
        return (boolean)$this->field->forceunique;
    }

} /// End of class efinition


/***** General purpose functions for custom fields *****/

function customfield_load_data(&$item, $prefix, $tableprefix) {
    global $CFG, $DB;
    $typestr = '';
    $params = array();
    if (isset($item->typeid)) {
        $typestr = 'typeid = ?';
        $params[] = $item->typeid;
    }

    $fields = $DB->get_records_select($tableprefix.'_info_field', $typestr, $params);

    foreach ($fields as $field) {
        require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
        $newfield = 'customfield_'.$field->datatype;
        $formfield = new $newfield($field->id, $item->id, $prefix, $tableprefix);
        $formfield->edit_load_item_data($item);
    }
}

/**
 * Print out the customisable fields
 * @param  object  instance of the moodleform class
 */
function customfield_definition(&$mform, $itemid, $prefix, $typeid=0, $tableprefix) {
    global $CFG, $DB;

    $typestr = '';
    $params = array();

    if ($typeid) {
        $typestr = 'typeid = ?';
        $params[] = $typeid;
    }

    $fields = $DB->get_records_select($tableprefix.'_info_field', $typestr, $param, 'sortorder ASC');

    // check first if *any* fields will be displayed
    $display = false;
    foreach ($fields as $field) {
        if ($field->hidden == false) {
            $display = true;
        }
    }

    // display the header and the fields
    if ($display) {
        $mform->addElement('header', 'customfields', get_string('customfields', 'totara_customfield'));
        foreach ($fields as $field) {
            require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
            $newfield = 'customfield_'.$field->datatype;
            $formfield = new $newfield($field->id, $itemid, $prefix, $tableprefix);
            $formfield->edit_field($mform);
        }
    }
}

function customfield_definition_after_data(&$mform, $itemid, $prefix, $typeid=0, $tableprefix) {
    global $CFG, $DB;

    $typestr = '';
    $params = array();

    if ($typeid) {
        $typestr = 'typeid = ?';
        $params[] = $typeid;
    }

    $fields = $DB->get_records_select($tableprefix.'_info_field', $typestr, $params);
    foreach ($fields as $field) {
        require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
        $newfield = 'customfield_'.$field->datatype;
        $formfield = new $newfield($field->id, $itemid, $prefix, $tableprefix);
        $formfield->edit_after_data($mform);
    }
}

function customfield_validation($itemnew, $prefix, $tableprefix) {
    global $CFG, $DB;

    $err = array();

    $typestr = '';
    $params = array();

    if (!empty($itemnew->typeid)) {
        $typestr = 'typeid = ?';
        $params[] = $itemnew->typeid;
    }

    $fields = $DB->get_records_select($tableprefix.'_info_field', $typestr, $params);

    foreach ($fields as $field) {
        require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
        $newfield = 'customfield_'.$field->datatype;
        $formfield = new $newfield($field->id, $itemnew->id, $prefix, $tableprefix);
        $err += $formfield->edit_validate_field($itemnew, $prefix, $tableprefix);
    }

    return $err;
}

function customfield_save_data($itemnew, $prefix, $tableprefix) {
    global $CFG, $DB;

    $typestr = '';
    $params = array();

    if (isset($itemnew->typeid)) {
        $typestr = 'typeid = ?';
        $params[] = $itemnew->typeid;
    }

    $fields = $DB->get_records_select($tableprefix.'_info_field', $typestr, $params);

    foreach ($fields as $field) {
        require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
        $newfield = 'customfield_'.$field->datatype;
        $formfield = new $newfield($field->id, $itemnew->id, $prefix, $tableprefix);
        $formfield->edit_save_data($itemnew, $prefix, $tableprefix);
    }
}

/**
 * Return an associative array of custom field name/value pairs for display
 *
 * The array contains values formatted for printing to the page. Hidden and
 * empty fields are not returned. Data has been passed through the appropriate
 * display_data() method.
 *
 * @param integer $itemid ID of the item the fields belong to
 * @param string $tableprefix Prefix to append '_info_field' to
 * @param string $prefix Custom field prefix (e.g. 'course' or 'position')
 *
 * @return array Associate array of field names and data values
 */
function customfield_get_fields($itemid, $tableprefix, $prefix) {
    global $CFG, $DB;
    $out = array();

    $fields = $DB->get_records($tableprefix.'_info_field', array(), 'sortorder ASC');

    foreach ($fields as $field) {
        require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
        $newfield = 'customfield_'.$field->datatype;
        $formfield = new $newfield($field->id, $itemid, $prefix, $tableprefix);
        if (!$formfield->is_hidden() and !$formfield->is_empty()) {
            $out[s($formfield->field->fullname)] = $formfield->display_data();
        }
    }
    return $out;
}


/**
 * Return the HTML to display a set of table rows containing the custom fields
 *
 * Just the table rows are returned, no HTML table tags
 *
 * @param integer $itemid ID of the item the fields belong to
 * @param string $tableprefix Prefix to append '_info_field' to
 * @param string $prefix Custom field prefix (e.g. 'course' or 'position')
 *
 * @return string HTML to display the table rows
 */
function customfield_display_fields($itemid, $tableprefix, $prefix) {
    global $DB;

    $fields = $DB->get_fields($itemid, $tableprefix, $prefix);

    foreach ($fields as $field => $data) {
        echo html_writer::tag('tr',
                html_writer::tag('th', $field, array('class' => 'label c0')) .
                html_writer::tag('td', $data, array('class' => 'info c1')));
    }
}

/**
 * Returns an object with the custom fields set for the given id
 * @param  integer  id
 * @return  object
 */
function customfield_record($id, $tableprefix) {
    global $CFG, $DB;
    $item = new stdClass();

    $fields = $DB->get_records_select($tableprefix.'_info_field', '');

    foreach ($fields as $field) {
        require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
        $newfield = 'customfield_'.$field->datatype;
        $formfield = new $newfield($field->id, $id);
        if ($formfield->is_object_data()) $item->{$field->shortname} = $formfield->data;
    }

    return $item;
}
