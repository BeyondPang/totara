<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @package totara
 * @subpackage plan
 */

require_once '../../../../config.php';
require_login();
$save_string = get_string('save');
$cancel_string = get_string('cancel');

?>

// Bind functionality to page on load
$(function() {

    /// Find course prerequisites
    ///
    (function() {
        var url = '<?php echo $CFG->wwwroot ?>/local/plan/components/competency/';
        var saveurl = url + 'update-course.php?planid='+plan_id+'&competencyid='+competency_id+'&update=';

        var handler = new totaraDialog_handler_preRequisite();
        handler.baseurl = url;

        totaraDialogs['evidence'] = new totaraDialog(
            'assigncourses',
            'show-course-dialog',
            {
                 buttons: {
                     '<?php echo $cancel_string ?>': function() { handler._cancel() },
                     '<?php echo $save_string ?>': function() { handler._save(saveurl) }
                 },
                title: '<?php
                    echo '<h2>';
                    echo get_string('addlinkedcourses', 'local_plan');
                    echo '</h2>';
                ?>'
            },
            url+'find-course.php?planid='+plan_id+'&competencyid='+competency_id,
            handler
        );
    })();

});

// Create handler for the dialog
totaraDialog_handler_preRequisite = function() {
    // Base url
    var baseurl = '';
}

totaraDialog_handler_preRequisite.prototype = new totaraDialog_handler_treeview_multiselect();

/**
 * Add a row to a table on the calling page
 * Also hides the dialog and any no item notice
 *
 * @param string    HTML response
 * @return void
 */
totaraDialog_handler_preRequisite.prototype._update = function(response) {

    // Hide dialog
    this._dialog.hide();

    // Remove no item warning (if exists)
    $('.noitems-'+this._title).remove();

    // Grab table
    var table = $('div#content table.dp-plan-component-items');

    // If table found
    if (table.size()) {
        table.replaceWith(response);
    }
    else {
        // Add new table
        $('div#content div#dp-competency-courses-container').prepend(response);
    }
}