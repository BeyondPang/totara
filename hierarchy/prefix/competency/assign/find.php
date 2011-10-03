<?php

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/local/js/lib/setup.php');

///
/// Setup / loading data
///

// Parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// competency evidence id - may be 0 for new competency evidence
$id = optional_param('id', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// should we show hidden frameworks?
$showhidden = optional_param('showhidden', false, PARAM_BOOL);

// check they have permissions on hidden frameworks in case parameter is changed manually
$context = get_context_instance(CONTEXT_SYSTEM);
if ($showhidden && !has_capability('moodle/local:updatecompetencyframeworks', $context)) {
    print_error('nopermviewhiddenframeworks', 'hierarchy');
}

// No javascript parameters
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_TEXT);
$s = optional_param('s', '', PARAM_TEXT);

// string of params needed in non-js url strings
$urlparams = 'id='.$id.'&amp;frameworkid='.$frameworkid.'&amp;nojs='.$nojs.'&amp;returnurl='.urlencode($returnurl).'&amp;s='.$s;

// Setup page
admin_externalpage_setup('competencymanage', '', array(), '', $CFG->wwwroot.'/competency/evidence/add.php');

///
/// Display page
///

if(!$nojs) {
    // Load dialog content generator
    $dialog = new totara_dialog_content_hierarchy('competency', $frameworkid, $showhidden);

    // Toggle treeview only display
    $dialog->show_treeview_only = $treeonly;

    // Load items to display
    $dialog->load_items($parentid);

    // Display
    echo $dialog->generate_markup();

} else {
    // non JS version of page
    // Check permissions
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    require_capability('moodle/local:updatecompetency', $sitecontext);

    // Setup hierarchy object
    $hierarchy = new competency();

    // Load framework
    if (!$framework = $hierarchy->get_framework($frameworkid, $showhidden)) {
        error('Competency framework could not be found');
    }

    // Load competencies to display
    $competencies = $hierarchy->get_items_by_parent($parentid);

    admin_externalpage_print_header();
    echo '<h2>'.get_string('assigncompetency', $hierarchy->prefix).'</h2>';

    echo '<p><a href="'.$returnurl.'">'.get_string('cancelwithoutassigning','hierarchy').'</a></p>';

    if(empty($frameworkid) || $frameworkid == 0) {

        echo build_nojs_frameworkpicker(
            $hierarchy,
            $CFG->wwwroot.'/hierarchy/prefix/competency/assign/find.php',
            array(
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => 1,
                'id' => $id,
                'frameworkid' => $frameworkid,
            )
        );

    } else {
        ?>
<div id="nojsinstructions">
<?php
        echo build_nojs_breadcrumbs($hierarchy,
            $parentid,
            $CFG->wwwroot.'/hierarchy/prefix/competency/assign/find.php',
            array(
                'id' => $id,
                'returnurl' => $returnurl,
                's' => $s,
                'nojs' => $nojs,
                'frameworkid' => $frameworkid,
            )
        );

?>
<p>
<?php echo  get_string('clicktoassign', $hierarchy->prefix).' '.
            get_string('clicktoviewchildren', $hierarchy->prefix) ?>
</p>
</div>
<div class="nojsselect">
<?php
         echo build_nojs_treeview(
            $competencies,
            get_string('nochildcompetenciesfound', 'competency'),
            $CFG->wwwroot.'/hierarchy/prefix/competency/assign/save.php',
            array(
                's' => $s,
                'returnurl' => $returnurl,
                'nojs' => 1,
                'frameworkid' => $frameworkid,
                'id' => $id,
            ),
            $CFG->wwwroot.'/hierarchy/prefix/competency/assign/find.php?'.$urlparams,
            $hierarchy->get_all_parents()
        );

?>
</div>
<?php
    }

    print_footer();

}
