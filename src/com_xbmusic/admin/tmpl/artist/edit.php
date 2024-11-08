<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/artist/edit.php
 * @version 0.0.18.8 8th November 2024
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
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=artist&layout=edit&id='. (int) $this->item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
    	<div class="row form-vertical">
    		<div class="col-md-10">
            	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
    		</div>
    		<div class="col-md-2">
    			<?php echo $this->form->renderField('id'); ?> 
    		</div>
    	</div>
    	<div class="row">
			<div class="row">
           		<div class="col-12 col-lg-6">
					<?php echo $this->form->renderField('type'); ?> 
				</div>   	
				<?php if (XbcommonHelper::checkComponent('com_xbpeople',true)) : ?>				
           			<div class="col-12 col-lg-6">
						<?php echo $this->form->renderField('group_id'); ?> 
						<?php echo $this->form->renderField('person_id'); ?> 
					</div>
				<?php endif; ?>
				<?php if (($this->item->type == 2) && ($this->item->groupmembers)) : ?>
					<p class="xbr09"><span class="xbit"><?php echo Text::_('Group Members');?></span>:
					<?php
                        $list = '';  
                        foreach ($this->item->groupmembers as $member) {
                            $list .= $member['artistname'];
                            if ($member['role']) $list.= ' ('.$member['role']. ')';
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

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('General')); ?>
			<div class="row form-vertical">
           		<div class="col-12 col-lg-9">
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
         			<?php echo $this->form->renderField('tags'); ?> 
         			<?php echo $this->form->renderField('access'); ?> 
        			<?php echo $this->form->renderField('ordering'); ?> 
        			<?php echo $this->form->renderField('note'); ?> 
           		</div>
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php if (!empty($this->tagparentids)) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('Tag Groups')); ?>
			<div class="row">
				<?php echo $this->form->renderFieldset('taggroups'); ?>
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php endif; ?>
	
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('Linked Items')); ?>
			<div class="row">
				<div class="col-12">
					<?php echo $this->form->renderField('tracklist'); ?>	
				</div>
				<div class="col-12">
					<?php echo Text::_('Albums'); ?><br />
					<?php if (is_array($this->item->albums)) : ?>
						<?php if (count($this->item->albums) > 1) : ?>
							<details>
								<summary>
									<p class="xbit"><?php echo Text::sprintf('Found on %s albums',count($this->item->albums)); ?></p>
								</summary>
								<ul>
									<?php foreach ($this->item->albums as $album) : ?>
									    <li>
									    	<?php echo $album['albumtitle'];
									    	if ($album['rel_date']) echo ' ('.$album['rel_date'].')'; ?>
									    </li>
									<?php endforeach; ?>
								</ul>
							</details>
						<?php elseif (count($this->item->albums)==1) :?>
							<?php $album = $this->item->albums[0]; ?>
							<p><?php echo $album['albumtitle'];
                                if ($album['rel_date']) echo ' ('.$album['rel_date'].')'; ?>							
						<?php endif; ?>
					<?php else: ?>
						<p class="xbit"><?php echo Text::_('Not found on any albums'); ?></p>
					<?php endif; ?>
				</div>
				<div class="col-12">
					<?php echo Text::_('Single tracks not listed with an album'); ?><br />
					<?php if (is_array($this->item->singles)) : ?>
						<?php if (count($this->item->singles) > 1) : ?>
							<details>
								<summary>
									<p class="xbit"><?php echo Text::sprintf('%s single tracks found',count($this->item->singles)); ?></p>
								</summary>
								<ul>
									<?php foreach ($this->item->singles as $single) : ?>
									    <li>
									    	<?php echo $single['tracktitle'];
									    	if ($single['rel_date']) echo ' ('.$single['rel_date'].')'; ?>
									    </li>
									<?php endforeach; ?>
								</ul>
							</details>
						<?php elseif (count($this->item->singles)==1) :?>
							<?php $single = $this->item->singles[0]; ?>
							<p><?php echo $single['albumtitle'];
							if ($single['rel_date']) echo ' ('.$single['rel_date'].')'; ?>							
						<?php endif; ?>
					<?php else: ?>
						<p class="xbit"><?php echo Text::_('No single tracks listed'); ?></p>
					<?php endif; ?>
				</div>
    		</div>
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
