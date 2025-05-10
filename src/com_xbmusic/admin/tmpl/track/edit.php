<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/track/edit.php
 * @version 0.0.30.2 9th February 2025
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
<link rel="stylesheet" href="<?php echo Uri::root(true);?>/media/com_xbmusic/css/foldertree.css">
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

</script>
<div id="xbcomponent">
	<?php // if (($this->id3loaded) && ($item->id > 0)) Factory::getApplication()->enqueueMessage('New data loaded from file','Warning'); ?>
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=track&layout=edit&id='. (int) $item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
      <input type="hidden" id="basefolder" value="<?php echo $this->basemusicfolder; ?>" />
      <input type="hidden" id="multi" value="0" />
      <input type="hidden" id="extlist" value="mp3" />
      <input type="hidden" id="posturi" value="<?php echo Uri::base(true).'/components/com_xbmusic/vendor/Foldertree.php'; ?>"/>
      	<?php  $fpn = $this->form->getValue('filepathname'); ?>
    	<div class="row form-vertical" <?php if ((!empty($fpn) ) && (file_exists($fpn) )) echo 'style="display:none;"'; ?>>
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
       	<?php $localpath = (empty($fpn)) ? '' : str_replace($this->basemusicfolder,'',pathinfo($fpn, PATHINFO_DIRNAME)).'/'; ?>
    	<div class="row">
    		<div class="col-md-6">
    			<p><i><?php echo Text::_('Music Folder'); ?></i> : 
    				<?php echo $this->basemusicfolder; ?></p>
    			<p><i><?php echo Text::_('Track folder'); ?></i> : 
    				<?php echo $localpath; ?></p>
     		</div>
    		<div class="col-md-3">
    			<p><i><?php echo Text::_('Track file'); ?></i> : 
        			<b><?php echo (empty($fpn)) ? '' : basename($fpn); ?></b></p>
				<?php if (!empty($fpn)) : ?>        			
                    <audio controls>
                    	<source src="<?php echo Uri::root(true).'/xbmusic/'.$localpath.basename($fpn); ?>">
                    	<i>Your browser does not support the audio tag.</i>
                    </audio>        		
    			<?php endif; ?>
            </div>
    		<div class="col-md-3">
    		<?php if(!empty($item->imgurl)) : ?>
    			<div class="control-group">
    				<img class="img-polaroid hidden-phone" style="height:150px;object-fit:contain;" 
        				src="<?php echo $item->imgurl; ?>" />
    			</div>
    		<?php else : ?>
    			<div class="xbbox xbboxwht xbnit" style="width:100%;"><?php echo Text::_('XBMUSIC_NO_TRACK_IMAGE'); ?></div>
    		<?php endif; ?>
    			 
    		</div>
        </div>
        <div class="hide">
        	<?php echo $this->form->renderField('filepathname'); ?> 
        	<?php echo $this->form->renderField('filename'); ?> 
        </div>
        <hr />
    	<div class="row form-vertical">
    		<div class="col-md-10">
            	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
            	<?php if ((key_exists('title', $this->replaced)) || (key_exists('alias', $this->replaced))) : ?>
            	    <div class="row" style="margin:-25px;">
            	    	<div class="col-md-5">
            	    	<?php if (key_exists('title', $this->replaced)) : ?>
            	    		<dl class="xbdl xbred">
            	    			<dt><?php echo Text::_('Old value');?></dt>
            	    			<dd><?php echo $this->replaced['title']; ?></dd></dl>
            	    	<?php endif; ?>
            	    	</div>
            	    	<div class="col-md-5">
            	    	<?php if (key_exists('alias', $this->replaced)) : ?>
            	    		<dl class="xbdl xbred">
            	    			<dt><?php echo Text::_('Old value');?></dt>
            	    			<dd><?php echo $this->replaced['alias']; ?></dd></dl>
            	    	<?php endif; ?>
            	    	</div>
            	    </div>
            	<?php endif; ?>
    		</div>
    		<div class="col-md-2">
    			<?php echo $this->form->renderField('id'); ?> 
    		</div>
    	</div>
    	<div class="row">
    		<div class="col-md-6">
     			<?php echo $this->form->renderField('sortartist'); ?> 
    	    	<?php if (key_exists('sortartist', $this->replaced)) : ?>
    	    		<dl class="xbdl xbred">
    	    			<dt><?php echo Text::_('Old value');?></dt>
    	    			<dd><?php echo $this->replaced['sortartist']; ?></dd></dl>
    	    	<?php endif; ?>
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
            	    	<?php if (key_exists('rec_date', $this->replaced)) : ?>
            	    		<dl class="xbdl xbred">
            	    			<dt><?php echo Text::_('Old value');?></dt>
            	    			<dd><?php echo $this->replaced['rec_date']; ?></dd></dl>
            	    	<?php endif; ?>
        					<?php echo $this->form->renderField('rel_date'); ?> 
            	    	<?php if (key_exists('rel_date', $this->replaced)) : ?>
            	    		<dl class="xbdl xbred">
            	    			<dt><?php echo Text::_('Old value');?></dt>
            	    			<dd><?php echo $this->replaced['rel_date']; ?></dd></dl>
            	    	<?php endif; ?>
        					<?php echo $this->form->renderField('duration'); ?> 
            	    	<?php if (key_exists('duration', $this->replaced)) : ?>
            	    		<dl class="xbdl xbred">
            	    			<dt><?php echo Text::_('Old value');?></dt>
            	    			<dd><?php echo $this->replaced['duration']; ?></dd></dl>
            	    	<?php endif; ?>
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
            	    	<?php if (key_exists('catid', $this->replaced)) : ?>
            	    		<dl class="xbdl xbred">
            	    			<dt><?php echo Text::_('Old value');?></dt>
            	    			<dd><?php echo XbcommonHelper::getCat($this->replaced['catid'])->title; ?></dd></dl>
            	    	<?php endif; ?>
         			<?php echo $this->form->renderField('access'); ?> 
        			<?php echo $this->form->renderField('ordering'); ?> 
        			<?php echo $this->form->renderField('note'); ?> 
           		</div>
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('Tags')); ?>
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
	
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('Linked Items')); ?>
		<div class="row form-vertical">
    		<div class="col-12 col-md-3">
         		<h4><?php echo Text::_('Connections to other items')?></h4>
       			<b><?php echo Text::_('Album'); ?></b>
       			<br />
   				<?php if ($item->album_id >0) : ?>   				
   					<a href="<?php echo $albelink.$item->album['id'];?>">
   						<?php echo $item->album['title'];?></a> [<?php echo $item->album['rel_date']; ?>]
   				<?php endif; ?>
        		<hr />
        		<b><?php echo Text::_('Songs'); ?></b>
        		<?php if (!empty($item->songs)) : ?>
            		<ul>
            			<?php foreach ($item->songs as $listitem) : ?>
            				<li>
            					<a href="<?php echo $sngelink.$listitem['song_id'];?>">
            						<?php echo $listitem['title']; ?></a>        			
                			</li>
            			<?php endforeach; ?>
            		</ul>
         		<?php endif; ?>
       			<hr />
        		<b><?php echo Text::_('Artists'); ?></b>
         		<?php if (!empty($item->artists)) : ?>
            		<ul>
            			<?php foreach ($item->artists as $listitem) : ?>
            				<li>
            					<a href="<?php echo $artelink.$listitem['artist_id'];?>">
            						<?php echo $listitem['name']; ?></a>        			
                			</li>
            			<?php endforeach; ?>
	        		</ul>
        		<?php endif; ?>
        		<p class="xbnote"><?php echo Text::_('Links above are to edit page for the item'); ?></p>
        		<?php if ($this->id3loaded==1) : ?>
        			<hr />
        			<div class="xbred">
        			</div>
        			<h4><?php echo Text::_('Items from ID3 data'); ?></h4>
        			<?php if (!empty($this->id3data['albumdata']))
        			    echo '<i>'.Text::_('Album').'</i>: '.$this->id3data['albumdata']['title'].'<hr />';
    			    if (!empty($this->id3data['song']))
    			        echo '<i>'.Text::_('Song').'</i>: '.$this->id3data['song']['title'].'<hr />';
    			    if (!empty($this->id3data['artists'])) {
    		            echo '<i>'.Text::_('Artists').'</i>:'.'<ul style="list-style:none;">';
    		            foreach ($this->id3data['artists'] as $artist) {
    		                echo '<li>'.$artist['name'].'</li>';
    		            }
    		            echo '</ul>';
    			    }
        			?>
        			<p class="xbnote">
        				<?php echo Text::_('Items above will be created if necessary and linked to this track on Save'); ?>
        			</p>
        		<?php endif; ?>
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
    		<hr />
    		<?php echo $this->form->renderField('ext_links');?>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'image', Text::_('Image')); ?>
        	<div class="row">
           		<div class="col-12 col-md-5">
                    <?php $imgurl = $this->form->getValue('imgurl'); ?>
                    <?php if (empty($imgurl)) : ?>
                    	<p class="xbnit"><?php echo Text::_('artwork not yet available, either load from music file or selected and existing image');?></p>
                    <?php else : ?>
                    	<?php if (key_exists('imgurl', $this->replaced)) {
                    	    echo Text::_('New image reloaded from music file');
                    	} else {
                    	   echo Text::_('Artwork'); 
                    	} ?><br/>
                    	<img src="<?php echo $imgurl; ?>" style/>
                    <?php endif; ?>
				</div>        		
           		<div class="col-12 col-md-7">
					<fieldset id="pv_desc" class="xbbox xbboxwht xbyscroll">
						<legend>Image details</legend>
						<p><?php $imgpath = '';
						if (!empty($item->url)) : ?>
							<?php $imgpath = str_replace(Uri::root(),JPATH_ROOT.'/',$item->imgurl);
                            if (file_exists($imgpath)) : ?>
                            	<i><?php echo Text::_('Image file'); ?></i> : 
                            		<?php echo str_replace(Uri::root(), '', $item->imgurl); ?>
                            <?php else : ?>
                                <?php echo Text::_('Local image file missing.'); ?>
                            <?php endif; ?>
						<?php else : ?>
                            <?php echo Text::_('No image file saved yet.'); ?>
 						<?php endif; ?>
    					<?php echo $this->form->renderField('imgurl');
    					echo $this->form->renderField('image_type'); ?> 
    					<?php echo $this->form->renderField('image_desc'); ?> 
    					<?php if (!empty($item->imageinfo)) : ?>
    						<p class="xbbold xbit"><?php echo Text::_('Original ID3 image data');?></p>
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
    					<?php 
    					if (($imgpath !='') && (file_exists($imgpath))) : ?>
    						<p class="xbbold xbit"><?php echo Text::_('Saved local image data'); ?></p>
    						<?php $imageinfo = getimagesize($imgpath);    						
    						?>
    						<dl class="xbdl">
    						
        						<dt><?php echo Text::_('Type'); ?>:</dt>
        						<dd><?php echo $imageinfo['mime'];?></dd>
        						<dt><?php echo Text::_('Dimensions'); ?>:</dt>
        						<dd><?php echo $imageinfo[0];?>&nbsp;x&nbsp;
        						<?php echo $imageinfo[1];?> px</dd>
        						<dt><?php echo Text::_('Size'); ?>:</dt>
        						<dd><?php echo number_format(filesize($imgpath)/1024, 2);?> kB</dd>
    						</dl>
						<?php else : ?>    
							<p class="xbit"><?php echo Text::_('local image not available'); ?></p>
												
    					<?php endif; ?>
					</fieldset>
				</div>
        	</div>
			<div class="row">
           		<div class="col-12 col-lg-5 form-vertical">
           			<?php if (key_exists('imgurl', $this->replaced)) : ?>
           				<p class="xbit xbred"><?php echo Text::_('Previous image'); ?></p>
           				<img src="<?php echo $this->replaced['imgurl']; ?>" style="max-width:200px; height:auto;" />
           				<p><span class="xbnote xbred"><?php echo Text::_('to restore before saving copy url below to Image Url field above right'); ?></span>
           				<br /><b><?php echo $this->replaced['imgurl']; ?></b></p>
           			<?php endif; ?>
                    <p><?php echo Text::_('Select an alternative image to be used on save'); ?></p>
                    <?php echo $this->form->renderField('picture_options'); ?>
                    <?php echo $this->form->renderField('picturefile'); ?>
                    <?php echo $this->form->renderField('noalbimgnote'); ?>
                    <?php echo $this->form->renderField('albumimage'); ?> 
					
				</div>
           		<div class="col-12 col-lg-7">
           			<p>If you load a different image it will be used for the track within xbMusic, but will not be saved back to the file. External applications (eg Azuracast) will still use the original image.</p>
           			<p>An option to save the image back to ID3 dat ain the music file, and also to replace the Azuracast image file if that has been linked, will appear here in a future version of xbMusic</p>
   				</div>
			</div>
        
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'id3', Text::_('ID3 data')); ?>
        	<div class="row">
           		<div class="col-12 col-md-6">
           			<p><?php echo Text::_('Saved raw data from file'); ?>
					<?php if (!empty($item->id3_tags)) : ?>
    					<fieldset id="id3dets" class="xbbox xbboxwht ">
    						<legend>Saved ID3 Tags</legend>
    						<dl class="xbdl">
                        		<?php foreach ($item->id3_tags as $key=>$value) : ?>
                        			<?php if (is_array($value)) $value=implode(', ', $value); ?>
                            			<dt><?php echo $key; ?></dt>
                            			<dd><?php if (is_string($value)) {
                            			    echo $value;
                            			} else {
                            			    echo print_r($value,true);
                            			}?></dd>
                        		<?php endforeach; ?>        
    						</dl>
    					</fieldset>
					<?php else : ?>
						<p class="xbit"><?php echo Text::_('no id3 tags have been saved yet'); ?></p>
					<?php endif; ?>
 					<?php if (!empty($item->fileinfo)) : ?>
    					<fieldset id="id3dets" class="xbbox xbboxwht ">
    						<legend>Fileinfo</legend>
    						<dl class="xbdl">
                        		<?php foreach ($item->fileinfo as $key=>$value) : ?>
                        			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                        		<?php endforeach; ?>        
    						</dl>
    					</fieldset>
					<?php else : ?>
						<p class="xbit"><?php echo Text::_('file info has not been saved yet'); ?></p>
					<?php endif; ?>
					<?php if (!empty($item->audioinfo)) : ?>
    					<fieldset id="id3dets" class="xbbox xbboxwht ">
    						<legend>Saved Audio Info</legend>
    						<dl class="xbdl">
                        		<?php foreach ($item->audioinfo as $key=>$value) : ?>
                        			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                        		<?php endforeach; ?>        
    						</dl>
    					</fieldset>
					<?php else : ?>
						<p class="xbit"><?php echo Text::_('audio info has not been saved yet'); ?></p>
					<?php endif; ?>
        		</div>
        		
        		<div class="col-12 col-md-6">
        			<?php if ($this->id3loaded==1) : ?>
        				<p><?php echo Text::_('Reloaded file data'); ?></p>
    					<?php if (!empty($this->id3data['id3tags'])) : ?>
        					<fieldset id="id3dets" class="xbbox xbboxwht ">
        						<legend>Reloaded ID3 Tags</legend>
        						<dl class="xbdl">
                            		<?php $id3data = json_decode($this->id3data['id3tags']);
                            		foreach ($id3data as $key=>$value) : ?>                            		
                            			<dt><?php echo $key; ?></dt>
                            			<dd><?php if (is_string($value)) {
                            			    echo $value;
                            			} else {
                            			    echo print_r($value,true);
                            			}?></dd>
                            		<?php endforeach; ?>        
        						</dl>
        					</fieldset>
    					<?php else : ?>
    						<p class="xbit"><?php echo Text::_('no id3 tags reloaded'); ?></p>
    					<?php endif; ?>
     					<?php if (!empty($this->id3data['fileinfo'])) : ?>
        					<fieldset id="id3dets" class="xbbox xbboxwht ">
        						<legend>Reloaded Fileinfo</legend>
        						<dl class="xbdl">
                            		<?php $fileinfo = json_decode($this->id3data['fileinfo']);
                            		foreach ($fileinfo as $key=>$value) : ?>
                            			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                            		<?php endforeach; ?>        
        						</dl>
        					</fieldset>
    					<?php else : ?>
    						<p class="xbit"><?php echo Text::_('file info has not been saved yet'); ?></p>
    					<?php endif; ?>
    					<?php if (!empty($this->id3data['audioinfo'])) : ?>
        					<fieldset id="id3dets" class="xbbox xbboxwht ">
        						<legend>Reloaded Audio Info</legend>
        						<dl class="xbdl">
                            		<?php $audioinfo = json_decode($this->id3data['audioinfo']);
                            		foreach ($audioinfo as $key=>$value) : ?>
                            			<dt><?php echo $key; ?></dt><dd><?php echo $value; ?></dd>
                            		<?php endforeach; ?>        
        						</dl>
        					</fieldset>
    					<?php else : ?>
    						<p class="xbit"><?php echo Text::_('audio info has not been saved yet'); ?></p>
    					<?php endif; ?>


        			<?php else : ?>
        				<p class="xbnote"><?php echo Text::_('if file data is reloaded the replacement data will appear here for checking before it is saved'); ?></p>
        			<?php endif; ?>
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
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
<script>
       updatePvMd();
       document.getElementById("jform_description").addEventListener("input", (event) => updatePvMd());
</script>
