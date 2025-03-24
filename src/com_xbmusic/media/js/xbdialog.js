/**
 * @package xbmusic
 * @filesource /media/js/xbdialog.js
 * @version 0.0.42.6 24th March 2025
 * @desc script for confirm button with pass header, body and task. Use as module.
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/
import JoomlaDialog from 'joomla.dialog';

window.doConfirm = function(poptext,pophead,task) {
	poptext = poptext + '<br />Are you really sure?'
    JoomlaDialog.confirm(poptext,pophead).then((result) => { 
    if(result) {
        Joomla.submitbutton(task);
      };
   });
}

window.doAlert = function(poptext,pophead) {
    JoomlaDialog.alert(poptext,pophead); 
}
