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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage dashboard 
 */

require_once('../config.php');
require_once($CFG->dirroot.'/totara/dashboard/lib.php');

require_login();

$dashaction = optional_param('dashaction', null, PARAM_ALPHANUM);
$dletid = optional_param('dlet', 0, PARAM_INT);
$col = optional_param('col', 1, PARAM_INT);
$edit=optional_param('edit', -1, PARAM_BOOL);

// Check user capabilities
$sitecontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('totara/dashboard:view', $sitecontext, $USER->id);
$canedit = (has_capability('totara/dashboard:edit', $sitecontext, $USER->id));
//TODO: maybe add some dashboard-specific caps here

$dashb = new Dashboard('mylearning', $USER->id, 'user');
$isdefaultinstance = empty($dashb->instance->userid);

$PAGE = page_create_object('totara-dashboard', $dashb->instance->id);
$pageblocks = blocks_setup($PAGE,BLOCKS_PINNED_FALSE);

$blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);

if (!empty($dashaction) && !empty($dletid) && confirm_sesskey() && !$dashb->is_using_default_instance() && $canedit) {
    $redirect = strip_querystring(me());

    switch ($dashaction) {
        case 'toggle' :
            $dashb->dashlet_toggle_visibility($dletid);
            break;
        case 'delete' :
            $dashb->dashlet_delete($dletid);
            break;
        case 'moveup' :
            $dashb->dashlet_move_vertical($dletid, 'up');
            break;
        case 'movedown' :
            $dashb->dashlet_move_vertical($dletid, 'down');
            break;
        case 'moveleft' :
            $dashb->dashlet_move_horizontal($dletid, 'left');
            break;
        case 'moveright' :
            $dashb->dashlet_move_horizontal($dletid, 'right');
            break;
        case 'config' :
            //TODO: redirect to dashlet config page
            break;
        case 'add' :
            if ($block_instance_id = $dashb->add_block_instance($dletid)) {
                $dashb->dashlet_add($block_instance_id, $col);
            }
            // TODO: go to dlet config page if necessary??
            break;
        default:
            break;
    }

    redirect($redirect);
}

$strheading = $dashb->data->title;

$pagetitle = format_string($strheading);
$navlinks[] = array('name' => get_string('dashboard', 'local_dashboard'), 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);
$navbaritem = !empty($canedit) ? $dashb->get_editing_button($edit) : '';

print_header_simple($pagetitle, '', $navigation, '', null, true, $navbaritem);

echo '<table id="layout-table">';
echo '<tr valign="top">';
echo '<td valign="top" id="middle-column">';
echo '<h1>'.$strheading.'</h1>';

$format = $PAGE->user_is_editing() ? 'useredit' : 'user';
$dashb->set_type($format);
echo $dashb->output();

echo '</td>';
echo '</tr></table>';

print_footer();

?>
