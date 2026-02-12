<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/playlist/edit.php
 * @version 0.0.59.17 21st December 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

/**
 on loading page get user, we have a station id - but not an azstid need that 
 
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
// use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
// use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useScript('xbmusic.showdown')
    ->useScript('xbmusic.markdownhelper')
    ->useScript('xbmusic.playlisthelper')
    ->useScript('xbmusic.getplaylists')
    ->useScript('joomla.dialog')
    ->useScript('xbmusic.xbgeneral');

$root = Uri::root();
$document = Factory::getApplication()->getDocument();
$document->addScriptOptions('com_xbmusic.uri', array("root" => $root));

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$trackelink='index.php?option=com_xbmusic&task=track.edit&id=';

$input = Factory::getApplication()->getInput();

?>
<script >
	function setimpbtn(elval) {
		var btnloc = document.getElementById('btnloc');
		var btnaz = document.getElementById('btnaz');
		if (elval == "2") {
			btnaz.style.display = 'block';
			btnloc.style.display = 'none';
		} else {
			btnaz.style.display = 'none';
			btnloc.style.display = 'block';
		}
	}
	function setexpbtn(elval) {
		var btnexploc = document.getElementById('btnexploc');
		var btnexpaz = document.getElementById('btnexpaz');
		var clearrem = document.getElementById('clearrem');
		if (elval == "2") {
			btnexpaz.style.display = 'block';
			btnexploc.style.display = 'none';
			clearrem.style.display = "block";
			
		} else {
			btnexpaz.style.display = 'none';
			btnexploc.style.display = 'block';
			clearrem.style.display = "none";
		}
	}
</script>
<script type="module" src="<?php echo Uri::root(); ?>/media/com_xbmusic/js/xbdialog.js"></script>
<style>
/* set colum,n widths for schedule table - override Joomla default share space equally*/
    #subfieldList_jform_schedulelist thead th:first-child { width:70px !important; color:grey; }
    #subfieldList_jform_schedulelist thead th:nth-child(2) {width:70px !important; color:grey; }
    #subfieldList_jform_schedulelist thead th:nth-child(3) {width:100px !important; color:green; }
    #subfieldList_jform_schedulelist thead th:nth-child(4) {width:100px !important; color:red; }
    #subfieldList_jform_schedulelist thead th:nth-child(5) {width:250px !important; color:green; }
    #subfieldList_jform_schedulelist thead th:nth-child(6) {width:250px !important; color:red; }
    #subfieldList_jform_schedulelist thead th:nth-child(7) {width:unset !important; }
    #subfieldList_jform_schedulelist thead th:nth-child(8) {width:125px !important; }
    #subfieldList_jform_schedulelist thead th:nth-child(9) {width:125px !important; }
    #subfieldList_jform_schedulelist thead td {width:unset !important;}
/* set style for calendar controls on subform */
    #subfieldList_jform_schedulelist .field-calendar .input-group {flex-wrap:nowrap;}
    #subfieldList_jform_schedulelist .field-calendar .input-group button  
    {background-color: #fff;
        color: #777;
        border: 1px solid;     
        border-color:  #B8C9E0 #B8C9E0 #B8C9E0 transparent;                                                              
        font-size: 1.2rem;
        padding: 0 15px;
    }
</style>

