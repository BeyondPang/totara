<?PHP //$Id$
  /*
  * Totara Notifications
  *
  * @package blocks
  * @subpackage totara_notify
  * @author: Piers Harding
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
  * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
  */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/totara_msg/lib.php');

class block_totara_notify extends block_base {
    function init() {
        $this->title = get_string('blockname', 'block_totara_notify');
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

      // just get the notifications for this user
        $roleid = $this->current_roleid();
        $role_assertion = '';
         if ($roleid) {
             $role_assertion = '?roleid='.$roleid;
         }
        $total = tm_messages_count('totara_notification', false, $roleid);
        $this->msgs = tm_messages_get('totara_notification', 'timecreated DESC ', false, true, $roleid);
        $count = is_array($this->msgs) ? count($this->msgs) : 0;
        $this->title = get_string('notifications', 'block_totara_notify').
            ' <span>'.get_string('showingxofx', 'block_totara_notify', array($count, $total)).'</span>';

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
                $cssclass = totara_msg_cssclass($msg->msgtype);
                // statement - multipart: user + statment + object
                $bkgd = ($cnt % 2) ? 'shade' : 'noshade';
                $content  = "<tr class=\"".$bkgd."\">";
                $content .= '<td class="status">';
                $content .= '<img class="msgicon" src="' . totara_msg_icon_url($msg->icon) . '" title="' . $msg->subject . '" alt="' . $msg->subject .'" />';
                $content .= '</td>';
                $detailjs = totara_msg_alert_popup($msg->id);
                $msgtext = '<a id="detailtask'.$msg->id.'-dialog" href="';
                if ( $msg->contexturl ){
                    $msgtext .= $msg->contexturl;
                } else {
                    $msgtext .= '#';
                }
                $msgtext .= '">'.($msg->subject ? $msg->subject : $msg->fullmessage).'</a>';
                $content .= "<td class=\"statement\">{$msgtext}{$detailjs}</td>";
                $content .= "</tr>";
                $this->content->text .= $content;
            }
        }
        $this->content->text .= '</table>';
        if (!empty($this->msgs)) {
            $this->content->footer = '<div class="viewall"><a href="'.$CFG->wwwroot.'/local/totara_msg/notifications.php'.$role_assertion.'">'.
                                     get_string('viewallnot', 'block_totara_notify').'</a></div>';
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
