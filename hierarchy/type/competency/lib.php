<?php // $Id$

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * competency/lib.php
 *
 * Library to construct competency hierarchies
 * @copyright Catalyst IT Limited
 * @author Jonathan Newman
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 */
require_once($CFG->dirroot.'/hierarchy/lib.php');
require_once($CFG->dirroot.'/hierarchy/type/competency/evidenceitem/type/abstract.php');

/**
 * Competency aggregation methods
 *
 * These are mapped to lang strings in the competency lang file
 * with the key as a suffix e.g. for ALL, 'aggregationmethod1'
 */
global $COMP_AGGREGATION;
$COMP_AGGREGATION = array(
    'ALL'       => 1,
    'ANY'       => 2,
/*
    'UNIT'      => 3,
    'FRACTION'  => 4,
    'SUM'       => 5,
    'AVERAGE'   => 6,
*/
);

/**
 * Oject that holds methods and attributes for competency operations.
 * @abstract
 */
class competency extends hierarchy {

    /**
     * The base table prefix for the class
     */
    const PREFIX = 'competency';
    const SHORT_PREFIX = 'comp';
    var $prefix = self::PREFIX;
    var $shortprefix = self::SHORT_PREFIX;
    var $extrafields = array('evidencecount');

    /**
     * Get template
     * @param int Template id
     * @return object|false
     */
    function get_template($id) {
        return get_record($this->shortprefix.'_template', 'id', $id);
    }

    /**
     * Gets templates.
     *
     * @global object $CFG
     * @return array
     */
    function get_templates() {
        global $CFG;
        return get_records($this->shortprefix.'_template', 'frameworkid', $this->frameworkid, 'fullname');
    }

    /**
     * Hide the competency template
     * @var int - the template id to hide
     * @return void
     */
    function hide_template($id) {
        $template = $this->get_template($id);
        if ($template) {
            $visible = 0;
            if (!set_field($this->shortprefix.'_template', 'visible', $visible, 'id', $template->id)) {
                notify('Could not update that '.$this->prefix.' template!');
            }
        }
    }

    /**
     * Show the competency template
     * @var int - the template id to show
     * @return void
     */
    function show_template($id) {
        $template = $this->get_template($id);
        if ($template) {
            $visible = 1;
            if (!set_field($this->shortprefix.'_template', 'visible', $visible, 'id', $template->id)) {
                notify('Could not update that '.$this->prefix.' template!');
            }
        }
    }

    /**
     * Delete competency framework and updated associated scales
     * @access  public
     * @return  void
     */
    function delete_framework() {

        // Start transaction
        begin_sql();

        // Run parent method
        parent::delete_framework();

        // Delete references to scales
        if (count_records($this->shortprefix.'_scale_assignments', 'frameworkid', $this->frameworkid)) {
            if (!delete_records($this->shortprefix.'_scale_assignments', 'frameworkid', $this->frameworkid)) {
                rollback_sql();
                error('Could not delete scale assignments');
            }
        }

        // End transaction
        commit_sql();
    }

    /**
     * Delete a competency and everything to do with it.
     *
     * @param int $id
     * @param boolean $usetransaction
     * @return boolean
     */
    function delete_framework_item($id, $usetransaction = true) {
        global $CFG;
        global $USER;

        if ( $usetransaction ){
            begin_sql();
        }

        if ( parent::delete_framework_item($id, false) ){
            $result = true;
            $result = $result && delete_records($this->shortprefix.'_evidence','competencyid',$id);
            $result = $result && delete_records($this->shortprefix.'_evidence_items','competencyid',$id);
            $result = $result && delete_records($this->shortprefix.'_evidence_items_evidence','competencyid',$id);

            // Update competencycount of templates this competency belongs to
            $sql = <<<SQL
                select t.id as id, t.competencycount as competencycount
                from
                    {$CFG->prefix}{$this->shortprefix}_template_assignment ta,
                    {$CFG->prefix}{$this->shortprefix}_template t
                where 
                    ta.instanceid = {$id}
                    and ta.templateid = t.id
SQL;
            $templates = get_records_sql($sql);
            if ( is_array($templates) ) foreach ( $templates as $origtemplate ){
                $newtemplate = new stdClass();
                $newtemplate->id = $origtemplate->id;
                $newtemplate->competencycount = ($origtemplate->competencycount - 1);
                $newtemplate->timemodified = time();
                $newtemplate->usermodified = $USER->id;
                $result = $result && update_record($this->shortprefix.'_template', $newtemplate);
            }
            $result = $result && delete_records($this->shortprefix.'_template_assignment','instanceid',$id);
            $result = $result && delete_records(hierarchy::get_short_prefix('position').'_competencies','competencyid',$id);
            $result = $result && delete_records($this->shortprefix.'_relations','id1',$id);
            $result = $result && delete_records($this->shortprefix.'_relations','id2',$id);

            //Delete Learning plan items of this competency
            $result = $result && delete_records('dp_plan_competency_assign', 'competencyid', $id);

            if ( $result ){
                if ( $usetransaction ){
                    commit_sql();
                }
                return true;
            } else {
                if ( $usetransaction ){
                    rollback_sql();
                }
                return false;
            }
        } else {
            if ($usetransaction){
                rollback_sql();
            }
            return false;
        }
    }

