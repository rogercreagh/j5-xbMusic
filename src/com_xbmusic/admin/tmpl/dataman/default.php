<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dataman/default.php
 * @version 0.0.41.4 4th March 2025
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
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

//HTMLHelper::_('behavior.multiselect');
//HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.framework');

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.foldertree')
->useScript('xbmusic.showdown');
$wa->useScript('joomla.dialog')

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

//$input = Factory::getApplication()->getInput();

?>
<link rel="stylesheet" href="<?php echo Uri::root(true);?>/media/com_xbmusic/css/foldertree.css">
<script type="module" >
    import JoomlaDialog from 'joomla.dialog';

    window.doConfirm = function(poptext,pophead,task) {
        JoomlaDialog.confirm(poptext,pophead).then((result) => { 
        if(result) {
            Joomla.submitbutton('dataman.'+task);
          };
       });
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
	<div>
            <p class="xbinfo">
            	<?php echo Text::_('Import tab to import tracks from MP3 file ID3 data by folder or selected files, and to import m3u or pls playlists');?>
            </p>
<details>
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('Import Tracks using ID3 data from music folder/files'); ?></span>
	</summary>	
	<div class="row form-vertical">
		<div class="col-md-6">
			<p><?php echo Text::_('XBMUSIC_SELECT_FOLDER')?>
	    	<div id="container"> </div>
	    	<p>
	         	<?php echo $this->form->renderField('impcat'); ?>
	        </p>
	        <p>
	         	<?php echo $this->form->renderField('splitsongs'); ?>
			</p>	    	
	        <p>
	         	<?php echo $this->form->renderField('nobrackets'); ?>
			</p>	    	
	    	<?php $popbody = '<br />Are you really sure?'; 
	    	  $pophead = 'Confirm Import from MP3'; 
	    	  $confirm = "doConfirm('<i>Import from </i>'+document.getElementById('jform_foldername').value+
        			'".$popbody."','".$pophead."','importmp3');"; 
	    	  ?>
	    	 <p><button id="impmp3" class="btn btn-warning" type="button" 
        		onclick="<?php echo $confirm; ?>" >
					<i class="icon-upload icon-white"></i> 
        		<?php echo Text::_('XB_IMPORT'); ?>
        		</button>        		
			</p>
		</div>
		<div class="col-md-6">
        	<!-- <div id="selected_file">Selected filepath will appear here</div> -->
			<p class="xbinfo"><?php  echo Text::_('XBMUSIC_IMPORT_NOTE1')?>
				<br /><?php echo Text::_('XBMUSIC_IMPORT_NOTE2')?></p>	
        	<?php echo $this->form->renderField('foldername'); ?> 
        	<?php echo $this->form->renderField('selectedfiles'); ?> 
        	<?php echo $this->form->renderField('filepathname'); ?> 
        	<?php echo $this->form->renderField('filename'); ?> 
       </div>
	</div>
</details>
<hr />
<details>
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('Import Data from CSV file')?></span>
	</summary>
	<p>tba </p>
<p>Functionality expected here:</p>
<ol>
    <li>Import datatype from csv</li>
</ol>
</details>
<hr />
<details>
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('Import Playlist from PLS/3U file')?></span>
    </summary>		
<p>Functionality expected here:</p>
<ol>
    <li>Import playlist
    	<ul>
    		<li>Select type PLS/M3U</li>
    		<li>List missing tracks in warnings box</li>
    	</ul>
    </li>
    <li></li>
</ol>
	<p>tba </p>
</details>
	
	<hr />
	<h4>Import Logs</h4>
		<div class="row">
			<div class="col-md-6">
				<h4><?php echo Text::_('XB_LOG_FILE')?></h4>
				<div class="xbbox gradyellow xbyscroll xbmh300">
					<?php if ($this->log == '') : ?>
						<p><i>no log loaded</i></p>
					<?php else: ?>
						<?php echo $this->log; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="col-md-6">
				<h4><?php echo Text::_('XB_SELECT_LOG_FILE')?></h4>
				<?php echo $this->form->renderField('logfile'); ?>
			</div>
		</div>
				
