<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010-2012 Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

require_once '../../config.php';
$id = optional_param('id', 0, PARAM_INT);
$save_string = get_string('save');
$cancel_string = get_string('cancel');

?>

// Bind functionality to page on load
$(function() {

    ///
    /// Add related competency dialog
    ///
    (function() {
        var url = '<?php echo $CFG->wwwroot ?>/hierarchy/prefix/competency/related/';

        totaraMultiSelectDialog(
            'related',
            '<?php echo get_string('assignrelatedcompetencies', 'competency'); ?>',
            url+'find.php?id=<?php echo $id;?>',
            url+'save.php?id=<?php echo $id;?>&deleteexisting=1&add='
        );
    })();

<?php if (!empty($CFG->competencyuseresourcelevelevidence)) { ?>
    ///
    /// Assign evidence item dialog (resource-level)
    ///
    (function() {
        var url = '<?php echo $CFG->wwwroot ?>/hierarchy/prefix/competency/evidenceitem/';

        var handler = new totaraDialog_handler_assignEvidence();
        handler.baseurl = url;

        totaraDialogs['evidence'] = new totaraDialog(
            'evidence',
            'show-evidence-dialog',
            {
                buttons: {
                    '<?php echo $cancel_string ?>': function() { handler._cancel() }
                },
                title: '<?php echo '<h2>'.get_string('assignnewevidenceitem', 'competency').'</h2>'; ?>'
            },
            url+'edit.php?id=<?php echo $id;?>',
            handler
        );
    })();
<?php } ?>
});
<?php if (!empty($CFG->competencyuseresourcelevelevidence)) { ?>
    // Create handler for the assign evidence dialog
    totaraDialog_handler_assignEvidence = function() {
        // Base url
        var baseurl = '';
    }

    totaraDialog_handler_assignEvidence.prototype = new totaraDialog_handler_treeview();


    totaraDialog_handler_assignEvidence.prototype._handle_update_hierarchy = function(list) {
        var handler = this;
        $('span', list).click(function() {
            var par = $(this).parent();

            // Get the id in format item_list_XX
            var id = par.attr('id').substr(10);

            // Check it's not a category
            if (id.substr(0, 3) == 'cat') {
                return;
            }

            handler._handle_course_click(id);
        });
    }

    totaraDialog_handler_assignEvidence.prototype._handle_course_click = function(id) {
        // Load course details
        var url = this.baseurl+'course.php?id='+id+'&competency=<?php echo $id;?>';

        // Indicate loading...
        this._dialog.showLoading();

        this._dialog._request(url, this, '_display_evidence');
    }

    /**
     * Display course evidence items
     *
     * @param string    HTML response
     */
    totaraDialog_handler_assignEvidence.prototype._display_evidence = function(response) {
        this._dialog.hideLoading();

        $('.selected', this._dialog.dialog).html(response);

        var handler = this;

        // Bind click event
        $('#available-evidence', this._dialog.dialog).find('.addbutton').click(function(e) {
            e.preventDefault();
            var type = $(this).parent().attr('type');
            var instance = $(this).parent().attr('id');
            var url = handler.baseurl+'add.php?competency=<?php echo $id;?>&type='+type+'&instance='+instance;
            handler._dialog._request(url, handler, '_update');
        });

    }
<?php } else { // use course-level dialog ?>

    // Bind functionality to page on load
    $(function() {

        (function() {
            var url = '<?php echo $CFG->wwwroot ?>/hierarchy/prefix/competency/evidenceitem/';
            var saveurl = url + 'add.php?competency=<?php echo $id; ?>&type=coursecompletion&instance=0&deleteexisting=1&update=';

            var handler = new totaraDialog_handler_compEvidence();
            handler.baseurl = url;

            totaraDialogs['evidence'] = new totaraDialog(
                'evidence',
                'show-evidence-dialog',
                {
                     buttons: {
                        '<?php echo $cancel_string ?>': function() { handler._cancel() },
                        '<?php echo $save_string ?>': function() { handler._save(saveurl) }
                     },
                    title: '<?php
                        echo '<h2>';
                        echo get_string('assigncoursecompletions', 'competency');
                        echo '</h2>';
                    ?>'
                },
                url+'edit.php?id='+<?php echo $id; ?>,
                handler
            );
        })();

    });

    // Create handler for the dialog
    totaraDialog_handler_compEvidence = function() {
        // Base url
        var baseurl = '';
    }

    totaraDialog_handler_compEvidence.prototype = new totaraDialog_handler_treeview_multiselect();

    /**
     * Add a row to a table on the calling page
     * Also hides the dialog and any no item notice
     *
     * @param string    HTML response
     * @return void
     */
    totaraDialog_handler_compEvidence.prototype._update = function(response) {

        // Hide dialog
        this._dialog.hide();

        // Remove no item warning (if exists)
        $('.noitems-'+this._title).remove();

        //Split response into table and div
        var new_table = $(response).find('table.list-evidence');

        // Grab table
        var table = $('div#content table.list-evidence');

        // If table found
        if (table.size()) {
            table.replaceWith(new_table);
        }
        else {
            // Add new table
            $('div#content div#evidence-list-container').append(new_table);
        }
    }
<?php } ?>
