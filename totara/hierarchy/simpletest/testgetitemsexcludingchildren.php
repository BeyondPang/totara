<?php // $Id$
/*
**
 * Unit tests for get_items_excluding_children()
 *
 * @author Simon Coggins <simon.coggins@totaralms.com>
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');
require_once($CFG->dirroot . '/admin/tool/unittest/simpletestlib.php');

class getitemsexcludingchildren_test extends UnitTestCaseUsingDatabase {
    var $org_cols = array(
        'id', 'fullname', 'shortname', 'description', 'idnumber', 'frameworkid',
        'path', 'parentid', 'sortthread', 'depthlevel',
        'visible', 'timecreated', 'timemodified', 'usermodified', 'typeid'
    );

    var $org_data = array(
        array(1, 'Organisation A', 'Org A', 'Org Description A', 'OA', 1,
        '/1', 0, '01', 1, 1, 1234567890, 1234567890, 2, 0),
        array(2, 'Organisation B', 'Org B', 'Org Description B', 'OB', 1,
        '/1/2', 1, '01.01', 2, 1, 1234567890, 1234567890, 2, 0),
        array(3, 'Organisation C', 'Org C', 'Org Description C', 'OC', 1,
        '/1/2/3', 2, '01.01.01', 3, 1, 1234567890, 1234567890, 2, 0),
        array(4, 'Organisation D', 'Org D', 'Org Description D', 'OD', 1,
        '/1/2/4', 2, '01.01.02', 3, 1, 1234567890, 1234567890, 2, 0),
        array(5, 'Organisation E', 'Org E', 'Org Description E', 'OE', 1,
        '/5', 0, '02', 1, 1, 1234567890, 1234567890, 2, 0),
        array(6, 'Organisation F', 'Org F', 'Org Description F', 'OF', 1,
        '/5/6', 5, '02.01', 2, 1, 1234567890, 1234567890, 2, 0),
        array(7, 'Organisation G', 'Org G', 'Org Description G', 'OG', 1,
        '/5/6/7', 6, '02.01.01', 3, 1, 1234567890, 1234567890, 2, 0),
        array(8, 'Organisation H', 'Org H', 'Org Description H', 'OH', 1,
        '/5/6/8', 6, '02.01.02', 3, 1, 1234567890, 1234567890, 2, 0),
        array(9, 'Organisation I', 'Org I', 'Org Description I', 'OI', 1,
        '/5/6/8/9', 8, '02.01.02.01', 4, 1, 1234567890, 1234567890, 2, 0),
        array(10, 'Organisation J', 'Org J', 'Org Description J', 'OJ', 1,
        '/10', 0, '03', 1, 1, 1234567890, 1234567890, 2, 0)
    );

    function setUp() {
        global $CFG;
        parent::setup();

        //set up the test database
        $this->create_test_table('org', 'totara/hierarchy');
        $this->load_test_data('org', $this->org_cols, $this->org_data);
        $this->switch_to_test_db();
    }

    function tearDown() {
        global $db,$CFG;
        $this->drop_test_table('org');
        $this->revert_to_real_db();
        parent::tearDown();
    }

/*
 * Testing hierarchy:
 *
 * A
 * |_B
 * | |_C
 * | |_D
 * E
 * |_F
 * | |_G
 * | |_H
 * |   |_I
 * J
 *
 */
    function test_cases_with_no_children() {
        $org = new organisation();

        // cases where no items are the children of any others
        $testcases = array(
            array(2,5,10),
            array(2),
            array(1,9),
            array(4,8),
        );

        foreach ($testcases as $testcase) {
            // should match exactly without change
            $output = $org->get_items_excluding_children($testcase);
            $this->assertEqual($output, $testcase);
        }
    }

    function test_cases_with_duplicates() {
        $org = new organisation();

        // cases where there are duplicates
        $testcases = array(
            array(2,5,10,5),
            array(2,2),
            array(1,9,1,9),
            array(4,8,4),
        );

        foreach ($testcases as $testcase) {
            // should match the unique elements of the array
            $output = $org->get_items_excluding_children($testcase);
            $this->assertEqual($output, array_unique($testcase));
        }
    }


    function test_cases_with_children() {
        $org = new organisation();

        // cases where no items are the children of any others
        $testcases = array(
            array('before' => array(1,3,5,7,9), 'after' => array(1,5)),
            array('before' => array(1,2,3,4,5,6,7,8,9,10), 'after' => array(1,5,10)),
            array('before' => array(2,4,6,9), 'after' => array(2,6)),
            array('before' => array(8,9), 'after' => array(8)),
        );

        foreach ($testcases as $testcase) {
            // should match the 'after' state
            $output = $org->get_items_excluding_children($testcase['before']);
            $this->assertEqual($output, $testcase['after']);
        }
    }

    function test_cases_with_duplicates_and_children() {
        $org = new organisation();

        // cases where no items are the children of any others
        $testcases = array(
            array('before' => array(1,3,5,1,7,9,1), 'after' => array(1,5)),
            array('before' => array(1,2,3,3,4,5,9,6,7,8,2,9,10), 'after' => array(1,5,10)),
            array('before' => array(2,2,2,2,4,9,6,9), 'after' => array(2,6)),
            array('before' => array(8,9,8), 'after' => array(8)),
        );

        foreach ($testcases as $testcase) {
            // should match the 'after' state
            $output = $org->get_items_excluding_children($testcase['before']);
            $this->assertEqual($output, $testcase['after']);
        }
    }
}
