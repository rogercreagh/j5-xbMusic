/**
 * @package xbmusic
 * @filesource /media/js/playlisthelper.js
 * @version 0.0.42.6 24th March 2025
 * @desc functions for playlist actions
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/

/**
 * @description gets to value for the selected item in playlists control and submits form with task loadplaylist
 */
function loadplaylist(azid) {
	document.getElementById('jform_az_id').value = azid;
	document.getElementById('jform_az_dbstid').value = document.getElementById('jform_azstation').value;
	Joomla.submitform('playlist.loadplaylist',document.getElementById('item-form'));
}

