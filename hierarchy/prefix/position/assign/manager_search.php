<?php // $Id$

/**
 * Page containing manager search results
 *
 * @copyright Totara Learning Solution Limited
 * @author Simon Coggins
 * @author Aaron Barnes
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 * @subpackage dialog
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/dialogs/search_form.php');
require_once($CFG->dirroot . '/local/dialogs/dialog_content_hierarchy.class.php');

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
$userid = optional_param('userid', -1, PARAM_INT); // user being assigned a manager

$strsearch = get_string('search');
#$stritemplural = get_string($prefix . 'plural', $prefix);
$strqueryerror = get_string('queryerror', 'hierarchy');

// Trim whitespace off seach query
$query = urldecode(trim($query));

// Search form
// Data
$hidden = array();

// Grab data from dialog object (if applicable)
if (isset($this) && isset($this->customdata['current_user'])) {
    $hidden['userid'] = $this->customdata['current_user'];
} else if ($userid) {
    $hidden['userid'] = $userid;
}

// Create form
$mform = new dialog_search_form($CFG->wwwroot. '/hierarchy/prefix/position/assign/manager_search.php',
    compact('hidden', 'query'));

// Display form
$mform->display();

// Display results
if (strlen($query)) {

    // extract quoted strings from query
    $keywords = user_search_parse_keywords($query);

    $fields = "
        SELECT
            u.id,
            ".sql_fullname('u.firstname', 'u.lastname')." AS fullname
    ";

    $count = 'SELECT COUNT(u.id)';

    $from = "
        FROM
            {$CFG->prefix}user u
    ";

    $order = ' ORDER BY u.firstname, u.lastname';

    // Match search terms
    $where = user_search_get_keyword_where_clause($keywords);
    $where .= " AND u.id <> {$userid}";

    $total = count_records_sql($count . $from . $where);
    $start = $page * HIERARCHY_SEARCH_NUM_PER_PAGE;

    if ($total) {
        if($results = get_records_sql($fields . $from . $where .
            $order, $start, HIERARCHY_SEARCH_NUM_PER_PAGE)) {

            $data = array('query' => urlencode(stripslashes($query)));

            $url = new moodle_url($CFG->wwwroot . '/hierarchy/prefix/position/assign/manager_search.php', $data);
            print '<div class="search-paging">';
            print print_paging_bar($total, $page, HIERARCHY_SEARCH_NUM_PER_PAGE, $url, 'page', false, true, 5);
            print '</div>';

            // Generate some treeview data
            $dialog = new totara_dialog_content();
            $dialog->items = array();
            $dialog->parent_items = array();

            foreach($results as $result) {
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


/**
 * Parse a query into individual keywords, treating quoted phrases one item
 *
 * Pairs of matching double or single quotes are treated as a single keyword.
 *
 * @param string $query Text from user search field
 *
 * @return array Array of individual keywords parsed from input string
 */
function user_search_parse_keywords($query) {
    // query arrives with quotes escaped, but quotes have special meaning
    // within a query. Strip out slashes, then re-add any that are left
    // after parsing done (to protect against SQL injection)
    $query = stripslashes($query);

    $out = array();
    // break query down into quoted and unquoted sections
    $split_quoted = preg_split('/(\'[^\']+\')|("[^"]+")/', $query, 0,
        PREG_SPLIT_DELIM_CAPTURE);
    foreach($split_quoted as $item) {
        // strip quotes from quoted strings but leave spaces
        if(preg_match('/^(["\'])(.*)\\1$/', trim($item), $matches)) {
            $out[] = addslashes($matches[2]);
        } else {
            // split unquoted text on whitespace
            $keyword = addslashes_recursive(preg_split('/\s/', $item, 0,
                PREG_SPLIT_NO_EMPTY));
            $out = array_merge($out, $keyword);
        }
    }
    return $out;
}


/**
 * Return an SQL WHERE clause to search for the given keywords
 *
 * @param array $keywords Array of strings to search for
 *
 * @return string SQL WHERE clause to match the keywords provided
 */
function user_search_get_keyword_where_clause($keywords) {

    // fields to search
    $fields = array(sql_fullname('u.firstname', 'u.lastname'));

    //get guest user
    $guest = guest_user();

    // exclude deleted users and guest user
    $queries = array(' u.deleted = 0 ', ' u.id != '.$guest->id);
    foreach($keywords as $keyword) {
        $matches = array();
        foreach($fields as $field) {
            $matches[] = $field . ' ' . sql_ilike() . " '%" . $keyword . "%'";
        }
        // look for each keyword in any field
        $queries[] = '(' . implode(' OR ', $matches) . ')';
    }
    // all keywords must be found in at least one field
    return ' WHERE ' . implode(' AND ', $queries);
}
