/**
 * @package xbmusic
 * @filesource /media/mod_xbimages/js/new-cover.js
 * @version 0.0.2.0 14th February 2026
 * @desc functions to auto details sections and prevent propogation of clicks
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/
if (!window.Joomla) {
  throw new Error('Joomla API was not properly initialised');
}

const { covers } = Joomla.getOptions('mod_xbimages.vars');
const { delay } = Joomla.getOptions('mod_xbimages.vars');
const { albuminfo } = Joomla.getOptions('mod_xbimages.vars');
var n = covers.length;
setInterval(function() {
	var r = Math.floor(Math.random() * n);
	const cover = covers[r];
  	var fpath = cover[0];
  	var tit = cover[1];
  	var art = cover[2];
    document.getElementById('coverimg').src = fpath;
	if ((albuminfo==1) || (albuminfo==3)) {
		document.getElementById('albumtitle').innerText = tit;
	}
	if (albuminfo > 1) {
		document.getElementById('albumartist').innerText = art;
	}
}, delay*1000);
