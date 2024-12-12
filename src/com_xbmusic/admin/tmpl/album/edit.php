<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/album/edit.php
 * @version 0.0.19.1 24th November 2024
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

$artelink = 'index.php?option=com_xbmusic&task=artist.edit&id=';
$sngelink = 'index.php?option=com_xbmusic&task=song.edit&id=';
$trkelink = 'index.php?option=com_xbmusic&task=track.edit&id=';

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

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('General')); ?>
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
           			<h4><?php echo Text::_('Primary Album Image'); ?></h4>
					<img src="<?php echo $item->imgurl; ?>" />
					<?php echo $this->form->renderField('imgurl'); ?> 
				</div>        		
           		<div class="col-12 col-md-7">
           			<h4><?php echo Text::_('Primary image details'); ?></h4>
					<div>
                      <dl class="xbdl">
                      	<dt>Title</dt><dd><?php echo $item->imageinfo->imagetitle; ?></dd>
                      	<dt>Description</dt><dd><?php echo $item->imageinfo->imagedesc; ?></dd>
                      	<dt>Filename</dt><dd><?php echo $item->imageinfo->basename; ?></dd>
                        <dt>Folder</dt><dd><?php echo $item->imageinfo->folder; ?></dd>
                        <dt>Filesize</dt><dd><?php echo $item->imageinfo->filesize; ?></dd>
                        <dt>FileDate</dt><dd><?php echo $item->imageinfo->filedate;?></dd>
                        <dt>Dimensions</dt><dd><?php echo $item->imageinfo->filewidth.' x '.$item->imageinfo->fileht.' px'; ?></dd>
                        <dt>Mime type</dt><dd><?php echo $item->imageinfo->filemime; ?></dd>                        
                      </dl>
						<p class="xbnote">
						<?php if(isset($item->imageinfo->datalength)) {
						    echo Text::_('Album Image has been taken from a track ID3 data');
						    if ($item->imageinfo->fileht < $item->imageinfo->image_height) {
						        echo Xbtext::_('and resized from ',XBT_SP_BOTH);
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
           			<h4><?php echo Text::_('Select new image for Album only')?></h4>
					<?php echo $this->form->renderField('newimage'); ?> 
           			<p class="xbnote">an option to also save the image to all tracks will appear here 
				</div>
           		<div class="col-12 col-lg-6">
           			<h4><?php echo Text::_('Edit title and description for album')?></h4>
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
	
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('Links')); ?>
        <div class="row">
        	<div class="col-12 col-md-3">
        		<h4><?php echo Text::_('Connections to other items')?></h4>
        		<b><?php echo Text::_('Tracks'); ?></b>
        		<ol>
        			<?php foreach ($item->tracks as $track) {
        			    if($item->num_discs > 1) {
        			        $track['trackno'] = ((int)$track['discno']*100)+$track['trackno'];
        			    }
        			    echo '<li value="'.$track['trackno'].'">';
        			    echo '<a href="'.$trkelink.$track['track_id'].'">'.$track['title'].'</a></li>';
        			}?>
        		</ol>
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
				<?php echo $this->form->renderField('albumlinksnote'); ?> 
				<div class="form-vertical">
    				<?php echo $this->form->renderField('tracklist'); ?> 
    				<?php echo $this->form->renderField('songlist'); ?> 
    				<?php echo $this->form->renderField('artistlist'); ?> 
		        </div>
		    </div>
			<hr />
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