<hr />
          
	</div>
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
<?php if ($this->azuracast == 1) : ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'azuracast', Text::_('Azuracast')); ?>
        <p class="xbinfo">
        	<?php echo Text::_('Azuracast functions');?>
        </p>
        <div class="row">
        	<div class="col-md-6">
        		<h4>Stations in Database</h4>
        		<?php if (empty($this->xbstations)) : ?>
        			<p><?php echo Text::_('No stations in database yet. Create by importing from Azuracast');?>
        			</p>
        		<?php else : ?>
        			<?php foreach ($this->xbstations as $station) : ?>
						<details>
							<summary>id: <?php echo $station['id']; ?> <span class="xbr11"> <?php echo $station['title']; ?></span>
							</summary>
							<?php if ($station['az_id']>0 ) : ?>
							    <i>AzID</i>: 
							    <?php echo $station['az_id'].' '.$station['az_apiname']; ?>
							    <br />
								<i>AzURL</i>: 
								<a href="<?php echo $station['az_url']; ?>" target="_blank">
                         			<?php echo $station['az_url']; ?></a>
							<?php else : ?>
						        <span class="xbit"><?php echo Xbtext::_('Azuracast details missing'); ?></span>
						        <!-- update button -->
							<?php endif; ?> 
							<br /><i>Website</i>: 
						    <a href="<?php echo $station['website']; ?>" target="_blank">
								<?php echo $station['website']; ?></a> 
							<p class="xb09"><?php echo $station['description'];?></p>        
						</details>
        			    <hr />
        			<?php endforeach; ?>
        		<?php endif;?>
        		<p class="xbit xb09"><?php echo Text::_('Use this button to add any additional stations using the apikey currently in config').' (',$this->apiname.')'; ?>
    	    	<?php $popbody = 'Import missing stations from Azuracast. Existing stations will not be updated.<br />Are you really sure?'; 
    	    	  $pophead = 'Confirm Import from Azuracast'; 
    	    	  $confirm = "doConfirm('".$popbody."','".$pophead."','importazstations');"; 
    	    	  ?>
    	    	 <br /><button id="impaz" class="btn btn-warning" type="button" 
            		onclick="<?php echo $confirm; ?>" >
    					<i class="icon-upload icon-white"></i> 
            		<?php echo Text::_('XB_IMPORT'); ?>
            		</button>        		
    			</p>
        		
        	</div>
        	<div class="col-md-6">
        		<h4>Stations accessible through API</h4>
        		<?php if ($this->azstations) : ?>
        			<?php foreach($this->azstations as $station) : ?>
						<details>
							<summary><i>AzID</i>: <?php echo $station->id; ?> 
								<span class="xbr11"> <?php echo $station->name; ?></span>
							</summary>
							<i>AzURL</i>: <a href="<?php echo $this->azurl; ?>" target="_blank">
                         			<?php echo $this->azurl; ?></a>
                         	<br />
							<i>Website</i>: 
						    <a href="<?php echo $station->url; ?>" target="_blank">
								<?php echo $station->url; ?></a>
							<br /> 
							<i>Stream</i>: 
						    <a href="<?php echo $station->listen_url; ?>" target="_blank">
								<?php echo $station->listen_url; ?></a> 
							<br />
							<i>Public page</i>: 
						    <a href="<?php echo $station->public_player_url; ?>" target="_blank">
								<?php echo $station->listen_url; ?></a> 
							<p class="xb09"><?php echo $station->description;?></p>        							
						</details>
						<!-- 
            	    	<?php //$popbody = $station->name.' will be created or updated.<br />Are you really sure?'; 
            	    	  //$pophead = 'Confirm Import from Azuracast'; 
            	    	  //$confirm = "doConfirm(".$popbody."','".$pophead."','loadazst');"; 
            	    	  ?>
            	    	 <p><button id="impmp3" class="btn btn-warning" type="button" 
                    		onclick="document.getElementById('jform_loadazid').value=<?php //echo $station->id; ?>;<?php //echo $confirm; ?>" >
            					<i class="icon-upload icon-white"></i> 
                    		<?php //echo Text::_('XB_IMPORT'); ?>
                    		</button>        		
            			</p>
 						 -->
    				<?php endforeach; ?>
        			<?php // +
                    		
                    		echo $this->form->renderField('loadazid'); ?>
       			<?php else : ?>
                      <p><i>No stations found at <code><?php echo $this->az_url; ?></code><br />Please check Azuracast URL and API key in config settings.</i>
                <?php endif; ?>
        	</div>
        </div>

	<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>
			
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'filemanager', Text::_('Files')); ?>

        	<?php echo $this->form->renderField('fmnote1'); ?> 
			<?php if (empty($this->symlinks)) : ?>
				<?php echo Text::_('XBMUSIC_NO_SYMLINKS'); ?>
			<?php else : ?>
				<h4><?php echo count($this->symlinks).' '.Text::_('XBMUSIC_EXISTING_SYMLINKS');?></h4>
                <table class="table-striped xbml50">
                  	<tr><th style="text-align:right;">Name in <code>/xbmusic</code></th>
                  		<th style="width:50px;text-align:center;">-></th>
                  		<th>Target Path</th>
                  		<th></th></tr>
				<?php $n=0; 
				foreach ($this->symlinks as $link) : ?>
					<tr><td style="text-align:right;">
				    	<?php $n++; 
				    	   $name = str_replace(JPATH_ROOT.'/xbmusic/', '', $link['name']); 
				    	   $popbody = '<b>'.$name.'</b> linked to '.$link['target'];
				    	   $pophead = 'Confirm OK to Remove Symlink';
				    	   echo '<b>'.$name.'</b>'; ?>
				    	</td><td style="text-align:center">-></td><td><?php echo $link['target']; ?></td>
				    	<td style="padding:5px;">
				    		<button id="remsym<?php echo $n;?>" class="btn btn-danger btn-sm" type="button"
                   				onclick="document.getElementById('rem_name').value='<?php echo $link['name']; ?>';
                   					doConfirm('<?php echo $popbody; ?>',
                   					'<?php echo $pophead; ?>',
                   					'remsymlink');" >
        						<i class="icon-link icon-white"></i> <?php echo Text::_('XBMUSIC_REMOVE_LINK'); ?>
        					</button>
        				</td>
				    </tr>
				<?php endforeach; ?>
				</table>
				<p>&nbsp;</p>
			<?php endif; ?>
        	<?php echo $this->form->renderField('link_target'); ?> 
        	<?php echo $this->form->renderField('link_name'); ?> 
        	<?php echo $this->form->renderField('fmnote2'); ?> 
        	<?php $pophead = 'Confirm Create SymLink in /xbmusic/'; 
        	   $confirm = "doConfirm('<i>Link</i> '+document.getElementById('jform_link_target').value+
        			'<br /><i>as</i> <b>'+document.getElementById('jform_link_name').value+'</b>',
                    '".$pophead."','makesymlink');";
        	?>
	    	 <p><button id="impmp3" class="btn btn-warning" type="button" 
        		onclick="<?php echo $confirm; ?>" >
					<i class="icon-link"></i> 
        		<?php echo Text::_('XBMUSIC_CREATE_LINK'); ?>
        		</button>        		
			</p>
        				
			<?php echo HTMLHelper::_('uitab.endTab'); ?>

            <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        	<hr />
         </div>

		<input type="hidden" id="rem_name" name="rem_name" value="" />
		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>

	</form>
    <p>&nbsp;</p>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>