    /**
     * Delete template and associated data
     * @var int - the template id to delete
     * @return  void
     */
    function delete_template($id) {
        delete_records($this->shortprefix.'_template_assignment','templateid',$id);
        delete_records(hierarchy::get_short_prefix('position').'_competencies','templateid',$id);

        // Delete this item
        delete_records($this->shortprefix.'_template', 'id', $id);
    }

    /**
     * Get competencies assigned to a template
     * @param int $id Template id
     * @return array|false
     */
    function get_assigned_to_template($id) {
        global $CFG;

        return get_records_sql(
            "
            SELECT
                c.id AS id,
                d.fullname AS depth,
                c.fullname AS competency,
                c.fullname AS fullname    /* used in some places (for genericness) */
            FROM
                {$CFG->prefix}{$this->shortprefix}_template_assignment a
            LEFT JOIN
                {$CFG->prefix}{$this->shortprefix}_template t
             ON t.id = a.templateid
            LEFT JOIN
                {$CFG->prefix}{$this->shortprefix} c
             ON a.instanceid = c.id
            LEFT JOIN
                {$CFG->prefix}{$this->shortprefix}_depth d
             ON c.depthid = d.id
            WHERE
                t.id = {$id}
            "
        );
    }

    /**
     * Get evidence items for a competency
     * @param $item object Competency
     * @return array|false
     */
    function get_evidence($item) {
        return get_records($this->shortprefix.'_evidence_items', 'competencyid', $item->id);
    }

    /**
     * Get related competencies
     * @param $item object Competency
     * @return array|false
     */
    function get_related($item) {
        global $CFG;

        return get_records_sql(
            "
            SELECT DISTINCT
                c.id AS id,
                c.fullname,
                f.id AS fid,
                f.fullname AS framework,
                d.fullname AS depth
            FROM
                {$CFG->prefix}{$this->shortprefix}_relations r
            INNER JOIN
                {$CFG->prefix}{$this->shortprefix} c
             ON r.id1 = c.id
             OR r.id2 = c.id
            INNER JOIN
                {$CFG->prefix}{$this->shortprefix}_framework f
             ON f.id = c.frameworkid
            INNER JOIN
                {$CFG->prefix}{$this->shortprefix}_depth d
             ON d.id = c.depthid
            WHERE
                (r.id1 = {$item->id} OR r.id2 = {$item->id})
            AND c.id != {$item->id}
            "
        );
    }

