/**
 * @package xbmusic
 * @filesource /media/js/xbtimefunctions.js
 * @version 0.0.51.5 24th April 2025
 * @desc functions do stuff with time strings
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/

/*
function hmstosecs(hms) {
	var a = hms.split(':'); // split it at the colons
	return (+a[0]) * 3600 + (+a[1]) * 60 + (+a[2]); 
}

function hmtosecs(hm) {
	var a = hm.split(':'); // split it at the colons
	return (+a[0]) * 3600 + (+a[1]) * 60; 
}

function secstohms(secs) {
	//only works for values less than 24hrs
	return new Date(secs * 1000).toISOString().substring(11, 19)
}

function stephms() {
	var hms = document.getElementById('filter_starttime').value;
	//step must be in seconds
	var step = document.getElementById('filter_starttime').getAttribute('step');
	
	let end =  19;
	if (hms.length == 5) {
		hms = hms + ":00";
		end = 16;
	}
	var secs = hmstosecs(hms);
	var stepped = step * (Math.trunc(secs/step));
	var newhms = new Date(stepped * 1000).toISOString().substring(11, end)
	document.getElementById('filter_starttime').value = newhms;
}
*/

/**
 * @param el refers to the form field in the document
 * use 'onchange="steptime(this);" in the xml field definition
 */
function steptime(el) {
	var hms = el.value;
	var timeend =  19; //the end of the desired time substring in toISOString()
	//if it is only HH:MM we need to append :00 secs
	if (hms.length == 5) {
		hms = hms + ":00";
                //also adjust the end position to return just HH:MM
		timeend = 16;
	}
	//convert the time string to seconds	
	var a = hms.split(':'); // split it at the colons
	var secs =  (+a[0]) * 3600 + (+a[1]) * 60 + (+a[2]); 
        //if the value is greater than max constrain it to max
	var max = el.getAttribute('max');
	if (max > 0) {
		if (secs > max) secs = max;
	}
	//if value is less than min constrain to min
	var min = el.getAttribute('min');
	if (min > 0) {
		if (secs < min) secs = min;
                //temporarily adjust the time so it is based on min time
		secs = secs - min;
	}
	//if we have a step value we'll force value to the nearest lower step value counting from min
	var step = el.getAttribute('step');
	if (step != null) secs = step * (Math.trunc(secs/step));
	//put back the zero adjustment if we made it
	if (min > 0) secs = secs + min;
	//NB if duration is more than 24hrs gives the time after midnight
	hms = new Date(secs * 1000).toISOString().substring(11, timeend)
	el.value = hms;
}
