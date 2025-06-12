<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dataman/default.php
 * @version 0.0.53.0 5th June 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
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
->useScript('xbmusic.showdown')
->useScript('joomla.dialog');

/**
$wa->addInlineScript("window.onload = function() {
    var selfolder = sessionStorage.getItem('selfolder');
    if (selfolder) {
        var sellink = document.querySelector('#container li.folder a[rel=selfolder]');
        if (sellink) { sellink.parentElement.classList.add('selected expanded');}
    }
};");
**/

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

//$input = Factory::getApplication()->getInput();

?>
<link rel="stylesheet" href="<?php echo Uri::root(true);?>/media/com_xbmusic/css/foldertree.css">

<script type="module" src="<?php echo Uri::root(); ?>/media/com_xbmusic/js/xbdialog.js"></script>

<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=dataman'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="hidden" id="basefolder" value="<?php echo $this->basemusicfolder; ?>" />
		<input type="hidden" id="multi" value="1" />
		<input type="hidden" id="extlist" value="mp3" />
		<input type="hidden" id="posturi" value="<?php echo Uri::base(true).'/components/com_xbmusic/vendor/Foldertree.php'; ?>"/>
		<input  type="hidden" id="autoclose" name="autoclose" value="yes" checked="true" />

        <h3>xbMusic Data Manager</h3>

		<div class="main-card">
			<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'import', 'recall' => true]); ?>
    
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'import', Text::_('XB_IMPORT')); ?>
	<div>
            <p class="xbinfo">
            	<?php echo Text::_('XBMUSIC_IMPORT_DETAILS');?>
            </p>
<details id="imptk">
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('XBMUSIC_IMPORT_TRACKS'); ?></span>
	</summary>	
	<div class="row form-vertical">
		<div class="col-md-6">
			<p><?php echo Text::_('XBMUSIC_SELECT_FOLDER')?>
	    	<div id="container"> </div>
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
	<hr />
	<div class="row">
		<div class="col-md-8 form-vertical">
         	<?php echo $this->form->renderField('impcat'); ?>
         	<?php echo $this->form->renderField('splitsongs'); ?>
         	<?php echo $this->form->renderField('nobrackets'); ?>
		</div>
		<div class="col-md-4">
	    	<?php $popbody = "'<i>Import from </i>'+document.getElementById('jform_foldername').value"; 
	    	  $pophead = 'Confirm Import from MP3'; 
	    	  $confirm = "doConfirm(".$popbody.",'".$pophead."','dataman.importmp3');"; 
	    	  ?>
	    	 <p><button id="impmp3" class="btn btn-warning" type="button" 
        		onclick="<?php echo $confirm; ?>" >
					<i class="icon-download"></i> &nbsp;
        			<?php echo Text::_('XB_IMPORT'); ?>
        		</button>        		
			</p>
		</div>
	</div>
</details>
<hr />
<details id="impcsv">
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('XBMUSIC_IMPORT_CSV')?></span>
	</summary>
	<p>tba </p>
<p>Functionality expected here:</p>
<ol>
    <li>Import datatype from csv</li>
    <li>choose file</li>
    
</ol>
</details>
<hr />
<details id="imppl">
	<summary>
		<span class="xbr11 xbbold"><?php echo Text::_('XBMUSIC_IMPORT_PLAYLIST')?></span>
    </summary>		
<p>Functionality expected here:</p>
<ol>
    <li>Import playlist
    	<ul>
    		<li>set playlist (new/existing) - if existing append or replace
    		<li>Select file</li>
    		<li>foreach line check file
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
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'report', Text::_('XB_REPORT')); ?>
                <p class="xbinfo">
                	<?php echo Text::_('XBMUSIC_GENERATE_REPORTS')?>
                </p>
<p>Functionality expected here:</p>
<ol>
    <li>Show orphan artists & songs without track</li>
    <li>Show orphan tracks without playlist, album</li>
    
    <li></li>
