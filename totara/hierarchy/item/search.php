<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 - 2012 Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage hierarchy
 */

/*
 * Page containing hierarchy item search results
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/core/dialogs/search_form.php');
require_once($CFG->dirroot . '/totara/core/dialogs/dialog_content_hierarchy.class.php');
require_once($CFG->dirroot . '/totara/core/searchlib.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * How many search results to show before paginating
 *
 * @var integer
 */
define('HIERARCHY_SEARCH_NUM_PER_PAGE', 50);

// these vars provided by build_search_interface initially, but
// come from the form when it has been submitted
if (!isset($prefix)) {
    $prefix = required_param('prefix', PARAM_ALPHA); // prefix of hierarchy
}
if (!isset($select)) {
    $select = optional_param('select', true, PARAM_BOOL); // show framework selector?
}
if (!isset($frameworkid)) {
    $frameworkid = optional_param('frameworkid', 0, PARAM_INT); // specify framework to search
}
if (!isset($disabledlist)) {
    $disabledlist = unserialize(stripslashes(optional_param('disabledlist', '', PARAM_TEXT))); // items to disable
}
if (!isset($templates)) {
    $templates = optional_param('templates', false, PARAM_BOOL); // search templates only
}
if (!isset($showhidden)) {
    $showhidden = optional_param('showhidden', false, PARAM_BOOL); // include hidden frameworks
    // check they have permissions on hidden frameworks in case parameter is changed manually
    $context = context_system::instance();
    if ($showhidden && !has_capability('totara/hierarchy:update'.$prefix.'frameworks', $context)) {
        print_error('nopermviewhiddenframeworks', 'totara_hierarchy');
    }
}

$query = optional_param('query', null, PARAM_TEXT); // search query
$page = optional_param('page', 0, PARAM_INT); // results page number

$strsearch = get_string('search');
$stritemplural = get_string($prefix . 'plural', 'totara_hierarchy');
$strqueryerror = get_string('queryerror', 'totara_hierarchy');

$hierarchy = hierarchy::load_hierarchy($prefix);

$shortprefix = hierarchy::get_short_prefix($prefix);

// Trim whitespace off seach query
$query = urldecode(trim($query));

// Search form
// Data
$disabledarray = $disabledlist;
$disabledlist = serialize($disabledlist);
$hidden = compact('prefix', 'select', 'templates', 'disabledlist');

// Create form
$mform = new dialog_search_form($CFG->wwwroot . '/totara/hierarchy/item/search.php',
    compact('hidden', 'query', 'frameworkid', 'shortprefix', 'prefix', 'showhidden'));

// Display form
$mform->display();

// Display results
if (strlen($query)) {

    // extract quoted strings from query
    $keywords = totara_search_parse_keywords($query);

    $fields = 'SELECT i.id,i.fullname';
    $count = 'SELECT COUNT(*)';
    $from = " FROM {$shortprefix} i
        JOIN {{$shortprefix}_framework} f
        ON frameworkid = f.id";
    $order = ' ORDER BY frameworkid,sortthread';

    // If searching templates, change tables
    if ($templates) {
        $from .= '_template';
        $order = ' ORDER BY fullname';
    }

    // match search terms
    $dbfields = array('i.fullname', 'i.shortname', 'i.description');
    list($searchsql, $params) = totara_search_get_keyword_where_clause($keywords, $dbfields);
    $where = ' WHERE ' . $searchsql;

    // restrict by framework if required
    if ($frameworkid) {
        $where .= " AND i.frameworkid = ?";
        $params[] = $frameworkid;
    }

    // don't show hidden items
    $where .= ' AND i.visible = 1';
    if (!$showhidden) {
        $where .= ' AND f.visible = 1';
    }

    $total = $DB->count_records_sql($count . $from . $where, $params);
    $start = $page * HIERARCHY_SEARCH_NUM_PER_PAGE;

    if ($total) {
        if ($results = $DB->get_records_sql($fields . $from . $where .
            $order, $params, $start, HIERARCHY_SEARCH_NUM_PER_PAGE)) {

            $data = array('prefix' => $prefix,
                    'frameworkid' => $frameworkid,
                    'select' => $select,
                    'query' => urlencode(stripslashes($query)),
                    'disabledlist' => serialize($disabledlist),
                    'templates' => $templates,
            );
            $url = new moodle_url('/totara/hierarchy/item/search.php', $data);
            print html_writer::start_tag('div', array('class' => 'search-paging'));
            $pagingbar = new paging_bar($total, $page, HIERARCHY_SEARCH_NUM_PER_PAGE, $url);
            $OUTPUT->render($pagingbar);
            print html_writer::end_tag('div');

            $addbutton_html = new pix_icon('add', get_string('add'), 'theme', array('class' => 'addbutton'));

            // Generate some treeview data
            $dialog = new totara_dialog_content_hierarchy($prefix, $frameworkid);
            $dialog->items = array();
            $dialog->parent_items = array();
            $dialog->disabled_items = $disabledarray;

            foreach ($results as $result) {
                $title = hierarchy_search_get_path($hierarchy, $result->id);

                $item = new stdClass();
                $item->id = $result->id;
                $item->fullname = $result->fullname;
                $item->hover = $title;

                $dialog->items[$item->id] = $item;
            }

            echo $dialog->generate_treeview();

        } else {
            // if count succeeds, query shouldn't fail
            // must be something wrong with query
            print $strqueryerror;
        }
    } else {
        $params = new stdClass();
        $params->query = stripslashes($query);
        if ($frameworkid) {
            $errorstr = 'noresultsforinframework';
            $params->framework = $DB->get_field($shortprefix . '_framework', 'fullname', array('id' => $frameworkid));
        } else {
            $errorstr = 'noresultsfor';
        }
        print html_writer::tag('p', get_string($errorstr, 'totara_hierarchy', $params), array('class' => 'message'));
    }
} else {
    print html_writer::empty_tag('br');
}


/**
 * Returns the name of the item, preceeded by all parent nodes that lead to it
 *
 * @param object $hierarchy Hierarchy object that this item belongs to
 * @param integer $id ID of the hierarchy item to generate path for
 *
 * @return string Text string containing ordered path to this item in hierarchy
 */
function hierarchy_search_get_path($hierarchy, $id) {
    $path = '';

    // this gives all items in path, but not in order
    $members = $hierarchy->get_item_lineage($id);

    // find order by starting from parent id of 0 (top
    // of tree) and working down

    // prevent infinite loop in case of bad members list
    $escape = 0;

    // start at top of tree
    $parentid = 0;
    while (count($members) && $escape < 100) {
        foreach ($members as $key => $member) {
            if ($member->parentid == $parentid) {
                // add to path
                if ($parentid) {
                    // include ' > ' before name except on top element
                    $path .= ' &gt; ';
                }
                $path .= $member->fullname;
                // now update parent id and
                // unset this element
                $parentid = $member->id;
                unset($members[$key]);
            }
        }
        $escape++;
    }

    return $path;
}
