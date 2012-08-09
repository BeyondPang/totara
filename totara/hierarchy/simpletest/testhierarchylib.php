<?php // $Id$
/*
**
 * Unit tests for hierarchy/lib.php
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot . '/admin/tool/unittest/simpletestlib.php');


class hierarchylib_test extends UnitTestCaseUsingDatabase {
    // test data for database
    var $framework_data = array(
        array('id', 'fullname', 'shortname', 'idnumber','description','sortorder','visible',
            'hidecustomfields','timecreated','timemodified','usermodified'),
        array(1, 'Framework 1', 'FW1', 'ID1','Description 1', 1, 1, 1, 1265963591, 1265963591, 2),
        array(2, 'Framework 2', 'FW2', 'ID2','Description 2', 2, 1, 1, 1265963591, 1265963591, 2),
    );

    var $type_data = array(
        array('id', 'fullname', 'shortname', 'description', 'timecreated', 'timemodified',
            'usermodified'),
        array(1, 'type 1', 'type 1', 'Description 1', 1265963591, 1265963591, 2),
        array(2, 'type 2', 'type 2', 'Description 2', 1265963591, 1265963591, 2),
        array(3, 'type 3', 'type 3', 'Description 3', 1265963591, 1265963591, 2),
    );

    var $competency_data = array(
        array('id', 'fullname', 'shortname', 'description', 'idnumber', 'frameworkid', 'path', 'parentid',
            'sortthread', 'visible', 'aggregationmethod', 'proficiencyexpected', 'evidencecount', 'timecreated',
            'timemodified', 'usermodified', 'depthlevel', 'typeid'),
        array(1, 'Competency 1', 'Comp 1', 'Competency Description 1', 'C1', 1, '/1', 0, '01', 1, 1, 1, 0,
            1265963591, 1265963591, 2, 1, 1),
        array(2, 'Competency 2', 'Comp 2', 'Competency Description 2', 'C2', 1, '/1/2', 1, '01.01', 1, 1, 1, 0,
            1265963591, 1265963591, 2, 2, 2),
        array(3, 'F2 Competency 1', 'F2 Comp 1', 'F2 Competency Description 1', 'F2 C1', 2, '/3', 0, '01', 1, 1, 1, 0,
            1265963591, 1265963591, 2, 2, 2),
        array(4, 'Competency 3', 'Comp 3', 'Competency Description 3', 'C3', 1, '/1/4', 1, '01.02', 1, 1, 1, 0,
            1265963591, 1265963591, 2, 2, 2),
        array(5, 'Competency 4', 'Comp 4', 'Competency Description 4', 'C4', 1, '/5', 0, '02', 1, 1, 1, 0,
            1265963591, 1265963591, 2, 1, 1)
    );

    var $type_field_data = array(
        array('id', 'fullname', 'shortname', 'typeid', 'datatype', 'description', 'sortorder', 'hidden',
            'locked', 'required', 'forceunique', 'defaultdata', 'param1', 'param2', 'param3', 'param4', 'param5'),
        array(1, 'Custom Field 1', 'CF1', 2, 'checkbox', 'Custom Field Description 1', 1, 0, 0, 0, 0, 0, null, null,
            null, null, null),
    );

    var $type_data_data = array(
        array('id', 'data', 'fieldid', 'competencyid'),
        array(1, 1, 1, 2),
    );

    var $evidence_data = array(
        array('id', 'userid', 'competencyid', 'timecreated', 'timemodified', 'reaggregate', 'manual', 'iteminstance', 'usermodified', 'itemid'),
        array(1, 1, 1, 1265963591, 1265963591, 1, 1, 1, 2, 1)
    );

    var $template_data = array(
        array('id', 'frameworkid', 'fullname', 'visible', 'competencycount', 'timecreated', 'timemodified', 'usermodified'),
        array(1, 1, 'framework 1', 1, 1, 1265963591, 1265963591, 2)
    );

    var $org_pos_data = array(
        array('id', 'positionid', 'organisationid', 'timecreated', 'timemodified', 'usermodified'),
        array(1,1,1,1265963591,1265963591,2)
    );

    var $relations_data = array(
        array('id', 'id1', 'id2'),
        array(1,1,2)
    );

    var $comp_template_assignment_data = array(
        array('id', 'templateid', 'type', 'instanceid', 'timecreated', 'usermodified'),
        array(1, 1, 1, 1, 1265963591, 2),
    );

    var $comp_scale_assignments_data = array(
        array('id', 'scaleid', 'frameworkid', 'timemodified', 'usermodified'),
        array(1,1,1,1,1),
    );

    var $dp_plan_competency_assign_data = array(
        array('id', 'planid', 'competencyid'),
        array(1, 1, 5),
    );
    var $dp_plan_course_assign_data = array(
        array('id', 'planid', 'courseid'),
        array(2, 1, 3),
    );
    var $events_handlers_data = array(
        array('id', 'eventname', 'handlermodule', 'handlerfile', 'handlerfunction', 'schedule', 'status'),
        array(1, 'fakeevent', '', '', '', '', ''),
    );

    function load_test_data_table($tablename, $tablelocation, array $dataarray){
        $this->create_test_table($tablename, $tablelocation);
        $this->load_test_data($tablename, $dataarray[0], array_slice($dataarray, 1));
    }

    function setUp() {
        global $CFG;
        parent::setup();
        //set up the test database
        $this->load_test_data_table('comp_framework', 'totara/hierarchy', $this->framework_data);
        $this->load_test_data_table('comp_type', 'totara/hierarchy', $this->type_data);
        $this->load_test_data_table('comp', 'totara/hierarchy', $this->competency_data);
        $this->load_test_data_table('comp_type_info_field', 'totara/hierarchy', $this->type_field_data);
        $this->load_test_data_table('comp_type_info_data', 'totara/hierarchy', $this->type_data_data);
        $this->load_test_data_table('comp_evidence', 'totara/hierarchy', $this->evidence_data);
        $this->load_test_data_table('comp_evidence_items', 'totara/hierarchy', $this->evidence_data);
        $this->load_test_data_table('comp_evidence_items_evidence', 'totara/hierarchy', $this->evidence_data);
        $this->load_test_data_table('comp_template', 'totara/hierarchy', $this->template_data);
        $this->load_test_data_table('comp_template_assignment', 'totara/hierarchy', $this->comp_template_assignment_data);
        $this->load_test_data_table('pos_competencies', 'totara/hierarchy', $this->org_pos_data);
        $this->load_test_data_table('org_competencies', 'totara/hierarchy', $this->org_pos_data);
        $this->load_test_data_table('comp_relations', 'totara/hierarchy', $this->relations_data);
        $this->load_test_data_table('comp_scale_assignments', 'totara/hierarchy', $this->comp_scale_assignments_data);
        $this->load_test_data_table('dp_plan_competency_assign', 'totara/plan', $this->dp_plan_competency_assign_data);
        $this->load_test_data_table('dp_plan_course_assign', 'totara/plan', $this->dp_plan_course_assign_data);
        $this->load_test_data_table('events_handlers', 'lib', $this->events_handlers_data);
        $this->switch_to_test_db();

        // create the competency object
        $this->competency = new competency();
        $this->competency->frameworkid = 1;
        // create 2nd competency object with no frameworkid specified
        $this->nofwid = new competency();

        // create some sample objects
        // framework
        $this->fw1 = new stdClass();
        $this->fw1->fullname = 'Framework 1';
        $this->fw1->shortname = 'FW1';
        $this->fw1->idnumber = 'ID1';
        $this->fw1->description = 'Description 1';
        $this->fw1->sortorder = '1';
        $this->fw1->visible = '1';
        $this->fw1->hidecustomfields = '1';
        $this->fw1->timecreated = '1265963591';
        $this->fw1->timemodified = '1265963591';
        $this->fw1->usermodified = '2';
        $this->fw1->id = '1';
        // hierarchy type
        $this->type1 = new stdClass();
        $this->type1->id = 1;
        $this->type1->fullname = 'type 1';
        $this->type1->shortname = 'type 1';
        $this->type1->description = 'Description 1';
        $this->type1->timecreated = '1265963591';
        $this->type1->timemodified = '1265963591';
        $this->type1->usermodified = '2';
        $this->type1->icon = '';
        $this->type1->idnumber = null;
        // competency
        $this->c1 = new stdClass();
        $this->c1->id = '1';
        $this->c1->fullname = 'Competency 1';
        $this->c1->shortname = 'Comp 1';
        $this->c1->description = 'Competency Description 1';
        $this->c1->idnumber = 'C1';
        $this->c1->frameworkid = '1';
        $this->c1->path = '/1';
        $this->c1->parentid = '0';
        $this->c1->sortthread = '01';
        $this->c1->visible = '1';
        $this->c1->aggregationmethod = '1';
        $this->c1->proficiencyexpected = '1';
        $this->c1->evidencecount = '0';
        $this->c1->timecreated = '1265963591';
        $this->c1->timemodified = '1265963591';
        $this->c1->usermodified = '2';
        $this->c1->depthlevel = '1';
        $this->c1->typeid = '1';
        // another competency
        $this->c2 = new stdClass();
        $this->c2->id = '1';
        $this->c2->fullname = 'Competency 2';
        $this->c2->shortname = 'Comp 2';
        $this->c2->description = 'Competency Description 2';
        $this->c2->idnumber = 'C2';
        $this->c2->frameworkid = '1';
        $this->c2->path = '/1/2';
        $this->c2->parentid = '1';
        $this->c2->sortthread = '01.01';
        $this->c2->visible = '1';
        $this->c2->aggregationmethod = '1';
        $this->c2->evidencecount = '0';
        $this->c2->proficiencyexpected = '1';
        $this->c2->timecreated = '1265963591';
        $this->c2->timemodified = '1265963591';
        $this->c2->usermodified = '2';
        $this->c2->depthlevel = '2';
        $this->c2->typeid = '2';

        //Expected custom field return data for get_custom_fields
        $this->cf1 = new stdClass();
        $this->cf1->id = 1;
        $this->cf1->fullname = 'Custom Field 1';
        $this->cf1->shortname = 'CF1';
        $this->cf1->typeid = 2;
        $this->cf1->datatype = 'checkbox';
        $this->cf1->description = 'Custom Field Description 1';
        $this->cf1->sortorder = 1;
        $this->cf1->hidden = 0;
        $this->cf1->locked = 0;
        $this->cf1->required = 0;
        $this->cf1->forceunique = 0;
        $this->cf1->defaultdata = 0;
        $this->cf1->param1 = null;
        $this->cf1->param2 = null;
        $this->cf1->param3 = null;
        $this->cf1->param4 = null;
        $this->cf1->param5 = null;
        $this->cf1->data = 1;
        $this->cf1->fieldid = 1;
        $this->cf1->competencyid = 2;
        $this->cf1->categoryid = null;
    }

    function tearDown() {
        global $db,$CFG;
        $this->drop_test_tables(array('comp_relations', 'pos_competencies', 'org_competencies', 'comp_template',
            'comp_template_assignment', 'comp_evidence_items_evidence', 'comp_evidence_items', 'comp_evidence',
            'comp_type_info_data', 'comp_type_info_field', 'comp', 'comp_type', 'comp_framework', 'comp_scale_assignments',
            'dp_plan_competency_assign', 'dp_plan_course_assign', 'events_handlers'
        ));
        $this->revert_to_real_db();
        parent::tearDown();
   }

    function test_hierarchy_get_framework() {
        $competency = $this->competency;
        $fw1 = $this->fw1;

        // specifying id should get that framework
        $this->assertEqual($competency->get_framework(2)->fullname, 'Framework 2');
        // not specifying id should get first framework (by sort order)
        $this->assertEqual($competency->get_framework()->fullname,$fw1->fullname);
        // the framework returned should contain all the necessary fields
        $this->assertEqual($competency->get_framework(1), $fw1);
        // clear all frameworks
        $this->testdb->delete_records('comp_framework');
        // if no frameworks exist should return false
        $this->assertFalse($competency->get_framework(0, false, true));
    }

    function test_hierarchy_get_type_by_id() {
        $competency = $this->competency;
        $type1 = $this->type1;
        // the type returned should contain all the necessary fields
        $this->assertEqual($competency->get_type_by_id(1), $type1);
        // the type with the correct id should be returned
        $this->assertEqual($competency->get_type_by_id(2)->fullname, 'type 2');
        // false should be returned if the type doesn't exist
        $this->assertFalse($competency->get_type_by_id(999));
    }

    function test_hierarchy_get_frameworks() {
        $competency = $this->competency;
        $fw1 = $this->fw1;
        // should return an array of frameworks
        $this->assertTrue(is_array($competency->get_frameworks()));
        // the array should include all frameworks
        $this->assertEqual(count($competency->get_frameworks()), 2);
        // each array element should contain a framework
        $this->assertEqual(current($competency->get_frameworks()), $fw1);
        // clear out the framework
        $this->testdb->delete_records('comp_framework');
        // if no frameworks exist should return false
        $this->assertFalse($competency->get_frameworks());
    }

    function test_hierarchy_get_types() {
        $competency = $this->competency;
        $type1 = $this->type1;
        // should return an array of types
        $this->assertTrue(is_array($competency->get_types()));
        // the array should include all types (in this framework)
        $this->assertEqual(count($competency->get_types()), 3);
        // each array element should contain a type
        $this->assertEqual(current($competency->get_types()), $type1);
        // clear out the types
        $this->testdb->delete_records('comp_type');
        // if no types exist should return false
        $this->assertFalse($competency->get_types());
    }

    function test_hierarchy_get_custom_fields() {
        $competency = $this->competency;
        $customfields = $competency->get_custom_fields(2);

        //Returned value is an array
        $this->assertTrue(is_array($customfields));

        //Returned array is not empty
        $this->assertFalse(empty($customfields));

        //Returned array contains one item
        $this->assertEqual(count($customfields),1);

        //Returned array is identical to expected data
        $this->assertEqual($customfields, array($this->cf1->id => $this->cf1));

        //Empty array is returned for a non-existent item id
        $this->assertEqual($competency->get_custom_fields(9000), array());
    }

    function test_hierarchy_get_item() {
        $competency = $this->competency;
        $c1 = $this->c1;
        // the item returned should contain all the necessary fields
        $this->assertEqual($competency->get_item(1), $c1);
        // the item should match the id requested
        $this->assertEqual($competency->get_item(2)->fullname, 'Competency 2');
        // should return false if the item doesn't exist
        $this->assertFalse($competency->get_item(999));
    }

    function test_hierarchy_get_items() {
        $competency = $this->competency;
        $c1 = $this->c1;
        // should return an array of items
        $this->assertTrue(is_array($competency->get_items()));
        // the array should include all items
        $this->assertEqual(count($competency->get_items()), 4);
        // each array element should contain an item object
        $this->assertEqual(current($competency->get_items()), $c1);
        // clear out the items
        $this->testdb->delete_records('comp');
        // if no items exist should return false
        $this->assertFalse($competency->get_items());
    }

    function test_hierarchy_get_items_by_parent() {
        $competency = $this->competency;
        $c1 = $this->c1;
        // should return an array of items belonging to specified parent
        $this->assertTrue(is_array($competency->get_items_by_parent(1)));
        // should return one element per item
        $this->assertEqual(count($competency->get_items_by_parent(1)), 2);
        // each array element should contain an item
        $this->assertEqual(current($competency->get_items_by_parent(1))->fullname, 'Competency 2');
        // if no parent specified should return root level items
        $this->assertEqual(current($competency->get_items_by_parent()), $c1);
        // clear out the items
        $this->testdb->delete_records('comp');
        // if no items exist should return false for root items and parents
        $this->assertFalse($competency->get_items_by_parent());
        $this->assertFalse($competency->get_items_by_parent(1));
    }

    function test_hierarchy_get_all_root_items() {
        $competency = $this->competency;
        $nofwid = $this->nofwid;
        $c1 = $this->c1;
        // should return root items for framework where id specified
        $this->assertEqual(current($competency->get_all_root_items()), $c1);
        // should return all root items (cross framework) if no fwid given
        $this->assertEqual(count($nofwid->get_all_root_items()), 3);
        // should return all root items, even if fwid given, if $all set to true
        $this->assertEqual(count($competency->get_all_root_items(true)), 3);
        // clear out the items
        $this->testdb->delete_records('comp');
        // if no items exist should return false
        $this->assertFalse($competency->get_all_root_items());
        $this->assertFalse($nofwid->get_all_root_items());
    }

    function test_hierarchy_get_item_descendants() {
        $competency = $this->competency;
        $c1 = $this->c1;
        $nofwid = $this->nofwid;

        // create an object of the expected format
        $obj = new StdClass();
        $obj->fullname = $c1->fullname;
        $obj->parentid = $c1->parentid;
        $obj->path = $c1->path;
        $obj->sortthread = $c1->sortthread;
        $obj->id = $c1->id;

        // should return an array of items
        $this->assertTrue(is_array($competency->get_item_descendants(1)));
        // array elements should match an expected format
        $this->assertEqual(current($competency->get_item_descendants(1)), $obj);
        // should return the item with the specified ID and all its descendants
        $this->assertEqual(count($competency->get_item_descendants(1)), 3);
        // should still return itself if an item has no descendants
        $this->assertEqual(count($competency->get_item_descendants(2)), 1);
        // should work the same for different frameworks
        $this->assertEqual(count($nofwid->get_item_descendants(3)), 1);
    }

    function test_hierarchy_get_hierarchy_item_adjacent_peer() {
        $competency = $this->competency;
        $c1 = $this->c1;
        $c2 = $this->c2;

        // if an adjacent peer exists, should return its id
        $this->assertEqual($competency->get_hierarchy_item_adjacent_peer($c2, HIERARCHY_ITEM_BELOW), 4);
        // should return false if no adjacent peer exists in the direction specified
        $this->assertFalse($competency->get_hierarchy_item_adjacent_peer($c2, HIERARCHY_ITEM_ABOVE));
        $this->assertFalse($competency->get_hierarchy_item_adjacent_peer($c1, HIERARCHY_ITEM_ABOVE));
        // should return false if item is not valid
        $this->assertFalse($competency->get_hierarchy_item_adjacent_peer(null));
    }

    function test_hierarchy_make_hierarchy_list() {
        $competency = $this->competency;
        $c1 = $this->c1;

        // standard list with default options
        $competency->make_hierarchy_list($list);
        // list with other options
        $competency->make_hierarchy_list($list2, null, true, true);

        // value should be fullname by default
        $this->assertEqual($list[1], $c1->fullname);
        // value should be shortname if required
        $this->assertEqual($list2[1], $c1->shortname);
        // should include all children unless specified
        $this->assertFalse(array_search('Comp 1 (and all children)', $list));
        // should include all children row if required
        $this->assertEqual(array_search('Comp 1 (and all children)', $list2),'1,2,4');

        // clear out the items
        $this->testdb->delete_records('comp');
        // if no items exist should return false
        $competency->make_hierarchy_list($list3);
        // should return empty list if no items found
        $this->assertEqual($list3, array());
    }

    function test_hierarchy_get_item_lineage() {
        $competency = $this->competency;
        $c1 = $this->c1;
        $nofwid = $this->nofwid;

        // expected format of result
        $obj = new stdClass();
        $obj->fullname = $c1->fullname;
        $obj->parentid = $c1->parentid;
        $obj->depthlevel = $c1->depthlevel;
        $obj->id = (int) $c1->id;

        // should return an array of items
        $this->assertTrue(is_array($competency->get_item_lineage(2)));
        // array elements should match an expected format
        $this->assertEqual(current($competency->get_item_lineage(2)), $obj);
        // should return the item with the specified ID and all its parents
        $this->assertEqual(count($competency->get_item_lineage(2)), 2);
        // should still return itself if an item has no parents
        $this->assertEqual(count($competency->get_item_lineage(1)), 1);
        $this->assertEqual(current($competency->get_item_lineage(1))->fullname, 'Competency 1');
        // should work the same for different frameworks
        $this->assertEqual(count($nofwid->get_item_lineage(3)), 1);
        // NOTE function ignores fwid of current hierarchy object
        // not sure that this is correct behaviour
        $this->assertEqual(current($competency->get_item_lineage(3))->fullname, 'F2 Competency 1');
    }

    // skipped tests for the following display functions:
    // get_editing_button()
    // display_framework_selector()
    // display_add_item_button()
    // display_add_type_button()

    function test_hierarchy_hide_item() {
        $competency = $this->competency;
        $competency->hide_item(1);
        $visible = $this->testdb->get_field('comp', 'visible', array('id' => 1));
        // item should not be visible
        $this->assertEqual($visible, 0);
        // also test show item
        $competency->show_item(1);
        $visible = $this->testdb->get_field('comp', 'visible', array('id' => 1));
        // item should be visible again
        $this->assertEqual($visible, 1);
    }

    function test_hierarchy_hide_framework() {
        $competency = $this->competency;
        $competency->hide_framework(1);
        $visible =  $this->testdb->get_field('comp_framework', 'visible', array('id' => 1));
        // framework should not be visible
        $this->assertEqual($visible, 0);
        // also test show framework
        $competency->show_framework(1);
        $visible =  $this->testdb->get_field('comp_framework', 'visible', array('id' => 1));
        // framework should be visible again
        $this->assertEqual($visible, 1);
    }

    function test_hierarchy_framework_sortorder_offset() {
        $competency = $this->competency;
        $this->assertEqual($competency->get_framework_sortorder_offset(), 1002);
    }

    function test_hierarchy_move_framework() {
        $competency = $this->competency;
        $f1_before =  $this->testdb->get_field('comp_framework', 'sortorder', array('id' => 1));
        $f2_before =  $this->testdb->get_field('comp_framework', 'sortorder', array('id' => 2));
        // a successful move should return true
        $this->assertTrue($competency->move_framework(2, true));
        $f1_after =  $this->testdb->get_field('comp_framework', 'sortorder', array('id' => 1));
        $f2_after =  $this->testdb->get_field('comp_framework', 'sortorder', array('id' => 2));
        // frameworks should have swapped sort orders
        $this->assertEqual($f1_before, $f2_after);
        $this->assertEqual($f2_before, $f1_after);
        // a failed move should return false
        $this->assertFalse($competency->move_framework(2, true));
    }

    function test_hierarchy_delete_hierarchy_item() {
        $competency = $this->competency;
        // function should return true
        $this->assertTrue($competency->delete_hierarchy_item(1, false));
        // the item should have be deleted
        $this->assertFalse($competency->get_item(1));
        // the item's children should also have been deleted
        $this->assertFalse($competency->get_items_by_parent(1));
        // custom field data for items and children should also be deleted
        $this->assertFalse($this->testdb->get_records('comp_type_info_data', array('competencyid' => 2)));
        // non descendants in same framework should not be deleted
        $this->assertEqual(count($competency->get_items()), 1);
    }

    function test_hierarchy_delete_framework() {
        $competency = $this->competency;
        // function should return null
        $this->assertTrue($competency->delete_framework(false));
        // items should have been deleted
        $this->assertFalse($competency->get_items());
        // types should still all exist because they are framework independant
        $this->assertEqual(count($competency->get_types()), 3);
        // the framework should have been deleted
        $this->assertFalse($this->testdb->get_records('comp_framework', array('id' => 1)));
    }

    function test_hierarchy_delete_type() {
        $competency = $this->competency;

        // delete all items to make deleting types possible
        $this->testdb->delete_records('comp');

        $before = count($competency->get_types());
        // should return true if type is deleted
        $this->assertTrue($competency->delete_type(2));
        $after = count($competency->get_types());
        // should have deleted the type
        $this->assertNotEqual($before, $after);
    }

    function test_hierarchy_delete_type_metadata() {
        $competency = $this->competency;

        // function should return null
        $this->assertTrue($competency->delete_type_metadata(2));
        // should have deleted all fields for the type
        $this->assertFalse($this->testdb->get_records('comp_type_info_field', array('typeid' => 2)));

    }

    function test_hierarchy_get_item_data() {
        $competency = $this->competency;
        $c1 = $this->c1;
        // should return an array of info
        $this->assertTrue(is_array($competency->get_item_data($c1)));
        // if no params requested, should return default ones (includes aggregation method which
        // is specific to competencies)
        $this->assertEqual(count($competency->get_item_data($c1)), 6);
        // should return the correct number of fields requested
        $this->assertEqual(count($competency->get_item_data($c1, array('sortthread', 'description'))), 4);
        // should return the correct information based on fields requested
        $result = current($competency->get_item_data($c1, array('description')));
        $this->assertEqual($result['title'], 'Description');
        $this->assertEqual($result['value'], 'Competency Description 1');
    }

    function test_hierarchy_get_max_depth() {
        $competency = $this->competency;
        $nofwid = $this->nofwid;
        $nofwid->frameworkid = 999;
        // should return the correct maximum depth level if there are depth levels
        $this->assertEqual($competency->get_max_depth(), 2);
        // should return null for framework with no depth levels
        $this->assertEqual($nofwid->get_max_depth(), null);
    }

    function test_hierarchy_get_all_parents() {
        $competency = $this->competency;
        $nofwid = $this->nofwid;
        // should return an array containing all items that have children
        // array should contain an item that has children
        $this->assertTrue(array_key_exists(1, $competency->get_all_parents()));
        // array should not contain an item if it does not have children
        $this->assertFalse(array_key_exists(2, $competency->get_all_parents()));
        // should work even if frameworkid not set
        $this->assertFalse(array_key_exists(3, $nofwid->get_all_parents()));

        // clear out all items
        $this->testdb->delete_records('comp');
        // should return an empty array if no parents found
        $this->assertEqual($competency->get_all_parents(), array());
    }

    function test_get_short_prefix(){
        $shortprefix = hierarchy::get_short_prefix('competency');
        $this->assertEqual('comp', $shortprefix);
    }


}
