<?php // $Id$
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
 * @package totara
 * @subpackage plan 
 */

/**
 * Workflow settings page for development plan templates
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('template_forms.php');

$id = optional_param('id', null, PARAM_INT);
$save = optional_param('save', false, PARAM_BOOL);
$moveup = optional_param('moveup', 0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$hide = optional_param('hide', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);

admin_externalpage_setup('managetemplates');

if(!$template = get_record('dp_template', 'id', $id)){
    error(get_string('error:invalidtemplateid', 'local_plan'));
}

$returnurl = $CFG->wwwroot . '/totara/plan/template/components.php?id=' . $id;

if($save){
    if(update_plan_component_name('componentname', $id)){
        totara_set_notification(get_string('update_components_settings', 'local_plan'), $returnurl, array('class' => 'notifysuccess'));
    } else {
        totara_set_notification(get_string('error:update_components_settings', 'local_plan'), $returnurl);
    }
}

if ((!empty($moveup) or !empty($movedown))) {

    $move = NULL;
    $swap = NULL;

    // Get value to move, and value to replace
    if (!empty($moveup)) {
        $move = get_record('dp_component_settings', 'id', $moveup);
        $resultset = get_records_sql("
            SELECT *
            FROM {$CFG->prefix}dp_component_settings
            WHERE
            templateid = {$template->id}
            AND sortorder < {$move->sortorder}
            ORDER BY sortorder DESC", 0, 1
        );
        if ( $resultset && count($resultset) ){
            $swap = reset($resultset);
            unset($resultset);
        }
    } else {
        $move = get_record('dp_component_settings', 'id', $movedown);
        $resultset = get_records_sql("
            SELECT *
            FROM {$CFG->prefix}dp_component_settings
            WHERE
            templateid = {$template->id}
            AND sortorder > {$move->sortorder}
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
        if (!(set_field('dp_component_settings', 'sortorder', $move->sortorder, 'id', $swap->id)
            && set_field('dp_component_settings', 'sortorder', $swap->sortorder, 'id', $move->id)
        )) {
            rollback_sql();
            totara_set_notification(get_string('error:update_components_sortorder', 'local_plan'), $returnurl);
        }
        commit_sql();
    }
}

if($show) {
    if(!$component = get_record('dp_component_settings', 'id', $show)){
            totara_set_notification(get_string('error:invalid_component_id', 'local_plan'), $returnurl);
    } else {
        $enabled = 1;
        if (!set_field('dp_component_settings', 'enabled', $enabled, 'id', $component->id)) {
            rollback_sql();
            totara_set_notification(get_string('error:update_components_enabled', 'local_plan'), $returnurl);
        } else {
            commit_sql();
            if ($plans = get_records('dp_plan', 'templateid', $template->id, '', 'id')) {
                dp_plan_check_plan_complete(array_keys($plans));
            }
        }
    }
}

if($hide) {
    if(!$component = get_record('dp_component_settings', 'id', $hide)){
            totara_set_notification(get_string('error:invalid_component_id', 'local_plan'), $returnurl);
    } else {
        $enabled = 0;
        if (!set_field('dp_component_settings', 'enabled', $enabled, 'id', $component->id)) {
            rollback_sql();
            totara_set_notification(get_string('error:update_components_enabled', 'local_plan'), $returnurl);
        } else {
            commit_sql();
            if ($plans = get_records('dp_plan', 'templateid', $template->id, '', 'id')) {
                dp_plan_check_plan_complete(array_keys($plans));
            }
        }
    }
}

$navlinks = array();    // Breadcrumbs
$navlinks[] = array('name'=>get_string("managetemplates", "local_plan"),
                    'link'=>"{$CFG->wwwroot}/totara/plan/template/index.php",
                    'type'=>'misc');
$navlinks[] = array('name'=>format_string($template->fullname), 'link'=>'', 'type'=>'misc');

admin_externalpage_print_header('', $navlinks);

if($template){
    print_heading($template->fullname);
} else {
    print_heading(get_string('newtemplate', 'local_plan'));
}

$currenttab = 'components';
require('tabs.php');

$mform = new dp_components_form(null, compact('id'));
$mform->display();

admin_externalpage_print_footer();

?>
