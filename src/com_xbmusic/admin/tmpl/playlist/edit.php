<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/playlist/edit.php
 * @version 0.0.42.6 23rd March 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

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
->useScript('joomla.dialog');

$root = Uri::root();
$document = Factory::getApplication()->getDocument();
$document->addScriptOptions('com_xbmusic.uri', array("root" => $root));

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$input = Factory::getApplication()->getInput();

?>
<script type="module" src="/media/com_xbmusic/js/xbdialog.js"></script>

<div id="xbcomponent">
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=playlist&layout=edit&id='. (int) $this->item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
    	<div class="row form-vertical">
    		<div class="col-md-10">
            	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    		</div>
    		<div class="col-md-2">
    			<?php echo $this->form->renderField('id'); ?> 
    		</div>
    	</div>
    	<?php if ($this->azuracast == 1) : ?>
    		<div class="row">
				<?php if ($this->stncnt == 0)  : ?>
    				<p><?php echo Text::_('No radio stations have been defined yet, visit DataManager to import stations from Azuracast using the credentials defined in Config Options'); ?>
    				<br /><?php echo Text::_('Current credentials'); ?> : APIname: <code><?php echo $this->az_apiname; ?></code> 
    				at <code><?php echo $this->az_url; ?></code></p>
				<?php elseif ($this->item->az_id > 0) : ?>
        			<div class="col-md-6">
    					<p><i><?php echo Text::_('Azuracast station'); ?></i> : 
    						<?php echo $this->station['title'].' at '.$this->station['az_url']; ?>
        				<br /><i><?php echo Text::_('Azuracast playlist'); ?></i> : 
        					<?php echo $this->item->az_id.' - '.$this->item->az_name; ?>
        				</p>
        			</div>
        			<div class="col-md-6">
            	    	<?php $popbody = "'Unlink playlist id:'+document.getElementById('jform_az_id').value+' from Azuracast'"; 
            	    	  $pophead = 'Confirm Unlink Playlist from Azuracast'; 
            	    	  $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.unlinkplaylist');"; 
            	    	  ?>                
            	    	 <p><button id="reload" class="btn btn-danger icon-white" type="button" 
                    		onclick="<?php echo $confirm; ?>" >
            					<i class="fas fa-link-slash"></i> &nbsp; 
                    			<?php echo Text::_('Unlink from to Azuracast'); ?>
                    		</button>        		
            			</p>
        			</div>				
				<?php else : ?>
        			<div class="col-md-6" id="loadstations" >
        				<p><?php echo Text::_('To import a playlist from Azuracast first select the station'); ?>
        					<br /><?php echo $this->form->renderField('azstation'); ?>
        					<br /><?php echo Text::_('if the station you want is not listed')?>
        				</p>
        			</div>
        			<div class="col-md-6" id="loadplaylists" >
        				<p><?php echo Text::_('Select playlist to import'); ?></p>
        				<div id="playlists"></div>   				
        			</div>				    				   
         		<?php endif; ?>
            </div>
			<?php if (isset($this->station)) : ?>
    		<?php endif; ?>
    				   		
    	<?php endif; ?>
		<?php echo $this->form->renderField('az_dbstid'); ?>
		<?php echo $this->form->renderField('az_id'); ?>    	<hr />
     <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true]); ?>

    	<?php if(($this->azuracast == 1) && ($this->item->az_dbstid) > 0) : ?>

	        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'azuracast', 'Azuracast'); ?>
	        
	        	<h4><?php echo Text::_('Azuracast Specific Values'); ?></h4>
	    		<div class="row">
	    			<div class="col-md-6">
           				<p><?php echo Text::_('Local data'); ?></p>
                		<?php echo $this->form->renderField('az_name') ;?>
                		<?php echo $this->form->renderField('az_type') ;?>
                		<?php echo $this->form->renderField('az_cntper') ;?>
                		<?php echo $this->form->renderField('az_jingle') ;?>
                		<?php echo $this->form->renderField('az_weight') ;?>
                		<?php echo $this->form->renderField('az_order') ;?>
                		<p><?php echo Text::_('If you edit the settings above then they will not take effect on the stastion until you Push the changes back to Azuracast. Other fields listed in the right hand panel are not editable within xbMusic, and have no impact on xbMusic views.')?></p>
                		<?php if($this->azchanged == true) : ?>
                			<p class="xbred"><?php echo Text::_('xbMusic data no longer matches Azuracast data - please reload from Azuracast or push changes to Azuracast'); ?></p>
                		<?php endif; ?>
	    			</div>
	    			<div class="col-md-6">
           				<p><?php echo Text::_('Saved raw data from Azuracast'); ?></p>
    					<?php if (!empty($this->item->az_info)) : ?>
        					<fieldset id="azinfo" class="xbbox xbboxwht ">
        						<legend><?php echo Text::_('Azuracast Settings'); ?></legend>
        						<dl class="xbdl">
                            		<?php foreach ($this->item->az_info as $key=>$value) : ?>
                            			<?php if ($key == 'total_length') 
                            			    $value = $this->frmtlength; ?>
                            			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                            		<?php endforeach; ?>        
        						</dl>
        					</fieldset>
        					<p class="info"><?php echo Text::_('Check Schedule tab for Azuracast schedule entries'); ?></p>
    					<?php else : ?>
    						<p class="xbit"><?php echo Text::_('Azuracast Info Missing'); ?></p>
    					<?php endif; ?>
        			</div>
        		</div>
                <?php if($this->azchanged == true) : ?>
            		<div class="row">
            			<div class="col-md-6">
    	        			<p class="xbnote">
    	        				<?php echo Text::_('Use Reload button to get settings from Azuracast - will overwrite any local changes'); ?>
    	        				<br /><?php echo Text::_('Use Push button to post changes to Azuracast - will overwrite any changes there'); ?>
    	        			</p>
            			</div>
            			<div class="col-md-6">
        					<div class="pull-left">
                    	    	<?php $popbody = "'Reloading playlist id:'+document.getElementById('jform_az_id').value+
                                                ' from station id: X'"; 
                    	    	      $pophead = 'Confirm Reload playlist from Azuracast'; 
                    	    	      $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.reloadplaylist');"; 
                    	    	  ?>                
                    	    	 <p><button id="reload" class="btn btn-info" type="button" 
                            		onclick="<?php echo $confirm; ?>" >
                    					<i class="icon-download icon-black"></i> 
                            			<?php echo Text::_('Reload from Azuracast'); ?>
                            		</button>        		
                    			</p>
        					</div>
        					<div class="pull-right">
                    	    	<?php $popbody = "'Write changes back to playlist id:'+document.getElementById('jform_az_id').value+
                                                ' on Azuracast'"; 
                    	    	      $pophead = 'Confirm Put changes to Azuracast'; 
                    	    	      $confirm = "doConfirm(".$popbody.",'".$pophead."','playlist.putplaylist');"; 
                    	    	  ?>                
                    	    	 <p><button id="reload" class="btn btn-warning" type="button" 
                            		onclick="<?php echo $confirm; ?>" >
                    					<i class="icon-upload icon-white"></i> 
                            			<?php echo Text::_('Put changes to Azuracast'); ?>
                            		</button>        		
                    			</p>
        					</div>
            			</div>
            		</div>
        		<?php endif; ?>
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
         
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('XB_LINKS')); ?>
		<div class="row form-vertical">
    		<div class="col-12 col-md-3">
     			<h4><?php echo Text::_('XBMUSIC_LINKS_TO_TRACKS')?></h4>
       			<?php if (isset($item->tracks)) : ?>  			
        		<ul>
        			<?php foreach ($item->tracks as $listitem) : ?>
        				<li>
        					<a href="<?php echo $trackelink.$listitem['track_id'];?>">
        						<?php echo $listitem['title']; ?></a> [<?php echo $listitem['artist']; ?>]        			
            			</li>
        			<?php endforeach; ?>
        		</ul>
        		<?php else : ?>
            		<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_TRACKS_LISTED'); ?></p>
        		<?php endif; ?>
			</div>
    		<div class="col-12 col-md-9">
	       		<div class="xbmh800 xbyscroll">
					<?php echo $this->form->renderField('tracklist'); ?>
				</div>
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
