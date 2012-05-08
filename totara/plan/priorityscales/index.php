<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Alastair Munro <alastair@catalyst.net.nz>
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage plan
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('../lib.php');
require_once('lib.php');

$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$moveup = optional_param('moveup', null, PARAM_INT);
$movedown = optional_param('movedown', null, PARAM_INT);

/// Setup / loading data
$sitecontext = get_context_instance(CONTEXT_SYSTEM);

// Setup page and check permissions
admin_externalpage_setup('priorityscales');

if ((!empty($moveup) or !empty($movedown))) {

    $move = NULL;
    $swap = NULL;

    // Get value to move, and value to replace
    if (!empty($moveup)) {
        $move = get_record('dp_priority_scale', 'id', $moveup);
        $resultset = get_records_sql("
            SELECT *
            FROM {$CFG->prefix}dp_priority_scale
            WHERE
            sortorder < {$move->sortorder}
            ORDER BY sortorder DESC", 0, 1
        );
        if ( $resultset && count($resultset) ){
            $swap = reset($resultset);
            unset($resultset);
        }
    } else {
        $move = get_record('dp_priority_scale', 'id', $movedown);
        $resultset = get_records_sql("
            SELECT *
            FROM {$CFG->prefix}dp_priority_scale
            WHERE
            sortorder > {$move->sortorder}
            ORDER BY sortorder ASC", 0, 1
        );
        if ( $resultset && count($resultset) ){
            $swap = reset($resultset);
            unset($resultset);
        }
    }

    if ($swap && $move) {
        // Swap sortorders
        begin_sql();
        if (!(set_field('dp_priority_scale', 'sortorder', $move->sortorder, 'id', $swap->id)
            && set_field('dp_priority_scale', 'sortorder', $swap->sortorder, 'id', $move->id)
        )) {
            error(get_string('error:updatepriorityscaleordering', 'local_plan'));
        }
        commit_sql();
    }
}

if($delete) {
    if(!$scale = get_record('dp_priority_scale', 'id', $delete)) {
       print_error('error:invalidpriorityscaleid', 'local_plan');
    }
    if ( dp_priority_scale_is_used($delete) ){
        print_error('error:nodeletepriorityscaleinuse', 'local_plan');
    }
    if ( dp_priority_scale_is_assigned($delete) ){
        print_error('error:nodeletepriorityscaleassigned', 'local_plan');
    }

    if($confirm) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }

        delete_records('dp_priority_scale_value', 'priorityscaleid', $scale->id); // Delete scale values
        delete_records('dp_priority_scale', 'id', $scale->id); // Delete scale itself
        totara_set_notification(get_string('deletedpriorityscale', 'local_plan', format_string($scale->name)), $CFG->wwwroot.'/totara/plan/priorityscales/index.php', array('class' => 'notifysuccess'));

    } else {
        $returnurl = "{$CFG->wwwroot}/totara/plan/priorityscales/index.php";
        $deleteurl = "{$CFG->wwwroot}/totara/plan/priorityscales/index.php?delete={$delete}&amp;confirm=1&amp;sesskey=" . sesskey();

        admin_externalpage_print_header();
        $strdelete = get_string('deletecheckpriority', 'local_plan');

        notice_yesno(
            "{$strdelete}<br /><br />".format_string($scale->name),
            $deleteurl,
            $returnurl
        );

        print_footer();
        exit;
    }
}

/// Build page
admin_externalpage_print_header();

$priorities = dp_get_priorities();
dp_priority_display_table($priorities, $editingon=1);

admin_externalpage_print_footer();

?>
