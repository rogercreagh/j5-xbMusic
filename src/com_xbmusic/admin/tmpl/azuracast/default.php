<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/azuracast/default.php
 * @version 0.0.59.9 29th November 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
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

HTMLHelper::_('jquery.framework');

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('xbmusic.foldertree')
->useScript('xbmusic.showdown')
->useScript('joomla.dialog');

$wa->addInlineScript("function pleaseWait(targ) {
		document.getElementById(targ).style.display = 'block';
	}");

$wa->addInlineScript("
    $(document).ready(function() {
        $('#jform_newapikey').on('keyup', function() {
            if (document.getElementById('jform_newapikey').value.length==49) {
                document.getElementById('impapi').style.display = 'unset';
            } else {
                document.getElementById('impapi').style.display = 'none';
            }
        });
    })
");
?>    
<link rel="stylesheet" href="<?php echo Uri::root(true);?>/media/com_xbmusic/css/foldertree.css">

<script type="module" src="<?php echo Uri::root(); ?>/media/com_xbmusic/js/xbdialog.js"></script>

<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=azuracast'); ?>" method="post" name="adminForm" id="adminForm">
<?php if (($this->azuracast == 0) || ($this->azurl == '') ) : ?>
    <div class="xbbox gradpink xbht200 xbflexvc">
        <div class="xbcentre"><h3><?php echo $this->noazmess1; ?></h3>
        	<p><?php echo $this->noazmess2; ?></p>
        </div>
    </div>
<?php else: ?>

		<input type="hidden" id="basefolder" value="<?php echo $this->basemusicfolder; ?>" />
		<input type="hidden" id="multi" value="0" />
		<input type="hidden" id="extlist" value="xxx" />
		<input type="hidden" id="posturi" value="<?php echo Uri::base(true).'/components/com_xbmusic/vendor/Foldertree.php'; ?>"/>
		<h2><?php echo Text::_('XBMUSIC_AZURACAST_SERVER_STATIONS');?></h2>
         
	<?php if($this->azme == false): ?>
		<p><?php echo Text::_('XBMUSIC_AZAPI_KEY_INVALID'); ?></p>
	<?php else : ?>
		<p><?php echo Text::sprintf('XBMUSIC_USING_SERVER_ACCOUNT',$this->azurl, $this->account); ?> </p>
		<p><?php echo Text::_('Current API key'); ?>
		: <?php echo $this->apicomment; ?> <code><?php echo $this->apikey; ?></code></p>
	<?php endif; ?>
      
    	<div id="azwaiter" class="xbbox alert-info" style="display:none;">
          <table style="width:100%">
              <tr>
                  <td style="width:200px;"><img src="/media/com_xbmusic/images/waiting.gif" style="height:100px" /> </td>
                  <td style="vertical-align:middle;"><b><?php echo Text::_('XBMUSIC_WAITING_SERVER'); ?></b> </td>
              </tr>
          </table>
    	</div>
        <div class="pull-left xblblcompact xbw600 xbmwp50" >
			<?php echo $this->form->renderField('apilist'); ?>
			<?php echo $this->form->renderField('newapikey'); ?>
        </div>
        <div class="pull-left xbml50 xblblcompact xbctl150 xbmwp50">
			<br />
			<?php ?>
        	<button id="impapi" class="btn btn-sm btn-primary" type="button"
        		onclick="Joomla.submitbutton('azuracast.saveapi');" >
				<i class="icon-save icon-white"></i> &nbsp;<?php echo Text::_('XBMUSIC_SAVE_NEW_KEY'); ?>        	
        	</button>
        </div>
        <div class="clearfix"></div>

	<?php if ($this->apikey == ''): ?>
		<P><?php echo Text::_('XBMUSIC_AZAPI_NO_KEY')?>
	<?php endif; ?>
	</p>
	<p class="xbtc xbnote xbmb5"><?php echo Text::_('XB_AUTOCLOSE_DROPS'); ?> <input  type="checkbox" id="autoclose" name="autoclose" value="" checked="" style="margin:0 5px;" />
    </p>
	
        <div class="row">
        	<div class="col-md-6">
        		<h4><?php echo Text::_('XBMUSIC_STATIONS_IN_DB'); ?></h4>
        		<?php if (empty($this->xbstations)) : ?>
        			<p><?php echo Text::_('XBMUSIC_NO_STATIONS');?>
        			</p>
        		<?php else : ?>
            	    <?php $pophead = Text::_('XBMUSIC_CONFIRM_STAT_DEL'); ?>
        			<?php foreach ($this->xbstations as $station) : ?>
	        			<?php //$stationkeyid = explode(':',$station['az_apikey'])[0]; ?>
    	        		<?php if ($station['isadmin']) {
                		    $popbody = "'". Text::sprintf('XBMUSIC_REMOVE_STATION',$station['title'])."'";
    		      		    $confirm = "doConfirm(".$popbody.",'".$pophead."','azuracast.deletestation','azwaiter');"; 
            		        } ?>
						<details id="dbst<?php echo $station['id']; ?>">
							<summary><span class="xbr11"> <?php echo $station['title']; ?></span>
							 <span class="xbit xbpl20 xbdarkgrey">[xbMusic id: <?php echo $station['id']; ?>
							 	AzID: <?php echo $station['az_stid']; ?>]
							 </span>
								<div class="pull-right">
    	        				<?php if ($station['isadmin']) : ?>
									<button id="editst<?php echo $station['id']; ?>" 
									class="btn btn-sm btn-primary" type="button"
        							onclick="document.getElementById('jform_dbstid').value=
        							 <?php echo $station['id'];?>;Joomla.submitbutton('azuracast.editstation');" >
									<i class="icon-edit icon-white"></i> &nbsp;<?php echo Text::_('XB_EDIT'); ?>
									</button>
									<button id="delst<?php echo $station['id']; ?>" 
									class="btn btn-sm btn-danger" type="button"
        							onclick="document.getElementById('jform_dbstid').value=
        							 <?php echo $station['id'].';'.$confirm; ?>;" >
									<i class="icon-trash icon-white"></i> &nbsp;<?php echo Text::_('XB_DELETE'); ?>
									</button>
								<?php endif; ?>
								</div>
							</summary>
							<dl class="xbgl">
								<dt><?php echo Text::_('Slug'); ?></dt>
								<dd><?php echo $station['alias']; ?></dd>
								<dt><?php echo Text::_('Description'); ?></dt>
								<dd class="xb09"><?php echo $station['description']; ?></dd>
								<dt><?php echo Text::_('Local Media Path'); ?></dt>
								<dd><code><?php echo '/xbmusic/'.$station['mediapath']; ?></code></dd>
								<dt><?php echo Text::_('Website'); ?></dt>
								<dd><a href="<?php echo $station['website']; ?>" target="_blank">
									<?php echo $station['website']; ?></a></dd>
								<dt><?php echo Text::_('Player URL'); ?></dt>
								<dd><a href="<?php echo $station['az_player']; ?>" target="_blank">
									<?php echo $station['az_player']; ?></a></dd>
								<dt><?php echo Text::_('Stream'); ?></dt>
								<dd><a href="<?php echo $station['az_stream']; ?>" target="_blank">
									<?php echo $station['az_stream']; ?></a></dd>
								<dt><?php echo Text::_('Playlists'); ?></dt>
								<dd><?php echo $station['plcnt'].' playlists imported'; ?></dd>
								<dt><?php echo Text::_('Schedule'); ?></dt>
								<dd><?php echo $station['schtot'].' schedule slots imported'; ?></dd>
								<dt><?php echo Text::_('Last sync\'d'); ?></dt>
								<dd><?php echo $station['created']; ?></dd>
								<dt><?php echo Text::_('Local save'); ?></dt>
								<dd><?php echo $station['modified']; ?>
									<?php if ($station['modified'] > $station['created']) : ?>
									    <br /><span class="xbred xbit"><?php echo Text::_('Local data has been modified; sync or reload required')?></span>
									<?php endif; ?>
								</dd>
							</dl>
							
        					<i><?php echo Text::_('Now Playing'); ?></i>
    						<div class="xbmw450 xbml50 xbht150 xbp10-20" style="background-color:#FdFfFd;">
        						<iframe src="<?php echo $station['az_player'];?>/embed?theme=light" frameborder="0" allowtransparency="true" style="width: 100%; min-height: 150px; border: 0;"></iframe>
    						</div>
						</details>
                        <?php if(empty($station['mediapath'])) :?>
    	        			<p class="xbit"><span class="xbred">
                         		<?php echo Text::_('XBMUSIC_STATION_NO_MEDIA_FOLDER'); ?></span> ... 
            	        		<?php if ($station['isadmin']) : ?>						
                         			<?php echo Text::_('XBMUSIC_EDIT_STATION_MEDIA_FOLDER'); ?>                        			  
    							<?php else : ?> 								
    								<?php echo Text::_('XBMUSIC_NEED_ADMIN_API'); ?>                  			
                         		<?php endif; ?>
                     		</p>
                     	<?php endif; ?>
						<hr />						
        			<?php endforeach; ?>
        		<?php endif;?>        		
        	</div>
        	
        	<div class="col-md-6">
        		<h4><?php echo Text::_('XBMUSIC_STATIONS_ON_SERVER'); ?></h4>
        		<?php if ($this->azstations) : ?>
        			<?php if (isset($this->azstations->code)) : ?>
        			 <p class="xbit xbred"><?php echo Text::_('XBMUSIC_AZURACAST_ERROR').' '.$this->azstations->code; ?>
        			 	<br /><?php echo $this->azstations->formatted_message; ?>
        			 	<br /><?php echo Text::_('XBMUSIC_CHECK_TRY_LATER'); ?>
        			 </p>
        			<?php else : ?>
        				<p></p><?php echo Text::sprintf('XBMUSIC_STATIONS_AVAILABLE_AT', 
        				    $this->azurl, $this->azme->name); ?>
        				<br /><?php echo Text::_('XBMUSIC_BUTTONS_TO_IMPORT'); ?> 
            	    	<?php $pophead = "'".Text::_('XBMUSIC_CONFIRM_AZIMPORT')."'"; ?>
            			<?php foreach($this->azstations as $azstation) : ?>
            				<?php if ($azstation->isadmin) : ?>            				
    							<?php if(!in_array($this->azurl.'-'.$azstation->id, array_column($this->xbstations, "azurlid"))) {
                                    $btnclass = 'btn-info';
                                    $btntext = Text::_('XB_IMPORT');
                                    $popbody = "'".Text::sprintf('XBMUSIC_IMPORT_FROM',$azstation->name,$this->azurl)."'";
                                    $confirm = "doConfirm(".$popbody.",".$pophead.",'azuracast.importazstation','azwaiter');"; 
                                } 
                                ?>  								
            				<?php endif; ?>
            				<details id="azstid<?php echo $azstation->id; ?>" >
    							<summary><i></i>
    								<span class="xbit xbpl20 xbdarkgrey">AzID: <?php echo $azstation->id; ?></span>
    								<span class="xbr11"> <?php echo $azstation->name; ?></span>
                					<?php if ($azstation->isadmin) : ?>
        								<div class="pull-right">
                							<?php if (!in_array($this->azurl.'-'.$azstation->id, 
                							    array_column($this->xbstations, "azurlid"))) : ?>  								
                									<button id="impaz<?php echo $azstation->id; ?>" 
                    									class="btn btn-sm <?php echo $btnclass; ?>" type="button"
                            							onclick="document.getElementById('jform_loadazid').value=
                            							<?php echo $azstation->id.';'.$confirm; ?>;" >
                    									<i class="icon-download"></i> <?php echo $btntext; ?>
            										</button>
            								<?php else: ?>
            									<p class="xbit xbdarkgrey">Already Imported
            								<?php endif; ?></p>
        								</div>
        							<?php endif; ?>
    								<span class="xbit xbpl20 xbdarkgrey">(
     								<?php if ($azstation->isadmin) : ?>
     									<?php if ($azstation->issysadmin) : ?>
        									<?php echo Text::_('System Manager');  ?>
     									<?php else: ?>
        									<?php echo Text::_('Station Manager');  ?>
     									<?php endif; ?>
    								<?php else : ?>
    									<?php echo Text::_('not admin');  ?>
    								<?php endif; ?>
    								)</span>
    							</summary>
    							<dl class="xbgl">
    								<dt>Shortcode</dt>
    								<dd><?php echo $azstation->shortcode; ?></dd>
    								<dt>Description</dt>
    								<dd><span class="xb09"><?php echo $azstation->description; ?></span></dd>
    								
    								<dt><?php echo Text::_('XB_WEBSITE'); ?></dt>
    								<dd><a href="<?php echo $azstation->url; ?>" target="_blank">
    									<?php echo $azstation->url; ?></a></dd>
    								<dt><?php echo Text::_('XBMUSIC_PUBLIC_PAGE'); ?></dt>
    								<dd><a href="<?php echo $azstation->public_player_url; ?>" target="_blank">
    									<?php echo $azstation->public_player_url; ?></a></dd>
    								<dt><?php echo Text::_('XBMUSIC_STREAM'); ?></dt>
    								<dd><a href="<?php echo $azstation->listen_url; ?>" target="_blank">
    									<?php echo $azstation->listen_url; ?></a></dd>
    								<dt>Mount URLs</dt>
    								<dd><?php echo count($azstation->mounts); ?> mount points
                             			<?php foreach ($azstation->mounts  as $mount) {
                             	          echo '<br />'.$mount->name;
                             	          echo '<br /><span class="xbpl50">'.$mount->url.'</span>';
                             	          echo '<br /><span class="xbpl50">Listeners: '.
                                 	          $mount->listeners->current.' (unique: '.$mount->listeners->unique.' total: '.$mount->listeners->total.')';
                             	        }?>
    								</dd>
    								<?php if (isset($azstation->services)) : ?>
        								<dt>Service status</dt>
        								<dd>Frontend: 
        									<?php echo ($azstation->services->frontendRunning == 1)? 
                             	              'OK' : '<span class="xbred">Stopped</span>' ; ?> 
                             	        </dd>
        								<?php if (isset($azstation->admininfo->frontend_type)) : ?>
            								<dd>
                                         	<details>
                                         		<summary>
                                         			<?php echo $azstation->admininfo->frontend_type; ?>
                                    			</summary> 
                                    			<pre><?php echo print_r($azstation->admininfo->frontend_config,true);?>
                                    			</pre>
                                    		</details>
            								</dd>
        								<?php endif; ?>
        								<dd>Backend: 
                             	            <?php echo ($azstation->services->backendRunning == 1)? 
                             	              'OK' : '<span class="xbred">Stopped</span>' ; ?>
                             	        </dd>
        								<?php if (isset($azstation->admininfo->backend_type)) : ?>
            								<dd>
                                         	<details>
                                         		<summary>
                                         			<?php echo $azstation->admininfo->backend_type; ?>
                                    			</summary> 
                                    			<pre><?php echo print_r($azstation->admininfo->backend_config,true);?>
                                    			</pre>
                                    		</details>
            								</dd>
        								<?php endif; ?>
    								<?php endif; ?>
    								<?php if (isset($azstation->schedule)) : ?>
        								<dt>Schedule</dt>
        								<dd><?php echo (count($azstation->schedule)); ?>
        									<?php echo Text::_('schedule timeslots defined'); ?>
        								</dd>
    								<?php endif; ?>
    								<?php if (isset($azstation->quota)) : ?>
        								<dt>Disc Quota</dt>
        								<dd><?php echo $azstation->quota->used; ?>
                                 	       (<?php echo $azstation->quota->used_percent; ?>%) from  
                                        	<?php echo $azstation->quota->available; ?> allowed. 
                                        	<?php echo number_format($azstation->quota->num_files); ?> files
                                        </dd>
    								<?php endif; ?>
    								<?php if($azstation->issysadmin): ?>
        								<dd><hr /><b><i>System Admin Info</i></b></dd>
         								<?php if (isset($azstation->admininfo->timezone)) : ?>
            								<dt>Timezone</dt>
            								<dd><?php echo $azstation->admininfo->timezone; ?>
            								</dd>   									
        								<?php endif; ?>
         								<?php if (isset($azstation->admininfo->genre)) : ?>
            								<dt>Genres</dt>
            								<dd><?php echo $azstation->admininfo->genre; ?>
            								</dd>   									
        								<?php endif; ?>
         								<?php if (($azstation->admininfo->enable_requests)) : ?>
            								<dt>Requests</dt><dd>Enabled</dd>
            								<dd><?php echo 'delay '.$azstation->admininfo->request_delay; ?></dd>
            								<dd><?php echo 'threshold '.$azstation->admininfo->request_Threshold; ?>
            								</dd>   									
        								<?php endif; ?>
         								<?php if (($azstation->admininfo->enable_streamers)) : ?>
            								<dt>Streamers</dt><dd>Enabled</dd>
            								<dd><?php if($azstation->admininfo->is_streamer_live) : ?>
            									<span class="xbred">Currently Live</span>
            								<?php else : ?>
            									Not Live
            								<?php endif; ?>
            								</dd>   									
        								<?php endif; ?>
        								
        								<?php if (isset($this->indexedlocs)) : ?>
            								<dt>Media</dt>
            								<dd><?php $loc = $this->indexedlocs[$azstation->admininfo->media_storage_location];
            								    echo $loc->path;?></dd>
            								<dd>Used <?php echo $loc->storageUsed.' of '.$loc->storageQuota; ?>
            								<dt>Recordings</dt>
            								<dd><?php $loc = $this->indexedlocs[$azstation->admininfo->recordings_storage_location];
            								    echo $loc->path;?></dd>
            								<dd>Used <?php echo $loc->storageUsed.' of '.$loc->storageQuota; ?>
            								<dt>Podcasts</dt>
            								<dd><?php $loc = $this->indexedlocs[$azstation->admininfo->podcasts_storage_location];
            								    echo $loc->path;?></dd>
            								<dd>Used <?php echo $loc->storageUsed.' of '.$loc->storageQuota; ?>
        								<?php endif ;?>
    								<?php endif; ?>
    							</dl>                             	
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

        <hr />

        <h3><?php echo Text::_('Azuracast Server Info'); ?></h3>
        <p>Server URL: <?php echo $this->azurl; ?>
        
        <?php if (isset($this->item->server->code)) : ?>
	        </p><p class="xbit xbred">Current API user is not System Admin. 
	        	<br /><?php echo $this->item->server->code.' '.$this->item->server->formatted_message; ?>
	        </p>
	    <?php else: ?>
	    	<span class="xbpl50"><i><?php echo ($this->item->server->sysadmin == 2) ? 'Full system admin privileges' : 'Limited system admin privileges'; ?></i></span></p>
	    	<?php echo Text::_('Basic Server Info'); ?>
	    	<dl class="xbgl">
	    		<dt>Server Name</dt>
	    		<dd><?php echo $this->item->server->instance_name; ?></dd>
	    		
	    		<dt>Base URL</dt>
	    		<dd><?php echo $this->azurl; ?></dd>
	    			    		
	    		<dt>Azuracast version</dt>
	    		<dd><?php echo $this->item->server->update_results->current_release; ?></dd>
	    		<dd><dl>
	    		<dt>Latest release</dt>
	    		<dd><?php echo $this->item->server->update_results->latest_release; ?> 
	    			<i>Last checked</i>: <?php echo date('Y-m-d H:i',$this->item->server->update_last_run);  ?></dd>
	    		</dl></dd>	    	
	    	</dl>
	    	<?php if (isset($this->item->server->users)) : ?>
	    		<details>
	    			<summary><b><?php echo Text::_('Server Users')?></b> 
	    				<span class="xbnote">Your current API Azuracast username is in bold</span>
	    			</summary>
        	    	<?php echo count($this->item->server->users); ?> users on Server	
        	    	<dl class="xbgl">
            	    	<?php foreach ($this->item->server->users as $usr) : ?>	    		
            	    		<dt>
                	    		<?php if ($usr->is_me) echo '<b>';
                	    		echo $usr->name; ?>
                	    		<?php if ($usr->is_me) echo '</b>'; ?>
            	    		</dt>
            	    		<dd>
                	    		 <i>Roles:</i> <?php foreach ($usr->roles as $role) {
                	    		     echo ' ['.$role->name. '] ';
                	    		 } 
                	    		 echo ' <i>with '.count($usr->api_keys).' API keys</i>';?>      	    		
            	    		</dd>
            	    	<?php endforeach; ?>
        	    	</dl>
	    		</details>
	    	<?php endif; ?>
	    	
	    	<?php if (isset($this->item->server->backups)) : ?>
	    		<details>
	    			<summary><b><?php echo Text::_('Backups')?></b></summary>
        	    	<dl class="xbgl">
        	    		<dt>Last run</dt>
        	    		<dd><?php echo date('Y-m-d H:i',$this->item->server->backup_last_run); ?></dd>
        	    		
        	    		<dt>Most Recent</dt>
        	    		<dd><?php echo $this->item->server->backups[0]->path; ?></dd>
        	    		<dd><dl><dt>saved</dt>
        	    			<dd><?php echo date('Y-m-d H:i',$this->item->server->backups[0]->timestamp); ?></dd>
        	    			<dt>size</dt>
        	    			<dd><?php echo XbcommonHelper::formatBytes($this->item->server->backups[0]->size); ?>
        	    		 		<span class="xbit xbpl20">in location:</span> 
        	    		 		ID:<?php echo $this->item->server->backups[0]->storageLocationId; ?>
        	    		 		<span class="xbnote">(<?php echo Text::_('details below if available'); ?>)</span>
        	    		 	</dd>
        	    		</dl></dd>
        	    	</dl>
	    		</details>
    	    		
	    	<?php endif; ?>
	    	
	    	<?php if (isset($this->item->server->storage_locations)) : ?>
	    		<details>
	    			<summary><b><?php echo Text::_('Storage Locations')?></b>
	    				<span class="xbnote">(absolute paths on Azuracast server)</span>
	    			</summary>
    	    		
        	    	<dl class="xbgl">
            	    	<?php foreach ($this->item->server->storage_locations as $loc) : ?>	    		       	    		
            	    		<dt>ID : <?php echo $loc->id; ?></dt>
            	    		<dd><?php echo $loc->type; ?></dd>
            	    		<dd><i>Path</i>: <?php echo $loc->path; ?></dd>
            	    		<dd><i>Used</i>: 
            	    			<?php echo $loc->storageUsed.' of '.$loc->storageQuota.' ('.$loc->storageUsedPercent.'%)'; ?>
            	    		</dd>           	    		
            	    	<?php endforeach; ?>
        	    	</dl>
        	    </details>
	    	<?php endif; ?>
	    	
	    	<?php if (isset($this->item->server->serverstats)) : ?>
	    		<details>
	    			<summary><b><?php echo Text::_('Server Status')?></b> 
	    				<span class="xbnote">(<?php echo Text::_('snapshot when page loaded'); ?>)</span>	    			  	    		
        	    	</summary>
        	    	<dl class="xbgl">
        	    		<dt>CPU Cores</dt>
        	    		<dd><?php echo count($this->item->server->serverstats->cpu->cores); ?></dd>
        	    		
        	    		<dt>Memory</dt>
        	    		<dd><i>Total</i>: <?php echo $this->item->server->serverstats->memory->total_readable; ?></dd>      	    		
        	    		<dd><i>Free</i>: <?php echo $this->item->server->serverstats->memory->free_readable; ?></dd>
        	    		
        	    		<dt>Swap</dt>
        	    		<dd><i>Total</i>: <?php echo $this->item->server->serverstats->swap->total_readable; ?></dd>
        	    		<dd><i>Free</i>: <?php echo $this->item->server->serverstats->swap->free_readable; ?></dd>   	    		
        	    		
        	    		<dt>Disk</dt>
        	    		<dd><i>Total</i>: <?php echo $this->item->server->serverstats->disk->total_readable; ?></dd>
        	    		<dd><i>Free</i>: <?php echo $this->item->server->serverstats->disk->free_readable; ?></dd>        	    		
        	    	</dl>
        	    </details>
	    	<?php endif; ?>
	    	
	    	
	    	<?php //echo '<pre>'.print_r($this->item->server, true);?>
	    <?php endif; ?>
		 
        
        
		<input type="hidden" id="rem_name" name="rem_name" value="" />
		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>

<?php endif; //azuracast enabled ?>
	</form>
    <p>&nbsp;</p>
    <?php echo XbcommonHelper::credit('xbMusic');?>
    
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/closedetails.js" ></script>
    
</div>