    /**
     * Get competency evidence using in a course
     *
     * @param   $courseid   int
     * @return  array|false
     */
    function get_course_evidence($courseid) {
        global $CFG;

        return get_records_sql(
                "
                SELECT DISTINCT
                    cei.id AS evidenceid,
                    c.id AS id,
                    c.fullname,
                    f.id AS fid,
                    f.fullname AS framework,
                    d.fullname AS depth,
                    cei.itemtype AS evidencetype,
                    cei.iteminstance AS evidenceinstance,
                    cei.itemmodule AS evidencemodule
                FROM
                    {$CFG->prefix}{$this->shortprefix}_evidence_items cei
                INNER JOIN
                    {$CFG->prefix}{$this->shortprefix} c
                 ON cei.competencyid = c.id
                INNER JOIN
                    {$CFG->prefix}{$this->shortprefix}_framework f
                 ON f.id = c.frameworkid
                INNER JOIN
                    {$CFG->prefix}{$this->shortprefix}_depth d
                 ON d.id = c.depthid
                LEFT JOIN
                    {$CFG->prefix}modules m
                 ON cei.itemtype = 'activitycompletion'
                AND m.name = cei.itemmodule
                LEFT JOIN
                    {$CFG->prefix}course_modules cm
                 ON cei.itemtype = 'activitycompletion'
                AND cm.instance = cei.iteminstance
                AND cm.module = m.id
                WHERE
                (
                        cei.itemtype <> 'activitycompletion'
                    AND cei.iteminstance = {$courseid}
                )
                OR
                (
                        cei.itemtype = 'activitycompletion'
                    AND cm.course = {$courseid}
                )
                ORDER BY
                    c.fullname
                "
        );
    }

    /**
     * Run any code before printing header
     * @param $page string Unique identifier for page
     * @return void
     */
    function hierarchy_page_setup($page = '', $item=null) {
        global $CFG, $USER;

        if (!in_array($page, array('template/view', 'item/view', 'item/add'))) {
            return;
        }

        // Setup custom javascript
        require_once($CFG->dirroot.'/local/js/lib/setup.php');

        // Setup lightbox
        local_js(array(
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW,
            TOTARA_JS_DATEPICKER
        ));

        switch ($page) {
            case 'item/view':
                $itemid = !(empty($item->id)) ? "?id={$item->id}" : '';
                require_js(array(
                    $CFG->wwwroot.'/local/js/competency.item.js.php'.$itemid,
                ));
                break;
            case 'template/view':
                $itemid = !(empty($item->id)) ? "?id={$item->id}" : '';
                require_js(array(
                    $CFG->wwwroot.'/local/js/competency.template.js.php'.$itemid,
                ));
                break;
            case 'item/add':
                require_js(array(
                    $CFG->wwwroot.'/local/js/competency.add.js.php',
                    $CFG->wwwroot.'/local/js/position.user.js.php?userid='.$USER->id,
                ));
                break;
        }
    }

    /**
     * Print any extra markup to display on the hierarchy view item page
     * @param $item object Competency being viewed
     * @return void
     */
    function display_extra_view_info($item) {
        global $CFG, $can_edit, $editingon;

        if ($editingon) {
            $str_edit = get_string('edit');
            $str_remove = get_string('remove');
        }

        // Display related competencies
        $related = $this->get_related($item);
        require $CFG->dirroot.'/hierarchy/type/competency/view-related.html';

        // Display evidence
        $evidence = $this->get_evidence($item);
        require $CFG->dirroot.'/hierarchy/type/competency/view-evidence.html';
    }

    /**
     * Return hierarchy type specific data about an item
     *
     * The returned array should have the structure:
     * array(
     *  0 => array('title' => $title, 'value' => $value),
     *  1 => ...
     * )
     *
     * @param $item object Item being viewed
     * @param $cols array optional Array of columns and their raw data to be returned
     * @return array
     */
    function get_item_data($item, $cols = NULL) {

        $data = parent::get_item_data($item, $cols);

        // Item's depth
        $depth = $this->get_depth_by_id($item->depthid);

        // Add aggregation method
        $data[] = array(
            'title' => get_string('aggregationmethodview', $this->prefix, $depth->fullname),
            'value' => get_string('aggregationmethod'.$item->aggregationmethod, $this->prefix)
        );

        return $data;
    }

    /**
     * Get the competency scale for this competency (including all the scale's
     * values in an attribute called valuelist)
     *
     * @global object $CFG
     * @return object
     */
    function get_competency_scale(){
        global $CFG;
        $sql = <<<SQL
            select scale.*
            from
                {$CFG->prefix}{$this->shortprefix}_scale_assignments sa,
                {$CFG->prefix}{$this->shortprefix}_scale scale
            where
                sa.scaleid = scale.id
                and sa.frameworkid = {$this->frameworkid}
SQL;
        $scale = get_record_sql($sql);
        if ( !$scale ){
            return false;
        }

        $valuelist = get_records($this->shortprefix.'_scale_values', 'scaleid', $scale->id, 'sortorder');
        if ( $valuelist ){
            $scale->valuelist = $valuelist;
        } else {
            $scale->valuelist = array();
        }
        return $scale;
    }

