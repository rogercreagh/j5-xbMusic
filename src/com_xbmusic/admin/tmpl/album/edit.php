<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/album/edit.php
 * @version 0.0.30.1 7th February 2025
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
->useScript('xbmusic.showdown');

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($item->attribs));

$artistelink = 'index.php?option=com_xbmusic&task=artist.edit&id=';
$songelink = 'index.php?option=com_xbmusic&task=song.edit&id=';
$trackelink = 'index.php?option=com_xbmusic&task=track.edit&id=';

$input = Factory::getApplication()->getInput();
$item = $this->item;
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

</script>
<div id="xbcomponent">
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=album&layout=edit&id='. (int) $item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
    	<p class="xbnit">
    	<?php if ($item->id == 0 ) : ?>
    		<?php echo Text::sprintf('XBMUSIC_MUSIC_LOCATION',$this->basemusicfolder); ?>
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
      			<?php echo $this->form->renderField('subtitle'); ?> 
     		</div>
    		<div class="col-md-6">
     			<?php echo $this->form->renderField('albumartist'); ?> 
     			<?php echo $this->form->renderField('sortartist'); ?> 
     		</div>
     	</div>
    	<hr />
     <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('XB_GENERAL')); ?>
			<div class="row form-vertical">
           		<div class="col-12 col-lg-9">
   					<div class="row xb09">
		           		<div class="col-12 col-lg-5">
						</div>   					
		           		<div class="col-12 col-lg-7">
        					<?php echo $this->form->renderField('rel_date'); ?> 
           				</div>
        			</div>
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

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('XB_TAGS')); ?>
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
        <div class="row">
        	<div class="col-12">
				<?php echo $this->form->renderField('albumlinksnote'); ?> 
        		<h4><?php echo Text::_('XB_LINKS_OTHER_ITEMS')?></h4>
        		<b><?php echo Text::_('XBMUSIC_TRACKS'); ?></b>
        		<table class="xbtablehgrid">
        			<thead>
        				<tr>
        					<th><?php echo Text::_('XBMUSIC_TRACK'); ?></th>
        					<th><?php echo Text::_('XBMUSIC_ARTISTS_TRACK'); ?></th>
        					<th><?php echo Text::_('XBMUSIC_SONGS_TRACK'); ?></th>
        				</tr>
        			</thead>
        			<tbody>
        			<?php foreach ($item->tracks as $track) :
        			    if($item->num_discs > 1) {
        			        $track['trackno'] = ((int)$track['discno']*100)+$track['trackno'];
        			    } ?>
        			    <tr>
        			    	<td>
        			    		<?php if ($track['trackno'] >0 ) echo $track['trackno'].'&nbsp '; 
                    			    echo '<a href="'.$trackelink.$track['trackid'].'">'.$track['tracktitle'].'</a>';
           			    		?>
        			    	</td>
        			    	<td>
        			    		<?php if (!empty($track['artistlist'])) {
        			    		    foreach ($track['artistlist'] as $artist) {
        			    		        echo '<a href="'.$artistelink.$artist['artistid'].'">'.$artist['artistname'].'</a><br />';
        			    		    }
        			    		} ?>
        			    	</td>
        			    	<td>
        			    		<?php if (!empty($track['songlist'])) {
        			    		    foreach ($track['songlist'] as $song) {
        			    		        echo '<a href="'.$songelink.$song['songid'].'">'.$song['songtitle'].'</a><br />';
        			    		    }
        			    		} ?>
        			    	</td>
        			    </tr>
        			    <?php endforeach; ?>
        			</tbody>
        		</table>
        		<p class="xbnote"><?php echo Text::_('XB_LINKS_NOTE_ITEM_EYCON'); ?></p>
        	</div>
			<hr />
            <?php echo $this->form->renderField('ext_links');?>
        </div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'image', Text::_('XB_IMAGES')); ?>
        	<div class="row">
           		<div class="col-12 col-md-5">
           			<h4><?php echo Text::_('XBMUSIC_PRIME_ALBUM_IMG'); ?></h4>
					<img src="<?php echo $item->imgurl; ?>" />
					<?php echo $this->form->renderField('imgurl'); ?> 
				</div>        		
           		<div class="col-12 col-md-7">
           			<h4><?php echo Text::_('XBMUSIC_PRIME_IMG_DETAILS'); ?></h4>
					<div>
                      <dl class="xbdl">
                      	<dt><?php echo Text::_('XB_TITLE'); ?></dt><dd><?php echo $item->imageinfo->imagetitle; ?></dd>
                      	<dt><?php echo Text::_('XB_DESCRIPTION'); ?></dt><dd><?php echo $item->imageinfo->imagedesc; ?></dd>
                      	<dt><?php echo Text::_('XB_FILENAME'); ?></dt><dd><?php echo $item->imageinfo->basename; ?></dd>
                        <dt><?php echo Text::_('XB_FOLDER'); ?></dt><dd><?php echo $item->imageinfo->folder; ?></dd>
                        <dt><?php echo Text::_('XB_FILESIZE'); ?></dt><dd><?php echo $item->imageinfo->filesize; ?></dd>
                        <dt><?php echo Text::_('XB_FILEDATE'); ?></dt><dd><?php echo $item->imageinfo->filedate;?></dd>
                        <dt><?php echo Text::_('XB_DIMENSIONS'); ?></dt><dd><?php echo $item->imageinfo->filewidth.' x '.$item->imageinfo->fileht.' px'; ?></dd>
                        <dt><?php echo Text::_('XB_MIME_TYPE'); ?></dt><dd><?php echo $item->imageinfo->filemime; ?></dd>                        
                      </dl>
						<p class="xbnote">
						<?php if(isset($item->imageinfo->datalength)) {
						    echo Text::_('XBMUSIC_ALBUM_IMAGE_FROM');
						    if ($item->imageinfo->fileht < $item->imageinfo->image_height) {
						        echo Xbtext::_('XBMUSIC_AND_RESIZED_FROM',XBSP3);
						        echo $item->imageinfo->image_width.' x '.$item->imageinfo->image_height.' px';
						    }
						}
						?></p>
					</div>
				</div>
        	</div>
        	<hr />
			<div class="row">
           		<div class="col-12 col-lg-6 form-vertical">
           			<h4><?php echo Text::_('XBMUSIC_ALBUM_IMAGE_SELECT')?></h4>
					<?php echo $this->form->renderField('newimage'); ?> 
           			<p class="xbnote">an option to also save the image to all tracks will appear here 
				</div>
           		<div class="col-12 col-lg-6">
           			<h4><?php echo Text::_('XBMUSIC_EDIT_IMG_TITLEDESC')?></h4>
					<?php echo $this->form->renderField('newimagetitle'); ?>
					<?php echo $this->form->renderField('newimagedesc'); ?>
   				</div>
			</div>        
			<div class="row">
           		<div class="col-12 col-lg-6 form-vertical">
           			<p class="xbnote">a proposed future enhancement is to allow multiple images for albums. eg to include back and cover and inside sleeve notes</p>
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
