<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/track/edit.php
 * @version 0.0.19.1 25th November 2024
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

HTMLHelper::_('jquery.framework');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.foldertree')
->useScript('xbmusic.showdown');

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$artelink = 'index.php?option=com_xbmusic&task=artist.edit&id=';
$albelink = 'index.php?option=com_xbmusic&task=album.edit&id=';
$sngelink = 'index.php?option=com_xbmusic&task=song.edit&id=';

$input = Factory::getApplication()->getInput();
$item = $this->item;

?>
<link rel="stylesheet" href="/media/com_xbmusic/css/foldertree.css">
<script type="text/javascript" >
</script>
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
</script>
<div id="xbcomponent">
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=track&layout=edit&id='. (int) $item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
      <input type="hidden" id="basefolder" value="<?php echo $this->basemusicfolder; ?>" />
      <input type="hidden" id="multi" value="0" />
      <input type="hidden" id="extlist" value="mp3" />
      <input type="hidden" id="posturi" value="<?php echo Uri::base(true).'/components/com_xbmusic/vendor/Foldertree.php'; ?>"/>
    	<p class="xbnit">
    	<?php if ($item->id == 0 ) : ?>
   			<?php //$this->form->setFieldAttribute('pathname','directory',$this->basemusicfolder); ?>
     		<?php //$this->form->setFieldAttribute('getid3onsave','default','1'); ?>
     		
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
     	<?php if (($item->id == 0) || (!file_exists($item->filepathname)) ) : ?>
        	<div class="row form-vertical">
       			<div class="col-md-6">
       				<p><?php echo Text::_('Select music track')?>
			    	<div id="container"> </div>
        		</div>
        		<div class="col-md-6">
                	<!-- <div id="selected_file">Selected filepath will appear here</div> -->
                	<p> </p>
        			<?php echo $this->form->renderField('foldername'); ?> 
                	<?php echo $this->form->renderField('selectedfiles'); ?> 
                 	<?php // echo $this->form->renderField('getid3onsave'); ?>
               </div>
     		</div>
        	<div class="row">
        	</div>
    	<?php else: ?>
        	<?php $localpath = str_replace($this->basemusicfolder,'',pathinfo($item->filepathname, PATHINFO_DIRNAME)).'/'; ?>
        	<div class="row">
        		<div class="col-md-6">
        			<p><i><?php echo Text::_('Music Folder'); ?></i> : 
        				<?php echo $this->basemusicfolder; ?></p>
        			<p><i><?php echo Text::_('Track folder'); ?></i> : 
        				<?php echo $localpath; ?></p>
         		</div>
        		<div class="col-md-6">
        			<p><i><?php echo Text::_('Track file'); ?></i> : 
        				<b><?php echo $item->filename; ?></b></p>
                    <audio controls>
                    	<source src="<?php echo '/xbmusic/'.$localpath.$item->filename; ?>">
                    	Your browser does not support the audio tag.
                    </audio>        		
        			<?php //echo $this->form->renderField('getid3onsave'); ?>
                </div>
            </div>
    	<?php endif; ?>
        <div class="hide">
        	<?php echo $this->form->renderField('filepathname'); ?> 
        	<?php echo $this->form->renderField('filename'); ?> 
        </div>
        <hr />
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
    						<?php if (!empty($item->fileinfo)) : ?>
        					<fieldset id="filedets" class="xbbox xbboxwht xbyscroll">
        						<legend>File details</legend>
    		           			<dl class="xbdl">
    		           				<dt><?php echo Text::_('Duration'); ?></dt>
    		           				<dd><?php echo $item->fileinfo->playtime_string; ?></dd>
    		           				<dt><?php echo Text::_('Type'); ?></dt>
    		           				<dd><?php echo $item->fileinfo->mime_type.' ('.$item->fileinfo->fileformat.')'; ?></dd>
    		           				<dt><?php echo Text::_('File size'); ?></dt>
    		           				<dd><?php echo number_format($item->fileinfo->filesize/1024,2).'kB'; ?></dd>
    		           				<dt><?php echo Text::_('Bitrate'); ?></dt>
    		           				<dd><?php echo number_format($item->audioinfo->bitrate/1000,0).'bps,'; ?>
    		           				<?php echo Text::_('mode').' '.$item->audioinfo->bitrate_mode; ?></dd>
    		           				<dt><?php echo Text::_('Channels'); ?></dt>
    		           				<dd><?php echo $item->audioinfo->channels; ?>
    		           				<?php echo $item->audioinfo->channelmode; ?></dd>
    		           				<dt><?php echo Text::_('Sample rate'); ?></dt>
    		           				<dd><?php echo number_format($item->audioinfo->sample_rate/1000,1).'kHz'; ?></dd>
    		           				<dt><?php echo Text::_('Effective compression ratio'); ?></dt>
    		           				<dd><?php echo number_format((1-$item->audioinfo->compression_ratio)*100,1).'%'; ?></dd>
    		           			</dl>
        					</fieldset>
        					<?php else : ?>
        						<p class="xbit"><?php echo Text::_('fileinfo not available from id3 data'); ?></p>
        					<?php endif; ?>
						</div>   					
		           		<div class="col-12 col-lg-7">
        					<?php echo $this->form->renderField('rec_date'); ?> 
        					<?php echo $this->form->renderField('rel_date'); ?> 
        					<?php echo $this->form->renderField('duration'); ?> 
    	        			<?php //echo $item->id3_tags->duration; ?>
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

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'image', Text::_('Image')); ?>
        	<div class="row">
           		<div class="col-12 col-md-5">
           			<?php if (empty($item->imgurl)) : ?>
           				<?php if ($item->id == 0) : ?>
           					<p class="xbit"><?php echo Text::_('When you save file artwork will be loaded from ID3 data if available'); ?></p>
           				<?php else: ?>
           					<p class="xbit"><?php echo Text::_('No artowrk specified. You can either save and load from ID3 if the music file has been updated, or save and copy from the album if one is specified and has a picture, or choose a picture below - this will also become the album image if one does not exist when you save the track.'); ?>
           					<?php $this->form->renderField('picture_options'); ?>
							<?php $this->form->renderField('picturefile'); ?> 
           				<?php endif; ?>
           				<?php echo Text::_('')?>
           			<?php else : ?>
	           			<?php echo Text::_('Artwork'); ?><br/>
						<img src="<?php echo $item->imgurl; ?>" />
					<?php endif; ?>
				</div>        		
           		<div class="col-12 col-md-7">
					<fieldset id="pv_desc" class="xbbox xbboxwht xbyscroll">
						<legend>Image details</legend>
    					<?php echo $this->form->renderField('image_type'); ?> 
    					<?php echo $this->form->renderField('image_desc'); ?> 
    					<?php if (!empty($item->imageinfo)) : ?>
    						<dl class="xbdl">
        						<dt><?php echo Text::_('Type'); ?>:</dt>
        						<dd><?php echo $item->imageinfo->image_mime;?></dd>
        						<dt><?php echo Text::_('Dimensions'); ?>:</dt>
        						<dd><?php echo $item->imageinfo->image_width;?>&nbsp;x&nbsp;
        						<?php echo $item->imageinfo->image_height;?> px</dd>
        						<dt><?php echo Text::_('Size'); ?>:</dt>
        						<dd><?php echo number_format($item->imageinfo->datalength/1024, 2);?> kB</dd>
    						</dl>
    					<?php else : ?>
    						<p class="xbit"><?php echo Text::_('imageinfo not available from id3 data'); ?></p>
    					<?php endif; ?>
					</fieldset>
				</div>
        	</div>
			<div class="row">
           		<div class="col-12 col-lg-6">
				</div>
           		<div class="col-12 col-lg-6">
           			
   				</div>
			</div>
        
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'id3', Text::_('ID3 data')); ?>
        	<div class="row">
           		<div class="col-12 col-md-5">
					<?php if (!empty($item->id3_tags)) : ?>
    					<fieldset id="id3dets" class="xbbox xbboxwht ">
    						<legend>ID3 Comment Tags</legend>
    						<dl class="xbdl">
                        		<?php foreach ($item->id3_tags as $key=>$value) : ?>
                        			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                        		<?php endforeach; ?>        
    						</dl>
    					</fieldset>
					<?php else : ?>
						<p class="xbit"><?php echo Text::_('id3 data not yet loaded or not available'); ?></p>
					<?php endif; ?>
        		</div>
        		<div class="col-12 col-md-7">
        			<p>Reload ID3, display new, if diff option to resave with new</p>
        		</div>
        	</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>
        
       <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('Tags')); ?>
			<div class="row">
				<div class="col-12 col-md-4">
         			<?php echo $this->form->renderField('tags'); ?> 
         		</div>
				<div class="col-md-8">
					<?php if (!empty($this->tagparentids)) : ?>
						<?php echo $this->form->renderFieldset('taggroups'); ?>
					<?php else: ?>
						<p class="xbnote"><?php echo Text::_('You can define groups for different types of tags by specifying a group parent tags in the options and they will be listed separately here - eg "genres" and "places" might be useful group parents'); ?></p>
 					<?php endif; ?>
				</div>
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>
	
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('Linked Items')); ?>
			<div class="row form-vertical">
    		<div class="col-12 col-md-3">
     		<h4><?php echo Text::_('Connections to other items')?></h4>
   			<b><?php echo Text::_('Album'); ?></b>
   				<br /><a href="<?php echo $albelink.$item->album['id'];?>">
   					<?php echo $item->album['title'];?></a> [<?php echo $item->album['rel_date']; ?>]
    		<hr />
    		<b><?php echo Text::_('Songs'); ?></b>
    		<ul>
    			<?php foreach ($item->songs as $listitem) : ?>
    				<li>
    					<a href="<?php echo $sngelink.$listitem['song_id'];?>">
    						<?php echo $listitem['title']; ?></a>        			
        			</li>
    			<?php endforeach; ?>
    		</ul>
    		<hr />
    		<b><?php echo Text::_('Artists'); ?></b>
    		<ul>
    			<?php foreach ($item->artists as $listitem) : ?>
    				<li>
    					<a href="<?php echo $artelink.$listitem['artist_id'];?>">
    						<?php echo $listitem['name']; ?></a>        			
        			</li>
    			<?php endforeach; ?>
    		</ul>
    		<p class="xbnote"><?php echo Text::_('Links above are to edit page for the item'); ?></p>
    		</div>
	       	<div class="col-12 col-md-9">
	       		<div class="row form-vertical">
    				<div class="col-12 col-md-6">
    					<?php echo $this->form->renderField('album_id'); ?> 
    				</div>
    				<div class="col-12 col-md-3 xbctl150">
    					<?php echo $this->form->renderField('discno'); ?> 
    				</div>
    				<div class="col-12 col-md-3 xbctl150">
    					<?php echo $this->form->renderField('trackno'); ?> 
    				</div>
	       		</div>
				<?php echo $this->form->renderField('songlist'); ?>	
        		<?php echo $this->form->renderField('artistlist');?>
			</div>
    		</hr>
    		<?php echo $this->form->renderField('ext_links');?>
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
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
<script>
       updatePvMd();
       document.getElementById("jform_description").addEventListener("input", (event) => updatePvMd());
</script>
