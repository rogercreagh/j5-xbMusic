<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dataman/default.php
 * @version 0.0.18.8 8th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
// use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

//HTMLHelper::_('behavior.multiselect');
//HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.framework');

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.foldertree')
->useScript('xbmusic.showdown');

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

//$input = Factory::getApplication()->getInput();

?>
<link rel="stylesheet" href="/media/com_xbmusic/css/foldertree.css">
<script type="text/javascript" >
	function confirmImportMp3(){
		if (confirm('This will import from MP3 data\n Are you really sure?')){
			document.getElementById('task').value='dataman.importmp3';
			return true;
		} else {
			return false;
		}
	}
</script>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=dataman'); ?>" method="post" name="adminForm" id="adminForm">
      <input type="hidden" id="basefolder" value="<?php echo $this->basemusicfolder; ?>" />
      <input type="hidden" id="multi" value="1" />
      <input type="hidden" id="extlist" value="mp3" />
      <input type="hidden" id="posturi" value="<?php echo Uri::base(true).'/components/com_xbmusic/vendor/Foldertree.php'; ?>"/>
        <h3>xbMusic Data Manager</h3>

		<div class="main-card">
			<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'import', 'recall' => true]); ?>
    
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'import', Text::_('Import')); ?>
            <p class="xbinfo">
            	<?php echo Text::_('Import tab to import tracks from MP3 file ID3 data by folder or selected files, and to import m3u or pls playlists');?>
            </p>
<details>
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('Import Tracks using ID3 data from music folder/files'); ?></span>
	</summary>	
	<div class="row form-vertical">
		<div class="col-md-6">
			<p><?php echo Text::_('Select folder or tracks')?>
	    	<div id="container"> </div>
        	<p><button id="impmp3" class="btn btn-warning" type="submit" 
        		onclick="if(confirmImportMp3()) {this.form.submit();}" />
        		<i class="icon-upload icon-white"></i> 
        		<?php echo Text::_('Import'); ?>
        	</button>
        	</p>
		</div>
		<div class="col-md-6">
        	<!-- <div id="selected_file">Selected filepath will appear here</div> -->
			<p class="xbinfo"><?php  echo Text::_('If you select a folder then all MP3 files in that folder (not sub-folders) will be imported.')?>
				<br /><?php echo Text::_('If you select one or more files then only those files will be imported')?></p>	
        	<?php echo $this->form->renderField('foldername'); ?> 
        	<?php echo $this->form->renderField('selectedfiles'); ?> 
        	<?php echo $this->form->renderField('filepathname'); ?> 
        	<?php echo $this->form->renderField('filename'); ?> 
         	<?php echo $this->form->renderField('impcat'); ?>
       </div>
	</div>
</details>
<hr />
<details>
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('Import Data from CSV file')?></span>
	</summary>
	<p>tba </p>
</details>
<hr />
<details>
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('Import Playlist from PLS/3U file')?></span>
    </summary>		
	<p>tba </p>
</details>
	
	<hr />
	<h4>Import Logs</h4>
		<div class="row">
			<div class="col-md-6">
				<h4><?php echo Text::_('Log File')?></h4>
				<div class="xbbox gradyellow xbyscroll xbmh300">
					<?php if ($this->log == '') : ?>
						<p><i>no log loaded</i></p>
					<?php else: ?>
						<?php echo $this->log; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="col-md-6">
				<h4><?php echo Text::_('Select Log File')?></h4>
				<?php echo $this->form->renderField('logfile'); ?>
			</div>
		</div>
				
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
                <p class="xbinfo">
                	<?php echo Text::_('Report tab to generate reports of possible data problems (eg multi-song or mutli-artist tracks, orphan artists, missing album/playlist tracks etc')?>
                </p>
<p>Functionality expected here:</p>
<ol>
    <li>Show orphan artists & songs without track</li>
    <li>Show orphan tracks without playlist, album</li>
    
    <li></li>
</ol>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'export', Text::_('Export')); ?>
                <p class="xbinfo">
                	<?php echo Text::_('Export tab to export track, song, artist, and album data to csv and playlists to m3u/pls') ?>
                </p>
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
                <p class="xbinfo">
                	<?php echo Text::_('Delete tab to clean orphans and redundant links and delete selected data')?>
                </p>
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
                <p class="xbinfo">
                	<?php echo Text::_('Azuracast functions');?>
                </p>
<p>Only show tab if azuracast support set in options<br />
Functionality expected here:</p>
<ol>
<li>Display azuracast and station infos</li>
<li>Find tracks listed here but not in azuracast</li>
</ol>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>

            <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        	<hr />
         </div>

		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>

	</form>
    <p>&nbsp;</p>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>