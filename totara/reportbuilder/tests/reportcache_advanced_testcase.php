<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010-2013 Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 *
 * Base class for testing reports with cache support
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/cron.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/data_generator.php');

class reportcache_advanced_testcase extends advanced_testcase {
    protected static $generator = null;
    /**
     * Enables cache for report
     *
     * @global stdClass $DB
     * @param int $id report id
     */
    protected function enable_caching($id) {
        global $DB, $CFG;
        $CFG->enablereportcaching = 1;
        // schedule cache
        $DB->execute('UPDATE {report_builder} SET cache = 1 WHERE id = ?', array($id));
        reportbuilder_schedule_cache($id, array('initschedule' => 1));
        // generate cache
        reportbuilder_generate_cache($id);
    }

    /**
     *  Get report recordset
     * @param string $shortname Report shortname
     * @param array $data Report parameters
     * @param bool $ensurecache Make assertion that result was taken from cache
     * @param array $form Search form parameters
     */
    protected function get_report_result($shortname, $data, $ensurecache = false, $form = array()) {
        global $DB, $SESSION;

        $SESSION->reportbuilder = array();
        $report = reportbuilder_get_embedded_report($shortname, $data);
        if ($form) {
            $SESSION->reportbuilder[$report->_id] = $form;
        }
        list($sql, $params, $cache) = $report->build_query(false, true);
        if ($ensurecache) {
            $this->assertArrayHasKey('cachetable', $cache);
            $this->assertStringMatchesFormat('{report_builder_cache_%d_%d}', $cache['cachetable']);
        }
        $result = $DB->get_records_sql($sql, $params);
        return $result;
    }

    /**
     * Data provider for testing both report states cached and uncached
     * @return array or array of params
     */
    public function provider_use_cache() {
        return array(array(0), array(1));
    }

    /**
     * Get data generator
     * @static Late static binding of overloaded generator
     * @return reportcache_phpunit_data_generator
     */
    public static function getDataGenerator() {
        if (is_null(static::$generator)) {
            static::$generator = new reportcache_phpunit_data_generator();
        }
        return static::$generator;
    }
}
/**
 * This class intended to generate different mock entities
 *
 */
class reportcache_phpunit_data_generator extends phpunit_data_generator {
    protected static $ind = 0;
    /**
     * Add particular mock params to cohort rules
     *
     * @staticvar int $paramid
     * @param int $ruleid
     * @param array $params Params to add
     * @param array List of values
     */
    public function create_cohort_rule_params($ruleid, $params, $listofvalues) {
        global $DB;
        $data = array($params);
        foreach($listofvalues as $l) {
            $data[] = array('listofvalues' => $l);
        }
        foreach($data as $d) {
            foreach ($d as $name => $value) {
                $todb = new stdClass();
                $todb->id = self::$ind;
                $todb->ruleid = $ruleid;
                $todb->name = $name;
                $todb->value = $value;
                $todb->timecreated = time();
                $todb->timemodified = time();
                $todb->modifierid = 2;
                $DB->insert_record('cohort_rule_params', $todb);
                self::$ind++;
            }
        }
    }

    /**
     * Create mock program
     *
     * @param array $data Override default properties
     * @return stdClass Program record
     */
    public function create_program($data = array()) {
        global $DB;
        self::$ind++;
        $now = time();
        $sortorder = $DB->get_field('prog', 'MAX(sortorder) + 1', array());
        $default = array('fullname' => 'Program ' . self::$ind,
                         'availablefrom' => 0,
                         'availableuntil' => 0,
                         'sortorder' => $sortorder,
                         'timecreated' => $now,
                         'timemodified' => $now,
                         'usermodified' => 2,
                         'category' => 1,
                         'shortname' => '',
                         'idnumber' => '',
                         'available' => 1,
                         'sortorder' => !empty($sortorder) ? $sortorder : 0,
                         'icon' => 1,
                         'exceptionssent' => 0,
                         'visible' => 1,
                         'summary' => '',
                         'endnote' => ''
                        );
        $properties = array_merge($default, $data);

        $todb = (object)$properties;
        $newid = $DB->insert_record('prog', $todb);
        $program = new program($newid);
        return $program;
    }

    /**
     * Create mock user with assigned manager
     *
     * @see phpunit_util::create_user
     * @global stdClass $DB
     * @param  array|stdClass $record
     * @return stdClass
     */
    public function create_user($record = null, array $options = null) {
        global $DB;
        $user = parent::create_user($record, $options);

        if (is_object($record)) {
            $record = (array)$record;
        }
        // assign manager for correct event messaging handler work
        $managerid = isset($record['managerid']) ? $record['managerid'] : 2;
        $manager = array('managerid' => $managerid, 'fullname' => '', 'timecreated' => time(),
            'timemodified' => time(), 'usermodified' => 2, 'userid' => $user->id);
        $DB->insert_record('pos_assignment', (object)$manager);

        return $user;
    }

    /**
     * Add mock program to user
     *
     * @param int $programid Program id
     * @param array $userid User ids array of int
     */
    public function assign_program($programid, $userids) {
        $data = new stdClass();
        $data->id = $programid;
        $data->item = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completiontime = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completionevent = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completioninstance = array(ASSIGNTYPE_INDIVIDUAL => array());

        foreach ($userids as $userid) {
            $data->item[ASSIGNTYPE_INDIVIDUAL][$userid] = 1;
            $data->completiontime[ASSIGNTYPE_INDIVIDUAL][$userid] = -1;
            $data->completionevent[ASSIGNTYPE_INDIVIDUAL][$userid] = 0;
            $data->completioninstance[ASSIGNTYPE_INDIVIDUAL][$userid] = 0;
        }

        $category = new individuals_category();
        $category->update_assignments($data);

        $program = new program($programid);
        $assignments = $program->get_assignments();
        $assignments->init_assignments($programid);

        $program->update_learner_assignments();
    }

   /**
     * Create mock program
     *
     * @param int $userid User id
     * @param array|stdClass $record Ovveride default properties
     * @return stdClass Program record
     */
    public function create_plan($userid, $record = array()) {
        global $DB;
        if (is_object($record)) {
            $record = (array)$record;
        }
        self::$ind++;

        $default = array(
            'templateid' => 0,
            'userid' => $userid,
            'name' => 'Learning plan '. self::$ind,
            'description' => '',
            'startdate' => null,
            'enddate' => time() + 23328000,
            'timecompleted' => null,
            'status' => DP_PLAN_STATUS_COMPLETE
        );
        $properties = array_merge($default, $record);

        $todb = (object)$properties;
        $newid = $DB->insert_record('dp_plan', $todb);

        $plan = new development_plan($newid);
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_CREATE);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        return $plan;
    }
}