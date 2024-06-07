<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/track/edit.php
 * @version 0.0.6.11 2nd June 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.showdown');

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

 	function postFolder() {
 		document.getElementById('task').value='track.setfolder';
 		this.form.submit();
 	}
//     	var userdata = {'id':mydata,'name':myname};
//         jQuery.ajax({
//                 type: "POST",
//                 url: "YOUR PHP URL HERE",
//                 data:userdata, 
//                 success: function(data){
//                     console.log(data);
//                 }
//                 });
</script>
<div id="xbcomponent">
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=track&layout=edit&id='. (int) $this->item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
    	<p class="xbnit">
    	<?php if ($this->item->id == 0 ) : ?>
    		Default base folder to find music files from <code><?php echo $this->basemusicfolder; ?></code> This is set in xbMusic Options.
   			<?php $this->form->setFieldAttribute('pathname','directory',$this->basemusicfolder); ?>
    	<?php else : ?>
    	<?php endif; ?>
		<?php 
            $session = Factory::getApplication()->getSession();
            $musicpath = $session->get('musicfolder','');
			if (is_dir($musicpath)) {
			}
            $session->clear('musicfolder');
		?>
    	</p>
    	<div class="row form-vertical">
     	<?php if ($this->item->id == 0 ) : ?>
    		<div class="col-md-6">
    			<?php echo $this->form->renderField('pathname'); ?> 
    		</div>
    		<div class="col-md-6">
    			<?php echo $this->form->renderField('filename'); ?> 
    		</div>
    	<?php else: ?>
    		<div class="col-md-6">
    			<p>Track folder : <?php echo $this->item->pathname; ?></p>
     		</div>
    		<div class="col-md-6">
    			<p>Track file : <?php echo $this->item->filename; ?></p>
    		</div>
    	<?php endif; ?>
    	</div>
    	<div class="row form-vertical">
    		<div class="col-md-10">
            	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    		</div>
    		<div class="col-md-2">
    			<?php echo $this->form->renderField('id'); ?> 
    		</div>
    	</div>
    	<div class="row">
    		<div class="col-md-6">
     			<?php echo $this->form->renderField('sortartist'); ?> 
     		</div>
     	</div>
    	<hr />
     <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('General')); ?>
			<div class="row form-vertical">
           		<div class="col-12 col-lg-9">
   					<div class="row xb09">
		           		<div class="col-12 col-lg-5">
        					<fieldset id="filedets" class="xbbox xbboxwht xbyscroll">
        						<legend>File details</legend>
    		           			<dl class="xbdl">
    		           				<dt><?php echo Text::_('Duration'); ?></dt>
    		           				<dd><?php echo $this->item->fileinfo->playtime_string; ?></dd>
    		           				<dt><?php echo Text::_('Type'); ?></dt>
    		           				<dd><?php echo $this->item->fileinfo->mime_type.' ('.$this->item->fileinfo->fileformat.')'; ?></dd>
    		           				<dt><?php echo Text::_('File size'); ?></dt>
    		           				<dd><?php echo number_format($this->item->fileinfo->filesize/1024,2).'kB'; ?></dd>
    		           				<dt><?php echo Text::_('Bitrate'); ?></dt>
    		           				<dd><?php echo number_format($this->item->audioinfo->bitrate/1000,0).'bps,'; ?>
    		           				<?php echo Text::_('mode').' '.$this->item->audioinfo->bitrate_mode; ?></dd>
    		           				<dt><?php echo Text::_('Channels'); ?></dt>
    		           				<dd><?php echo $this->item->audioinfo->channels; ?>
    		           				<?php echo $this->item->audioinfo->channelmode; ?></dd>
    		           				<dt><?php echo Text::_('Sample rate'); ?></dt>
    		           				<dd><?php echo number_format($this->item->audioinfo->sample_rate/1000,1).'kHz'; ?></dd>
    		           				<dt><?php echo Text::_('Effective compression ratio'); ?></dt>
    		           				<dd><?php echo number_format((1-$this->item->audioinfo->compression_ratio)*100,1).'%'; ?></dd>
    		           			</dl>
        					</fieldset>
						</div>   					
		           		<div class="col-12 col-lg-7">
        					<?php echo $this->form->renderField('rec_date'); ?> 
        					<?php echo $this->form->renderField('rel_date'); ?> 
        					<?php echo $this->form->renderField('duration'); ?> 
    	        			<?php //echo $this->item->id3_tags->duration; ?>
           				</div>
        			</div>
  					<div class="row">
		  	     		<div class="col-12 col-lg-6">
        					<?php echo $this->form->renderField('description'); ?> 
        				</div>
		           		<div class="col-12 col-lg-6">
		           			<div class="control-group"><div class="control-label" style="width:90%;">
		           					<?php echo Text::_('Preview with Markdown formatting'); ?>
		           				</div>
								<div id="pv_desc" class="xbbox xbboxwht" style="height:23.5rem; overflow-y:scroll;">
		           				</div>
        					</div> 
        				</div>
        			</div>
            		<?php echo $this->form->renderField('ext_links');?>

	   			</div>
           		<div class="col-12 col-lg-3">
        			<?php echo $this->form->renderField('status'); ?> 
        			<?php echo $this->form->renderField('catid'); ?> 
         			<?php echo $this->form->renderField('tags'); ?> 
         			<?php echo $this->form->renderField('access'); ?> 
        			<?php echo $this->form->renderField('ordering'); ?> 
        			<?php echo $this->form->renderField('note'); ?> 
           		</div>
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'image', Text::_('Image')); ?>
        	<div class="row">
           		<div class="col-12 col-md-5">
           			<?php echo Text::_('id3 Image'); ?><br/>
					<img src="<?php echo $this->item->artwork; ?>" />
					<br />[LOAD NEW IMAGE] (modal selector)
				</div>        		
           		<div class="col-12 col-md-7">
					<fieldset id="pv_desc" class="xbbox xbboxwht xbyscroll">
						<legend>Image details</legend>
    					<?php echo $this->form->renderField('image_type'); ?> 
    					<?php echo $this->form->renderField('image_desc'); ?> 
						<dl class="xbdl">
    						<dt><?php echo Text::_('Type'); ?>:</dt>
    						<dd><?php echo $this->item->imageinfo->image_mime;?></dd>
    						<dt><?php echo Text::_('Dimensions'); ?>:</dt>
    						<dd><?php echo $this->item->imageinfo->image_width;?>&nbsp;x&nbsp;
    						<?php echo $this->item->imageinfo->image_height;?> px</dd>
    						<dt><?php echo Text::_('Size'); ?>:</dt>
    						<dd><?php echo number_format($this->item->imageinfo->datalength/1024, 2);?> kB</dd>
						</dl>
					</fieldset>
				</div>
        	</div>
			<div class="row">
           		<div class="col-12 col-lg-6">
					<?php //echo $this->form->renderField('picturefile'); ?> 
				</div>
           		<div class="col-12 col-lg-6">
           			
   				</div>
			</div>
        
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'id3', Text::_('ID3 data')); ?>
        	<div class="row">
           		<div class="col-12 col-md-5">
					<fieldset id="id3dets" class="xbbox xbboxwht ">
						<legend>ID3 Comment Tags</legend>
						<dl class="xbdl">
                    		<?php foreach ($this->item->id3_tags as $key=>$value) : ?>
                    			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                    		<?php endforeach; ?>        
						</dl>
					</fieldset>
        		</div>
        		<div class="col-12 col-md-7">
        			<p>Reload ID3, display new, if diff option to resave with new</p>
        		</div>
        	</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>
        
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('Tag Groups')); ?>
			<div class="row">
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'albums', Text::_('Linked Items')); ?>
			<div class="row">
				<div class="col-12 col-md-6">
					<?php echo $this->form->renderField('album_id'); ?> 
				</div>
				<div class="col-12 col-md-3">
					<?php echo $this->form->renderField('discno'); ?> 
				</div>
				<div class="col-12 col-md-3">
					<?php echo $this->form->renderField('trackno'); ?> 
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<?php echo $this->form->renderField('artistlist'); ?> 
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<?php echo $this->form->renderField('songlist'); ?> 
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					Playlists
				</div>
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('Publishing')); ?>
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
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('Permissions')); ?>
            <fieldset id="fieldset-rules" class="options-form">
                <legend><?php echo Text::_('User Group Permissions'); ?></legend>
                <div>
                	<?php echo $this->form->getInput('rules'); ?>
                </div>
            </fieldset>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    	<hr />
    </div>	
    <input type="hidden" name="task" id="task" value="track.edit" />
    <input type="hidden" name="newfolder" id="newfolder" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <div class="clearfix"></div>
    <?php echo XbmusicHelper::credit('xbMusic');?>
</div>
<script>
       updatePvMd();
       document.getElementById("jform_description").addEventListener("input", (event) => updatePvMd());
</script>
