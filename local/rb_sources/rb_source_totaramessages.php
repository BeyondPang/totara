<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__FILE__)).'/totara_msg/lib.php');

class rb_source_totaramessages extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns;

    function __construct() {
        global $CFG;
        $this->base = $CFG->prefix . 'message_working20';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        parent::__construct();
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    function define_joinlist() {
        global $CFG;

        $joinlist = array(
            new rb_join(
                'msg',
                'LEFT',
                $CFG->prefix . 'message20',
                'msg.id = base.unreadmessageid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'mdata',
                'LEFT',
                $CFG->prefix . 'message_metadata',
                'mdata.messageid = base.unreadmessageid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'processor',
                'INNER',
                $CFG->prefix . 'message_processors20',
                'base.processorid = processor.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        // include some standard joins
        $this->add_user_table_to_joinlist($joinlist, 'msg', 'useridfrom');

        return $joinlist;
    }

    function define_columnoptions() {
        $columnoptions = array(
            new rb_column_option(
                'message_values',         // type
                'statement',              // value
                get_string('statement', 'rb_source_totaramessages'),              // name
                'msg.fullmessage',        // field
                array('joins' => 'msg')   // options
            ),
//            new rb_column_option(
//                'message_values',
//                'msgstatus',
//                get_string('msgstatus', 'rb_source_totaramessages'),
//                'mdata.msgstatus',
//                array('joins' => 'mdata',
//                      'displayfunc' => 'msgstatus_link')
//                ),
//            new rb_column_option(
//                'message_values',
//                'msgstatus_text',
//                get_string('msgstatus', 'rb_source_totaramessages'),
//                'mdata.msgstatus',
//                array('joins' => 'mdata',
//                      'displayfunc' => 'msgstatus_text')
//                ),
            new rb_column_option(
                'message_values',
                'urgency',
                get_string('msgurgency', 'rb_source_totaramessages'),
                'mdata.urgency',
                array('joins' => 'mdata',
                      'displayfunc' => 'urgency_link')
                ),
            new rb_column_option(
                'message_values',
                'urgency_text',
                get_string('msgurgency', 'rb_source_totaramessages'),
                'mdata.urgency',
                array('joins' => 'mdata',
                      'displayfunc' => 'urgency_text')
                ),
            new rb_column_option(
                'message_values',
                'msgtype',
                get_string('msgtype', 'rb_source_totaramessages'),
                'mdata.msgtype',
                array('joins' => 'mdata',
                      'displayfunc' => 'msgtype_link')
            ),
            new rb_column_option(
                'message_values',
                'msgtype_text',
                get_string('msgtype', 'rb_source_totaramessages'),
                'mdata.msgtype',
                array('joins' => 'mdata',
                      'displayfunc' => 'msgtype_text')
            ),
            new rb_column_option(
                'message_values',
                'sent',
                get_string('sent', 'rb_source_totaramessages'),
                'msg.timecreated',
                array('joins' => 'msg',
                      'displayfunc' => 'nice_date')
            ),
            new rb_column_option(
                'message_values',
                'dismiss_link',
                get_string('dismissmsg', 'rb_source_totaramessages'),
                'base.unreadmessageid',
                array('displayfunc' => 'dismiss_link',
                      'nosort' => true)
            ),
            new rb_column_option(
                'message_values',
                'reminder_links',
                get_string('actions', 'rb_source_totaramessages'),
                'base.unreadmessageid',
                array('displayfunc' => 'reminder_links',
                      'nosort' => true)
            ),
            new rb_column_option(
                'message_values',
                'checkbox',
                get_string('select', 'rb_source_totaramessages'),
                'base.unreadmessageid',
                array('displayfunc' => 'message_checkbox',
                      'nosort' => true)
            ),
            new rb_column_option(
                'message_values',
                'msgid',
                get_string('msgid', 'rb_source_totaramessages'),
                'msg.id',
                array('nosort' => true,
                      'noexport' => true,
                      'hidden' => 1,
                      'joins' => 'msg',
                    )
            ),
        );

        // include some standard columns
        $this->add_user_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'message_values',       // type
                'sent',                 // value
                get_string('datesent', 'rb_source_totaramessages'),            // label
                'date',                 // filtertype
                array()                 // options
            ),
            new rb_filter_option(
                'message_values',
                'statement',
                get_string('details', 'rb_source_totaramessages'),
                'text'
            ),
//            new rb_filter_option(
//                'message_values',
//                'msgstatus',
//                'Message Status',
//                'select',
//                array(
//                    'selectfunc' => 'message_status_list',
//                    'selectoptions' => rb_filter_option::select_width_limiter(),
//                )
//            ),
            new rb_filter_option(
                'message_values',
                'urgency',
                'Message Urgency',
                'select',
                array(
                    'selectfunc' => 'message_urgency_list',
                    'selectoptions' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'message_values',
                'msgtype',
                get_string('msgtype', 'rb_source_totaramessages'),
                'select',
                array(
                    'selectfunc' => 'message_type_list',
                    'selectoptions' => rb_filter_option::select_width_limiter(),
                )
            ),
        );
        // include some standard filters
        $this->add_user_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    function define_contentoptions() {
        $contentoptions = array(
//            new rb_content_option(
//                'current_org',                      // class name
//                get_string('currentorg', 'rb_source_totaramessages'),  // title
//                'base.userid',                      // field
//                null                                // joins
//            ),
//            new rb_content_option(
//                'current_pos',                      // class name
//                get_string('currentpos', 'rb_source_totaramessages'),      // title
//                'base.userid',                      // field
//                null                                // joins
//            ),
//            new rb_content_option(
//                'completed_org',
//                get_string('completedorg', 'rb_source_totaramessages'),
//                'base.organisationid'
//            ),
//            new rb_content_option(
//                'user',
//                get_string('user', 'rb_source_totaramessages'),
//                'base.userid'
//            ),
//            new rb_content_option(
//                'date',
//                get_string('date', 'rb_source_totaramessages'),
//                'base.timemodified'
//            ),
        );
        return $contentoptions;
    }

    function define_paramoptions() {
        // this is where you set your hardcoded filters
        $paramoptions = array(
            new rb_param_option(
                'userid',        // parameter name
                'msg.useridto',  // field
                'msg'            // joins
            ),
            new rb_param_option(
                'name',            // parameter name
                'processor.name',  // field
                'processor'        // joins
            ),
            new rb_param_option(
                'roleid',          // parameter name
                'mdata.roleid',    // field
                'mdata'            // joins
            ),
        );

        return $paramoptions;
    }

    function define_defaultcolumns() {
        $defaultcolumns = array(
        );
        return $defaultcolumns;
    }

    function define_defaultfilters() {
        $defaultfilters = array(
        );
        return $defaultfilters;
    }

    function define_requiredcolumns() {
        $requiredcolumns = array(
        );
        return $requiredcolumns;
    }

    //
    //
    // Source specific column display methods
    //
    //

    // generate status icon link
    function rb_display_msgstatus_link($comp, $row) {
        global $CFG;
        $display = totara_msg_msgstatus_text($row->message_values_msgstatus);
        return "<img class=\"iconsmall\" src=\"{$display['icon']}\" alt=\"{$display['text']}\" />";
    }

    // generate status text
    function rb_display_msgstatus_text($comp, $row) {
        global $CFG;
        $display = totara_msg_msgstatus_text($row->message_values_msgstatus);
        return $display['text'];
    }

    // generate urgency icon link
    function rb_display_urgency_link($comp, $row) {
        global $CFG;
        $display = totara_msg_urgency_text($row->message_values_urgency);
        return "<img class=\"iconsmall\" src=\"{$display['icon']}\" title=\"{$display['text']}\" alt=\"{$display['text']}\" />";
    }

    // generate urgency text
    function rb_display_urgency_text($comp, $row) {
        global $CFG;
        $display = totara_msg_urgency_text($row->message_values_urgency);
        return $display['text'];
    }

    // generate type icon link
    function rb_display_msgtype_link($comp, $row) {
        global $CFG;
        $msg = get_record('message20', 'id', $row->message_values_msgid);
        $metadata = get_record('message_metadata', 'messageid', $row->message_values_msgid);
        //$display = totara_msg_msgtype_text($row->message_values_msgtype);
        $icon = (($metadata && isset($metadata->icon)) ? $metadata->icon : 'default.png');
        return '<img class="msgicon" src="' . totara_msg_icon_url($icon) . '" title="' . format_string($msg->subject) . '" alt="' . format_string($msg->subject) .'" />';
//        return "<img class=\"iconsmall\" src=\"{$display['icon']}\" title=\"{$display['text']}\" alt=\"{$display['text']}\" />";
    }

    // generate status type text
    function rb_display_msgtype_text($comp, $row) {
        global $CFG;
        $display = totara_msg_msgtype_text($row->message_values_msgtype);
        return $display['text'];
    }

    // generate dismiss message link
    function rb_display_dismiss_link($id, $row) {
        return '<div class="totara_notifications_actions">'.
                '<table class="totara_messages_actions" border="0"><tr><td>'.
                totara_msg_dismiss_action($id).
                '</td><td>'.
                '<script type="text/javascript"> '.
                "// bind functionality to page on load
                $(function() {
                    // checkbox
                    (function() {
                        $('#totara_msgcbox_".$id."').css('display','block');
                    })();
                });" .
                '</script>'.
                '<input class="selectbox" name="totara_message_'.$id.'" value="'.$id.'" type="checkbox" id="totara_msgcbox_'.$id.'" style="display:none;"/></td>'.
                '</tr></table>'.
                '</div>';
    }

    // generate reminder message links
    function rb_display_reminder_links($id, $row) {
        return '<div class="totara_reminders_actions">'.
                '<table class="totara_messages_actions" border="0"><tr>'.
                '<td>'.totara_msg_accept_reject_action($id).'</td>'.
                '<td>'.totara_msg_dismiss_action($id).'</td>'.
                '<td>'.
                '<script type="text/javascript"> '.
                "// bind functionality to page on load
                $(function() {
                    // checkbox
                    (function() {
                        $('#totara_msgcbox_".$id."').css('display','block');
                    })();
                });" .
                '</script>'.
                '<input class="selectbox" name="totara_message_'.$id.'" value="'.$id.'" type="checkbox" id="totara_msgcbox_'.$id.'" style="display:none;"/></td>'.
                '</tr></table>'.
                '</div>';
    }


    // generate message checkbox
    function rb_display_message_checkbox($id, $row) {
        return '<input class="selectbox" name="totara_message_'.$id.'" value="'.$id.'" type="checkbox" id="totara_message">';
    }

    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_message_status_list() {
        $statusselect = array();
        $statusselect[TOTARA_MSG_STATUS_OK] = 'Status OK';
        $statusselect[TOTARA_MSG_STATUS_NOTOK] = 'Status not OK';
        $statusselect[TOTARA_MSG_STATUS_UNDECIDED] = 'Status undecided';
        return $statusselect;
    }

    function rb_filter_message_urgency_list() {
        $urgencyselect = array();
        $urgencyselect[TOTARA_MSG_URGENCY_NORMAL] = 'Normal';
        $urgencyselect[TOTARA_MSG_URGENCY_URGENT] = 'Urgent';
        return $urgencyselect;
    }

    function rb_filter_message_type_list() {
        $typeselect = array();
        $typeselect['0'] = 'Unknown';

        return $typeselect;
    }

} // end of rb_source_competency_evidence class
