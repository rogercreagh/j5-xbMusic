/**
 * @package xbmusic
 * @filesource /media/js/markdownhelper.js
 * @version 0.0.42.5 22nd March 2025
 * @desc functions for hanling markdown in a description field
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/

/**
 * @desc assumes a form field named description
 * @desc converts markdown to html and then strips out html tags leaving plain text
 */
function clearmd() {
	var descMd = document.getElementById('jform_description').value;
	var converter = new showdown.Converter();
    var descHtml = converter.makeHtml(descMd);
	var descText = stripHtml(descHtml);
    document.getElementById('jform_description').value = descText;
    updatePvMd();
}

function stripHtml(html) {
   let doc = new DOMParser().parseFromString(html, 'text/html');
   return doc.body.textContent || "";
}

/**
 * @desc assumes fields name description and pv_desc
 * @desc is called by input eventListener on description field
 * @desc updates the pv_desc field html
 */
function updatePvMd() {
	var descText = document.getElementById('jform_description').value;
	var converter = new showdown.Converter();
    var descHtml = converter.makeHtml(descText);
	document.getElementById('pv_desc').innerHTML= descHtml;
}

