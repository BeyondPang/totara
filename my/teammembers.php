<?php

/**
 * Moodle - Modular Object-Oriented Dynamic Learning Environment
 *          http://moodle.org
 * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
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
 * @package    moodle
 * @subpackage totara
 * @author     Simon Coggins <simonc@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * Displays information for the current user's team
 *
 */

require_once('../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->dirroot.'/local/reportbuilder/lib.php');
require_once($CFG->dirroot . '/local/plan/lib.php');

require_login();

global $SESSION,$USER;


/**
 * Define the "Team Members" embedded report
 */
$strheading = get_string('teammembers', 'local');

$embed = new object();
$embed->source = 'user';
$embed->fullname = $strheading;
$embed->filters = array(); //hide filter block
$embed->columns = array(
    array(
        'type' => 'user',
        'value' => 'namelinkicon',
        'heading' => 'Name'
    ),
    array(
        'type' => 'user',
        'value' => 'lastlogin',
        'heading' => 'Last Login'
    ),
    array(
        'type' => 'statistics',
        'value' => 'coursesstarted',
        'heading' => 'Courses Started'
    ),
    array(
        'type' => 'statistics',
        'value' => 'coursescompleted',
        'heading' => 'Courses Completed'
    ),
    array(
        'type' => 'statistics',
        'value' => 'competenciesachieved',
        'heading' => 'Competencies Achieved'
    ),
    array(
        'type' => 'user',
        'value' => 'userlearningicons',
        'heading' => 'Links',
    )
);
$embed->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;
$embed->contentsettings = array(
    'user' => array(
        'enable' => 1,
        'who' => 'reports'
    )
);
$embed->embeddedparams = array();
$shortname = 'team_members';
$report = new reportbuilder(null, $shortname, $embed);
/**
 * End of defining the report
 */


$PAGE = page_create_object('Totara', $USER->id);
$pageblocks = blocks_setup($PAGE,BLOCKS_PINNED_BOTH);
$blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);

// see which reports exist in db and add columns for them to table
// these reports should have the "userid" url parameter enabled to allow
// viewing of individual reports
$staff_records = get_field('report_builder','id','shortname','staff_learning_records');
$staff_f2f = get_field('report_builder','id','shortname','staff_facetoface_sessions');

$PAGE->print_header($strheading, $strheading);

// Plan menu
echo dp_display_plans_menu(0,0,'manager');

// Plan page content
print_container_start(false, '', 'dp-plan-content');

echo '<h1>'.$strheading.'</h1>';

echo '<p>' . get_string('teammembers_text', 'local') . '</p>';

$report->include_js();
$report->display_table();

print_container_end();
print_footer();

?>
