<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/playlist/edit.php
 * @version 0.0.42.3 15th March 2025
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

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.showdown')
->useScript('xbmusic.getplaylists');

$root = Uri::root();
$document = Factory::getApplication()->getDocument();
$document->addScriptOptions('com_xbmusic.uri', array("root" => $root));

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$input = Factory::getApplication()->getInput();

?>
<script>
	function clearmd() {
    	var descMd = document.getElementById('jform_description').value;
    	var converter = new showdown.Converter();
        var descHtml = converter.makeHtml(descMd);
		var descText = stripHtml(descHtml);
        document.getElementById('jform_description').value = descText;
        updatePvMd();
	}
	
    function stripHtml(html) {
	   let doc = new DOMParser().parseFromString(html, 'text/html');
	   return doc.body.textContent || "";
    }

	function updatePvMd() {
    	var descText = document.getElementById('jform_description').value;
		var converter = new showdown.Converter();
        var descHtml = converter.makeHtml(descText);
		document.getElementById('pv_desc').innerHTML= descHtml;
    }
 	
 	function loadplaylist(azid) {
 		document.getElementById('jform_az_id').value = azid;
 		document.getElementById('jform_az_dbstid').value = document.getElementById('jform_azstation').value;
 		Joomla.submitform('playlist.loadplaylist',document.getElementById('item-form'));
 	}
 	
</script>
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
    	<?php if (($this->azuracast == 1) || ($this->item->azdbstid > 0)) : ?>
    		<div class="row">
        		<?php if ($this->item->az_id > 0) : ?>
        			<div class="col-md-6">
        				<div class="pull-right importlist">
         					<button type="button" class="btn btn-danger btn-sm" onclick="unlinkaz();">Disconnect Azuracast</button>
         				</div>
        				<?php echo Text::_('Azuracast playlist').' '.$this->item->az_id.' : '.$this->item->az_name; ?>
        			</div>
        			<div class="col-md-6">
        			</div>
         		<?php endif; ?>
				<?php if ($this->stncnt == 0)  : ?>
    				<p>No radio stations have been defined yet, visit DataManager to import stations from Azuracast using the credentials below set in Config Options
    				<br />APIname: <code><?php echo $this->az_apiname; ?></code> at <code><?php echo $this->az_url; ?></code></p>
				<?php else : ?>
        			<div class="col-md-6" id="loadstations" >
        				<p>To import a playlist from Azuracast first select the station to import from.
        					<br /><?php echo $this->form->renderField('azstation'); ?>
        				</p>
        			</div>
        			<div class="col-md-6" id="loadplaylists" >
        				<p>To import a playlist from Azuracast select playlist</p>
        				<div id="playlists"></div>   				
        			</div>
        		<?php endif; ?>
            </div>
			<?php if (isset($this->station)) : ?>
    				<p><?php echo Text::_('Azuracast station').' '.$this->station['title'].' at '.$this->station['az_url']; ?>
    		<?php endif; ?>
    				   		
    	<?php endif; ?>
		<?php echo $this->form->renderField('az_dbstid'); ?>
		<?php echo $this->form->renderField('az_id'); ?>    	<hr />
     <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true]); ?>

    	<?php if(($this->azuracast == 1) && ($this->item->az_dbstid) > 0) : ?>

	        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'azuracast', 'Azuracast'); ?>
	        
	        	<h4><?php echo Text::_('Azuracast Specific Values'); ?></h4>
	        	<p class="xbnote"><?php Text::_('Use the button below to sync changes with Azuracast'); ?>
	        	</p>
	    
        		<?php $this->form->renderField('az_name') ;?>
        		<?php $this->form->renderField('az_type') ;?>
        		<?php $this->form->renderField('az_cntper') ;?>
        		<?php $this->form->renderField('az_jingle') ;?>
        		<?php $this->form->renderField('az_weight') ;?>
        	
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
