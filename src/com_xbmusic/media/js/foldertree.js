/**
 * @package xbmusic
 * @filesource /media/js/foldertree.js
 * @version 0.0.18.6 3rd November 2024
 * @desc used by filetree element
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * 
**/

$(document).ready( function() {

	var basefolder = document.getElementById('basefolder').value; //'<?php echo $this->basemusicfolder; ?>';
	var extlist = document.getElementById('extlist').value;
	var posturi = document.getElementById('posturi').value;
	var multi = document.getElementById('multi').value;
	$( '#container' ).html( '<ul class="filetree start"><li class="wait">' + 'Generating Tree...' + '<li></ul>' );
	
	getfilelist( $('#container') , basefolder  );
	
	function getfilelist( cont, root ) {
	
		$( cont ).addClass( 'wait' );
			
		$.post( posturi, { dir: root, ext: extlist }, function( data ) {
//		$.post( <?php echo "'/administrator/components/com_xbmusic/vendor/Foldertree.php'"; ?>, { dir: root, ext: 'mp3' }, function( data ) {
	
			$( cont ).find( '.start' ).html( '' );
			$( cont ).removeClass( 'wait' ).append( data );
			if( 'Sample' == root ) 
				$( cont ).find('UL:hidden').show();
			else 
				$( cont ).find('UL:hidden').slideDown({ duration: 500, easing: null });
			
		});
	}
	
	var prevfolder = null;
	var preventry = null;
	var prevvalue = null;
	$( '#container' ).on('click', 'LI A', function() {
		var entry = $(this).parent();
		if( entry.hasClass('folder') ) {
        	if (prevfolder!=null) {
              prevfolder.removeClass('selected');
              if ((!prevfolder.has(entry).length >0) && prevfolder.hasClass('expanded')) {
                  prevfolder.find('UL').slideUp({ duration: 500, easing: null });
                  prevfolder.removeClass('expanded').addClass('collapsed');         
              }
            };
          	entry.addClass('selected');
			prevfolder = entry;
            document.getElementById('jform_selectedfiles').value = null;
			document.getElementById('jform_foldername').value = $(this).attr( 'rel' ).replace(basefolder,'');
			if( entry.hasClass('collapsed') ) {						
				entry.find('UL').remove();
				getfilelist( entry, escape( $(this).attr('rel') ));
				entry.removeClass('collapsed').addClass('expanded');
			}
			else {
				entry.find('UL').slideUp({ duration: 500, easing: null });
				entry.removeClass('expanded').addClass('collapsed');
			}
		} else {
          	entry.addClass('selected');
        	if (prevfolder!=null) {prevfolder.removeClass('selected')};
          	if (multi == 1) {
	          	prevvalue = document.getElementById('jform_selectedfiles').value;          	
	//			document.getElementById('jform_filepathname').value = prevvalue + $(this).attr( 'rel' ).replace(basefolder,'') + "\n";
				document.getElementById('jform_selectedfiles').value = prevvalue + $(this).attr('rel').split('\\').pop().split('/').pop() + "\n";
			} else {	  
	        	if (preventry!=null) {preventry.removeClass('selected')};
				document.getElementById('jform_selectedfiles').value = $(this).attr( 'rel' ).split('\\').pop().split('/').pop();
			}
          	preventry = entry;
		}
	return false;
	});
	
});