<div id="xbcomponent">
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=playlist&layout=edit&id='. (int) $this->item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" enctype="multipart/form-data" >
    	<h2><?php echo $this->item->title; ?></h2>
    	<h3 class="xbnorm"><i>Station</i><b> : <?php echo $this->station['title']; ?></b>
    		<?php echo ' #'.$this->station['az_stid'].' <i>at Server</i> '.$this->station['az_url']; ?>
    	</h3>
    	
		<?php
            $waitmessage = 'XBMUSIC_WAITING_SERVER';
            echo LayoutHelper::render('xbmusic.waiter', array('message'=>$waitmessage)); ?>        
    	
    	<div class="row form-vertical">
    		<div class="col-md-8">
            	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    		</div>
    		<div class="col-md-1">
    		</div>
    		<div class="col-md-3">
                <div class="pull-left xbmr50" style="width:130px;">
    			  <?php echo $this->form->renderField('id'); ?> 
                </div>
                <div class="pull-left" style="width:130px;">
    			  <?php echo $this->form->renderField('az_plid'); ?>
                </div>
            </div>
    	</div>
    	<div class="row form-vertical">
    		<div class="col-md-3">
    			<?php echo $this->form->renderField('slug'); ?>
    		</div>
          	<div class="col-md-2">
				<?php echo $this->form->renderField('publicschd'); ?>
            </div>
    		<div class="col-md-7">
				<span class="xbpl10"><i><?php echo Text::_('XB_ORDERING'); ?></i> :
					<?php echo ucfirst($this->item->az_order); ?>
				</span>
				<span class="xbpl50"><i><?php echo Text::_('XB_TYPE'); ?></i> :
					<?php if ($this->item->az_type < 2) {
					    echo Text::_('XBMUSIC_AZTYPE'.$this->item->az_type); 
					} else {
					    echo Text::sprintf('XBMUSIC_AZTYPE'.$this->item->az_type, $this->item->az_cntper);
					} ?>
				</span>
				<span class="xbpl50"><i><?php echo Text::_('XB_WEIGHT'); ?></i> :
					<?php echo $this->item->az_info->weight; 
					 ?>
				</span>
				<span class="xbpl50">
					<?php if ($this->item->az_jingle) : ?>
				        <?php echo Text::_('Jingle Mode'); ?>
				        <span class="xbr08"><br />
				        	<?php echo Text::_('(hidden from audience & schedule)'); ?> 
				        </span>
					<?php endif; ?>
				</span>
				<br />
				<span class="xbpl10"><i><?php echo Text::_('Track Count'); ?></i> :
					<?php echo $this->aztrkcnt.Xbtext::_('in Azuracast',XBTRL  + XBSP1); ?>
					<span <?php if ($this->aztrkcnt != $this->dbtrkcnt) echo 'class="xbred"'; ?>>, 
						<?php echo $this->dbtrkcnt.Xbtext::_('in local list', XBTRL + XBSP1); ?>						
					</span>
				</span>
				
    		</div>
    	</div>
		<?php if($this->azchanged == true) : ?>
			<p class="xbred"><?php echo Text::_('XBMUSIC_AZURACAST_PLAYLIST_DATA_MATCH_INFO'); ?></p>
			<div class="row">
				<div class="col-md-6">
					<?php $popbody = "'Reloading playlist id:'+document.getElementById('jform_az_id').value+
                                    ' from station id: X'"; 
        	    	      $pophead = 'Confirm Reload playlist from Azuracast'; 
        	    	      $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.reloadplaylist');"; 
        	    	?>                
        	    	<p><button id="reload" class="btn btn-info btn-sm" type="button" 
						onclick="<?php echo $confirm; ?>" >
        					<i class="icon-download icon-black"></i> 
                			<?php echo Text::_('XBMUSIC_AZURACAST_RELOAD'); ?>
                		</button>        		
        				<br />			
	        			<span class="xbnote">
    	    				<?php echo Text::_('XBMUSIC_AZURACAST_RELOAD_INFO'); ?>
        				</span>
        			</p>
				</div>
				<div class="col-md-6">
    				<div class="pull-right xbtr">
            	    	<?php $popbody = "'Write changes back to playlist id:'+document.getElementById('jform_az_id').value+
                                        ' on Azuracast'"; 
            	    	      $pophead = 'Confirm Put changes to Azuracast'; 
            	    	      $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.putplaylist');"; 
            	    	?>                
            	    	<p><button id="writeaz" class="btn btn-warning btn-sm" type="button" 
                    		onclick="<?php echo $confirm; ?>" >
            					<i class="icon-upload icon-white"></i> 
                    			<?php echo Text::_('XBMUSIC_AZURACAST_PUT'); ?>
                    		</button>        		
            				<br />
             				<span class="xbnote">
            					<?php echo Text::_('XBMUSIC_AZURACAST_PUT_INFO'); ?>
            				</span>
            			</p>
   					</div>    	        								
				</div>
			</div>
		<?php endif; ?>
    				   		
    	<!-- hidden fields -->
    	<?php echo $this->form->renderField('db_stid'); ?>	  
		
		 
     <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true]); ?>

    	<?php if(($this->azuracast == 1) && ($this->item->db_stid) > 0) : ?>

	        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'azuracast', 'Azuracast'); ?>