</ol>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'export', Text::_('XB_EXPORT')); ?>
                <p class="xbinfo">
                	<?php echo Text::_('XBMUSIC_EXPORT_DETAILS') ?>
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
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'delete', Text::_('XB_DELETE')); ?>
                <p class="xbinfo">
                	<?php echo Text::_('XBMUSIC_DELETE_DETAILS')?>
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
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'azuracast', 'Azuracast'); ?>
        <p class="xbinfo">
        	<?php echo Text::_('XBMUSIC_AZURACAST_STATIONS');?>
        </p>
        <div class="row">
        	<div class="col-md-6">
        		<h4>Stations in Database</h4>
        		<?php if (empty($this->xbstations)) : ?>
        			<p><?php echo Text::_('XBMUSIC_NO_STATIONS');?>
        			</p>
        		<?php else : ?>
            	    	<?php $pophead = Text::_('XBMUSIC_CONFIRM_STAT_DEL'); ?>
        			<?php foreach ($this->xbstations as $station) : ?>
            				<?php $popbody = "'". Text::sprintf('XBMUSIC_DELETE_STATION',$station['title'])."'";
            				    $confirm = "doConfirm(".$popbody.",'".$pophead."','dataman.deletestation');"; ?>
						<details>
							<summary>id: <?php echo $station['id']; ?> <span class="xbr11"> <?php echo $station['title']; ?></span>
								<div class="pull-right">
									<button id="delst<?php echo $station['id']; ?>" 
									class="btn btn-sm btn-danger" type="button"
        							onclick="document.getElementById('jform_dbstid').value=
        							 <?php echo $station['id'].';'.$confirm; ?>;" >
									<i class="icon-trash icon-white"></i> &nbsp;<?php echo Text::_('XB_DELETE'); ?>
									</button>
								</div>
							</summary>
							<br /><i>Website</i>: 
						    <a href="<?php echo $station['website']; ?>" target="_blank">
								<?php echo $station['website']; ?></a> 
							<?php if ($station['az_id']>0 ) : ?>
							    <p><i>AzID</i>: 
							    <?php echo $station['az_id'].' '.$station['az_apiname']; ?>
							    <br />
								<i>AzURL</i>: 
								<a href="<?php echo $station['az_url']; ?>" target="_blank">
                         			<?php echo $station['az_url']; ?></a></p>
								<i>AzAPI User</i>: 
								<a href="<?php echo $station['az_apiname']; ?>" target="_blank">
                         			<?php echo $station['az_apiname']; ?></a></p>
							<?php else : ?>
						        <span class="xbit"><?php echo Xbtext::_('XBMUSIC_AZURACAST_NO_DETAILS'); ?></span>
							<?php endif; ?> 
							<p class="xb09"><?php echo $station['description'];?></p>        
						</details>
        			    <hr />
        			<?php endforeach; ?>
        		<?php endif;?>        		
        	</div>
        	<div class="col-md-6">
        		<h4>Stations accessible through API</h4>
        		<?php if ($this->azstations) : ?>
        			<?php if (isset($this->azstations->code)) : ?>
        			 <p class="xbit xbred"><?php echo Text::_('XBMUSIC_AZURACAST_ERROR').' '.$this->azstations->code; ?>
        			 	<br /><?php echo $this->azstations->formatted_message; ?>
        			 	<br /><?php echo Text::_('XBMUSIC_CHECK_TRY_LATER'); ?>
        			 </p>
        			<?php else : ?>
        				<p><?php echo Text::sprintf('XBMUSIC_STATIONS_AVAILABLE_AT', 
        				    $this->azurl, $this->apiname); ?>
        				<br /><?php echo Text::_('XBMUSIC_BUTTONS_TO_IMPORT'); ?> 
            	    	<?php $pophead = "'".Text::_('XBMUSIC_CONFIRM_AZIMPORT')."'"; ?>
            			<?php foreach($this->azstations as $station) : ?>
            				<?php $popbody = "'".Text::sprintf('XBMUSIC_IMPORT_FROM',$station->name,$this->azurl)."'";
            				    $confirm = "doConfirm(".$popbody.",".$pophead.",'dataman.importazstation');"; ?>
            				<details>
    							<summary><i>AzID</i>: <?php echo $station->id; ?> 
    								<span class="xbr11"> <?php echo $station->name; ?></span>
    								<div class="pull-right">
    									<button id="impaz<?php echo $station->id; ?>" 
    									class="btn btn-sm btn-warning" type="button"
            							onclick="document.getElementById('jform_loadazid').value=
            							 <?php echo $station->id.';'.$confirm; ?>;" >
    									<i class="icon-download"></i> <?php echo Text::_('XB_IMPORT'); ?>
										</button>
    								</div>
    							</summary>
    							<i>AzURL</i>: <a href="<?php echo $this->azurl; ?>" target="_blank">
                             			<?php echo $this->azurl; ?></a>
                             	<br />
    							<i><?php echo Text::_('XB_WEBSITE'); ?></i>: 
    						    <a href="<?php echo $station->url; ?>" target="_blank">
    								<?php echo $station->url; ?></a>
    							<br /> 
    							<i><?php echo Text::_('XBMUSIC_STREAM'); ?></i>: 
    						    <a href="<?php echo $station->listen_url; ?>" target="_blank">
    								<?php echo $station->listen_url; ?></a> 
    							<br />
    							<i><?php echo Text::_('XBMUSIC_PUBLIC_PAGE'); ?></i>: 
    						    <a href="<?php echo $station->public_player_url; ?>" target="_blank">
    								<?php echo $station->public_player_url; ?></a> 
    							<p class="xb09"><?php echo $station->description;?></p>        							
    						</details>
        				<?php endforeach; ?>
            			<?php echo $this->form->renderField('loadazid'); ?>
            			<?php echo $this->form->renderField('dbstid'); ?>
        			<?php endif; ?>
       			<?php else : ?>
                     <p><i><?php if ($this->azurl =='') {
                         echo Text::_('XBMUSIC_AZURACAST_NO_DETAILS').'<br />'.Text::_('XBMUSIC_AZURACAST_SET_OPTS');
                     } else {
                         echo Text::sprintf('XBMUSIC_AZURACAST_NO_STATIONS',$this->azurl); 
                     }?></i></p>
                <?php endif; ?>
        	</div>
        </div>

	<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>
			
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'filemanager', Text::_('XB_FILES')); ?>

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
    
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/closedetails.js" ></script>
    
</div>