    /**
     * Get competencies in a framework by parent. If a revision id is supplied,
     * add a 'disabled' flag that will be TRUE for the competencies present in
     * that IDP revision, and FALSE otherwise
     *
     * @global object $CFG
     * @param int $parentid
     * @param int $revisionid
     * @return array
     */
    function get_items_by_parent($parentid=false, $revisionid=0) {
        global $CFG;

        // If there's no revisionid, we can use the parent class's implementation
        if ( !$revisionid ){
            return parent::get_items_by_parent($parentid);
        }

        if ($parentid) {
            // Parentid supplied, do not specify frameworkid as
            // sometimes it is not set correctly. And a parentid
            // is enough to get the right results
            $sql = <<<SQL
                select
                    c.*,
                    (
                        select count(*)
                        from {$CFG->prefix}idp_revision_competency rc
                        where
                            rc.revision = {$revisionid}
                            and rc.competency = c.id
                    ) as disabled
                from {$CFG->prefix}{$this->shortprefix} c
                where c.parentid = {$parentid}
                    and c.visible=1
                order by frameworkid, sortorder, fullname
SQL;
            return get_records_sql($sql);
        } else {
            // If no parentid, grab the root node of this framework
            return $this->get_all_root_items(false, $revisionid);
        }
    }

    
    /*
     * Returns all items at the root level (parentid=0) for the current framework (obtained
     * from $this->frameworkid)
     * If no framework is specified, returns root items across all frameworks
     * This behaviour can also be forced by setting $all = true
     *
     * @global object $CFG
     * @param int $fwid Framework ID or null for all frameworks
     * @param boolean $all If true return root items for all frameworks even if $this->frameworkid is set
     * @return array|false
     */
    function get_all_root_items($all=false, $revisionid=0) {
        global $CFG;

        // If there's no revisionid, we can use the parent class's implementation
        if ( !$revisionid ){
            return parent::get_all_root_items($all);
        }

        if(empty($this->frameworkid) || $all) {
            // all root level items across frameworks
            return $this->get_items_by_parent(0, $revisionid);
        } else {
            // root level items for current framework only
            $sql = <<<SQL
                select
                    c.*,
                    (
                        select count(*)
                        from {$CFG->prefix}idp_revision_competency rc
                        where
                            rc.revision = {$revisionid}
                            and rc.competency = c.id
                    ) as disabled
                from {$CFG->prefix}{$this->shortprefix} c
                where 
                    c.parentid = 0
                    and c.frameworkid = {$this->frameworkid}
                    and c.visible = 1
                order by sortorder, fullname
SQL;
            return get_records_sql($sql);
        }
    }

    /**
     * Get scales for a competency
     * @return array|false
     */
    function get_scales() {
        return get_records($this->shortprefix.'_scale', '', '', 'name');
    }

    /**
     * Delete  a competency assigned to a template
     * @param $templateid
     * @param $competencyid
     * @return void;
     */
    function delete_assigned_template_competency($templateid, $competencyid) {
        if (!$template = $this->get_template($templateid)) {
            return;
        }

        // Delete assignment
        delete_records('comp_template_assignment', 'templateid', $template->id, 'instanceid', $competencyid);

        // Reduce competency count for template
        $template->competencycount--;

        if ($template->competencycount < 0) {
            $template->competencycount = 0;
        }

        update_record('comp_template', $template);

        add_to_log(SITEID, $this->prefix.'template', 'removeassignment', 
                    "view.php?id={$template->id}", "Competency ID $competencyid");

    }


    /**
     * Returns an array of all competencies that a user has a comp_evidence
     * record for, keyed on the competencyid. Also returns the required
     * proficiency value and isproficient, which is 1 if the user meets the
     * proficiency and 0 otherwise
     *
     * @todo move this method into the competency libraries
     */
    static function get_proficiencies($userid) {
        global $CFG;
        $sql = "SELECT ce.competencyid, ce.proficiency, cs.proficient,
                CASE WHEN ce.proficiency=cs.proficient THEN 1
                ELSE 0 END AS isproficient
            FROM {$CFG->prefix}comp_evidence ce
            LEFT JOIN {$CFG->prefix}comp c ON c.id=ce.competencyid
            LEFT JOIN {$CFG->prefix}comp_scale_assignments csa
                ON c.frameworkid = csa.frameworkid
            LEFT JOIN {$CFG->prefix}comp_scale cs ON cs.id=csa.scaleid
            WHERE ce.userid=$userid";
        return get_records_sql($sql);
    }


