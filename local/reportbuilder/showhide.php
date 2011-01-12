<?php

/**
 * Page containing column display options, displayed inside show/hide popup dialog
 *
 * @copyright Catalyst IT Limited
 * @author Simon Coggins
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 * @subpackage reportbuilder
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/local/reportbuilder/lib.php');

$id = required_param('id', PARAM_INT);

$report = new reportbuilder($id);
print '<div id="column-checkboxes">';
$count = 0;
foreach($report->columns as $column) {
    // skip empty headings
    if($column->heading == '') {
        continue;
    }
    $ident = "{$column->type}_{$column->value}";
    print '<input type="checkbox" id="'. $ident .'" name="' . $ident . '">';
    print '<label for="' . $ident . '">' . $column->heading.'</label><br />';
    $count++;
}
print '</div>';

?>
<script type="text/javascript">
// set checkbox state based on current column visibility
$('#column-checkboxes input').each(function() {
    var sel = '#' + shortname + ' .' + $(this).attr('name');
    var state = $(sel).css('display');
    var check = (state == 'none') ? false : true;
    $(this).attr('checked', check);
});
// when clicked, toggle visibility of columns
$('#column-checkboxes input').click(function() {
    var selheader = '#' + shortname + ' th.' + $(this).attr('name');
    var sel = '#' + shortname + ' td.' + $(this).attr('name');
    var value = $(this).attr('checked') ? 1 : 0;

    $(selheader).toggle();
    $(sel).toggle();

    $.ajax({
        url: '<?php print $CFG->wwwroot; ?>/local/reportbuilder/showhide_save.php',
        data: {'shortname' : shortname,
               'column' : $(this).attr('name'),
               'value' : value
        }
    });

});
</script>
