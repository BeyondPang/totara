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
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage plan 
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');

require_login();

$id = required_param('id', PARAM_INT); // plan id
$caid = required_param('itemid', PARAM_INT); // competency assignment id
$action = required_param('action', PARAM_TEXT); // what to do
$confirm = optional_param('confirm', 0, PARAM_INT); // confirm the action

$plan = new development_plan($id);
$componentname = 'competency';
$component = $plan->get_component($componentname);
$currenturl = $CFG->wwwroot . '/totara/plan/components/competency/approval.php?id='.$id.'&amp;itemid='.$caid.'&amp;action='.$action;
$returnurl = $component->get_url();
$canapprovecompetency = $component->get_setting('updatecompetency') == DP_PERMISSION_APPROVE;

if($confirm) {
    if(!confirm_sesskey()) {
        totara_set_notification(get_string('confirmsesskeybad','error'), $returnurl);
    }
    if(!$canapprovecompetency) {
        // no permission to complete the action
        totara_set_notification(get_string('nopermission', 'local_plan'),
            $returnurl);
        die();
    }

    $todb = new object();
    $todb->id = $caid;
    if($action=='decline') {
        $todb->approved = DP_APPROVAL_DECLINED;
    } else if ($action == 'approve') {
        $todb->approved = DP_APPROVAL_APPROVED;
    }

    if(update_record('dp_plan_competency_assign', $todb)) {
        //@todo send notifications/emails
        totara_set_notification(get_string('request'.$action,'local_plan'), $returnurl, array('style' => 'notifysuccess'));
    } else {
        //@todo send notifications/emails
        totara_set_notification(get_string('requestnot'.$action, 'local_plan'), $returnurl);
    }

}

$fullname = $plan->name;
$pagetitle = format_string(get_string('learningplan','local_plan').': '.$fullname);
$navlinks = array();
dp_get_plan_base_navlinks($navlinks, $plan->userid);
$navlinks[] = array('name' => $fullname, 'link'=> $CFG->wwwroot . '/totara/plan/view.php?id='.$id, 'type'=>'title');
$navlinks[] = array('name' => get_string($component->component, 'local_plan'), 'link' => $component->get_url(), 'type' => 'title');
$navlinks[] = array('name' => get_string('itemapproval','local_plan'), 'link' => '', 'type' => 'title');

$navigation = build_navigation($navlinks);

print_header_simple($pagetitle, '', $navigation, '', null, true, '');

print_heading($fullname);

notice_yesno(get_string('confirmrequest'.$action, 'local_plan'),
    $currenturl.'&amp;confirm=1&amp;sesskey='.sesskey(),
    $returnurl
);

print $component->display_competency_detail($caid);


print_footer();


?>
