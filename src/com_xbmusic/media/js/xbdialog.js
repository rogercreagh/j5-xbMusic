/**
 * @package xbmusic
 * @filesource /media/js/xbdialog.js
 * @version 0.0.61.6 12th April 2026
 * @desc script for confirm button with pass header, body and task. Use as module.
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/
import JoomlaDialog from 'joomla.dialog';

window.doConfirm = function(poptext, pophead, task, targ = '') {
	poptext = poptext + '<br />Are you really sure?'
    JoomlaDialog.confirm(poptext,pophead).then((result) => { 
    if(result) {
		if (targ !='') showEl(targ);
        Joomla.submitbutton(task);
      };
   });
}

window.doAlert = function(poptext,pophead) {
    JoomlaDialog.alert(poptext,pophead); 
}

window.pvItem = function(pophead, view, id = '') {
	let pvclass = 'pv'+view;
	let vars = Joomla.getOptions('com_xbmusic.uri');
	const dialog = new JoomlaDialog({
		popupType: 'iframe',
		textHeader: pophead,
		src: vars.root + 'index.php?option=com_xbmusic&view='+view+'&tmpl=component&layout=modal&id='+id,
		className: pvclass+' xbpvitem',
		height: '60vh',
		width:'67%'
	});
	dialog.addEventListener('joomla-dialog:close', () => {
	    dialog.destroy();
	});	
	dialog.show();
}