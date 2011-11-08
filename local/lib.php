<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @package totara
 * @subpackage local
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/totara.php');

/**
 * Post-installation setup steps
 * @global object $db
 * @global object $CFG
 * @return boolean success or failure
 */
function local_postinst() {

    global $db, $CFG;
    $olddebug = $db->debug;
    set_config('theme', 'totara');
    set_config("langmenu", 0);
    $db->debug = $CFG->debug;

    /// Insert default records
    $defaultdir = $CFG->dirroot.'/local/db/default';
    $includes = array();
    if (is_dir($defaultdir)) {
        // files installed in alphabetical order so use
        // number prefix to set desired order
        foreach (scandir($defaultdir) as $file) {

            // exclude dot directories
            if ($file == '.' || $file == '..') {
                continue;
            }
            // not a php file
            if (substr($file, -4) != '.php') {
                continue;
            }
            // include default data file
            $includes[] = $CFG->dirroot.'/local/db/default/'.$file;
        }
    }
    // sort so order of includes is known
    sort($includes);
    foreach($includes as $include) {
        include($include);
    }

    totara_reset_frontpage_blocks();
    totara_add_guide_block_to_adminpages();

    // set up frontpage
    set_config('frontpage', '');
    set_config('frontpageloggedin', '');
    set_config('allowvisiblecoursesinhiddencategories', '1');

    rebuild_course_cache(SITEID);

    // ensure page scrolls right to bottom when debugging on
    print "<div></div>";
    return true;
}

/**
 *  * Resize an image to fit within the given rectange, maintaing aspect ratio
 *
 * @param string Path to image
 * @param string Destination file - without file extention
 * @param int Width to resize to
 * @param int Height to resize to
 * @param string Force image to this format
 *
 * @global $CFG
 * @return string Path to new file else false
 */
function resize_image($originalfile, $destination, $newwidth, $newheight, $forcetype = false) {
    global $CFG;

    require_once($CFG->libdir.'/gdlib.php');

    if(!(is_file($originalfile))) {
        return false;
    }

    if (empty($CFG->gdversion)) {
        return false;
    }

    $imageinfo = GetImageSize($originalfile);
    if (empty($imageinfo)) {
        return false;
    }

    $image = new stdClass;

    $image->width  = $imageinfo[0];
    $image->height = $imageinfo[1];
    $image->type   = $imageinfo[2];

    $ratiosrc = $image->width / $image->height;

    if ($newwidth/$newheight > $ratiosrc) {
        $newwidth = $newheight * $ratiosrc;
    } else {
        $newheight = $newwidth / $ratiosrc;
    }

    switch ($image->type) {
    case IMAGETYPE_GIF:
        if (function_exists('ImageCreateFromGIF')) {
            $im = ImageCreateFromGIF($originalfile);
            $outputformat = 'png';
        } else {
            notice('GIF not supported on this server');
            return false;
        }
        break;
    case IMAGETYPE_JPEG:
        if (function_exists('ImageCreateFromJPEG')) {
            $im = ImageCreateFromJPEG($originalfile);
            $outputformat = 'jpeg';
        } else {
            notice('JPEG not supported on this server');
            return false;
        }
        break;
    case IMAGETYPE_PNG:
        if (function_exists('ImageCreateFromPNG')) {
            $im = ImageCreateFromPNG($originalfile);
            $outputformat = 'png';
        } else {
            notice('PNG not supported on this server');
            return false;
        }
        break;
    default:
        return false;
    }

    if ($forcetype) {
        $outputformat = $forcetype;
    }

    $destname = $destination.'.'.$outputformat;

    if (function_exists('ImageCreateTrueColor') and $CFG->gdversion >= 2) {
        $im1 = ImageCreateTrueColor($newwidth,$newheight);
    } else {
        $im1 = ImageCreate($newwidth, $newheight);
    }
    ImageCopyBicubic($im1, $im, 0, 0, 0, 0, $newwidth, $newheight, $image->width, $image->height);

    switch($outputformat) {
    case 'jpeg':
        imagejpeg($im1, $destname, 90);
        break;
    case 'png':
        imagepng($im1, $destname, 9);
        break;
    default:
        return false;
    }
    return $destname;
}


/**
 * hook to add extra sticky-able page types.
 */
function local_get_sticky_pagetypes() {
    return array(
        // not using a constant here because we're doing funky overrides to PAGE_COURSE_VIEW in the learning path format
        // and it clobbers the page mapping having them both defined at the same time
        'Totara' => array(
            'id' => 'Totara',
            'lib' => '/local/lib.php',
            'name' => 'Totara'
        ),
    );
}


/**
 * Determine if the current request is an ajax request
 *
 * @param array $server A $_SERVER array
 * @return boolean
 */
function is_ajax_request($server) {
    return (isset($server['HTTP_X_REQUESTED_WITH']) && strtolower($server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

/**
 * Displays relevant progress bar
 * @param $percent int a percentage value (0-100)
 * @param $size string large, medium...
 * @param $showlabel boolean show completion text label
 * @param $tooltip string required tooltip text
 * @return $out html string
 */
function local_display_progressbar($percent, $size='medium', $showlabel=false, $tooltip='DEFAULTTOOLTIP') {
    global $CFG;

    $percent = round($percent);

    if ($percent < 0 || $percent > 100) {
        return 'progress bar error- invalid value...';
    }

    // Add more sizes if as neccessary :)!
    switch ($size) {
        case 'large' :
            $bar = "{$CFG->pixpath}/t/progressbar-large.gif";
            $bar_background = "{$CFG->pixpath}/t/progressbar_back-large.png";
            $pixelvalue = ($percent / 100) * 121;
            $pixeloffset = round($pixelvalue - 120);
            break;
        case 'medium' :
        default :
            $bar = "{$CFG->pixpath}/t/progressbar-medium.gif";
            $bar_background = "{$CFG->pixpath}/t/progressbar_back-medium.png";
            $pixelvalue = ($percent / 100) * 61;
            $pixeloffset = round($pixelvalue - 60);
            break;
    }

    if ($tooltip == 'DEFAULTTOOLTIP') {
        $tooltip = "{$percent}%";
    }

    if (right_to_left()) {
        // Negate offset and add 1 to
        // fix display in RTL
        $pixeloffset  = -$pixeloffset + 1;
    }

    $out = '';

    $out .= '<img src="'.$bar.'" alt="' . $percent . '%" class="totaraprogressbar"
        style="background: white url('.$bar_background.') top left no-repeat;background-position: ' . $pixeloffset . 'px 0pt;"
        title="'.$tooltip.'" />';
    if ($showlabel) {
        $out .= " $percent % complete<br />\n";
    }

    return $out;
}


?>
