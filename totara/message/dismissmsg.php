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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @package totara
 * @subpackage message
 */

/**
 * Page containing column display options, displayed inside show/hide popup dialog
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/message/lib.php');

$PAGE->set_context(get_system_context());
require_login();

if (isguestuser()) {
    redirect($CFG->wwwroot);
}

$id = required_param('id', PARAM_INT);
$msg = $DB->get_record('message', array('id' => $id));
if (!$msg || $msg->useridto != $USER->id || !confirm_sesskey()) {
    print_error('notyours', 'totara_message', $id);
}
$metadata = $DB->get_record('message_metadata', array('messageid' => $id));

$from = $DB->get_record('user', array('id' => $msg->useridfrom));
$fromlink = html_writer::link(new moodle_url('/user/view.php', array('id' => $from->id)), fullname($from));
$subject = format_string($msg->subject);
$icon = $OUTPUT->pix_icon('i/info', format_string($msg->subject), 'moodle', array('class' => 'msgicon',  'alt' => format_string($msg->subject)));
$tab = new html_table();
$tab->attributes = array('class', 'fullwidth');
$tab->data  = array();
print html_writer::start_tag('div', array('id' => 'totara-msgs-dismiss'));

if(!empty($msg->subject)) {
    $cells = array();
    $cell = new html_table_cell(html_writer::tag('label', get_string('subject', 'forum'), array('for' => 'dismiss-type')));
    $cell->attributes['class'] = 'totara-msgs-action-left';
    $cells []= $cell;
    $cell = new html_table_cell(html_writer::tag('div', $subject, array('id' => 'dismiss-type')));
    $cell->attributes['class'] = 'totara-msgs-action-right';
    $cells []= $cell;
    $tab->data[] = new html_table_row($cells);
}
$cells = array();
$cell = new html_table_cell(html_writer::tag('label', get_string('type', 'block_totara_alerts'), array('for' => 'dismiss-type')));
$cell->attributes['class'] = 'totara-msgs-action-left';
$cells []= $cell;
$cell = new html_table_cell(html_writer::tag('div', $icon, array('id' => 'dismiss-type')));
$cell->attributes['class'] = 'totara-msgs-action-right';
$cells []= $cell;
$tab->data[] = new html_table_row($cells);

$cells = array();
$cell = new html_table_cell(html_writer::tag('label', get_string('from', 'block_totara_alerts'), array('for' => 'dismiss-from')));
$cell->attributes['class'] = 'totara-msgs-action-left';
$cells []= $cell;
$cell = new html_table_cell(html_writer::tag('div', $fromlink, array('id' => 'dismiss-from')));
$cell->attributes['class'] = 'totara-msgs-action-right';
$cells []= $cell;
$tab->data[] = new html_table_row($cells);

$cells = array();
$cell = new html_table_cell(html_writer::tag('label', get_string('statement', 'block_totara_alerts'), array('for' => 'dismiss-statement')));
$cell->attributes['class'] = 'totara-msgs-action-left';
$cells []= $cell;
$cell = new html_table_cell(html_writer::tag('div', $msg->fullmessagehtml, array('id' => 'dismiss-statement')));
$cell->attributes['class'] = 'totara-msgs-action-right';
$cells []= $cell;
$tab->data[] = new html_table_row($cells);
if ($msg->contexturl && $msg->contexturlname) {
    $cells = array();
    $cell = new html_table_cell(html_writer::tag('label', get_string('statement', 'block_totara_alerts'), array('for' => 'dismiss-context')));
    $cell->attributes['class'] = 'totara-msgs-action-left';
    $cells []= $cell;
    $cell = new html_table_cell(html_writer::tag('div', html_writer::tag('a', $msg->contexturlname, array('href' => $msg->contexturl)), array('id' => 'dismiss-context')));
    $cell->attributes['class'] = 'totara-msgs-action-right';
    $cells []= $cell;
}
print html_writer::table($tab);
print html_writer::end_tag('div');