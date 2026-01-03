/**
 * @package xbmusic
 * @filesource /media/js/xbgeneral.js
 * @version 0.0.59.17 21st December 2025
 * @desc general useful functions
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/

/** 
 * @name showEl()
 * @description shows a  hidden (display=none) element, optional message to display in elid waitmessage
 * @param {string} targ - the id of the target element
 * @param {string} [mess=''] optional message to be displayed
 */
function showEl(targ, mess = '') {
	let el = document.getElementById(targ);
	if (mess != '') {
		let messel = document.getElementById('waitmessage');
		if ((messel != null) && (el.contains(messel))) {
			messel.innerHTML = mess;
		}
	}
	el.style.display = 'block';
}

/**
 * @name hideEl()
 * @description hides an element
 * @param {string} targ - the id of the target element
 */
function hideEl(targ) {
	document.getElementById(targ).style.display = 'none';
}

/**
 * @name setElDisplay()
 * @description sets elements display css property
 * @param {string} targ - the target element id
 * @param {string} disp - they value to set style.display 
 */
function setElDisplay(targ, disp) {
	document.getElementById(targ).style.display == disp;
}

/**
 * @name toggleElDisplay()
 * @description swaps elements display state between none and block
 * @param {string} targ - the id of the target element
 */
function toggleEl(targ) {
	let el = document.getElementById(targ);
	if (el.style.display == 'none') {
		el.style.display = 'block';
	} else {
		el.style.display = 'none';
	}
}
function setElTranslate(targ, text) {
	let el = document.getElementById(targ);
	el.innerHTML = Joomla.JText._(text);
}
