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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @package totara
 * @subpackage local
 */

/**
 * Local db upgrades for Totara
 */

require_once($CFG->dirroot.'/local/db/utils.php');


/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_local_upgrade($oldversion) {
    global $CFG, $db;

    $result = true;
    if ($result && $oldversion < 2009091000) {

    /// Define field enablecompletion to be added to course
        $table = new XMLDBTable('course');
        $field = new XMLDBField('enablecompletion');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'defaultrole');

    /// Launch add field enablecompletion
        $result = $result && add_field($table, $field);

    /// Define field completion to be added to course_modules
        $table = new XMLDBTable('course_modules');
        $field = new XMLDBField('completion');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'groupmembersonly');

    /// Launch add field completion
        $result = $result && add_field($table, $field);

    /// Define field completiongradeitemnumber to be added to course_modules
        $field = new XMLDBField('completiongradeitemnumber');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'completion');

    /// Launch add field completiongradeitemnumber
        $result = $result && add_field($table, $field);

    /// Define field completionview to be added to course_modules
        $field = new XMLDBField('completionview');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completiongradeitemnumber');

    /// Launch add field completionview
        $result = $result && add_field($table, $field);

    /// Define field completionexpected to be added to course_modules
        $field = new XMLDBField('completionexpected');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completionview');

    /// Launch add field completionexpected
        $result = $result && add_field($table, $field);

   /// Define table course_modules_completion to be created
        $table = new XMLDBTable('course_modules_completion');
        if(!table_exists($table)) {

        /// Adding fields to table course_modules_completion
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('coursemoduleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('completionstate', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('viewed', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, null);
            $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

        /// Adding keys to table course_modules_completion
            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table course_modules_completion
            $table->addIndexInfo('coursemoduleid', XMLDB_INDEX_NOTUNIQUE, array('coursemoduleid'));
            $table->addIndexInfo('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        /// Launch create table for course_modules_completion
            create_table($table);
        }

    /// Define field availablefrom to be added to course_modules
        $table = new XMLDBTable('course_modules');
        $field = new XMLDBField('availablefrom');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'completionexpected');

    /// Conditionally launch add field availablefrom
        $result = $result && add_field($table, $field);

    /// Define field availableuntil to be added to course_modules
        $field = new XMLDBField('availableuntil');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'availablefrom');

    /// Conditionally launch add field availableuntil
        $result = $result && add_field($table, $field);

    /// Define field showavailability to be added to course_modules
        $field = new XMLDBField('showavailability');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'availableuntil');

    /// Conditionally launch add field showavailability
        $result = $result && add_field($table, $field);

    /// Define table course_modules_availability to be created
        $table = new XMLDBTable('course_modules_availability');

    /// Adding fields to table course_modules_availability
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('coursemoduleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('sourcecmid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('requiredcompletion', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('gradeitemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('grademin', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, null, null);
        $table->addFieldInfo('grademax', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, null, null);

    /// Adding keys to table course_modules_availability
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('coursemoduleid', XMLDB_KEY_FOREIGN, array('coursemoduleid'), 'course_modules', array('id'));
        $table->addKeyInfo('sourcecmid', XMLDB_KEY_FOREIGN, array('sourcecmid'), 'course_modules', array('id'));
        $table->addKeyInfo('gradeitemid', XMLDB_KEY_FOREIGN, array('gradeitemid'), 'grade_items', array('id'));

    /// Conditionally launch create table for course_modules_availability
        if (!table_exists($table)) {
            create_table($table);
        }

    /// Changes to modinfo mean we need to rebuild course cache
        rebuild_course_cache(0,true);

    /// Add course completion tables
    /// Define table course_completion_aggr_methd to be created
        $table = new XMLDBTable('course_completion_aggr_methd');

    /// Adding fields to table course_completion_aggr_methd
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('criteriatype', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('method', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('value', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);

    /// Adding keys to table course_completion_aggr_methd
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table course_completion_aggr_methd
        $table->addIndexInfo('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->addIndexInfo('criteriatype', XMLDB_INDEX_NOTUNIQUE, array('criteriatype'));

    /// Conditionally launch create table for course_completion_aggr_methd
        if (!table_exists($table)) {
            create_table($table);
        }

    /// Define table course_completion_criteria to be created
        $table = new XMLDBTable('course_completion_criteria');

    /// Adding fields to table course_completion_criteria
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('criteriatype', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('module', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('moduleinstance', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('enrolperiod', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('completedate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('gradepass', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->addFieldInfo('role', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('lock', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);

    /// Adding keys to table course_completion_criteria
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table course_completion_criteria
        $table->addIndexInfo('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->addIndexInfo('lock', XMLDB_INDEX_NOTUNIQUE, array('lock'));

    /// Conditionally launch create table for course_completion_criteria
        if (!table_exists($table)) {
            create_table($table);
        }


    /// Define table course_completion_crit_compl to be created
        $table = new XMLDBTable('course_completion_crit_compl');

    /// Adding fields to table course_completion_crit_compl
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('criteriaid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('gradefinal', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->addFieldInfo('unenroled', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecompleted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);

    /// Adding keys to table course_completion_crit_compl
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table course_completion_crit_compl
        $table->addIndexInfo('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->addIndexInfo('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->addIndexInfo('criteriaid', XMLDB_INDEX_NOTUNIQUE, array('criteriaid'));
        $table->addIndexInfo('timecompleted', XMLDB_INDEX_NOTUNIQUE, array('timecompleted'));

    /// Conditionally launch create table for course_completion_crit_compl
        if (!table_exists($table)) {
            create_table($table);
        }


    /// Define table course_completion_notify to be created
        $table = new XMLDBTable('course_completion_notify');

    /// Adding fields to table course_completion_notify
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('role', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('message', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timesent', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table course_completion_notify
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table course_completion_notify
        $table->addIndexInfo('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

    /// Conditionally launch create table for course_completion_notify
        if (!table_exists($table)) {
            create_table($table);
        }

    /// Define table course_completions to be created
        $table = new XMLDBTable('course_completions');

    /// Adding fields to table course_completions
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timenotified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timeenrolled', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('timecompleted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('rpl', XMLDB_TYPE_CHAR, '255', null, null, null, null);

    /// Adding keys to table course_completions
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table course_completions
        $table->addIndexInfo('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->addIndexInfo('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->addIndexInfo('timecompleted', XMLDB_INDEX_NOTUNIQUE, array('timecompleted'));

    /// Conditionally launch create table for course_completions
        if (!table_exists($table)) {
            create_table($table);
        }


    /// Add cols to course table
    /// Define field enablecompletion to be added to course
        $table = new XMLDBTable('course');
        $field = new XMLDBField('enablecompletion');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'defaultrole');

    /// Conditionally launch add field enablecompletion
        if (!field_exists($table, $field)) {
            add_field($table, $field);
        }

    /// Add cols to course completion criteria table
        $table = new XMLDBTable('course_completion_criteria');
        $field = new XMLDBField('courseinstance');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'moduleinstance');

        if (!field_exists($table, $field)) {
            add_field($table, $field);
        }

        set_config('defaulthtmleditor', 'tinymce');

    /// Create table competency_framework
        $table = new XMLDBTable('comp_framework');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('isdefault', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidecustomfields', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('sortorder', XMLDB_INDEX_UNIQUE, array('sortorder'));
        $result = $result && create_table($table);

    /// Create table competency_depth
        $table = new XMLDBTable('comp_depth');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('depthlevel', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_depth_info_category
        $table = new XMLDBTable('comp_depth_info_category');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_depth_info_field
        $table = new XMLDBTable('comp_depth_info_field');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('datatype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidden', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('locked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('required', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('forceunique', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('defaultdata', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param1', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param2', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param3', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param4', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param5', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency
        $table = new XMLDBTable('comp');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('path', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('parentid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('aggregationmethod', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('scaleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('proficiencyexpected', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('evidencecount', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_depth_info_data
        $table = new XMLDBTable('comp_depth_info_data');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('competencyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_relations
        $table = new XMLDBTable('comp_relations');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('id1', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('id2', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_evidence_items
        $table = new XMLDBTable('comp_evidence_items');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('competencyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('itemtype', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('itemmodule', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('iteminstance', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_scale
        $table = new XMLDBTable('comp_scale');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_scale_values
        $table = new XMLDBTable('comp_scale_values');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('name', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('scaleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('numericscore', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_scale_assignments
        $table = new XMLDBTable('comp_scale_assignments');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('scaleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_template
        $table = new XMLDBTable('comp_template');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('competencycount', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_template_assignment
        $table = new XMLDBTable('comp_template_assignment');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('templateid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('instanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_template_competencies
        $table = new XMLDBTable('comp_template_competencies');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('templateid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('competencyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('priority', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('proficiencyexpected', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidden', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table organisation_framework
        $table = new XMLDBTable('org_framework');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('isdefault', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidecustomfields', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('sortorder', XMLDB_INDEX_UNIQUE, array('sortorder'));
        $result = $result && create_table($table);

    /// Create table organisation_depth
        $table = new XMLDBTable('org_depth');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('depthlevel', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table organisation_depth_info_category
        $table = new XMLDBTable('org_depth_info_category');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table organisation_depth_info_field
        $table = new XMLDBTable('org_depth_info_field');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('datatype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidden', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('locked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('required', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('forceunique', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('defaultdata', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param1', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param2', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param3', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param4', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param5', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table organisation
        $table = new XMLDBTable('org');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('path', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('parentid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table organisation_depth_info_data
        $table = new XMLDBTable('org_depth_info_data');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('organisationid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table organisation_relations
        $table = new XMLDBTable('org_relations');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('id1', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('id2', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position_framework
        $table = new XMLDBTable('pos_framework');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('isdefault', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidecustomfields', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('sortorder', XMLDB_INDEX_UNIQUE, array('sortorder'));
        $result = $result && create_table($table);

    /// Create table position_depth
        $table = new XMLDBTable('pos_depth');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('depthlevel', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position_depth_info_category
        $table = new XMLDBTable('pos_depth_info_category');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position_depth_info_field
        $table = new XMLDBTable('pos_depth_info_field');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('datatype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidden', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('locked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('required', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('forceunique', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('defaultdata', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param1', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param2', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param3', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param4', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param5', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position
        $table = new XMLDBTable('pos');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('frameworkid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('path', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('depthid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('parentid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('visible', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timevalidfrom', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timevalidto', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position_depth_info_data
        $table = new XMLDBTable('pos_depth_info_data');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('positionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position_relations
        $table = new XMLDBTable('pos_relations');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('id1', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('id2', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position_assignment
        $table = new XMLDBTable('pos_assignment');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('positionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('reportstoid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timevalidfrom', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timevalidto', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table position_assignment_history
        $table = new XMLDBTable('pos_assignment_history');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('positionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('reportstoid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timevalidfrom', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timevalidto', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timefinished', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    /// Create table competency_evidence
        $table = new XMLDBTable('comp_evidence');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('competencyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('positionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('organisationid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('assessorid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('assessorname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('assessmenttype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('proficiency', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

    }

    if ($result && $oldversion < 2010021102) {
    // Add missing fields
        $table = new XMLDBTable('pos_assignment');

        $field = new XMLDBField('organisationid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && add_field($table, $field);

        $table = new XMLDBTable('pos_assignment_history');

        $field = new XMLDBField('organisationid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && add_field($table, $field);


    /// Fix sequences and nullables
        $table = new XMLDBTable('pos_assignment');

        $field = new XMLDBField('userid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('positionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('reportstoid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('type');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);


        $table = new XMLDBTable('comp_depth_info_data');

        $field = new XMLDBField('fieldid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('competencyid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $result = $result && change_field_type($table, $field);


        $table = new XMLDBTable('org_depth_info_data');

        $field = new XMLDBField('fieldid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('organisationid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $result = $result && change_field_type($table, $field);


        $table = new XMLDBTable('pos_assignment_history');

        $field = new XMLDBField('userid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('positionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('reportstoid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('type');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $result = $result && change_field_type($table, $field);
    }

    if ($result && $oldversion < 2010012700) {
        // this flag was added here on 29/08/2011, therefore any site that has
        // already upgraded past this point will never set the flag.
        //
        // So if the flag is set, we know it must be either a new install, or
        // an install so old that no report builder content was ever installed.
        // In both these cases we can skip all the messy report builder upgrades
        // and do a fresh install via local/reportbuilder/db/install.xml
        //
        // if this flag is not set, we need to follow the existing upgrade path
        // as the site has previously upgraded past this point
        set_config('skiplegacyupgrades', true, 'reportbuilder');
    }

    if ($result && $oldversion < 2010012800 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
    /// Create table learning_report
        $table = new XMLDBTable('learning_report');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('source', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('restriction', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->addFieldInfo('filters', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('columns', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('shortname', XMLDB_INDEX_UNIQUE, array('shortname'));
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2010020500 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
    // increase space for restriction data
        $table = new XMLDBTable('learning_report');
        $field = new XMLDBField('restriction');
        $result = $result && drop_field($table, $field);
        $field = new XMLDBField('restriction');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, null, null, null);
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010020800 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        // rename learning_report to report_builder
        $table = new XMLDBTable('learning_report');
        $result = $result && rename_table($table, 'report_builder');
    }

    if ($result && $oldversion < 2010021700) {
    /// Create table competency_evidence_items_evidence
        $table = new XMLDBTable('comp_evidence_items_evidence');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('competencyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('status', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('proficiencymeasured', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2010021701 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        // add hidden field to report builder table
        $table = new XMLDBTable('report_builder');
        $field = new XMLDBField('hidden');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0);
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010022401) {
    /// Create table competency_evidence_items
        $table = new XMLDBTable('pos_competencies');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('positionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('competencyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('templateid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2010022601) {
        // Limit RPL field to 255 characters
        $table = new XMLDBTable('course_completions');
        $field = new XMLDBField('rpl');
        $field->setType(XMLDB_TYPE_CHAR);
        $field->setLength(255);
        $result = $result && change_field_type($table, $field, true, true);

    }

    if ($result && $oldversion < 2010030600) {
        totara_reset_stickyblocks(true);
    }

    if ($result && $oldversion < 2010031200) {
    /// Add reaggregate to competency evidence table
        $table = new XMLDBTable('comp_evidence');
        $field = new XMLDBField('reaggregate');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timemodified');
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010031500) {
    /// Add proficient to competency scale table
        $table = new XMLDBTable('comp_scale');
        $field = new XMLDBField('proficient');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, null, 'description');
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010031600) {
    /// Add manual flag to competency evidence table
        $table = new XMLDBTable('comp_evidence');
        $field = new XMLDBField('manual');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $result = $result && add_field($table, $field);

    /// Make sure all existing evidence are set to manual
        execute_sql("
            UPDATE
                {$CFG->prefix}comp_evidence
            SET
                manual = 1
        ");
    }

    if ($result && $oldversion < 2010031601) {
    /// Add default to competency scale table
        $table = new XMLDBTable('comp_scale');
        $field = new XMLDBField('defaultid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, null, 'proficient');
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010031800) {
    /// Add missing indexes
        $table = new XMLDBTable('comp');

        $index = new XMLDBIndex('parentid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('parentid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('frameworkid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('frameworkid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('depthid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('depthid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('scaleid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('scaleid'));
        $sfield = new XMLDBField('scaleid');
        if (field_exists($table, $field)) {
            $result = $result && add_index($table, $index);
        }
        unset($field);

        $table = new XMLDBTable('comp_evidence');

        $index = new XMLDBIndex('competencyid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('competencyid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('userid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('reaggregate');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('reaggregate'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('manual');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('manual'));
        $result = $result && add_index($table, $index);


        $table = new XMLDBTable('comp_evidence_items');

        $index = new XMLDBIndex('competencyid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('competencyid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('itemtype');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('itemtype'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('iteminstance');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('iteminstance'));
        $result = $result && add_index($table, $index);


        $table = new XMLDBTable('comp_evidence_items_evidence');

        $index = new XMLDBIndex('itemid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('itemid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('userid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('proficiencymeasured');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('proficiencymeasured'));
        $result = $result && add_index($table, $index);

        $index = new XMLDBIndex('timemodified');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('timemodified'));
        $result = $result && add_index($table, $index);


        $table = new XMLDBTable('comp_scale');

        $index = new XMLDBIndex('proficient');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('proficient'));
        $result = $result && add_index($table, $index);
    }

    if ($result && $oldversion < 2010031801) {
    /// Remove not null constraints from competency_evidence
        $table = new XMLDBTable('comp_evidence');

        $field = new XMLDBField('assessorname');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $result = $result && change_field_type($table, $field);

        $field = new XMLDBField('assessmenttype');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $result = $result && change_field_type($table, $field);
    }

    if ($result && $oldversion < 2010033000) {
    // increase space for restriction data
        $table = new XMLDBTable('comp_framework');
        $field = new XMLDBField('isdefault');
        $result = $result && drop_field($table, $field);

        $table = new XMLDBTable('org_framework');
        $field = new XMLDBField('isdefault');
        $result = $result && drop_field($table, $field);

        $table = new XMLDBTable('pos_framework');
        $field = new XMLDBField('isdefault');
        $result = $result && drop_field($table, $field);
    }

    // Reorganizing the IDP-related capabilities
    if ($result && $oldversion < 2010041400){
        // Delete these because they're not in use
        $deletelist = array(
            'moodle/local:editownfavourite',
            'moodle/local:viewfavourite',
            'moodle/local:viewownfavourite',
            'moodle/local:withdrawownplan'
        );
        foreach ( $deletelist as $deletecap ){
            if ( false === delete_records('role_capabilities','capability',$deletecap) ){
                $result = false;
            }
            if ( false === delete_records('capabilities','name',$deletecap) ){
                $result = false;
            }
        }
    }

    if ($result && $oldversion < 2010041900){

        // Renaming hierarchy items types to be shorter, so the tables named
        // after them won't break Oracle's 30-char size limit
        $substlist = array(
            'competency' => 'comp',
            'organisation' => 'org',
            'position' => 'pos'
        );

        $tablelist = array(
            'competency',
            'competency_depth',
            'competency_depth_info_category',
            'competency_depth_info_data',
            'competency_depth_info_field',
            'competency_evidence',
            'competency_evidence_items',
            'competency_evidence_items_evidence',
            'competency_framework',
            'competency_relations',
            'competency_scale',
            'competency_scale_assignments',
            'competency_scale_values',
            'competency_template',
            'competency_template_assignment',
            'competency_template_competencies',
            'organisation',
            'organisation_depth',
            'organisation_depth_info_category',
            'organisation_depth_info_data',
            'organisation_depth_info_field',
            'organisation_framework',
            'organisation_relations',
            'position',
            'position_assignment',
            'position_assignment_history',
            'position_competencies',
            'position_depth',
            'position_depth_info_category',
            'position_depth_info_data',
            'position_depth_info_field',
            'position_framework',
            'position_relations'
        );

        foreach( $tablelist as $oldtablename ){
            $newtablename = $oldtablename;
            foreach( $substlist as $oldtype => $newtype ){
                $newtablename = str_replace($oldtype, $newtype, $newtablename);
            }
            $table = new XMLDBTable($oldtablename);
            if(table_exists($table)) {
                $result = $result && rename_table($table, $newtablename);
            }
        }
    }

    if ($result && $oldversion < 2010042800){
            // Delete related competency records where a competency is being related to itself
            $result = $result && execute_sql("delete from {$CFG->prefix}comp_relations where id1=id2");

            // Delete duplicate related competency records
            $sql = 'select count(*), min(id) as m, least(id1,id2) as a, greatest(id1,id2) as b ';
            $sql .= "from {$CFG->prefix}comp_relations ";
            $sql .= 'group by least(id1,id2), greatest(id1,id2) ';
            $sql .= 'having count(*) > 1';
            $duperecords = get_records_sql($sql);
            if ( is_array( $duperecords ) ){
                foreach ($duperecords as $duperec){
                    $result = $result && execute_sql(
                            "delete from {$CFG->prefix}comp_relations where id<>{$duperec->m} and "
                            . "((id1={$duperec->a} and id2={$duperec->b}) or (id1={$duperec->b} and  id2={$duperec->a}))"
                    );
                }
            }
    }

    if ($result && $oldversion < 2010050700) {

        // add indexes to speed up report performance
        $table = new XMLDBTable('pos_assignment');
        $index = new XMLDBIndex('userid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $result = $result && add_index($table, $index);

        $table = new XMLDBTable('facetoface_session_data');
        $index = new XMLDBIndex('sessionid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('sessionid'));
        $result = $result && add_index($table, $index);

        $table = new XMLDBTable('facetoface_session_data');
        $index = new XMLDBIndex('fieldid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('fieldid'));
        $result = $result && add_index($table, $index);

        if (!get_config('reportbuilder', 'skiplegacyupgrades')) {
            // lots of changes to report builder db structure
            totara_migrate_old_report_builder_reports($result);
        }

    }


    if ($result && $oldversion < 2010051100 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        $table = new XMLDBTable('report_builder_saved');
        if(!table_exists($table)) {

        /// Adding fields to table report_builder_saved
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('reportid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->addFieldInfo('search', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('ispublic', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

        /// Adding keys to table report_builder_saved
            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table report_builder_saved
            $table->addIndexInfo('reportid', XMLDB_INDEX_NOTUNIQUE, array('reportid'));
            $table->addIndexInfo('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        /// Launch create table for report_builder_saved
            create_table($table);
        }
    }

    // rename field to avoid mysql reserved word
    if ($result && $oldversion < 2010051900) {
        $table = new XMLDBTable('comp_scale_values');
        $field = new XMLDBField('numeric');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        if(field_exists($table, $field)) {
            $result = $result && rename_field($table, $field, 'numericscore');
        }
    }

    if ($result && $oldversion < 2010052600) {
    /// Create table report_heading_items
        $table = new XMLDBTable('report_heading_items');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('heading', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('defaultvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2010062300) {
        // Add pos/org data to course_completions
        // (to bring it up to spec with upstream)
        $table = new XMLDBTable('course_completions');

        $field = new XMLDBField('organisationid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'rpl');

        if (!field_exists($table, $field)) {
            add_field($table, $field);
        }

        $field = new XMLDBField('positionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'organisationid');

        if (!field_exists($table, $field)) {
            add_field($table, $field);
        }
    }

    if ($result && $oldversion < 2010070900) {

        // Rename misspelled column if exists
        $table = new XMLDBTable('course');
        $field = new XMLDBField('compleitonstartonenrol');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'enablecompletion');

        if(field_exists($table, $field)) {
            $result = $result && rename_field($table, $field, 'completionstartonenrol');
        }

        // Add completion setting to course table
        $table = new XMLDBTable('course_completions');
        $field = new XMLDBField('timestarted');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'timeenrolled');

        if (!field_exists($table, $field)) {
            add_field($table, $field);
        }

        // Add reaggregate field
        $field = new XMLDBField('reaggregate');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'rpl');

        if (!field_exists($table, $field)) {
            add_field($table, $field);
        }
    }

    if ($result && $oldversion < 2010071000) {
        // Create a table for organisational competencies
        $table = new XMLDBTable('org_competencies');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('organisationid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('competencyid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('templateid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2010072601 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
    /// Create table report_heading_items
        $table = new XMLDBTable('report_builder_settings');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('reportid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('reportid-type-name', XMLDB_INDEX_UNIQUE, array('reportid', 'type', 'name'));
        $result = $result && create_table($table);

        // migrate content settings from report_builder table
        if($records = get_records('report_builder')) {
            foreach($records as $record) {
                if($row = unserialize($record->contentsettings)) {
                    $reportid = $record->id;
                    foreach($row as $classname => $settings) {
                        $type = $classname . '_content';
                        foreach($settings as $name => $value) {
                            $todb = new object();
                            $todb->reportid = $reportid;
                            $todb->type = $type;
                            $todb->name = $name;
                            $todb->value = $value;
                            $result = $result &&
                                insert_record('report_builder_settings', $todb);
                        }
                    }
                }
            }
        }

        if($result) {
            // delete old content settings field
            $table = new XMLDBTable('report_builder');
            $field = new XMLDBField('contentsettings');
            $result = $result && drop_field($table, $field);

            // delete old access settings field
            $table = new XMLDBTable('report_builder');
            $field = new XMLDBField('accesssettings');
            $result = $result && drop_field($table, $field);
        }

        // migrate old access records to new format
        if($records = get_records('report_builder_access')) {
            $out = array();
            foreach($records as $record) {
                if($record->accesstype == 'role') {
                    $reportid = $record->reportid;
                    $out[$reportid][] = $record->typeid;
                }
            }
            foreach($out as $reportid => $roles) {
                $todb = new object();
                $todb->reportid = $reportid;
                $todb->type = 'role_access';
                $todb->name = 'activeroles';
                $todb->value = implode('|', $roles);
                $result = $result && insert_record('report_builder_settings',
                    $todb);
                $todb->name = 'enable';
                $todb->value = 1;
                $result = $result && insert_record('report_builder_settings',
                    $todb);
            }
        }

        // remove old access table
        if($result) {
            $table = new XMLDBTable('report_builder_access');
            $result = $result && drop_table($table);
        }

        // rename thedate_content to date_content in settings
        if($result) {
            if($daterecords = get_records('report_builder_settings', 'type', 'thedate_content')) {
                foreach($daterecords as $daterecord) {
                    $todb = new object();
                    $todb->id = $daterecord->id;
                    $todb->type = 'date_content';
                    $result = $result && update_record('report_builder_settings', $todb);
                }
            }
        }

        // create stored procedure for aggregating text by concatenation
        // mysql supports by default. The code below adds postgres support
        // see sql_group_concat() function for usage
        if($CFG->dbfamily == 'postgres') {
            $sql = '
                CREATE TYPE tp_concat AS (data TEXT[], delimiter TEXT);
                CREATE OR REPLACE FUNCTION group_concat_iterate(_state
                    tp_concat, _value TEXT, delimiter TEXT, is_distinct boolean)
                    RETURNS tp_concat AS
                $BODY$
                    SELECT
                        CASE
                            WHEN $1 IS NULL THEN ARRAY[$2]
                            WHEN $4 AND $1.data @> ARRAY[$2] THEN $1.data
                            ELSE $1.data || $2
                    END,
                    $3
                $BODY$
                    LANGUAGE \'sql\' VOLATILE;

                CREATE OR REPLACE FUNCTION group_concat_finish(_state tp_concat)
                    RETURNS text AS
                $BODY$
                    SELECT array_to_string($1.data, $1.delimiter)
                $BODY$
                    LANGUAGE \'sql\' VOLATILE;

                DROP AGGREGATE IF EXISTS group_concat(text, text, boolean);
                CREATE AGGREGATE group_concat(text, text, boolean) (SFUNC =
                    group_concat_iterate, STYPE = tp_concat, FINALFUNC =
                    group_concat_finish)';


            $result = $result && execute_sql($sql, false);
            /* To undo this, use the following:
             * DROP AGGREGATE group_concat(text, text, boolean);
             * DROP FUNCTION group_concat_finish(tp_concat);
             * DROP FUNCTION group_concat_iterate(tp_concat, text, text, boolean);
             * DROP TYPE tp_concat;
             */
        }

        /// Define table report_builder_group
        $table = new XMLDBTable('report_builder_group');
        if(!table_exists($table)) {

            /// Adding fields to table report_builder_group
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('preproc', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('baseitem', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->addFieldInfo('assigntype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('assignvalue', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);

            /// Adding keys to table report_builder_group
            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

            /// Launch create table for report_builder_group
            $result = $result && create_table($table);
        }

        /// Define table report_builder_group_assign
        $table = new XMLDBTable('report_builder_group_assign');
        if(!table_exists($table)) {

        /// Adding fields to table report_builder_group_assign
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('itemid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        /// Adding keys to table report_builder_group_assign
            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table report_builder_group_assign
            $table->addIndexInfo('groupid-itemid', XMLDB_INDEX_UNIQUE, array('groupid','itemid'));

        /// Launch create table for report_builder_group_assign
            $result = $result && create_table($table);
        }


        /// Define table report_builder_preproc_track
        $table = new XMLDBTable('report_builder_preproc_track');
        if(!table_exists($table)) {

        /// Adding fields to table report_builder_preproc_track
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
            $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('itemid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('lastchecked', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            $table->addFieldInfo('disabled', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);

        /// Adding keys to table report_builder_preproc_track
            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table report_builder_preproc track
            $table->addIndexInfo('groupid-itemid', XMLDB_INDEX_UNIQUE, array('groupid','itemid'));

        /// Launch create table for report_builder_preproc_track
            $result = $result && create_table($table);
        }

        /// Add hidden column to columns table
        $table = new XMLDBTable('report_builder_columns');
        $field = new XMLDBField('hidden');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', null);

        /// Conditionally add field hidden
        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }

    }

    if ($result && $oldversion < 2010072603) {
        $table = new XMLDBTable('block_guides_guide');
        $field = new XMLDBField('identifier');
        $field->setAttributes(XMLDB_TYPE_CHAR, '50', null, null, null, null, null);

        // Conditionally add field identifier
        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }
    }

    if ($result && $oldversion < 2010072604) {
        $success = true;
        $guides = get_records('block_guides_guide');
        if (!$guides) {
            $guides = array();
        }

        $dir = $CFG->dirroot . '/guides/guidedata/';
        $files = scandir($dir);
        foreach ($files as $file){
            if (strpos($file, '.') === 0) {
                continue;
            }
            if (!is_file($dir . $file)) {
                // Not interested in directories etc
                continue;
            }
            $matches = array();
            if (!preg_match('/[0-9]*_([A-Za-z0-9_\ -]*)\.php/', $file, $matches)) {
                continue;
            }
            $basename = $matches[1];
            $found = false;
            foreach ($guides as $guide) {
                if ($guide->identifier == $basename) {
                    $found = true;
                }
            }
            if ($found) {
                # We already know about that guide
                continue;
            }
            unset($guide);
            require_once($dir . $file);
            $guide->identifier = $basename;
            print "New guide found - adding $guide->name <br />\n";
            if(!insert_record("block_guides_guide",addslashes_object($guide)))
                $success = false;
        }

        $result = $result && $success;

    }

    if ($result && $oldversion < 2010072605) {
    /// Define table reminder to be created
        $table = new XMLDBTable('reminder');

    /// Adding fields to table reminder
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->addFieldInfo('title', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('config', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('modifierid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table reminder
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table reminder
        $table->addIndexInfo('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        $table->addIndexInfo('type', XMLDB_INDEX_NOTUNIQUE, array('type'));
        $table->addIndexInfo('deleted', XMLDB_INDEX_NOTUNIQUE, array('deleted'));

    /// Conditionally launch create table for reminder
        if (!table_exists($table)) {
            create_table($table);
        }


    /// Define table reminder_message to be created
        $table = new XMLDBTable('reminder_message');

    /// Adding fields to table reminder
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('reminderid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('type', XMLDB_TYPE_CHAR, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('period', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('copyto', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->addFieldInfo('subject', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->addFieldInfo('message', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->addFieldInfo('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    /// Adding keys to table reminder_message
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table reminder_message
        $table->addIndexInfo('reminderid', XMLDB_INDEX_NOTUNIQUE, array('reminderid'));
        $table->addIndexInfo('type', XMLDB_INDEX_NOTUNIQUE, array('type'));
        $table->addIndexInfo('deleted', XMLDB_INDEX_NOTUNIQUE, array('deleted'));

    /// Conditionally launch create table for reminder_message
        if (!table_exists($table)) {
            create_table($table);
        }


    /// Define table reminder_sent to be created
        $table = new XMLDBTable('reminder_sent');

    /// Adding fields to table reminder_sent
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('reminderid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('messageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('timesent', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

    /// Adding keys to table reminder_sent
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table reminder_sent
        $table->addIndexInfo('reminderid', XMLDB_INDEX_NOTUNIQUE, array('reminderid'));
        $table->addIndexInfo('messageid', XMLDB_INDEX_NOTUNIQUE, array('messageid'));
        $table->addIndexInfo('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

    /// Conditionally launch create table for reminder_sent
        if (!table_exists($table)) {
            create_table($table);
        }

    }

    /// add description field to report_builder table
    if ($result && $oldversion < 2010080200 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        $table = new XMLDBTable('report_builder');
        $field = new XMLDBField('description');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, null, null, null);
        $result = $result && add_field($table, $field);
    }

    // update existing reports to use new versions of
    // position and organisation filters (with dialogs)
    if ($result && $oldversion < 2010081000 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        if($filters = get_records_select('report_builder_filters',
            "(type = 'user' AND
             (value = 'organisationid' OR value = 'positionid') ) OR
             (type = 'course_completion' AND
             (value = 'organisationid' OR value = 'positionid') ) OR
             (type = 'competency_evidence' AND
             (value = 'organisationid' OR value = 'positionid') )")) {

            foreach($filters as $filter) {
                $todb = new object();
                $todb->id = $filter->id;
                $todb->value = str_replace(
                    array('positionid','organisationid'),
                    array('positionpath','organisationpath'),
                    $filter->value);
                $result = $result &&
                    update_record('report_builder_filters', $todb);
            }
        }
    }

    // set global export options to include all current
    // formats (excel, csv and ods)
    if ($result && $oldversion < 2010081200 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        set_config('exportoptions', 7, 'reportbuilder');
    }

    if ($result && $oldversion < 2010081900 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        // apply some database changes to get db in sync with install.xml version

        // remove not null from heading
        $table = new XMLDBTable('report_builder_columns');
        $field = new XMLDBField('heading');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
        $result = $result && change_field_type($table, $field);

        // add default 0 to advanced
        $table = new XMLDBTable('report_builder_filters');
        $field = new XMLDBField('advanced');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $result = $result && change_field_type($table, $field);

        // rename public to ispublic (keyword)
        if($CFG->dbfamily != 'mssql') {
            $table = new XMLDBTable('report_builder_saved');
            $field = new XMLDBField('public');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
            if(field_exists($table, $field)) {
                $result = $result && rename_field($table, $field, 'ispublic');
            }
        }

        // add default 0 to ispublic
        $table = new XMLDBTable('report_builder_saved');
        $field = new XMLDBField('ispublic');
        if (field_exists($table, $field)) {
            $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
            $result = $result && change_field_type($table, $field);
        }

        // add default 0 to disabled
        $table = new XMLDBTable('report_builder_preproc_track');
        $field = new XMLDBField('disabled');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $result = $result && change_field_type($table, $field);

        // remove unused capabilities
        $result = $result && delete_records_select('role_capabilities',
            "capability = 'moodle/local:viewownreports' OR
             capability = 'moodle/local:viewallreports' OR
             capability = 'moodle/local:viewstaffreports' OR
             capability = 'moodle/local:viewlocalreports'");

        $result = $result && delete_records_select('capabilities',
            "name = 'moodle/local:viewownreports' OR
             name = 'moodle/local:viewallreports' OR
             name = 'moodle/local:viewstaffreports' OR
             name = 'moodle/local:viewlocalreports'");

        // pretend that the reportbuilder local module has been installed
        // to skip the installation process for existing installation
        set_config('local_reportbuilder_version', '2010081900');
    }

    // copy across demo_setup config if set
    if ($result && $oldversion < 2010082000) {
        if($demo = get_config(null, 'mitms_demo_setup')) {
            $result = $result && set_config('totara_demo_setup', $demo);
        }
    }

    if ($result && $oldversion < 2010091300) {

        /// Create table course_info_category
        $table = new XMLDBTable('course_info_category');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

        /// Create table course_info_field
        $table = new XMLDBTable('course_info_field');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fullname', XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('datatype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('sortorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('categoryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hidden', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('locked', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('required', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('forceunique', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('defaultdata', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param1', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param2', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param3', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param4', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addFieldInfo('param5', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

        /// Create table course_info_data
        $table = new XMLDBTable('course_info_data');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('data', XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2010091500) {

        /// Define field icon to be added to course
        $table = new XMLDBTable('course');
        $field = new XMLDBField('icon');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'defaultrole');

        /// Launch add field icon
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010091501) {

        /// Define field icon to be added to course
        $table = new XMLDBTable('course_categories');
        $field = new XMLDBField('icon');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'theme');

        /// Launch add field icon
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010091600) {
        $table = new XMLDBTable('oldpassword');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->addFieldInfo('uid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addFieldInfo('hash', XMLDB_TYPE_CHAR, '100', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addIndexInfo('uid', XMLDB_INDEX_NOTUNIQUE, array('uid'));
        if (!table_exists($table)) {
            create_table($table);
        }
    }

    if ($result && $oldversion < 2010091700) {
        $table = new XMLDBTable('comp_template_competencies');
        if (table_exists($table)){
            $result = $result && drop_table($table);
        }
    }

    if ($result && $oldversion < 2010102000) {
        $table = new XMLDBTable('user_info_data');
        $key = new XMLDBKey('fieldid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('fieldid'), 'user_info_field', array('id'));

        /// Launch add key fieldid
        $result = $result && add_key($table, $key);

        $key = new XMLDBKey('userid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        /// Launch add key userid
        $result = $result && add_key($table, $key);
    }

    if ($result && $oldversion < 2010111600) {
        // Drop unused scaleid field from comp table
        $table = new XMLDBTable('comp');
        $field = new XMLDBField('scaleid');
        $result = $result && drop_field($table, $field);
    }

    if ($result && $oldversion < 2010111601) {
        if (!isset($CFG->registrationenabled)) {
            $result = $result && set_config('registrationenabled', '1');
        }
    }

    if ($result && $oldversion < 2010111602) {
        $table = new XMLDBTable('course');
        $field = new XMLDBField('coursetype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, null);
        if(!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }
    }

    if ($result && $oldversion < 2010111603) {
        $blockid = get_field('block', 'id', 'name', 'flash_video');
        if($blockid) {
            $dashb_instances = get_records_menu('block_instance', 'blockid', $blockid);
            if($dashb_instances) {
                $dashb_instance_ids = implode(',', array_keys($dashb_instances));
            }
        }
        if(isset($dashb_instance_ids)) {
            $result = $result && execute_sql("DELETE FROM {$CFG->prefix}dashb_instance_dashlet WHERE block_instance_id IN ({$dashb_instance_ids})", false);
            $result = $result && execute_sql("DELETE FROM {$CFG->prefix}block_instance WHERE blockid = {$blockid}", false);
            $result = $result && execute_sql("DELETE FROM {$CFG->prefix}block WHERE id={$blockid}", false);
            unset($dashb_instance_ids);
            unset($dashb_instances);
            unset($blockid);
        }
    }

    if ($result && $oldversion < 2011012800) {
        if (!get_records('comp_scale_values')) {
            global $USER;

            $todb = new stdClass;
            $todb->name = get_string('competencyscale', 'competency');
            $todb->description = '';
            $todb->usermodified = $USER->id;
            $todb->timemodified = time();
            if (!$scaleid = insert_record('comp_scale', $todb)) {
                $result = false;
            }

            $comp_scale_vals = array(
                array('name'=>get_string('competent', 'competency'), 'scaleid' => $scaleid, 'sortorder' => 1, 'usermodified' => $USER->id, 'timemodified' => time()),
                array('name'=>get_string('competentwithsupervision', 'competency'), 'scaleid' => $scaleid, 'sortorder' => 2, 'usermodified' => $USER->id, 'timemodified' => time()),
                array('name'=>get_string('notcompetent', 'competency'), 'scaleid' => $scaleid, 'sortorder' => 3, 'usermodified' => $USER->id, 'timemodified' => time())
                );

            $count = 0;
            foreach ($comp_scale_vals as $svrow) {
                $count++;
                $todb = new stdClass;
                foreach ($svrow as $key => $val) {
                    // Insert default competency scale values, if non-existent
                    $todb->$key = $val;
                }
                if (!$svid = insert_record('comp_scale_values', $todb)) {
                    $result = false;
                }
                if ($count == 1) {
                    $proficient = $svid;
                }
            }

            $todb = new stdClass;
            $todb->id = $scaleid;
            $todb->proficient = $proficient;
            $todb->defaultid = $svid;

            $result = $result && update_record('comp_scale', $todb);

            unset($comp_scale_vals, $scaleid, $svid, $todb, $proficient);
        }
    }

    if ($result && $oldversion < 2011012801) {
        //Rename Block
        $result = $result && execute_sql("DELETE FROM {$CFG->prefix}block WHERE name='totara_tasks'", false);
        $result = $result && execute_sql("UPDATE {$CFG->prefix}block SET name='totara_tasks' WHERE name='totara_reminders'", false);

    }


    // Add status column to course_completions table
    if ($result && $oldversion < 2011013100) {

        /// Define field status to be added to course_completions
        $table = new XMLDBTable('course_completions');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0, 'rpl');

        /// Launch add field status
        $result = $result && add_field($table, $field);

    }


    // Update any existing course_completions data
    if ($result && $oldversion < 2011013101) {

        require_once("{$CFG->libdir}/completion/completion_completion.php");

        // Begin transaction
        begin_sql();

        // Get all records
        $rs = get_recordset_sql("SELECT * FROM {$CFG->prefix}course_completions");

        if ($rs) {
            while ($record = rs_fetch_next_record($rs)) {
                // Update status column
                $status = completion_completion::get_status($record);
                if ($status) {
                    $status = constant('COMPLETION_STATUS_'.strtoupper($status));
                } else {
                    $status = COMPLETION_STATUS_NOTYETSTARTED;
                }

                $record->status = $status;

                if (!update_record('course_completions', $record)) {
                    $result = false;
                    break;
                }
            }
        }

        if ($result) {
            commit_sql();
        } else {
            rollback_sql();
        }
    }

    // Update column name in message20 table
    if ($result && $oldversion < 2011013102) {
        //Rename Block
        $result = $result && execute_sql("DELETE FROM {$CFG->prefix}block WHERE name='totara_alerts'", false);
        $result = $result && execute_sql("UPDATE {$CFG->prefix}block SET name='totara_alerts' WHERE name='totara_notify'", false);

        $table = new XMLDBTable('message20');
        $field = new XMLDBField('notification');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        if(field_exists($table, $field)) {
            $result = $result && rename_field($table, $field, 'alert');
        }
    }

    if ($result && $oldversion < 2011020700) {
        // change fullname to varchar so it can be sorted in MSSQL
        // 31/03/11 Added code to truncate any fields longer than new length
        //          and converted to a loop
        $tablenames = array('org_framework', 'org_depth',
            'org_depth_info_field', 'org', 'pos_framework', 'pos_depth',
            'pos_depth_info_field', 'pos', 'comp_framework', 'comp_depth',
            'comp_depth_info_field', 'comp');

        foreach($tablenames as $tablename) {

            $sql = "UPDATE {$CFG->prefix}{$tablename} SET fullname=" .
                sql_substr() . "(fullname, 1, 1024)";
            $result = $result && execute_sql($sql);

            $table = new XMLDBTable($tablename);
            $field = new XMLDBField('fullname');
            $field->setType(XMLDB_TYPE_CHAR);
            $field->setLength(1024);
            $result = $result && change_field_type($table, $field, true, true);
        }
    }

    if ($result && $oldversion < 2011021500) {
        $tables = array(
            'idp',
            'idp_revision',
            'idp_approval',
            'idp_revision_competency',
            'idp_revision_course',
            'idp_revision_comment',
            'idp_list_item',
            'idp_revision_competencytmpl',
            'idp_competency_eval',
            'idp_tmpl_priority_scale',
            'idp_tmpl_priority_scal_val',
            'idp_tmpl_priority_assign',
            'idp_template',
            'idp_comp_area',
            'idp_comp_area_fw',
            'demo_users',
            'import_users',
        );

        foreach ($tables as $tablename) {
            $table = new XMLDBTable($tablename);
            if (table_exists($table)) {
                $result = $result && drop_table($table);
            }
        }
    }

    if ($result && $oldversion < 2011021501) {
        //Update Block references
        $table = new XMLDBTable('message_processors20');
        if(table_exists($table)) {
            $result = $result && execute_sql("UPDATE {$CFG->prefix}message_processors20 SET name='totara_alert' WHERE name='totara_notification'", false);
            $result = $result && execute_sql("UPDATE {$CFG->prefix}message_processors20 SET name='totara_task' WHERE name='totara_reminder'", false);
        }

        $table = new XMLDBTable('message_providers20');
        if(table_exists($table)){
            $result = $result && execute_sql("UPDATE {$CFG->prefix}message_providers20 SET name='alrt' WHERE name='ntfy'", false);
            $result = $result && execute_sql("UPDATE {$CFG->prefix}message_providers20 SET name='task' WHERE name='rmdr'", false);
        }
    }

    if ($result && $oldversion < 2011030701) {
        $roles = get_records('role');
        // find administrator role (either admin or administrator)

        // Get admin role(s)
        if ($adminroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, get_context_instance(CONTEXT_SYSTEM))) {
            foreach ($adminroles as $adminrole) {
                foreach ($roles as $role) {
                    $assign = get_record('role_allow_assign', 'roleid', $adminrole->id, 'allowassign', $role->id);
                    if (!$assign) {
                        $role_assign = new object();
                        $role_assign->roleid = $adminrole->id;
                        $role_assign->allowassign = $role->id;
                        $result = $result && insert_record('role_allow_assign', $role_assign);
                    }
                }
            }
        }
    }

    // fix for bug introduced in v1.0.3
    if ($result && $oldversion < 2011032801 && !get_config('reportbuilder', 'skiplegacyupgrades')) {
        $table = new XMLDBTable('report_builder_filters');
        $field = new XMLDBField('ispublic');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        if (field_exists($table, $field)) {
            $result = $result && rename_field($table, $field, 'advanced');
        }
    }

    if ($result && $oldversion < 2011032802) {
        $sql = "UPDATE {$CFG->prefix}pos SET parentid=0 WHERE parentid IS NULL";
        $result = $result && execute_sql($sql);

        $table = new XMLDBTable('pos');
        $field = new XMLDBField('parentid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        $result = $result && change_field_type($table, $field);
    }

    // change types of fields that were missed previously by bad upgrade
    // in entry 2011020700 of this file (now fixed)
    // fixes sorting issues in MSSQL
    if ($result && $oldversion < 2011033000) {
        // truncate any text after 1024 chars
        $sql = "UPDATE {$CFG->prefix}pos SET fullname=" . sql_substr() .
            "(fullname, 1, 1024)";
        $result = $result && execute_sql($sql);

        $sql = "UPDATE {$CFG->prefix}comp SET fullname=" . sql_substr() .
            "(fullname, 1, 1024)";
        $result = $result && execute_sql($sql);

        $table = new XMLDBTable('pos');
        $field = new XMLDBField('fullname');
        $field->setType(XMLDB_TYPE_CHAR);
        $field->setLength(1024);
        $result = $result && change_field_type($table, $field, true, true);

        $table = new XMLDBTable('comp');
        $field = new XMLDBField('fullname');
        $field->setType(XMLDB_TYPE_CHAR);
        $field->setLength(1024);
        $result = $result && change_field_type($table, $field, true, true);
    }

    if ($result && $oldversion < 2011033100) {

        // Course completions
        // Check for duplicates
        $cc_sql = "
            SELECT
                MAX(cc.id)
            FROM
                {$CFG->prefix}course_completions cc
            GROUP BY
                cc.userid, cc.course
        ";

        // If any duplicates, keep newest version of course completion
        $result = $result && totara_data_object_duplicate_fix('course_completions', $cc_sql);
        if ($result) {
            // Add unique index
            $table = new XMLDBTable('course_completions');
            $index = new XMLDBIndex('courseuserid');
            $index->setAttributes(XMLDB_INDEX_UNIQUE, array('userid', 'course'));
            $result = $result && (index_exists($table, $index) || add_index($table, $index));
        }


        // Course completion criteria completions
        // Check for duplicates
        $ccc_sql = "
            SELECT
                MAX(ccc.id)
            FROM
                {$CFG->prefix}course_completion_crit_compl ccc
            GROUP BY
                ccc.userid, ccc.course, ccc.criteriaid
        ";

        // If any duplicates, keep newest version of course completion criteria completion
        $result = $result && totara_data_object_duplicate_fix('course_completion_crit_compl', $ccc_sql);
        if ($result) {
            // Add unique index
            $table = new XMLDBTable('course_completion_crit_compl');
            $index = new XMLDBIndex('courseuseridcritid');
            $index->setAttributes(XMLDB_INDEX_UNIQUE, array('userid', 'course', 'criteriaid'));
            $result = $result && (index_exists($table, $index) || add_index($table, $index));
        }

    }

    if ($result && $oldversion < 2011040600) {
        // add proficient field to comp_scale_values table
        $table = new XMLDBTable('comp_scale_values');
        $field = new XMLDBField('proficient');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $result = $result && add_field($table, $field);

        // copy proficient value from comp_scale to new location in comp_scale_values
        $scales = get_records('comp_scale');
        if ($scales) {
            // loop through each scale
            foreach ($scales as $scale) {
                // if proficient not set, leave all scale values as not proficient
                if (empty($scale->proficient)) {
                    continue;
                }
                // find the sort order of the proficient value
                $minprofsort = get_field('comp_scale_values', 'sortorder', 'id', $scale->proficient);
                // set proficient to 1 for all scale values with an equal or lower sortorder
                if ($minprofsort) {
                    $sql = "UPDATE {$CFG->prefix}comp_scale_values SET proficient=1
                        WHERE scaleid={$scale->id} AND sortorder <= $minprofsort";
                    $result = $result && execute_sql($sql);
                }
            }
        }

        // now remove the old field from comp_scale table
        // MSSQL requires index be dropped first
        $table = new XMLDBTable('comp_scale');
        $index = new XMLDBIndex('proficient');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('proficient'));
        if (index_exists($table, $index)) {
            $result = $result && drop_index($table, $index);
        }

        $table = new XMLDBTable('comp_scale');
        $field = new XMLDBField('proficient');
        if (field_exists($table, $field)) {
            $result = $result && drop_field($table, $field);
        }
    }

    // Rename field (if not already) to avoid reserved word
    if ($result && $oldversion < 2011042700) {
        $table = new XMLDBTable('course_completion_criteria');
        $field = new XMLDBField('date');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);

        if (field_exists($table, $field)) {
            $result = $result && rename_field($table, $field, 'completedate');
        }
    }

    if ($result && $oldversion < 2011042900) {

        // Run the old completion cron code one more time before setting up the role_assign()
        // trigger for future enrollments
        require_once($CFG->libdir.'/completion/completion_completion.php');

        completion_mark_users_started();
    }


    if ($result && $oldversion < 2011051706) {

        // Position assignments
        // Where clause to find duplicates
        $pa_sql = "
            SELECT
                MAX(pa.id)
            FROM
                {$CFG->prefix}pos_assignment pa
            GROUP BY
                pa.userid, pa.type
        ";

        // If any duplicates, keep newest version of a duplicate position assignment
        $result = $result && totara_data_object_duplicate_fix('pos_assignment', $pa_sql);
        if ($result) {
            // Add unique index
            $table = new XMLDBTable('pos_assignment');
            $index = new XMLDBIndex('typeuserid');
            $index->setAttributes(XMLDB_INDEX_UNIQUE, array('userid', 'type'));
            $result = $result && (index_exists($table, $index) || add_index($table, $index));
        }

        // Course completion aggregation methods
        // Where clause to find duplicates
        $ccam_sql = "
            SELECT
                MAX(ccam.id)
            FROM
                {$CFG->prefix}course_completion_aggr_methd ccam
            GROUP BY
                ccam.course, ccam.criteriatype
        ";

        // If any duplicates, keep newest version of a duplicate completion aggregation method
        $result = $result && totara_data_object_duplicate_fix('course_completion_aggr_methd', $ccam_sql);
        if ($result) {
            // Add unique index
            $table = new XMLDBTable('course_completion_aggr_methd');
            $index = new XMLDBIndex('coursecriteriatype');
            $index->setAttributes(XMLDB_INDEX_UNIQUE, array('course', 'criteriatype'));
            $result = $result && (index_exists($table, $index) || add_index($table, $index));
        }

        // Competency evidence items evidence
        // Add index if no duplicates
        $ceie_sql = "
            SELECT
                COUNT(id)
            FROM
                {$CFG->prefix}comp_evidence_items_evidence
            WHERE
                id NOT IN
            (
                SELECT
                    MAX(ceie.id)
                FROM
                    {$CFG->prefix}comp_evidence_items_evidence ceie
                GROUP BY
                    ceie.userid, ceie.competencyid, ceie.itemid
            )
        ";

        if ($result && !count_records_sql($ceie_sql)) {
            // Add unique index
            $table = new XMLDBTable('comp_evidence_items_evidence');
            $index = new XMLDBIndex('useridcompetencyiditemid');
            $index->setAttributes(XMLDB_INDEX_UNIQUE, array('userid', 'competencyid', 'itemid'));
            $result = $result && (index_exists($table, $index) || add_index($table, $index));
        }

        // Competency evidence
        // Add index if no duplicates
        $ce_sql = "
            SELECT
                COUNT(id)
            FROM
                {$CFG->prefix}comp_evidence
            WHERE
                id NOT IN
            (
                SELECT
                    MAX(ce.id)
                FROM
                    {$CFG->prefix}comp_evidence ce
                GROUP BY
                    ce.userid, ce.competencyid
            )
        ";

        if ($result && !count_records_sql($ce_sql)) {
            // Add unique index
            $table = new XMLDBTable('comp_evidence');
            $index = new XMLDBIndex('useridcompetencyid');
            $index->setAttributes(XMLDB_INDEX_UNIQUE, array('userid', 'competencyid'));
            $result = $result && (index_exists($table, $index) || add_index($table, $index));
        }
    }

    // Set RPL config options to match the site's configuration before they were configurable
    if ($result && $oldversion < 2011070801) {
        // Create default settings
        $result = $result && set_config('enablecourserpl', 1);

        // For upgrades
        if (isset($CFG->local_postinst_hasrun)) {
            // Default to module RPLs for Face-to-face only (if module installed)
            if ($f2fid = get_field('modules', 'id', 'name', 'facetoface')) {
                // Only set config variable if has not been set already
                if (!isset($CFG->enablemodulerpl)) {
                    $result = $result && set_config('enablemodulerpl', $f2fid);
                }
            }
        // For fresh installs
        } else {
            // Default to RPLs for all modules available
            $modules = get_records('modules', '', '', 'id', 'id');
            if ($modules) {
                $result = $result && set_config('enablemodulerpl', implode(',', array_keys($modules)));
            }
        }
    }

    if ($result && $oldversion < 2011072201) {
        $table = new XMLDBTable('comp_evidence_items_evidence');
        if (table_exists($table)) {
            $index = new XMLDBIndex('useriditemid_ix');
            $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid', 'itemid'));

            $result = $result && add_index($table, $index);
        }

        $table = new XMLDBTable('course_completion_crit_compl');
        if (table_exists($table)) {
            $index = new XMLDBIndex('useidcriteriaid_ix');
            $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid', 'criteriaid'));

            $result = $result && add_index($table, $index);
        }
    }

    if ($result && $oldversion < 2011072401) {
        $table = new XMLDBTable('course_completion_criteria');
        if (table_exists($table)) {
            // Remove index
            $lockindex = new XMLDBIndex('lock');
            if (index_exists($table, $lockindex)) {
                $result = $result && drop_index($table, $lockindex);
            }

            // Remove field
            $lockfield = new XMLDBField('lock');
            if (field_exists($table, $lockfield)) {
                $result = $result && drop_field($table, $lockfield);
            }
        }
    }

/// Totara 1.1 upgrade

    if ($result && $oldversion < 2011091200) {

    /// Define table errorlog to be created
        $table = new XMLDBTable('errorlog');

    /// Adding fields to table errorlog
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('timeoccured', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('version', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('build', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('details', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('hash', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

    /// Adding keys to table errorlog
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('hash', XMLDB_INDEX_NOTUNIQUE, array('hash'));

    /// Launch create table for errorlog
        if (!table_exists($table)) {
            $result = $result && create_table($table);
        }

        require_once($CFG->dirroot.'/local/plan/lib.php');

        // Add linktype field to pos_competencies table
        $table = new XMLDBTable('pos_competencies');
        $field = new XMLDBField('linktype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, PLAN_LINKTYPE_OPTIONAL, 'templateid');
        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }

        // Add linktype field to org_competencies table
        $table = new XMLDBTable('org_competencies');
        $field = new XMLDBField('linktype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, PLAN_LINKTYPE_OPTIONAL, 'templateid');
        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }

        // Define field linktype to be added to question_truefalse
        $table = new XMLDBTable('comp_evidence_items');
        $field = new XMLDBField('linktype');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, PLAN_LINKTYPE_OPTIONAL, 'iteminstance');
        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }


    /// Hierarchy migration
        foreach (array('pos', 'comp', 'org') as $hierarchy) {

            // skip if depth table has already been converted to type
            $oldtable = new XMLDBTable($hierarchy.'_depth');
            $newtable = new XMLDBTable($hierarchy.'_type');
            if (!table_exists($oldtable) && table_exists($newtable)) {
                notify("'{$hierarchy}' tables already upgraded. Skipping");
                continue;
            }

            // UPDATE ITEM TABLE

            // add depth level field to item table
            $table = new XMLDBTable($hierarchy);
            $field = new XMLDBField('depthlevel');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'usermodified');
            if (!field_exists($table, $field)) {
                $result = $result && add_field($table, $field);
            }

            // calculate the depth level (based on the item's depthid) and store in new field
            $originals = get_records_sql("
                SELECT h.id, d.depthlevel
                FROM {$CFG->prefix}{$hierarchy} h
                LEFT JOIN {$CFG->prefix}{$hierarchy}_depth d
                ON d.id = h.depthid");
            if ($originals) {
                foreach ($originals as $original) {
                    // check to see if there is any bad data
                    if (!$original->depthlevel) {
                        print_error('error:couldnotupgradehierarchyduetobaddata', 'hierarchy', "{$hierarchy} item has invalid depthid");
                    }
                    // should only update the depth level if it's empty
                    $updatesql = "
                        UPDATE {$CFG->prefix}{$hierarchy}
                        SET depthlevel = {$original->depthlevel}
                        WHERE id={$original->id} AND depthlevel IS NULL";
                    $result = $result && execute_sql($updatesql);
                }
            }

            // rename depthid column to typeid
            $table = new XMLDBTable($hierarchy);
            $field = new XMLDBField('depthid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
            if (field_exists($table, $field)) {
                $result = $result && rename_field($table, $field, 'typeid');
            }

            // default to zero (which represents no assigned type)
            $table = new XMLDBTable($hierarchy);
            $field = new XMLDBField('typeid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, 0);
            if (field_exists($table, $field)) {
                $result = $result && change_field_type($table, $field);
            }

            // Modify sorting to use threaded sorts

            /// Add sortthread field
            $table = new XMLDBTable($hierarchy);
            $field = new XMLDBField('sortthread');
            $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
            if (!field_exists($table, $field)) {
                $result = $result && add_field($table, $field);
            }

            // re-calculate sorting using new system and save to sortthread table
            if (!$fws = get_records($hierarchy.'_framework')) {
                // no frameworks
                $fws = array();
            }
            if (!$levels = get_records_sql("
                SELECT DISTINCT depthlevel
                FROM {$CFG->prefix}{$hierarchy}
                ORDER BY depthlevel")) {

                // no depth levels
                $levels = array();
            }

            begin_sql();

            foreach ($fws as $framework) {
                // work up through the levels
                foreach ($levels as $level) {
                    // get all the items at this level
                    $rs = get_recordset_select($hierarchy, "frameworkid={$framework->id} AND depthlevel = {$level->depthlevel}", 'sortorder');
                    // group them by parentid
                    if ($grouped_by_parent = totara_group_records($rs, 'parentid')) {
                        foreach ($grouped_by_parent as $parentid => $items) {
                            $parentsortthread = ($parentid != 0) ? get_field($hierarchy, 'sortthread', 'id', $parentid) . '.' : '';
                            $inc = 1;
                            foreach ($items as $item) {
                                $itemsortthread = $parentsortthread . totara_int2vancode($inc);
                                $result = $result && set_field($hierarchy, 'sortthread', $itemsortthread, 'id', $item->id);
                                $inc++;
                            }
                        }
                    }
                }
            }

            if ($result) {
                commit_sql();
            } else {
                rollback_sql();
            }

            // now get rid of sortorder field
            $table = new XMLDBTable($hierarchy);
            $field = new XMLDBField('sortorder');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
            if (field_exists($table, $field)) {
                $result = $result && drop_field($table, $field);
            }

            // UPDATE ITEM_DEPTH TABLE
            $depthtable = $hierarchy . '_depth';

            // remove depthlevel and frameworkid
            $table = new XMLDBTable($depthtable);
            $field = new XMLDBField('depthlevel');
            if (field_exists($table, $field)) {
                $result = $result && drop_field($table, $field);
            }
            $table = new XMLDBTable($depthtable);
            $field = new XMLDBField('frameworkid');
            if (field_exists($table, $field)) {
                $result = $result && drop_field($table, $field);
            }


            //UPDATE ITEM_DEPTH_INFO_CATEGORY TABLE
            $categorytable = $hierarchy . '_depth_info_category';

            // rename depthid column to typeid
            $table = new XMLDBTable($categorytable);
            $field = new XMLDBField('depthid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
            if (field_exists($table, $field)) {
                $result = $result && rename_field($table, $field, 'typeid');
            }


            //UPDATE ITEM_DEPTH_INFO_FIELD TABLE
            $fieldtable = $hierarchy . '_depth_info_field';

            // rename depthid column to typeid
            $table = new XMLDBTable($fieldtable);
            $field = new XMLDBField('depthid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
            if (field_exists($table, $field)) {
                $result = $result && rename_field($table, $field, 'typeid');
            }

            // allow null categoryids
            $table = new XMLDBTable($fieldtable);
            $field = new XMLDBField('categoryid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
            if (field_exists($table, $field)) {
                $result = $result && change_field_type($table, $field);
            }

            // allow null categoryids in course custom fields too
            $table = new XMLDBTable('course_info_field');
            $field = new XMLDBField('categoryid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
            if (field_exists($table, $field)) {
                $result = $result && change_field_type($table, $field);
            }

            // UPDATE ITEM_FRAMEWORK TABLE
            $frameworktable = $hierarchy . '_framework';

            // drop showdepthfullname field
            $table = new XMLDBTable($frameworktable);
            $field = new XMLDBField('showdepthfullname');
            if (field_exists($table, $field)) {
                $result = $result && drop_field($table, $field);
            }

            // drop showitemfullname field
            $table = new XMLDBTable($frameworktable);
            $field = new XMLDBField('showitemfullname');
            if (field_exists($table, $field)) {
                $result = $result && drop_field($table, $field);
            }

            // RENAME *_DEPTH_* TABLES

            $tables_to_rename = array(
                $hierarchy.'_depth' => $hierarchy.'_type',
                $hierarchy.'_depth_info_category' => $hierarchy.'_type_info_category',
                $hierarchy.'_depth_info_field' => $hierarchy.'_type_info_field',
                $hierarchy.'_depth_info_data' => $hierarchy.'_type_info_data',
            );

            foreach ($tables_to_rename as $before => $after) {
                // rename category table
                $table = new XMLDBTable($before);
                if (table_exists($table)) {
                    $result = $result && rename_table($table, $after);
                }

            }

            // Custom field categories have been removed from code but
            // left in DB for now. To remove entirely:
            // - drop the tables:
            //   - comp_type_info_category
            //   - pos_type_info_category
            //   - org_type_info_category
            //   - course_type_info_category
            // - drop the categoryid field in:
            //   - comp_type_info_field
            //   - pos_type_info_field
            //   - org_type_info_field
            //   - course_type_info_field


            // allow null shortnames in hierarchy, hierarchy_type and hierarchy_framework tables
            $table = new XMLDBTable($hierarchy);
            $field = new XMLDBField('shortname');
            $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $result = $result && change_field_type($table, $field);

            $table = new XMLDBTable($frameworktable);
            $field = new XMLDBField('shortname');
            $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $result = $result && change_field_type($table, $field);

            $table = new XMLDBTable($hierarchy.'_type');
            $field = new XMLDBField('shortname');
            $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $result = $result && change_field_type($table, $field);

            /// Define field icon to be added to each type
            $table = new XMLDBTable($hierarchy.'_type');
            $field = new XMLDBField('icon');
            $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
            if (!field_exists($table, $field)) {
                /// Add icon field
                $result = $result && add_field($table, $field);
            }
        }

        // remove unwanted icon field
        $table = new XMLDBTable('course_categories');
        $field = new XMLDBField('icon');
        if (field_exists($table, $field)) {
            $result = $result && drop_field($table, $field);
        }

        // Add "managerid" column to table "pos_assignment"
        $table = new XMLDBTable('pos_assignment');
        $field = new XMLDBField('managerid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED);

        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);
        }

        // Add index to "managerid" column
        $index = new XMLDBIndex('managerid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('managerid'));

        if (!index_exists($table, $index)) {
            $result = $result && add_index($table, $index);
        }

        // Fill column with data
        $psql = "
            SELECT
                pa.id AS id,
                ra.userid AS managerid
            FROM
                {$CFG->prefix}pos_assignment pa
            INNER JOIN
                {$CFG->prefix}role_assignments ra ON pa.reportstoid = ra.id
            WHERE
                pa.reportstoid IS NOT NULL
        ";

        // Only load position assignments with a reportstoid set and update with manager's id
        $pos_assignments = get_recordset_sql($psql);
        while ($pa = rs_fetch_next_record($pos_assignments)) {
            $result = $result && update_record('pos_assignment', $pa);
        }
        rs_close($pos_assignments);

    }

    if ($result && $oldversion < 2011091201) {
        $completion_type = COMPLETION_CRITERIA_TYPE_ACTIVITY;

        $sql =  "SELECT
            ccc.id, ccc.course
            FROM {$CFG->prefix}course_completion_criteria ccc
            LEFT JOIN {$CFG->prefix}course_modules cm
            ON
                ccc.moduleinstance = cm.id
            WHERE
                ccc.criteriatype={$completion_type}
            AND
                cm.id is NULL";

        $courses = array();

        if ($criteria_ids = get_records_sql($sql)) {
            foreach ($criteria_ids as $criteria) {
                // Delete criteria
                $result = $result && delete_records('course_completion_criteria', 'id', $criteria->id);

                // Delete related completion records
                $result = $result && delete_records('course_completion_crit_compl', 'criteriaid', $criteria->id);

                $courses[] = $criteria->course;
            }
        }

        // Reaggregate courses
        if ($courses = array_unique($courses)) {
            $now = time();

            $course_ids = implode(',', $courses);
            $sql = "UPDATE {$CFG->prefix}course_completions
                SET reaggregate={$now}
                WHERE course IN ({$course_ids})";

            $result = $result && execute_sql($sql);
        }
    }

    if ($result && $oldversion < 2011091202) {
        foreach (array('pos', 'comp', 'org') as $hierarchy) {
            /// Define field idnumber to be added to each hierarchy type
            $table = new XMLDBTable($hierarchy.'_type');
            $field = new XMLDBField('idnumber');
            $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null);
            if (!field_exists($table, $field)) {
                /// Add idnumber field
                $result = $result && add_field($table, $field);
            }

            /// Add index for idnumber field
            $index = new XMLDBIndex('idnumber');
            $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('idnumber'));
            if (!index_exists($table, $index)) {
                $result = $result && add_index($table, $index);
            }
        }
    }

    if ($result && $oldversion < 2011091204) {
        require_once($CFG->dirroot . '/hierarchy/prefix/position/lib.php');
        $table = new XMLDBTable('pos_assignment');
        $field = new XMLDBField('managerpath');
        $field->setAttributes(XMLDB_TYPE_CHAR, '1024', null, null, null, null, null);

        // Conditionally add field managerpath
        if (!field_exists($table, $field)) {
            $result = $result && add_field($table, $field);

            // fill primary position assignments with manager path data
            $primary_assignments = get_records('pos_assignment',
                'type', POSITION_TYPE_PRIMARY,
                'userid', 'id,userid,managerid');
            // build a keyed array for faster access
            // unique key ensures only one userid for each primary position assignment
            $manager_relations = array();
            if ($primary_assignments) {
                foreach ($primary_assignments as $assignment) {
                    $manager_relations[$assignment->userid] = $assignment->managerid;
                }

                foreach ($primary_assignments as $assignment) {
                    $path = '/' . implode(totara_get_lineage($manager_relations, $assignment->userid), '/');
                    $todb = new object();
                    $todb->id = $assignment->id;
                    $todb->managerpath = $path;
                    $result = $result && update_record('pos_assignment', $todb);
                }
            }

            if ($result) {
                // remove management plugin table
                $table = new XMLDBTable('manager');
                if (table_exists($table)) {
                    $result = $result && drop_table($table);
                }

                // unset config variables
                set_config('local_management_version', null);
                set_config('local_management_cron', null);
                set_config('local_management_lastcron', null);
            }
        }
    }

    if ($result && $oldversion < 2011091205) {
        // Add missing rpl column to course_completion_crit_compl table (missing from upgrades)
        $table = new XMLDBTable('course_completion_crit_compl');
        $field = new XMLDBField('rpl');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null);

        if (!field_exists($table, $field)) {
            // Add rpl field
            $result = $result && add_field($table, $field);
        } else {
            $sql = "UPDATE {$CFG->prefix}course_completion_crit_compl SET rpl=" .
                sql_substr() . "(rpl, 1, 255)";
            $result = $result && execute_sql($sql);

            $result = $result && change_field_type($table, $field, true, true);
        }
    }

    if ($result && $oldversion < 2012011000) {
        require_once($CFG->dirroot . '/hierarchy/prefix/position/lib.php');
        $table = new XMLDBTable('pos_assignment');
        $field = new XMLDBField('managerpath');
        $field->setAttributes(XMLDB_TYPE_CHAR, '1024', null, null, null, null, null);

        // If managerpath exists then check for inconsistencies and fix if necessary
        if (field_exists($table, $field)) {

            // fill primary position assignments with manager path data
            $primary_assignments = get_records('pos_assignment',
                'type', POSITION_TYPE_PRIMARY,
                'userid', 'id,userid,managerid,managerpath');
            // build a keyed array for faster access
            // unique key ensures only one userid for each primary position assignment
            $manager_relations = array();
            if ($primary_assignments) {
                foreach ($primary_assignments as $assignment) {
                    $manager_relations[$assignment->userid] = $assignment->managerid;
                }

                foreach ($primary_assignments as $assignment) {
                    $path = '/' . implode(totara_get_lineage($manager_relations, $assignment->userid), '/');

                    if ($path != $assignment->managerpath) {
                        $todb = new object();
                        $todb->id = $assignment->id;
                        $todb->managerpath = $path;
                        $result = $result && update_record('pos_assignment', $todb);
                    }
                }
            }
        }
    }

    return $result;
}
