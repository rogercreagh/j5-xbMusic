<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/artist/edit.php
 * @version 0.0.30.6 15th February 2025
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
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
->useScript('form.validate')
->useScript('xbmusic.showdown');

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$albumelink = 'index.php?option=com_xbmusic&task=album.edit&id=';
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
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=artist&layout=edit&id='.(int)$item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
    	<div class="row form-vertical">
    		<div class="col-md-7">
				<?php echo $this->form->renderField('name'); ?> 
				<?php echo $this->form->renderField('alias'); ?> 
						
            	<?php // echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    		</div>
    		<div class="col-md-2">
    			<?php echo $this->form->renderField('id'); ?> 
    		</div>
    		<div class="col-md-3">
        		<?php if($item->imgurl) : ?>
        			<div class="control-group">
        				<img class="img-polaroid hidden-phone" style="height:200px;object-fit:contain;" 
            				src="<?php echo $item->imgurl; ?>" />
        			</div>
        		<?php else : ?>
        			<div class="xbbox xbboxwht xbnit" style="width:100%;"><?php echo Text::_('XBMUSIC_NO_ARTIST_IMAGE'); ?></div>
    		<?php endif; ?>   			 
    		</div>
    	</div>
    	<div class="row">
			<div class="row">
           		<div class="col-12 col-lg-6">
					<?php echo $this->form->renderField('type'); ?> 
				</div>   	
				<?php if (XbcommonHelper::checkComponent('com_xbpeople',true)) : ?>				
           			<div class="col-12 col-lg-6">
						<?php //echo $this->form->renderField('group_id'); ?> 
						<?php //echo $this->form->renderField('person_id'); ?> 
					</div>
				<?php endif; ?>
				<?php if (!($item->type > 0)) : ?>
					<p class="xbnit xbred"><?php echo Text::_('XBMUSIC_SET_INDIVIDUAL_GROUP'); ?></p>
				<?php endif; ?>
				<?php if (($item->type == 2) && (!empty($item->members))) : ?>
					<p class="xbr09"><span class="xbit"><?php echo Text::_('XBMUSIC_GROUP_MEMBERS');?></span>:
					<?php
                        $list = '';  
                        foreach ($item->members as $member) {
                            $list .= $member['membername'];
                            if ($member['role']) $list.= ' ('.$member['role']. ')';
                            $list .=', ';
                        }
                        echo trim($list,', ');
                    ?></p>
				<?php endif; ?>
				<?php if (($item->type == 1) && (!empty($item->groups))) : ?>
					<p class="xbr09"><span class="xbit"><?php echo Text::sprintf('XBMUSIC_MEMBER_OF_GROUPS','');?></span>:
					<?php
                        $list = '';  
                        foreach ($item->groups as $group) {
                            $list .= $group['groupname'];
                            if ($group['role']) $list.= ' ('.$group['role']. ')';
                            $list .=', ';
                        }
                        echo trim($list,', ');
                    ?></p>
				<?php endif; ?>
			</div>
    	</div>
    	<hr />
     <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true]); ?>

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
		<?php echo $this->form->renderField('artistlinksnote'); ?>	    
		<div class="row form-vertical">
    		<div class="col-12 col-md-3">
     		<h4><?php echo Text::_('XB_LINKS_OTHER_ITEMS')?></h4>
     		<hr />
     		<?php if($this->form->getValue('type')==1): ?>
     			<b><?php echo (count($item->groups) == 1) ? Text::_('XBMUSIC_MEMBER_ONE_GROUP') : 
     			    Text::sprintf('XBMUSIC_MEMBER_OF_GROUPS', count($item->groups)); ?></b>
        		<ul>
        			<?php foreach ($item->groups as $listitem) : ?>
        				<li>
        					<a href="<?php echo $artistelink.$listitem['group_id'];?>">
        						<?php echo $listitem['groupname']; ?></a>
    						<?php $info = '';
    						    if ($listitem['role']) $info .= $listitem['role'].' ';
    					       if ($listitem['since']) $info .= $listitem['since'].' - '; ;      			
    					       if ($listitem['until']) $info .= $listitem['until']; 
    					       if ($info != '') echo '<br /><i>'.$info.'</i>'; 
    					    ?>
            			</li>
        			<?php endforeach; ?>
        		</ul>
    		<hr />
     			
     		<?php elseif($this->form->getValue('type')==2): ?>
     			<b><?php echo (count($item->members) == 1) ? Text::_('XBMUSIC_GROUP_MEMBERS_ONE') :
     			    Text::sprintf('XBMUSIC_GROUP_MEMBERS_LISTED',count($item->members)); ?></b>
        		<ul>
        			<?php foreach ($item->members as $listitem) : ?>
        				<li>
        					<a href="<?php echo $artistelink.$listitem['member_id'];?>">
        						<?php echo $listitem['membername']; ?></a>
    						<?php $info = '';
    						  if ($listitem['role']) $info .= $listitem['role'].' ';
    					       if ($listitem['since']) $info .= $listitem['since'].' - '; ;      			
    					       if ($listitem['until']) $info .= $listitem['until']; 
    					       if ($info != '') echo '<br /><i>'.$info.'</i>'; 
    					    ?>
            			</li>
        			<?php endforeach; ?>
        		</ul>
        		<hr />
			<?php endif; ?>
               <?php if (isset($item->albums)) : ?>
   			<b><?php echo count($item->albums).' '.Text::_('XBMUSIC_ALBUMS'); ?></b>
    		<ul>
    			<?php foreach ($item->albums as $listitem) : ?>
    				<li>
    					<a href="<?php echo $albumelink.$listitem['albumid'];?>">
    						<?php echo $listitem['albumtitle']; ?></a>
    						<?php if($listitem['rel_date']) echo '['.$listitem['rel_date'].']'; ?>    			
        			</li>
    			<?php endforeach; ?>
    		</ul>
  				<?php else : ?>
  					<p><i><?php echo Text::_('XBMUSIC_NO_ALBUMS_LISTED'); ?></i></p>
 				<?php endif; ?>
    		<hr />
              <?php if (isset($item->tracks)) : ?>
    		<b><?php echo count($item->tracks).' '.Text::_('XBMUSIC_TRACKS'); ?></b>
    		<ul>
    			<?php foreach ($item->tracks as $listitem) : ?>
    				<li>
    					<a href="<?php echo $trackelink.$listitem['track_id'];?>">
    						<?php echo $listitem['title']; ?></a> 
    						<?php if($listitem['rel_date']) echo '['.$listitem['rel_date'].']'; ?>      			
        			</li>
    			<?php endforeach; ?>
    		</ul>
  				<?php else : ?>
  					<p><i><?php echo Text::_('XBMUSIC_NO_TRACKS_LISTED'); ?></i></p>
 				<?php endif; ?>
    		<hr />
              <?php if (isset($item->songs)) : ?>
    		<b><?php echo count($item->songs).' '.Text::_('XBMUSIC_SONGS'); ?></b>
    		<ul>
    			<?php foreach ($item->songs as $listitem) : ?>
    				<li>
    					<a href="<?php echo $songelink.$listitem['songid'];?>">
    						<?php echo $listitem['songtitle']; ?></a>        			
        			</li>
    			<?php endforeach; ?>
    		</ul>
  				<?php else : ?>
  					<p><i><?php echo Text::_('XBMUSIC_NO_SONGS_LISTED'); ?></i></p>
 				<?php endif; ?>
    		<hr />
    		<p class="xbnote"><?php echo Text::_('XBMUSIC_LINKS_TO_EDIT'); ?> 
    		  <?php Text::_('XBMUSIC_EYECON_HINT'); ?></p>
    		</div>
	       	<div class="col-12 col-md-9">
         		<?php if($item->type == 1): ?>
					<?php echo $this->form->renderField('grouplist'); ?>
							
         		<?php elseif($item->type ==2): ?>
					<?php echo $this->form->renderField('memberlist'); ?>	
         		<?php endif; ?>
	       		<div class="xbmh800 xbyscroll">
					<?php echo $this->form->renderField('tracklist'); ?>	
	       		</div>
			</div>
		</div>
		<hr />
		<?php echo $this->form->renderField('ext_links');?>
       		
     <?php echo HTMLHelper::_('uitab.endTab'); ?>
         
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'image', Text::_('XB_IMAGE')); ?>
        	<div class="row">
           		<div class="col-12 col-md-5">
           			<h4><?php echo Text::_('XBMUSIC_ARTIST_IMAGE'); ?></h4>
					<img src="<?php echo $item->imgurl; ?>" />
					<?php echo $this->form->renderField('imgurl'); ?>
				</div>        		
           		<div class="col-12 col-md-7">
           			<h4><?php echo Text::_('XBMUSIC_IMAGE_DETAILS'); ?></h4>
           			<?php if (!empty($item->imageinfo)) : ?>
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
					</div>
					<?php else : ?>
						<p class="xbnit xbbold xbred"><?php echo Text::_('XBMUSIC_IMAGE_NOT_SAVED')?></p>
					<?php endif; ?>
				</div>
        	</div>
        	<hr />
			<div class="row">
           		<div class="col-12 col-lg-6 form-horizontal">
           			<h4><?php echo Text::_('XBMUSIC_SELECT_NEW_IMAGE')?></h4>
					<?php echo $this->form->renderField('newimage'); ?> 			
				</div>
           		<div class="col-12 col-lg-6">
           			<h4><?php echo Text::_('XBMUSIC_EDIT_IMG_TITLEDESC')?></h4>
					<?php echo $this->form->renderField('newimagetitle'); ?>
					<?php echo $this->form->renderField('newimagedesc'); ?>
   				</div>
			</div>        
			<div class="row">
           		<div class="col-12 col-lg-6 form-vertical">
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
    <input type="hidden" name="task" id="task" value="artist.edit" />
    <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
<script>
       updatePvMd();
       document.getElementById("jform_description").addEventListener("input", (event) => updatePvMd());
</script>
