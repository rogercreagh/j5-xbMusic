/**
 * @package xbmusic
 * @filesource /media/js/closedetails.js
 * @version 0.0.6.14 12th June 2024
 * @desc functions to auto details sections and prevent propogation of clicks
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/
const All_Details = document.querySelectorAll('details');

All_Details.forEach(deet=>{
  deet.addEventListener('toggle', toggleOpenOneOnly)
})

/**
 * @name toggleOpenOneOnly()
 * @description close other details sections when one is opened. 
 * Needs a checkbox (or hidden element) with id="autoclose" and attribute checked
 */
function toggleOpenOneOnly(e) {
  if (document.getElementById('autoclose').checked){
    if (this.open) {
      All_Details.forEach(deet=>{
        if (deet!=this && deet.open) deet.open = false
      });
    }   
  }
}

/**
 * @name stopProp()
 * @description prevents upward propogation of click - use on div or td enclosing details in list table to stop checkbox in col1 getting toggled by open/close details 
 */
function stopProp(event) {
	event.stopPropagation();
}

