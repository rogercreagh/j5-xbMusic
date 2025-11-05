<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/azuracast/default.php
 * @version 0.0.59.3 5th November 2025
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

?>    
<link rel="stylesheet" href="<?php echo Uri::root(true);?>/media/com_xbmusic/css/foldertree.css">

<script type="module" src="<?php echo Uri::root(); ?>/media/com_xbmusic/js/xbdialog.js"></script>

<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=azuracast'); ?>" method="post" name="adminForm" 
<?php if (($this->azuracast == 0) || ($this->azurl == '') ) : ?>
    <div class="xbbox gradpink xbht200 xbflexvc">
        <div class="xbcentre"><h3><?php echo $this->noazmess1; ?></h3>
        	<p><?php echo $this->noazmess2; ?></p>
        </div>
    </div>
<?php else: ?>
id="adminForm">
		<input type="hidden" id="basefolder" value="<?php echo $this->basemusicfolder; ?>" />
		<input type="hidden" id="multi" value="0" />
		<input type="hidden" id="extlist" value="xxx" />
		<input type="hidden" id="posturi" value="<?php echo Uri::base(true).'/components/com_xbmusic/vendor/Foldertree.php'; ?>"/>
		<h2><?php echo Text::_('XBMUSIC_AZURACAST_STATIONS');?></h2>
         
	<?php if($this->azme == false): ?>
		<p><?php echo Text::_('XBMUSIC_AZAPI_KEY_INVALID'); ?></p>
	<?php else : ?>
		<p><?php echo Text::sprintf('XBMUSIC_USING_SERVER_ACCOUNT',$this->azurl, $this->account); ?> </p>
		<p><?php echo Text::_('Current API key'); ?>
		: <?php echo $this->apicomment; ?> <code><?php echo $this->apikey; ?></code></p>
	<?php endif; ?>
      
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
	<p class="xbtc xbnote xbmb5"><?php echo Text::_('XB_AUTOCLOSE_DROPS'); ?> <input  type="checkbox" id="autoclose" name="autoclose" value="yes" checked="true" style="margin:0 5px;" />
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
    		      		    $confirm = "doConfirm(".$popbody.",'".$pophead."','azuracast.deletestation');"; 
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
						    <?php echo '<pre>'.print_r($station,true).'</pre>'; ?>
							<br /><i>Website</i>: <a href="<?php echo $station['website']; ?>" target="_blank">
								<?php echo $station['website']; ?></a> 
							<br /><i>Az Server URL</i>: <a href="<?php echo $station['az_url']; ?>" target="_blank">
                         		<?php echo $station['az_url']; ?></a>
    	        		<?php if ($station['isadmin']) : ?>
                     	<?php endif; ?>
                     		<br /><i>Media path under <code>/xbmusic/</code></i> :
                      			<?php echo (empty($station['mediapath'])) ? 
                      			    Xbtext::_('not set',XBTRL,'xbit xbbgwhite xbred') : $station['mediapath']; ?>               
							<p class="xb09 "><i>Azuracast Station Desciption</i> :<br /><?php echo $station['description'];?></p>        
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
    							<?php if (in_array($this->azurl.'-'.$azstation->id, array_column($this->xbstations, "azurlid"))) {
                                    $btnclass = 'btn-warning';
                                    $popbody = "'".Text::sprintf('XBMUSIC_RELOAD_FROM',$azstation->name,$this->azurl)."'"; 
                                    $btntext = Text::_('XB_RELOAD');
                                } else {
                                    $btnclass = 'btn-info';
                                    $btntext = Text::_('XB_IMPORT');
                                    $popbody = "'".Text::sprintf('XBMUSIC_IMPORT_FROM',$azstation->name,$this->azurl)."'";
                                } 
                                $confirm = "doConfirm(".$popbody.",".$pophead.",'azuracast.importazstation');"; 
                                ?>  								
            				<?php endif; ?>
            				<details id="azstid<?php echo $azstation->id; ?>" >
    							<summary><i></i>
    								<span class="xbit xbpl20 xbdarkgrey">AzID: <?php echo $azstation->id; ?></span>
    								<span class="xbr11"> <?php echo $azstation->name; ?></span>
    								<div class="pull-right">
            							<?php if (!in_array($this->azurl.'-'.$azstation->id, 
            							    array_column($this->xbstations, "azurlid"))) : ?>  								
                							<?php if ($azstation->isadmin) : ?>
            									<button id="impaz<?php echo $azstation->id; ?>" 
                									class="btn btn-sm <?php echo $btnclass; ?>" type="button"
                        							onclick="document.getElementById('jform_loadazid').value=
                        							<?php echo $azstation->id.';'.$confirm; ?>;" >
                									<i class="icon-download"></i> <?php echo $btntext; ?>
        										</button>
        									<?php endif; ?>
        								<?php else: ?>
        									<p class="xbit xbdarkgrey">Already Imported</p>
        								<?php endif; ?>
    								</div>
    								<span class="xbit xbpl20 xbdarkgrey">(
     								<?php if ($azstation->isadmin) : ?>
    									<?php echo Text::_('Station Manager');  ?>
    								<?php else : ?>
    									<?php echo Text::_('not admin');  ?>
    								<?php endif; ?>
    								)</span>
    							</summary>
    							<i>AzURL</i>: <a href="<?php echo $this->azurl; ?>" target="_blank">
                             			<?php echo $this->azurl; ?></a>
                             	<br />
                             	<?php echo '<pre>'.print_r($azstation,true).'</pre>'; ?>
    							<i><?php echo Text::_('XB_WEBSITE'); ?></i>: 
    						    <a href="<?php echo $azstation->url; ?>" target="_blank">
    								<?php echo $azstation->url; ?></a>
    							<br /> 
    							<i><?php echo Text::_('XBMUSIC_STREAM'); ?></i>: 
    						    <a href="<?php echo $azstation->listen_url; ?>" target="_blank">
    								<?php echo $azstation->listen_url; ?></a> 
    							<br />
    							<i><?php echo Text::_('XBMUSIC_PUBLIC_PAGE'); ?></i>: 
    						    <a href="<?php echo $azstation->public_player_url; ?>" target="_blank">
    								<?php echo $azstation->public_player_url; ?></a> 
    							<p class="xb09"><?php echo $azstation->description;?></p>        							
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
        <p>Server URL: <?php echo $this->azurl; ?></p>
		<p>User: blah has roles foo bar 
        
        
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