    /**
     * Prints the list of linked evidence
     *
     * @param int $courseid
     * @return string
     */
    function print_linked_evidence_list($courseid) {
        global $CFG;

        $can_edit = has_capability('moodle/local:updatecompetency', get_context_instance(CONTEXT_SYSTEM));

        if (!$course = get_record('course', 'id', $courseid)) {
            print_error('invalidcourseid');
        }

        $out = '<table width="95%" cellpadding="5" cellspacing="1" id="list-coursecompetency"
            class="generalbox editcompetency boxaligncenter">
            <tr>
                <th style="vertical-align:top; text-align: left; white-space:nowrap;" class="header c0" scope="col">'.
                    get_string('framework', 'competency').
                '</th>

                <th style="vertical-align:top; text-align: left; white-space:nowrap;" class="header c1" scope="col">'.
                    get_string('depthlevel', 'competency').
                '</th>

                <th style="vertical-align:top; text-align:left; white-space:nowrap;" class="header c2" scope="col">'.
                    get_string('name').
                '</th>';

        if (!empty($CFG->competencyuseresourcelevelevidence)) {
            $out .= '<th style="vertical-align:top; text-align:left; white-space:nowrap;" class="header c3" scope="col">'.
                get_string('evidence', 'competency').
            '</th>';
        }

        if ($can_edit) {
            $out .= '<th style="vertical-align:top; text-align:left; white-space:nowrap;" class="header c4" scope="col">'.
                get_string('options', 'competency').
            '</th>';
        } // if ($can_edit)
        $out .= '</tr>';

        // Get any competencies used in this course
        $competencies = $this->get_course_evidence($course->id);
        $oddeven = 0;
        if ($competencies) {

            $str_remove = get_string('remove');

            $activities = array();

            foreach ($competencies as $competency) {

                $out .= '<tr class="r' . $oddeven . '">';
                $out .= "<td><a href=\"{$CFG->wwwroot}/hierarchy/index.php?type=competency&frameworkid={$competency->fid}\">{$competency->framework}</a></td>";
                $out .= '<td>'.$competency->depth.'</td>';
                $out .= "<td><a href=\"{$CFG->wwwroot}/hierarchy/item/view.php?type=competency&id={$competency->id}\">{$competency->fullname}</a></td>";

                // Create evidence object
                $evidence = new object();
                $evidence->id = $competency->evidenceid;
                $evidence->itemtype = $competency->evidencetype;
                $evidence->iteminstance = $competency->evidenceinstance;
                $evidence->itemmodule = $competency->evidencemodule;

                if (!empty($CFG->competencyuseresourcelevelevidence)) {
                    $out .= '<td>';

                    $evidence = competency_evidence_type::factory($evidence);

                    $out .= $evidence->get_type();
                    if ($evidence->itemtype == 'activitycompletion') {
                        $out .= ' - '.$evidence->get_name();
                    }

                    $out .= '</td>';
                }

                // Options column
                if ($can_edit) {
                    $out .= '<td align="center">';
                    $out .= "<a href=\"{$CFG->wwwroot}/hierarchy/type/competency/evidenceitem/remove.php?id={$evidence->id}&course={$courseid}\" title=\"$str_remove\">".
                         "<img src=\"{$CFG->pixpath}/t/delete.gif\" class=\"iconsmall\" alt=\"$str_remove\" /></a>";
                    $out .= '</td>';
                }

                $out .= '</tr>';

                // for row striping
                $oddeven = $oddeven ? 0 : 1;
            }

        } else {

            $cols = 5;
            $out .= '<tr class="noitems-coursecompetency"><td colspan="'.$cols.'"><i>'.get_string('nocoursecompetencies', 'competency').'</i></td></tr>';
        }

        $out .= '</table>';

        return $out;
    }

}  // class
