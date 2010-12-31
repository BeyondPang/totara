<?PHP //$Id$
  /*
  * Totara Reminders
  *
  * @package blocks
  * @subpackage totara_reminders
  * @author: Piers Harding
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
  * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
  */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/totara_msg/lib.php');

class block_totara_reminders extends block_base {
    function init() {
        $this->title = get_string('blockname', 'block_totara_reminders');
        $this->version = 2010110101;
    }

    // only one instance of this block is required
    function instance_allow_multiple() {
      return false;
    } //instance_allow_multiple

    // label and button values can be set in admin
    function has_config() {
      return false;
    } //has_config

    function preferred_width() {
        return 210;
    }

    function get_content() {
        global $CFG, $USER, $COURSE, $FULLME;

        //cache block contents
        if ($this->content !== NULL) {
        return $this->content;
        }

        $this->content = new stdClass;

        // initialise jquery requirements
        $CFG->stylesheets[] = $CFG->wwwroot.'/local/js/lib/ui-lightness/jquery-ui-1.7.2.custom.css';
        require_once($CFG->dirroot.'/local/reportbuilder/lib.php');
        require_once($CFG->dirroot.'/local/js/lib/setup.php');
        $code = array();
        $code[] = TOTARA_JS_DIALOG;
        $js = array();
        local_js($code);
        $js['dismissmsg'] = $CFG->wwwroot.'/local/reportbuilder/confirm.js.php';
        require_js(array_values($js));

      // just get the reminders for this user
        $roleid = $this->current_roleid();
        $role_assertion = '';
         if ($roleid) {
             $role_assertion = '?roleid='.$roleid;
         }
        $total = tm_messages_count('totara_reminder', false, $roleid);
        $this->msgs = tm_messages_get('totara_reminder', 'timecreated DESC ', false, true, $roleid);
        $count = is_array($this->msgs) ? count($this->msgs) : 0;
        $this->title = get_string('reminders', 'block_totara_reminders').
            ' <span>'.get_string('showingxofx', 'block_totara_reminders', array($count, $total)).'</span>';

      // firstly pull in the stylesheet needed for the dismiss dialog
        $this->content->text =
        '<link rel="stylesheet" type="text/css" href="'.$CFG->wwwroot.'/local/js/lib/ui-lightness/jquery-ui-1.7.2.custom.css" />';

        if (empty($this->instance)) {
            return $this->content;
        }

        // now build the table of results
        $this->content->text  .= '<table>';
        if (!empty($this->msgs)) {
            $cnt = 0;
            foreach ($this->msgs as $msg) {
                // status Icon
                $cnt++;

                // user name + link
                $userfrom_link = $CFG->wwwroot.'/user/view.php?id='.$msg->useridfrom;
                $from = get_record('user', 'id', $msg->useridfrom);
                $fromname = fullname($from);

                // message creation time
                $when = userdate($msg->timecreated, '%e %b %y');

                // statement - multipart: user + statment + object
                $bkgd = ($cnt % 2) ? 'shade' : 'noshade';
                $content  = "<tr class=\"".$bkgd."\">";
                $dismiss = totara_msg_dismiss_action($msg->id);
                $content .= '<td class="status">';
                $content .= '<img class="msgicon" src="' . totara_msg_icon_url($msg->icon) . '" title="' . format_string($msg->subject) . '" alt="' . format_string($msg->subject) .'" />';
                $content .= '</td>';
                $msgtext = $msg->subject ? $msg->subject : $msg->fullmessage;
                $content .= "<td class=\"statement\"><p>{$msgtext}</p></td>";
                $accept_reject = totara_msg_accept_reject_action($msg->id);
                $content .= $accept_reject;
                $content .= "<td class=\"action\">{$dismiss}</td>";
                $content .= "</tr>";
                $this->content->text .= $content;
            }
        }
        $this->content->text .= '</table>';
        if (!empty($this->msgs)) {
            $this->content->footer = '<div class="viewall"><a href="'.$CFG->wwwroot.'/local/totara_msg/reminders.php'.$role_assertion.'">'.
                                     get_string('viewallnot', 'block_totara_reminders').'</a></div>';
        }
        return $this->content;
    }

    /**
    * Get the roleid for this dashlet context
    * @return int roleid
    **/
    function current_roleid() {
        global $CFG;
        if (instance_is_dashlet($this)) {
            // what dashlet role is this
            $role = get_dashlet_role($this->instance->pageid);
            $roleid = get_field('role', 'id', 'shortname', $role);
            return $roleid;
        }
        else {
            return false;
        }
    }
}

?>
