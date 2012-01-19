<?php
require_once($CFG->dirroot.'/lib/formslib.php');

class user_position_assignment_form extends moodleform {

    // Define the form
    function definition () {
        global $CFG, $COURSE, $POSITION_TYPES;

        $mform =& $this->_form;
        $type = $this->_customdata['type'];
        $pa = $this->_customdata['position_assignment'];
        $can_edit = $this->_customdata['can_edit'];
        $nojs = $this->_customdata['nojs'];

        // Check if an aspirational position
        $aspirational = false;
        if (isset($POSITION_TYPES[POSITION_TYPE_ASPIRATIONAL]) && $type == $POSITION_TYPES[POSITION_TYPE_ASPIRATIONAL]) {
            $aspirational = true;
        }

        // Get position title
        $position_title = '';
        if ($pa->positionid) {
            $position_title = get_field('pos', 'fullname', 'id', $pa->positionid);
        }

        // Get organisation title
        $organisation_title = '';
        if ($pa->organisationid) {
            $organisation_title = get_field('org', 'fullname', 'id', $pa->organisationid);
        }

        // Get manager title
        $manager_title = '';
        $manager_id = 0;
        if ($pa->reportstoid) {
            $manager = get_record_sql(
                "
                    SELECT
                        u.id,
                        u.firstname,
                        u.lastname,
                        ra.id AS ra
                    FROM
                        {$CFG->prefix}user u
                    INNER JOIN
                        {$CFG->prefix}role_assignments ra
                     ON u.id = ra.userid
                    WHERE
                        ra.id = {$pa->reportstoid}
                "
            );

            if ($manager) {
                $manager_title = fullname($manager);
                $manager_id = $manager->id;
            }
        }

        // Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if(!$nojs) {
            $mform->addElement('html','<noscript><p>This form requires Javascript to be enabled.
                <a href="'.qualified_me().'&amp;nojs=1">'.get_string('clickfornonjsform','position').'</a>.</p></noscript>');
        }
        $mform->addElement('header', 'general', get_string('type'.$type, 'position'));

        if (!$aspirational) {
            $mform->addElement('text', 'fullname', get_string('titlefullname', 'position'));
            $mform->setType('fullname', PARAM_TEXT);
            $mform->setHelpButton('fullname', array('userpositionfullname', get_string('titlefullname', 'position')), true);

            $mform->addElement('text', 'shortname', get_string('titleshortname', 'position'));
            $mform->setType('shortname', PARAM_TEXT);
            $mform->setHelpButton('shortname', array('userpositionshortname', get_string('titleshortname', 'position')), true);

            $mform->addElement('htmleditor', 'description', get_string('description'), array('rows'=> '10', 'cols'=>'35'));
            $mform->setType('description', PARAM_RAW);
            $mform->setHelpButton('description', array('text', get_string('helptext')), true);
        }

        if($nojs) {
            $allpositions = get_records_menu('pos','','','frameworkid,sortthread','id,fullname');
            $mform->addElement('select','positionid', get_string('chooseposition','position'), $allpositions);
            $mform->setHelpButton('positionid', array('userpositionposition', get_string('chooseposition', 'position')), true);
        } else {
            $pos_class = strlen($position_title) ? 'nonempty' : '';
            $mform->addElement('static', 'positionselector', get_string('position', 'position').
                    '<img class="req" title="Required field" alt="Required field" src="'.$CFG->pixpath.'/req.gif">',
                '<span class="'.$pos_class.'" id="positiontitle"> '.format_string($position_title).'</span>'.
                    ($can_edit ? '<input type="button" value="'.get_string('chooseposition', 'position').'" id="show-position-dialog" />' : '')
            );
            $mform->addElement('hidden', 'positionid');
            $mform->setType('positionid', PARAM_INT);
            $mform->setDefault('positionid', 0);
            if(!$aspirational) {
                $mform->setHelpButton('positionselector', array('userpositionposition', get_string('chooseposition', 'position')), true);
            } else {
                $mform->setHelpButton('positionselector', array('useraspirationalposition', get_string('chooseposition', 'position')), true);
            }

        }
        if (!$aspirational) {
            if($nojs) {
                $allorgs = get_records_menu('org','','','frameworkid,sortthread','id,fullname');
                if (is_array($allorgs) && !empty($allorgs) ){
                    $mform->addElement('select','organisationid', get_string('chooseorganisation','organisation'),
                        array(0 => get_string('chooseorganisation','organisation')) + $allorgs);
                } else {
                    $mform->addElement('static', 'organisationid', get_string('chooseorganisation','organisation'), get_string('noorganisation','organisation') );
                }
                $mform->setHelpButton('organisationid', array('userpositionorganisation', get_string('chooseorganisation', 'organisation')), true);
            } else {
                $org_class = strlen($organisation_title) ? 'nonempty' : '';
                $mform->addElement('static', 'organisationselector', get_string('organisation', 'position'),
                    '
                        <span class="'.$org_class.'" id="organisationtitle">'.format_string($organisation_title).'</span>
                    '.
                    ($can_edit ? '<input type="button" value="'.get_string('chooseorganisation', 'organisation').'" id="show-organisation-dialog" />' : '')
                );

                $mform->addElement('hidden', 'organisationid');
                $mform->setType('organisationid', PARAM_INT);
                $mform->setDefault('organisationid', 0);
                $mform->setHelpButton('organisationselector', array('userpositionorganisation', get_string('chooseorganisation', 'organisation')), true);
            }

            if($nojs) {
             $allmanagers = get_records_sql_menu("
                    SELECT
                        u.id,
                        ".sql_fullname('u.firstname', 'u.lastname')." AS fullname
                    FROM
                        {$CFG->prefix}user u
                    ORDER BY
                        u.firstname,
                        u.lastname");
                if ( is_array($allmanagers) && !empty($allmanagers) ){
                    $mform->addElement('select', 'managerid', get_string('choosemanager','position'),
                        array(0 => get_string('choosemanager','position')) + $allmanagers);
                    $mform->setDefault('managerid', $manager_id);
                } else {
                    $mform->addElement('static','managerid',get_string('choosemanager','position'), get_string('error:dialognotreeitems', 'manager'));
                }
                $mform->setHelpButton('managerid', array('userpositionmanager', get_string('choosemanager', 'position')), true);
            } else {
                // Show manager
                // If we can edit, show button. Else show link to manager's profile
                if ($can_edit) {
                    $manager_class = strlen($manager_title) ? 'nonempty' : '';
                    $mform->addElement(
                        'static',
                        'managerselector',
                        get_string('manager', 'position'),
                        '<span class="'.$manager_class.'" id="managertitle">'.format_string($manager_title).'</span>'
                        .'<input type="button" value="'.get_string('choosemanager', 'position').'" id="show-manager-dialog" />'
                    );
                } else {
                    $mform->addElement(
                        'static',
                        'managerselector',
                        get_string('manager', 'position'),
                        '<span id="managertitle"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$manager_id.'">'
                        .format_string($manager_title).'</a></span>'
                    );
                }

                $mform->addElement('hidden', 'managerid');
                $mform->setType('managerid', PARAM_INT);
                $mform->setDefault('managerid', $manager_id);
                $mform->setHelpButton('managerselector', array('userpositionmanager', get_string('choosemanager', 'position')), true);
            }

            $group = array();
            $group[] = $mform->createElement('text', 'timevalidfrom','', array('name'=>get_string('startdate', 'position'),'placeholder' => get_string('datepickerplaceholder')));
            $mform->addGroup($group, 'timevalidfrom_group', get_string('startdate', 'position'), array(' '), false);
            $mform->setType('timevalidfrom', PARAM_TEXT);
            $mform->setDefault('timevalidfrom', get_string('datepickerdisplayformat','langconfig'));
            $mform->setHelpButton('timevalidfrom_group', array('userpositionstartdate', get_string('startdate', 'position')), true);

            $group = array();
            $group[] = $mform->createElement('text', 'timevalidto', '', array('name'=>get_string('finishdate', 'position'),'placeholder' => get_string('datepickerplaceholder')));
            $mform->addGroup($group, 'timevalidto_group', get_string('finishdate', 'position'), array(' '), false);
            $mform->setType('timevalidto', PARAM_TEXT);
            $mform->setDefault('timevalidto', get_string('datepickerdisplayformat','langconfig'));
            $mform->setHelpButton('managerselector', array('userpositionmanager', get_string('choosemanager', 'position')), true);
            $mform->setHelpButton('timevalidto_group', array('userpositionfinishdate', get_string('finishdate', 'position')), true);

            $rule1['timevalidfrom'][] = array('Enter a valid date','regex' , get_string('datepickerregexphp'));
            $mform->addGroupRule('timevalidfrom_group', $rule1);
            $rule2['timevalidto'][] = array('Enter a valid date','regex' , get_string('datepickerregexphp'));
            $mform->addGroupRule('timevalidto_group', $rule2);
        }

        $this->add_action_buttons(true, get_string('updateposition', 'position'));
    }

    function definition_after_data() {
        $mform =& $this->_form;

        // Fix odd date values
        // Check if form is frozen
        if ($mform->elementExists('timevalidfrom_group')) {

            $groupfrom = $mform->getElement('timevalidfrom_group');
            $date = $groupfrom->getValue();
            $timevalidfromdateint = (int)$date["timevalidfrom"];

            if (!$timevalidfromdateint) {
                $mform->setDefault('timevalidfrom', '');
            }
            else {
                $mform->setDefault('timevalidfrom', date(get_string('datepickerparseformat','langconfig'), $timevalidfromdateint));
            }
        }

        if ($mform->elementExists('timevalidto_group')) {

            $groupto = $mform->getElement('timevalidto_group');
            $date2 = $groupto->getValue();
            $timevalidtodateint = (int)$date2["timevalidto"];

            if (!$timevalidtodateint) {
                $mform->setDefault('timevalidto', '');
            }
            else {
                $mform->setDefault('timevalidto', date(get_string('datepickerparseformat','langconfig'), $timevalidtodateint));
            }
        }
    }

    function freezeForm() {
        $mform =& $this->_form;

        // Freeze values
        $mform->hardFreezeAllVisibleExcept(array());

        // Hide elements with no values
        foreach (array_keys($mform->_elements) as $key) {

            $element =& $mform->_elements[$key];

            // Check static elements differently
            if ($element->getType() == 'static') {
                // Check if it is a js selector
                if (substr($element->getName(), -8) == 'selector') {
                    // Get id element
                    $elementid = $mform->getElement(substr($element->getName(), 0, -8).'id');

                    if (!$elementid || !$elementid->getValue()) {
                        $mform->removeElement($element->getName());
                    }

                    continue;
                }
            }

            // Get element value
            $value = $element->getValue();

            // Check groups
            // (matches date groups and action buttons)
            if (is_array($value)) {

                // If values are strings (e.g. buttons, or date format string), remove
                foreach ($value as $k => $v) {
                    if (!is_numeric($v)) {
                        $mform->removeElement($element->getName());
                        break;
                    }
                }
            }
            // Otherwise check if empty
            elseif (!$value) {
                $mform->removeElement($element->getName());
            }
        }
    }

    function validation($data, $files) {

        $mform =& $this->_form;

        $result = array();

        $timevalidfromstr = isset($data['timevalidfrom'])?$data['timevalidfrom']:'';
        $timevalidfrom = totara_date_parse_from_format(get_string('datepickerparseformat','langconfig'),$timevalidfromstr);
        $timevalidtostr = isset($data['timevalidto'])?$data['timevalidto']:'';
        $timevalidto = totara_date_parse_from_format(get_string('datepickerparseformat','langconfig'),$timevalidtostr);

        // Enforce valid dates
        if ( false === $timevalidfrom && $timevalidfromstr !== get_string('datepickerdisplayformat','langconfig') && $timevalidfromstr !== '' ){
            $result['timevalidfrom'] = get_string('error:dateformat','position', get_string('datepickerplaceholder'));
        }
        if ( false === $timevalidto && $timevalidtostr !== get_string('datepickerdisplayformat','langconfig') && $timevalidtostr !== '' ){
            $result['timevalidto'] = get_string('error:dateformat','position', get_string('datepickerplaceholder'));
        }

        // Enforce start date before finish date
        if ( $timevalidfrom > $timevalidto && $timevalidfrom !== false && $timevalidto !== false ){
            $errstr = get_string('error:startafterfinish','position');
            $result['timevalidfrom_group'] = $errstr;
            $result['timevalidto_group'] = $errstr;
            unset($errstr);
        }

        // Check that a position was set
        if (!$mform->getElement('positionid')->getValue()) {
            $result['positionselector'] = get_string('error:positionnotset', 'position');
        }

        return $result;
    }
}
