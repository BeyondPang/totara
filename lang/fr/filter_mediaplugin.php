<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'filter_mediaplugin', language 'fr', branch 'MOODLE_22_STABLE'
 *
 * @package   filter_mediaplugin
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['fallbackaudio'] = 'Lien audio';
$string['fallbackvideo'] = 'Lien vidéo';
$string['filtername'] = 'Plugins multimédia';
$string['flashanimation'] = 'Animation Flash';
$string['flashanimation_help'] = 'Fichiers avec l\'extension .swf. Pour des raisons de sécurité, ce filtre n\'est utilisé que dans des textes fiables.';
$string['flashvideo'] = 'Vidéo Flash';
$string['flashvideo_help'] = 'Fichiers avec l\'extension .flv ou .f4v. Permet la lecture de séquences vidéos au moyen du lecteur Flowplayer, qui requiert le plugin Flash et l\'activation de JavaScript. Si plusieurs sources sont spécifiées, utilise la vidéo en HTML5.';
$string['html5audio'] = 'Audio HTML 5';
$string['html5audio_help'] = 'Fichiers audio avec l\'extension *.ogg, *.acc et autres. Ce plugin n\'est compatible qu\'avec les navigateurs les plus récents. Aucun format n\'est malheureusement supporté par tous ces navigateurs.
Pour contourner ce problème, on indiquera des fichiers alternatifs, séparés par # (exemple : http://exemple.fr/audio.ogg#http://exemple.fr/audio.acc#http://exemple.fr/audio.mp3#). Le lecteur QuickTime est utilisé comme solution de remplacement avec d\'anciens navigateurs. Les formats alternatifs peuvent être de n\'importe quel type de fichier audio.';
$string['html5video'] = 'Vidéo HTML 5';
$string['html5video_help'] = 'Fichiers vidéos avec l\'extension *.webm, *.m4v, *.ogv, *.mp4 et autres. Ce plugin n\'est compatible qu\'avec les navigateurs les plus récents. Aucun format n\'est malheureusement supporté par tous ces navigateurs. Pour contourner ce problème, on indiquera des fichiers alternatifs, séparés par # (exemple : http://exemple.fr/video.ogv#http://exemple.fr/video.m4v#http://exemple.fr/video.mp4#d=640x480). Le lecteur QuickTime est utilisé comme solution de remplacement avec d\'anciens navigateurs.';
$string['legacyheading'] = 'Lecteurs média obsolètes';
$string['legacyheading_help'] = 'Les formats suivants ne sont pas recommandés pour un usage habituel. Ils sont plutôt utilisés dans des installation intranet avec des clients gérés de façon centralisée.';
$string['legacyquicktime'] = 'Lecteur QuickTime';
$string['legacyquicktime_help'] = 'Fichiers avec l\'extension *.mov, *.mp4, *.m4a, *.mp4 ou *.mpg. Requier l\'installation du lecteur QuickTime ou de codecs.';
$string['legacyreal'] = 'Lecteur Real media';
$string['legacyreal_help'] = 'Fichiers avec l\'extension *.rm, *.ra, *.ram, *.rp, *.rv. Requiert le lecteur RealPlayer.';
$string['legacywmp'] = 'Lecteur Windows media';
$string['legacywmp_help'] = 'Fichiers avec l\'extension *.avi ou *.wmv. Compatible avec Internet Explorer sous Windows. Peut être problématique dans d\'autres navigateurs et systèmes d\'exploitation.';
$string['mp3audio'] = 'Son MP3';
$string['mp3audio_help'] = 'Fichiers avec l\'extension *.mp3. Permet la lecture de sons au moyen du lecteur Flowplayer. Requier l\'installation du plugin Flash.';
$string['sitevimeo'] = 'Vimeo';
$string['sitevimeo_help'] = 'Site de partage de vidéos Vimeo';
$string['siteyoutube'] = 'YouTube';
$string['siteyoutube_help'] = 'Site de partage de vidéos YouTube. Les listes de vidéos (playlists) sont supportées.';