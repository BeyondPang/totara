<?php // $Id$

/**
 * Page containing search results
 *
 * @copyright Totara Learning Solution Limited
 * @author Simon Coggins
 * @author Aaron Barnes
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 * @subpackage dialog
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/dialogs/search_form.php');
require_once($CFG->dirroot . '/local/dialogs/dialog_content.class.php');
require_once($CFG->dirroot . '/local/searchlib.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * How many search results to show before paginating
 *
 * @var integer
 */
define('HIERARCHY_SEARCH_NUM_PER_PAGE', 50);

$query = optional_param('query', null, PARAM_TEXT); // search query
$page = optional_param('page', 0, PARAM_INT); // results page number

$strsearch = get_string('search');
#$stritemplural = get_string($prefix . 'plural', $prefix);
$strqueryerror = get_string('queryerror', 'hierarchy');

// Trim whitespace off seach query
$query = urldecode(trim($query));

// Search form
// Data
$hidden = array();

// Create form
$mform = new dialog_search_form($CFG->wwwroot. '/hierarchy/prefix/competency/evidenceitem/search.php',
    compact('hidden', 'query'));

// Display form
$mform->display();

// Display results
if (strlen($query)) {

    // extract quoted strings from query
    $keywords = local_search_parse_keywords($query);

    $fields = "
        SELECT
            c.id,
            c.fullname
    ";

    $count = 'SELECT COUNT(*)';

    $from = "
        FROM
            {$CFG->prefix}course c
    ";

    $order = ' ORDER BY c.sortorder ASC';

    // Match search terms
    $dbfields = array('c.fullname', 'c.shortname');
    $where = ' WHERE ' . local_search_get_keyword_where_clause($keywords, $dbfields);

    // Only show courses with completion enabled
    $where .= "
        AND c.enablecompletion = ".COMPLETION_ENABLED."
        AND c.visible = 1
    ";

    $total = count_records_sql($count . $from . $where);
    $start = $page * HIERARCHY_SEARCH_NUM_PER_PAGE;
    if ($total) {
        if ($results = get_records_sql($fields . $from . $where .
            $order, $start, HIERARCHY_SEARCH_NUM_PER_PAGE)) {

            $data = array('query' => urlencode(stripslashes($query)));

            $url = new moodle_url($CFG->wwwroot . '/hierarchy/prefix/competency/evidenceitem/search.php', $data);
            print '<div class="search-paging">';
            print print_paging_bar($total, $page, HIERARCHY_SEARCH_NUM_PER_PAGE, $url, 'page', false, true, 5);
            print '</div>';

            // Generate some treeview data
            $dialog = new totara_dialog_content();
            $dialog->items = array();
            $dialog->parent_items = array();

            foreach ($results as $result) {
                $item = new object();
                $item->id = $result->id;
                $item->fullname = $result->fullname;

                $dialog->items[$item->id] = $item;
            }

            echo $dialog->generate_treeview();

        } else {
            // if count succeeds, query shouldn't fail
            // must be something wrong with query
            print $strqueryerror;
        }
    } else {
        $params = new object();
        $params->query = stripslashes($query);
        $errorstr = 'noresultsfor';
        print '<p class="message">' . get_string($errorstr, 'hierarchy', $params). '</p>';
    }
} else {
    print '<br />';
}

