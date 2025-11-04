/**
 * @package xbmusic
 * @filesource /media/js/closedetails.js
 * @version 0.0.59.2 3rd November 2025
 * @desc functions to auto details sections and prevent propogation of clicks
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/
const All_Details = document.querySelectorAll('details');

/**
 * to save open details state for duration of session set an id string for each <details> tag on page
 * NB opening a details section on a different page will overwrite the reurn state for the previous page
 */
deetopen = sessionStorage.getItem('deetopen');

All_Details.forEach(deet=>{
	if(deet.id == deetopen) {
	  deet.open = true;
	  }
  deet.addEventListener('toggle', toggleOpenOneOnly)
})

/**
 * @name toggleOpenOneOnly()
 * @description close other details sections when one is opened. 
 * Set autoclose with an input type checkbox with id="autoclose".
 * checkbox enables user to toggle the autoclose, if not present then autoclose always on
 */
function toggleOpenOneOnly(e) {
	var checkbox = document.getElementById('autoclose');
	if (!checkbox || checkbox.checked){
		if (this.open) {
			All_Details.forEach(deet=>{
				if (deet!=this && deet.open) deet.open = false
			});
	  		sessionStorage.setItem('deetopen', this.id);
    	} else {
			sessionStorage.removeItem('deetopen');
		}  
  	}
}

/**
 * @name stopProp()
 * @description prevents upward propogation of click - 
 * eg in list table views where Joomla click anywhere on row toggles the select checkbox in column 1
 * set enclosing element to onclick="stopProp(event);" to prevent clicks propogating up
 * useful where details elements are in a table column
 */
function stopProp(event) {
	event.stopPropagation();
}

