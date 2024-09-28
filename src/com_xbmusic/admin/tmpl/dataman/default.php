<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dataman/default.php
 * @version 0.0.18.0 25th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

//HTMLHelper::_('behavior.multiselect');
//HTMLHelper::_('formbehavior.chosen', 'select');

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.showdown');

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$input = Factory::getApplication()->getInput();

HTMLHelper::_('jquery.framework');

?>
<link rel="stylesheet" href="/media/com_xbmusic/css/filetree.css">
<script type="text/javascript" >
$(document).ready( function() {

	$( '#container' ).html( '<ul class="filetree start"><li class="wait">' + 'Generating Tree...' + '<li></ul>' );
	
	getfilelist( $('#container') , '<?php echo $this->basemusicfolder; ?>' );
	
	function getfilelist( cont, root ) {
	
		$( cont ).addClass( 'wait' );
			
		$.post( <?php echo "'/administrator/components/com_xbmusic/vendor/Foldertree.php'"; ?>, { dir: root, ext: 'mp3' }, function( data ) {
	
			$( cont ).find( '.start' ).html( '' );
			$( cont ).removeClass( 'wait' ).append( data );
			if( 'Sample' == root ) 
				$( cont ).find('UL:hidden').show();
			else 
				$( cont ).find('UL:hidden').slideDown({ duration: 500, easing: null });
			
		});
	}
	
	var preventry = null;
	var prevvalue = null;
	var basefolder = '<?php echo $this->basemusicfolder; ?>';
	$( '#container' ).on('click', 'LI A', function() {
		var entry = $(this).parent();
		if( entry.hasClass('folder') ) {
            document.getElementById('jform_filepathname').value = null;
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
//        	if (preventry!=null) {preventry.removeClass('selected')};
          	entry.addClass('selected');
          	preventry = entry;
          	prevvalue = document.getElementById('jform_filepathname').value;          	
			document.getElementById('jform_filepathname').value = prevvalue + $(this).attr( 'rel' ).replace(basefolder,'') + "\n";
		}
	return false;
	});
	
});

	function confirmImportMp3(){
		if (confirm('This will import from MP3 data\n Are you really sure?')){
			document.getElementById('task').value='dataman.importmp3';
			return true;
		} else {
			return false;
		}
	}
	
 	function postFolder() {
 		document.getElementById('task').value='track.setfolder';
 		this.form.submit();
 	}
</script>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=dataman'); ?>" method="post" name="adminForm" id="adminForm">
        <h3>xbMusic Data Manager</h3>
        <p class="xbinfo">
        <?php echo Text::_('Import tab to import tracks from MP3 file ID3 data by folder or selected files, and to import m3u or pls playlists');?>
        <br /><?php echo Text::_('Export tab to export track, song, artist, and album data to csv and playlists to m3u/pls') ?>
        <br /><?php echo Text::_('Report tab to generate reports of possible data problems (eg multi-song or mutli-artist tracks, orphan artists, missing album/playlist tracks etc')?>
        <br /><?php echo Text::_('Delete tab to clean/delete selected data types')?>
        </p>

		<div class="main-card">
			<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'import', 'recall' => true]); ?>
    
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'import', Text::_('Import')); ?>
<details>
	<summary><span class="xbr11 xbbold">
		<?php echo Text::_('Import ID3 data from music folder/files'); ?></span>
	</summary>	
		<p class="xbinfo"><?php  echo Text::_('If you select a folder then all MP3 files in that folder (not sub-folders) will be imported.')?>
		<br /><?php echo Text::_('If you select one or more files then only those files will be imported')?></p>	
	<div class="row form-vertical">
		<div class="col-md-6">
			<p><?php echo Text::_('Select folder or tracks')?>
	    	<div id="container"> </div>
		</div>
		<div class="col-md-6">
        	<!-- <div id="selected_file">Selected filepath will appear here</div> -->
        	<p> </p>
        	<?php echo $this->form->renderField('foldername'); ?> 
        	<?php echo $this->form->renderField('filepathname'); ?> 
         	<?php echo $this->form->renderField('defcat_track'); ?>
       </div>
	</div>
	<button id="impmp3" class="btn btn-warning" type="submit" 
		onclick="if(confirmImportMp3()) {this.form.submit();}" />
		<i class="icon-upload icon-white"></i> 
		<?php echo Text::_('Import'); ?>
	</button>
				
</details>
<hr />
<summary><h4><?php echo Text::_('Import Data from CSV file')?></h4>
	<details>
	</details>
</summary>			
<hr />
<summary><h4><?php echo Text::_('Import Playlist from PLS/3U file')?></h4>
	<details>
	</details>
</summary>			
<hr />
<p>Functionality expected here:</p>
<ol>
    <li>id3 import tracks by file or folder
    	<ul>
    		<li>for each file read id3 and get info incl artist album track and song</li>
    		<li>look for existing, if not found </li>
    			<ul>
    				<li>getCreate artist</li>
    				<li>getCreate album</li>
    				<li>getCreate song</li>
    				<li>create track incl link track-album id</li>
    				<li>link artist-album</li>
    				<li>link artist-song</li>
    				<li>link artist-track</li>
    				<li>link song-track</li>
    			</ul>
    	</ul>
    </li>
    <li>Import datatype from csv</li>
    <li>Import playlist</li>
    	<ul>
    		<li>Select type PLS/M3U</li>
    		<li>List missing tracks in warnings box</li>
    	</ul>
    <li></li>
</ol>
          
   			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'report', Text::_('Report')); ?>
<p>Functionality expected here:</p>
<ol>
    <li>Show orphan artists & songs without track</li>
    <li>Show orphan tracks without playlist, album</li>
    
    <li></li>
</ol>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'export', Text::_('Export')); ?>
<p>Functionality expected here:</p>
<ol>
    <li>Export data type to csv</li>
    	<ul>
    		<li>optional select category to export (from those used by the datatype</li>
    	</ul>         
    <li>Export playlist to M3U/PLS</li>
</ol>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'delete', Text::_('Delete')); ?>
<p>Functionality expected here:</p>
<ol>
	<li>Empty trash (by datatype/all</li>
	<li>delete orphans by type</li>
		<ul>
			<li>songs with no track<li>
			<li>tracks with missing file</li>
			<li>albums with no track</li>
			<li>artists with no album or song or track		
		</ul>
<li></li>
</ol>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'azuracast', Text::_('Azuracast')); ?>
<p>Only show tab if azuracast support set in options<br />
Functionality expected here:</p>
<ol>
<li>Display azuracast and station infos</li>
<li>Find tracks listed here but not in azuracast</li>
</ol>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
<p>Functionality expected here:</p>
<ol>

<li></li>
</ol>
            <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        	<hr />
         </div>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>

	</form>
    <p>&nbsp;</p>
    <?php echo XbmusicHelper::credit('xbMusic');?>
</div>