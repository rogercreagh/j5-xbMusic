<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/song/edit.php
 * @version 0.0.40.0 18th February 2025
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
// use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.showdown');

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$artistelink = 'index.php?option=com_xbmusic&task=artist.edit&id=';
$albumelink = 'index.php?option=com_xbmusic&task=album.edit&id=';
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
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=song&layout=edit&id='. (int) $item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
    	<div class="row form-vertical">
    		<div class="col-md-10">
            	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    		</div>
    		<div class="col-md-2">
    			<?php echo $this->form->renderField('id'); ?> 
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
        					<?php echo $this->form->renderField('comp_date'); ?> 
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
	
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('Links')); ?>
		<?php echo $this->form->renderField('songlinksnote'); ?>	    
		<div class="row form-vertical">
    		<div class="col-12 col-md-3">
     		<h4><?php echo Text::_('XB_LINKS_OTHER_ITEMS')?></h4>
     		<hr />
   			<b><?php echo Text::_('Albums'); ?></b>
   			<?php if (isset($item->albums)) : ?>  			
    		<ul>
    			<?php foreach ($item->albums as $listitem) : ?>
    				<li>
    					<a href="<?php echo $albumelink.$listitem['albumid'];?>">
    						<?php echo $listitem['albumtitle']; ?></a> [<?php echo $listitem['rel_date']; ?>]       			
        			</li>
    			<?php endforeach; ?>
    		</ul>
    		<?php else : ?>
        		<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_ALBUMS_LISTED'); ?></p>
    		<?php endif; ?>
    		<hr />
    		<b><?php echo Text::_('Tracks'); ?></b>
   			<?php if (isset($item->tracks)) : ?>  			
    		<ul>
    			<?php foreach ($item->tracks as $listitem) : ?>
    				<li>
    					<a href="<?php echo $trackelink.$listitem['track_id'];?>">
    						<?php echo $listitem['title']; ?></a> [<?php echo $listitem['rel_date']; ?>]        			
        			</li>
    			<?php endforeach; ?>
    		</ul>
    		<?php else : ?>
        		<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_TRACKS_LISTED'); ?></p>
    		<?php endif; ?>
    		<hr />
   			<?php if (isset($item->artists)) : ?>  			
    		<b><?php echo Text::_('Artists'); ?></b>
    		<ul>
    			<?php foreach ($item->artists as $listitem) : ?>
    				<li>
    					<a href="<?php echo $artistelink.$listitem['artistid'];?>">
    						<?php echo $listitem['artistname']; ?></a>        			
        			</li>
    			<?php endforeach; ?>
    		</ul>
    		<?php else : ?>
        		<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_ARTISTS_LISTED'); ?></p>
    		<?php endif; ?>
    		<hr />
    		<p class="xbnote"><?php echo Text::_('XBMUSIC_LINKS_TO_EDIT'); ?></p>
    		</div>
	       	<div class="col-12 col-md-9">
	       		<div class="xbmh800 xbyscroll">
					<?php echo $this->form->renderField('tracklist'); ?>
				</div>
			</div>
		</div>
		</hr>
		<?php echo $this->form->renderField('ext_links');?>
       		
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
    <input type="hidden" name="task" id="task" value="song.edit" />
    <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
<script>
       updatePvMd();
       document.getElementById("jform_description").addEventListener("input", (event) => updatePvMd());
</script>
