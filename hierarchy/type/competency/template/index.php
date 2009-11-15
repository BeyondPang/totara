<?php

require_once('../../../../config.php');
require_once('../lib.php');
require_once($CFG->libdir.'/adminlib.php');

///
/// Setup / loading data
///

$sitecontext = get_context_instance(CONTEXT_SYSTEM);

// Get params
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$hide        = optional_param('hide', 0, PARAM_INT);
$show        = optional_param('show', 0, PARAM_INT);
$moveup      = optional_param('moveup', 0, PARAM_INT);
$movedown    = optional_param('movedown', 0, PARAM_INT);

// Get hierarchy object
$hierarchy = new competency();

// Load framework
$framework  = $hierarchy->get_framework($frameworkid);

// Cache user capabilities
$can_add = has_capability('moodle/local:create'.$hierarchy->prefix.'template', $sitecontext);
$can_edit = has_capability('moodle/local:update'.$hierarchy->prefix.'template', $sitecontext);
$can_delete = has_capability('moodle/local:delete'.$hierarchy->prefix.'template', $sitecontext);

if ($can_add || $can_edit || $can_delete) {
    $navbaritem = $hierarchy->get_editing_button($edit);
    $editingon = !empty($USER->{$hierarchy->prefix.'editing'});
} else {
    $navbaritem = '';
}

// Setup page and check permissions
admin_externalpage_setup($hierarchy->prefix.'templatemanage', $navbaritem);


///
/// Process any actions
///

if ($editingon) {
    // Hide or show a framework
    if ($hide or $show) {
        require_capability('moodle/local:update'.$hierarchy->prefix.'template', $sitecontext);
        // Hide an item
        if ($hide) {
            $hierarchy->hide_template($hide);
        } elseif ($show) {
            $hierarchy->show_template($show);
        }
    }

} // End of editing stuff


///
/// Load framework templates after any changes
///

// Get templates for this framework
$templates = $hierarchy->get_templates();


///
/// Generate / display page
///
$str_edit     = get_string('edit');
$str_delete   = get_string('delete');
$str_hide     = get_string('hide');
$str_show     = get_string('show');

if ($templates) {

    // Create display table
    $table = new stdclass();
    $table->class = 'generalbox edit'.$hierarchy->prefix;
    $table->width = '95%';

    // Setup column headers
    $table->head = array();
    $table->align = array();
    $table->head[] = get_string('template', $hierarchy->prefix);
    $table->align[] = 'left';
    $table->head[] = get_string('competencies', $hierarchy->prefix);
    $table->align[] = 'center';
    $table->head[] = get_string('positions', $hierarchy->prefix);
    $table->align[] = 'center';
    $table->head[] = get_string('users');
    $table->align[] = 'center';
    $table->head[] = get_string('createdon', $hierarchy->prefix);
    $table->align[] = 'left';

    // Add edit column
    if ($editingon && $can_edit) {
        $table->head[] = get_string('edit');
        $table->align[] = 'center';
    }

    // Add rows to table
    foreach ($templates as $template) {
        $row = array();

        $cssclass = !$template->visible ? 'class="dimmed"' : '';

        $row[] = "<a $cssclass href=\"{$CFG->wwwroot}/hierarchy/type/{$hierarchy->prefix}/template/view.php?id={$template->id}\">{$template->fullname}</a>";
        $row[] = "<a $cssclass href=\"{$CFG->wwwroot}/hierarchy/type/{$hierarchy->prefix}/template/view.php?id={$template->id}\">{$template->competencycount}</a>";
        $row[] = '0';
        $row[] = '0';
        $row[] = userdate($template->timecreated, '%A, %e %B %Y');

        // Add edit link
        $buttons = array();
        if ($editingon && $can_edit) {
            $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/type/{$hierarchy->prefix}/template/edit.php?id={$template->id}\" title=\"$str_edit\">".
                "<img src=\"{$CFG->pixpath}/t/edit.gif\" class=\"iconsmall\" alt=\"$str_edit\" /></a>";
            if ($template->visible) {
                $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/type/{$hierarchy->prefix}/template/index.php?hide={$template->id}\" title=\"$str_hide\">".
                    "<img src=\"{$CFG->pixpath}/t/hide.gif\" class=\"iconsmall\" alt=\"$str_hide\" /></a>";
            } else {
                $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/type/{$hierarchy->prefix}/template/index.php?show={$template->id}\" title=\"$str_show\">".
                    "<img src=\"{$CFG->pixpath}/t/show.gif\" class=\"iconsmall\" alt=\"$str_show\" /></a>";
            }
        }
        if ($editingon && $can_delete) {
            $buttons[] = "<a href=\"{$CFG->wwwroot}/hierarchy/type/{$hierarchy->prefix}/template/delete.php?id={$template->id}\" title=\"$str_delete\">".
                "<img src=\"{$CFG->pixpath}/t/delete.gif\" class=\"iconsmall\" alt=\"$str_delete\" /></a>";
        }

        if ($buttons) {
            $row[] = implode($buttons, ' ');
        }

        $table->data[] = $row;
    }
}


// Display page
admin_externalpage_print_header();

$hierarchy->display_framework_selector('template/index.php');

if ($templates) {
    print_table($table);
} else {
    print_heading(get_string('notemplates', $hierarchy->prefix));
}


// Editing buttons
if ($can_add) {
    echo '<div class="buttons">';

    // Print button for creating new template
    $data = array('frameworkid' => $framework->id);
    print_single_button($CFG->wwwroot.'/hierarchy/type/'.$hierarchy->prefix.'/template/edit.php', $data, get_string('addnewtemplate', $hierarchy->prefix), 'get');

    echo '</div>';
}

print_footer();