<!-- 
            	    	<?php //$popbody = "'Unlink playlist id:'+document.getElementById('jform_az_id').value+' from Azuracast'"; 
            	    	  // $pophead = 'Confirm Unlink Playlist from Azuracast'; 
            	    	  // $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.unlinkplaylist');"; 
            	    	  ?>                
            	    	 <p><button id="unlink" class="btn btn-danger btn-sm icon-white" type="button" 
                    		onclick="<?php //echo $confirm; ?>" >
            					<i class="fas fa-link-slash"></i> &nbsp; 
                    			<?php //echo Text::_('XBMUSIC_AZURACAST_UNLINK'); ?>
                    		</button> 
                    		<span class="xbpl50"><i><?php //echo Text::_('XBMUSIC_AZURACAST_UNLINK_PLAYLIST'); ?></i>       		
                    		</span>
            			</p>
 -->	        
	        
	        	<h4><?php echo Text::_('XBMUSIC_AZURACAST_VALUES'); ?></h4>
	    		<div class="row">
	    			<div class="col-md-6">
           				<p><?php echo Text::_('XB_LOCAL_DATA'); ?></p>
                		<?php echo $this->form->renderField('az_name') ;?>
                		<?php echo $this->form->renderField('az_type') ;?>
                		<?php echo $this->form->renderField('az_cntper') ;?>
                		<?php echo $this->form->renderField('az_jingle') ;?>
                		<?php echo $this->form->renderField('az_weight') ;?>
                		<?php echo $this->form->renderField('az_order') ;?>
                		<p><?php echo Text::_('XBMUSIC_AZURACAST_PLAYLIST_EDIT_INFO')?></p>
	    			</div>
	    			<div class="col-md-6">
           				<p><?php echo Text::_('XBMUSIC_AZURACAST_RAW_DATA'); ?></p>
    					<?php if (!empty($this->item->az_info)) : ?>
        					<fieldset id="azinfo" class="xbbox xbboxwht ">
        						<legend><?php echo Text::_('XBMUSIC_AZURACAST_SETTINGS'); ?></legend>
        						<dl class="xbdl xbmb0">
                            		<?php foreach ($this->item->az_info as $key=>$value) : ?>
                            			<?php if ($key == 'total_length') 
                            			    $value = $this->frmtlength; ?>
                            			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                            		<?php endforeach; ?>        
        						</dl>
								<dl class="xbdl">
                                	<dt>Scheduled Times</dt><dd><?php echo $this->item->scheduledcnt; ?></dd>
                                </dl>
								<?php echo $this->form->renderField('scheduledcnt') ;?>        						
        					</fieldset>
        					
        					<p class="info"><?php echo Text::_('XBMUSIC_CHECK_SCHEDULES_TAB'); ?></p>
    					<?php else : ?>
    						<p class="xbit"><?php echo Text::_('Azuracast Info Missing'); ?></p>
    					<?php endif; ?>
        			</div>
        		</div>
                <?php if($this->azchanged == true) : ?>
            		<div class="row">
            			<div class="col-md-6">
            			</div>
            			<div class="col-md-6">
            			</div>
            		</div>
        		<?php endif; ?>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
        
	    <?php endif; ?>

    	<?php if($this->item->scheduledcnt > 0) : ?>

	        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'schedule', 'Schedule'); ?>
	        	<p class="xbit">NB Currently changes can only be made to the schedule items in Azuracast</p>
	        	<?php echo $this->form->renderField('schedulelist'); ?>
 			<?php echo HTMLHelper::_('uitab.endTab'); ?>
        
	    <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('XB_GENERAL')); ?>
			<div class="row form-vertical">
           		<div class="col-12 col-lg-9">
  					<div class="row">
		  	     		<div class="col-12 col-lg-6">
        					<?php echo $this->form->renderField('description'); ?> 
        				</div>
		           		<div class="col-12 col-lg-6">
		           			<div class="control-group"><div class="control-label" style="width:90%;">
		           					<?php echo Text::_('XB_PREVIEW_MARKDOWN'); ?>
		           				</div>
								<div id="pv_desc" class="xbbox xbboxwht" style="height:23.5rem; overflow-y:scroll;">
		           				</div>
        					</div> 
        				</div>
        			</div>
	   			</div>
           		<div class="col-12 col-lg-3">
        			<?php echo $this->form->renderField('status'); ?> 
        			<?php echo $this->form->renderField('catid'); ?> 
         			<?php echo $this->form->renderField('access'); ?> 
        			<?php echo $this->form->renderField('ordering'); ?> 
        			<?php echo $this->form->renderField('note'); ?> 
           		</div>
    		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
         
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('Tag Groups')); ?>
			<div class="row">
				<div class="col-12">
                  <p class="xbnote"><?php echo Text::_('XB_TAGS_EDIT_NOTE1'); ?></p>
         			<?php echo $this->form->renderField('tags'); ?> 
         		</div>
         	</div>
         	<hr />
         	<div class="row">
				<div class="col-12">
					<?php if (!empty($this->tagparentids)) : ?>
						<p class="xbnote"><?php echo Text::_('XB_TAGS_EDIT_NOTE2'); ?></p>
						<?php echo $this->form->renderFieldset('taggroups'); ?>
					<?php else: ?>
						<p class="xbnote"><?php echo Text::_('XB_TAGS_EDIT_NOTE3'); ?></p>
 					<?php endif; ?>
				</div>
    		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
         
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'tracks', Text::_('XBMUSIC_TRACKS')); ?>
		
			<div class="row">
				<div class="col12 col-md-5">
            		<button id="btnloadm3u" class="btn btn-info btn-sm icon-white xbmr50" type="button" 
                        		onclick="hideEl('saveopts');
                        		  showEl('loadopts');
                        		  var opt = document.querySelector('input[name=\'jform[loadsource]\']:checked').value;
                        		  setimpbtn(opt);
                        " >
                					<i class="fas fa-link"></i> &nbsp; 
                        			<?php echo Text::_('Import Tracklist'); ?>
                    </button>
            		<button id="btnsavem3u" class="btn btn-success btn-sm icon-white" type="button" 
                        		onclick="hideEl('loadopts');
                        		  showEl('saveopts');" >
                					<i class="fas fa-link"></i> &nbsp; 
                        			<?php echo Text::_('Export Tracklist'); ?>
                    </button>				
				</div>
				<div class="col12 col-md-5 form-horizontal">
					<p><b><?php echo Text::_('Import reporting options'); ?></b>
						<br /><i><?php echo 'Logging'; ?></i> : <?php echo $this->logging; ?>
	        			<span class="xbpl20"><i><?php echo 'Messages'; ?></i> : 
	        				<?php echo $this->messaging; ?></span>
	        		</p>
	        	</div>	
			</div>
    		<div id="loadopts" class="xbbox" style="margin:10px; display:none;">
 				<div class="pull-right">
					<button type="button" aria-label="Close" class="button-close btn-close" 
						onclick="hideDiv(document.getElementById('loadopts'));"></button>
				</div>			
    			<div class="row">
        			<div class="col12 col-md-4 form-vertical">
        				<?php echo $this->form->renderfield('loadsource'); ?>
        			</div>
        			<div class="col12 col-md-4">
        				<p><b><i><?php echo Text::_('XBMUSIC_PLAYLIST_LOAD_OPTS')?></i></b></p>    				
            			<?php echo $this->form->renderfield('clearfirst','params'); ?>  				
        			</div>
        			<div class="col12 col-md-4">
            			<?php echo $this->form->renderField('ignoremissing','params'); ?>
            			<?php echo $this->form->renderField('createtrks','params'); ?>
        			</div>		
        		</div>
        		<div class="row">
        			<div class="col12 col-md-5">
             			<?php echo $this->form->renderfield('upload_filem3u'); ?>
            			<?php echo $this->form->renderfield('local_filem3u'); ?>
        			</div>
        			<div class="col12 col-md-3">
        				<?php echo $this->form->renderfield('delete_m3u'); ?>
        			</div>
        			<div class="col12 col-md-4">
        				<div id="btnaz" style="display:none;">
                            <div class="pull-right">
                				<b>Load tracklist from <?php echo $this->station['title']; ?></b>
                    	    	<?php $popbody = "'Import playlist tracklist from ".$this->station['title']."'"; 
                    	    	  $pophead = 'Confirm downloadload tracklist from Azuracast'; 
                    	    	  $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.importtrklistaz');"; 
                    	    	  ?>                
                    	    	 <button id="loadm3u" class="btn btn-secondary btn-sm icon-white xbml20 xbmr50" 
                    	    	 	type="button" onclick="<?php echo $confirm; ?>" >
                    					<i class="fas fa-link"></i> &nbsp; 
                            			<?php echo Text::_('Import from Azuracast'); ?>
                            	</button>    
                        	</div>    		              			
            			</div>    				
         				<div id="btnloc" style="display:block;">
                            <div class="pull-right">
                				<b>Load tracklist from .m3u playlist file</b>
                    	    	<?php $popbody = "'Import playlist track list from file'"; 
                    	    	  $pophead = 'Confirm load tracklist from m3u file'; 
                    	    	  $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.loadtrklistm3u');"; 
                    	    	  ?>                
                    	    	 <button id="loadm3ufile" class="btn btn-secondary btn-sm icon-white xbml20 xbmr50" 
                    	    	 	type="button" onclick="<?php echo $confirm; ?>" >
                    					<i class="fas fa-file"></i> &nbsp; 
                            			<?php echo Text::_('Import from File'); ?>
                            	</button>  
                        	</div>      			
        				</div>
        			</div>
        		</div>
        	</div>
    		<div id="saveopts" class="xbbox" style="margin:10px; display:none;">
				<div class="pull-right">
					<button type="button" aria-label="Close" class="button-close btn-close" 
						onclick="hideDiv(document.getElementById('saveopts'))"></button>
				</div>			
        		<div class="row">
        			<div class="col12 col-md-4 form-vertical">
        				<?php echo $this->form->renderField('savedest'); ?>
        			</div>
         			<div class="col12 col-md-4 form-vertical">
             			<div id="clearrem" style="display:none;">
             				<?php echo $this->form->renderField('clearremote','params'); ?>
             			</div>
         			</div>
         			<div class="col12 col-md-4 form-vertical">
         				<p> </p>
						<div id="btnexpaz" style="display:none;">
                        	<div class="pull-right">
            					<b>Upload tracklist to  <?php echo $this->station['title']; ?></b>
                    	    	<?php $popbody = "'Upload tracklist to ".$this->station['title']."'"; 
                    	    	  $pophead = 'Confirm upload tracklist to Azuracast'; 
                    	    	  $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.exporttrklistaz');"; 
                    	    	  ?>                
                    	    	 <button id="uploadm3u" class="btn btn-success btn-sm icon-white xbml20 xbmr50" 
                    	    	 	type="button" onclick="<?php echo $confirm; ?>" >
                    					<i class="fas fa-file-arrow-up"></i> &nbsp; 
                            			<?php echo Text::_('Upload to Azuracast'); ?>
    							</button>        		
                            </div>
                        </div>
						<div id="btnexploc" style="display:block;">
            				<div class="pull-right">
            					<b>Save tracklist to M3U file</b>
                    	    	<?php $popbody = "'Export tracklist to M3U file'"; 
                    	    	  $pophead = 'Confirm save tracklist as M3U'; 
                    	    	  $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.savetrklistm3u');"; 
                    	    	  ?>                
                    	    	 <button id="savem3u" class="btn btn-success btn-sm icon-white xbml20 xbmr50" 
                    	    	 	type="button" onclick="<?php echo $confirm; ?>" >
                    					<i class="fas fa-file-export"></i> &nbsp; 
                            			<?php echo Text::_('Save to File'); ?>
                            	</button>        		
                			</div>
            			</div>
        			</div>
        		</div>
        	</div>
    		<hr />
    		<div class="row">
        		<div class="col-12 col-md-6 form-vertical">
         			<h4><?php echo Text::_('XBMUSIC_TRACK_LIST')?></h4>
           			<?php if (isset($this->tracks)) : ?>  
        	    	<?php $popbody = "'Clear all tracks from playlist - Are you sure?'"; 
        	    	  $pophead = 'Confirm empty tracklist'; 
        	    	  $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.clearlist');"; 
        	    	  ?>                
        	    	 <div class="pull-right"><button id="clearlist" class="btn btn-danger btn-sm icon-white" type="button" 
                		onclick="<?php echo $confirm; ?>" >
        					<i class="fas fa-link"></i> &nbsp; 
                			<?php echo Text::_('Clear List'); ?>
                		</button>        		
        			</div>
           			<div class="xbyscroll xbmh50dvh">
                		<table>
                			<tr><td>[List ID]</td><td>Title</td><td>Artist</td></tr>
                			<?php foreach ($this->tracks as $listitem) : ?>
                				<tr>
                					<td>[<?php echo $listitem['track_id'];?>]</td> 
                					<td><a href="<?php echo $trackelink.$listitem['track_id'];?>">
                						<?php echo $listitem['title']; ?></a></td>
                					<td><?php echo $listitem['artist']; ?></td>        			
                    			</tr>
                			<?php endforeach; ?>
                		</table>
           			</div>			
            		
            		<?php else : ?>
                		<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_TRACKS_LISTED'); ?></p>
            		<?php endif; ?>
    			</div>
        		<div class="col-12 col-md-3">
        			<?php if ($this->item->az_order == 'sequential') : ?>
    					<div class="pull-left xbmr50">
    						<?php echo $this->form->renderField('allowdupes'); ?>
    					</div>
        				<div class="clearfix"></div>
    				<?php endif; ?>
        				<p class="xbr11"><?php echo $this->dbtrkcnt; ?> tracks in local playlist.</p>
        				<p> Use the 
        					<a href="index.php?option=com_xbmusic&view=playlisttracks&id=
        					<?php echo $this->item->id; ?>">
        						Playlist Tracks</a> 
        					view for track details and to add, remove, 
        					<?php if ($this->item->az_order == 'sequential') echo Text::_('reorder'); ?>
        				    and batch remove tracks.
        				</p>
        		</div>
    		</div>
		

		<?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('XB_PUBLISHING')); ?>
        <div class="row">
            <div class="col-12 col-lg-6">
                <fieldset id="fieldset-publishingdata" class="options-form">
                    <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
                    <div>
                    <?php echo LayoutHelper::render('joomla.edit.publishingdata', $this); ?>
                    </div>
                </fieldset>
            </div>
            <div class="col-12 col-lg-6">
                <fieldset id="fieldset-metadata" class="options-form">
                    <legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
                    <div>
                    <?php echo LayoutHelper::render('joomla.edit.metadata', $this); ?>
                    </div>
                </fieldset>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php if ($this->canDo->get('core.admin') ) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('XB_PERMISSIONS')); ?>
            <fieldset id="fieldset-rules" class="options-form">
                <legend><?php echo Text::_('XB_USER_GROUP_PERMISSIONS'); ?></legend>
                <div>
                	<?php echo $this->form->getInput('rules'); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    	<hr />
    </div>	
    <input type="hidden" name="task" id="task" value="playlist.edit" />
    <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
<script>
       updatePvMd();
       document.getElementById("jform_description").addEventListener("input", (event) => updatePvMd());
</script>
