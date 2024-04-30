<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/track/edit.php
 * @version 0.0.4.1 26th April 2024
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
    	<p class="xbnit">Base folder to find music files is <code><?php echo $this->basemusicfolder; ?></code> which is set in xbMusic Options.
    	</p>
    	<div class="row form-vertical">
    		<div class="col-md-6">
    			<?php echo $this->form->renderField('pathname'); ?> 
    		</div>
    		<div class="col-md-6">
    			<?php $session = Factory::getApplication()->getSession();
                    $musicpath = $session->get('musicfolder','');
        			if (is_dir($musicpath)) {
           			  $this->form->setFieldAttribute('filename','directory',$musicpath);
        			}
                    $session->clear('musicfolder');
    			?>
    			<?php echo $this->form->renderField('filename'); ?> 
    		</div>
    	</div>
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
  					<div class="row">
		           		<div class="col-12 col-lg-6">
        					<?php echo $this->form->renderField('rec_date'); ?> 
        				</div>
		           		<div class="col-12 col-lg-6">
        					<?php echo $this->form->renderField('rel_year'); ?> 
        				</div>
        			</div>
  					<div class="row">
		           		<div class="col-12 col-lg-6">
        					<?php echo $this->form->renderField('description'); ?> 
        				</div>
		           		<div class="col-12 col-lg-6">
		           			<?php echo Text::_('Preview Markdown'); ?>
							<div id="pv_desc" class="xbbox xbboxwht" style="height:80%;">
        					</div> 
        				</div>
        			</div>
  					<div class="row">
		           		<div class="col-12 col-lg-6">
        					<?php echo $this->form->renderField('picturefile'); ?> 
        				</div>
		           		<div class="col-12 col-lg-6">
		           			
		           			<?php echo Text::_('id3 Picture'); ?>
							<?php echo '<img src="data:image/jpeg;base64,'.base64_encode($this->item->id3_picture).'"/>';  ?>
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

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'id3', Text::_('ID3 data')); ?>
        
        <div class="row">
        	<div class="col-12 col-lg-4">
        	file & audio metadata - filesize, mime-type & format, playtime, bitrate, samplerate & mode, channels & mode, encoder, compression ratio
        	</div>
        	<div class="col-12 col-lg-4">
        	id3 data - song title, artist name, album title, genre, track no, year, ...and more
        	</div>
        	<div class="col-12 col-lg-4">
       			<?php echo Text::_('id3 Picture'); ?>
       			
				<?php echo '<img src="data:image/jpeg;base64,'.base64_encode($this->item->id3_picture).'"/>';  ?>
        	</div>
        </div>
        
        
         <?php echo HTMLHelper::_('uitab.endTab'); ?>
        
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('Tag Groups')); ?>
			<div class="row">
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'albums', Text::_('Albums')); ?>
			<div class="row">
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>
         
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'artists', Text::_('Artists')); ?>
			<div class="row">
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'playlists', Text::_('Plyalists')); ?>
			<div class="row">
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
