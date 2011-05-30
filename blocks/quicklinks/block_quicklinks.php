<?php
/**
 * Block for displaying user-defined links
 *
 * @package   totara
 * @copyright 2010 Totara Learning Solutions Ltd
 * @author    Eugene Venter <aaronb@catalyst.net.nz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_quicklinks extends block_base {

    function init() {
        $this->title = get_string('quicklinks', 'block_quicklinks');
        $this->version = 2010111000;
    }

    function preferred_width() {
        return 210;
    }

    function specialization() {
        // After the block has been loaded we customize the block's title display
        if (!empty($this->config) && !empty($this->config->title)) {
            // There is a customized block title, display it
            $this->title = $this->config->title;
        } else {
            // No customized block title, use localized remote news feed string
            $this->title = get_string('quicklinks', 'block_quicklinks');
        }
    }

    function get_content() {
        global $CFG, $USER;

        // Check if content is cached
        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            // We're being asked for content without an associated instance
            return $this->content;
        }

        if (empty($this->instance->pinned)) {
            $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
        } else {
            $context = get_context_instance(CONTEXT_SYSTEM); // pinned blocks do not have own context
        }

        $html = '';

        // Get links to display
        $links = get_records('block_quicklinks', 'block_instance_id', $this->instance->id, 'displaypos');
        $links = empty ($links) ? array() : $links;

	$html .= '<table><tbody>';
	$counter = 0;
        foreach ($links as $l) {
	    $class = ($counter % 2) ? 'noshade' : 'shade';
	    $counter++;
            $html .= '<tr class="'.$class.'"><td class="linkicon"></td><td class="linktext"><p><a href="'.format_string($l->url).'">'.$this->format_title($l->title).'</a></p></td></tr>';
        }
	$html .= '</tbody></table>';

        $this->content->text = $html;

        return $this->content;
    }

    function instance_allow_multiple() {
        return true;
    }

    function instance_allow_config() {
        $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);

        if (instance_is_dashlet($this)) {
            return (has_capability('block/quicklinks:manageownlinks', $context) || has_capability('block/quicklinks:managealllinks', $context));
        } else {
            return has_capability('block/quicklinks:managealllinks', $context);
        }
    }

    function instance_config_save($data) {
        global $USER;

        if (!empty($data->btnCancel)) {
            // Do nothing
            return true;
        }
        if (!empty($data->url)) {
            $addlink = isset($data->btnAddLink);
            if (empty($data->linktitle)) {
                if (!empty($data->url)) {
                    $data->linktitle = $data->url;
                }
            }
           // Save the block link
           $link = new stdClass;
           $link->userid = instance_is_dashlet($this) ? $USER->id : 0;
           $link->block_instance_id = $this->instance->id;
           $link->title = empty($data->linktitle) ? $data->url : $data->linktitle;
           $link->url = $data->url;
           $link->displaypos = count_records('block_quicklinks', 'block_instance_id', $this->instance->id) > 0 ? get_field('block_quicklinks', 'MAX(displaypos)+1', 'block_instance_id', $this->instance->id) : 0;
           insert_record('block_quicklinks', $link);
           unset($link);
        }


        unset($data->btnAddLink, $data->linktitle, $data->url);
        if (parent::instance_config_save($data)) {
            if (!empty($addlink)) {
                // HACK: redirect back to the same page
                redirect(get_referer(false));
            } else {
                return true;
            }
        }
    }

    function instance_create() {
        global $CFG, $USER;

        // Add some default quicklinks
        $links = array();
        if (instance_is_dashlet($this)) {


            // Insert default links, according to role
            $role = get_dashlet_role($this->instance->pageid);
            $shortname = ($role == 'manager') ? 'myteam' : 'mylearning';

            switch ($role) {
                case 'admin':
                case 'administrator' :
                    $links = array(get_string('home','block_quicklinks')=>"{$CFG->wwwroot}/index.php",
                        get_string('logs','block_quicklinks')=>"{$CFG->wwwroot}/course/report/log/index.php",
                        get_string('managereports','block_quicklinks')=>"{$CFG->wwwroot}/local/reportbuilder/index.php");
                    break;
                case 'manager' :
                case 'student' :
                    $sql = "SELECT blocki.id FROM
                        {$CFG->prefix}dashb_instance i
                        JOIN
                            {$CFG->prefix}dashb db
                                ON i.dashb_id=db.id
                        JOIN
                            {$CFG->prefix}dashb_instance_dashlet dbid
                                ON dbid.dashb_instance_id = i.id
                        JOIN
                            {$CFG->prefix}block_instance blocki
                                ON blocki.id = dbid.block_instance_id
                        JOIN
                            {$CFG->prefix}block b
                                ON b.id = blocki.blockid
                        WHERE shortname='{$shortname}'
                          AND userid=0
                          AND b.name='quicklinks'";

                    if ($default_block_instance_id = get_field_sql($sql)) {
                        $links = get_records_menu('block_quicklinks', 'block_instance_id', $default_block_instance_id, 'displaypos', 'title, url');
                    } else {
                        $links = array(get_string('home','block_quicklinks')=>"{$CFG->wwwroot}/index.php",
                            get_string('reports','block_quicklinks')=>"{$CFG->wwwroot}/my/reports.php",
                            get_string('courses','block_quicklinks')=>"{$CFG->wwwroot}/course/find.php");
                    }
                    break;
                default:
                    $links = array(get_string('home','block_quicklinks')=>"{$CFG->wwwroot}/index.php",
                        get_string('reports','block_quicklinks')=>"{$CFG->wwwroot}/my/reports.php",
                        get_string('courses','block_quicklinks')=>"{$CFG->wwwroot}/course/find.php");
                    break;
            }
        } else {
            // Insert global default links
            $links = array(get_string('home','block_quicklinks')=>"{$CFG->wwwroot}/index.php",
                get_string('reports','block_quicklinks')=>"{$CFG->wwwroot}/my/reports.php",
                get_string('courses','block_quicklinks')=>"{$CFG->wwwroot}/course/find.php");
        }

        $poscount = 0;
        foreach ($links as $title=>$url) {
            $link = new stdClass;
            $link->block_instance_id = $this->instance->id;
            $link->title = $title;
            $link->url = $url;
            $link->displaypos = $poscount;
            $link->userid = instance_is_dashlet($this) ? $USER->id : 0;
            insert_record('block_quicklinks', $link);
            $poscount++;
        }

        return true;

    }

    function instance_delete() {
        // Do some additional cleanup
        delete_records('block_quicklinks', 'block_instance_id', $this->instance->id);

        return true;
    }

    // Strips the title down and adds '...' for excessively long titles.
    function format_title($title,$max=64) {

    /// Loading the textlib singleton instance. We are going to need it.
        $textlib = textlib_get_instance();

        if ($textlib->strlen($title) <= $max) {
            return s($title);
        } else {
            return s($textlib->substr($title,0,$max-3).'...');
        }
    }
}

?>
