<?php 
/*******
 * @package xbMusic getID3
 * @filesource admin/vendor/j5getID3.php
 * @version 0.0.0.1 May 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

require_once(JPATH_COMPONENT_ADMINISTRATOR. '/vendor/getID3/getid3/getid3.php');

function getIdData($filename) {
// Initialize getID3 engine
$getID3 = new getID3;
// Analyze file and store returned data in $ThisFileInfo
$ThisFileInfo = $getID3->analyze($filename);
$getID3->CopyTagsToComments($ThisFileInfo);

return $ThisFileInfo;

}