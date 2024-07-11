<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/track/edit.php
 * @version 0.0.11.2 11th July 2024
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

HTMLHelper::_('jquery.framework');

?>
<link rel="stylesheet" href="/media/com_xbmusic/css/filetree.css">
<script type="text/javascript" >
$(document).ready( function() {

	$( '#container' ).html( '<ul class="filetree start"><li class="wait">' + 'Generating Tree...' + '<li></ul>' );
	
	getfilelist( $('#container') , '<?php echo $this->basemusicfolder; ?>' );
	
	function getfilelist( cont, root ) {
	
		$( cont ).addClass( 'wait' );
			
		$.post( <?php echo "'/administrator/components/com_xbmusic/tmpl/track/Foldertree.php'"; ?>, { dir: root }, function( data ) {
	
			$( cont ).find( '.start' ).html( '' );
			$( cont ).removeClass( 'wait' ).append( data );
			if( 'Sample' == root ) 
				$( cont ).find('UL:hidden').show();
			else 
				$( cont ).find('UL:hidden').slideDown({ duration: 500, easing: null });
			
		});
	}
	
	var preventry = null;
	$( '#container' ).on('click', 'LI A', function() {
		var entry = $(this).parent();
		//alert( $(this).attr('rel') );
		if( entry.hasClass('folder') ) {
			if( entry.hasClass('collapsed') ) {
						
				entry.find('UL').remove();
				getfilelist( entry, escape( $(this).attr('rel') ));
				entry.removeClass('collapsed').addClass('expanded');
			}
			else {
				//alert( "No" );
				entry.find('UL').slideUp({ duration: 500, easing: null });
				entry.removeClass('expanded').addClass('collapsed');
			}
		} else {
        	if (preventry!=null) {preventry.removeClass('selected')};
          	entry.addClass('selected');
          	preventry = entry;
//			$( '#jform_filepathname' ).value( $(this).attr( 'rel' ));
//			$( '#jform_filepathname' ).text( $(this).attr( 'rel' ));
//			$( '#selected_file' ).text( $(this).attr( 'rel' ));
			document.getElementById('jform_filepathname').value=$(this).attr( 'rel' );
		}
	return false;
	});
	
});
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
   			<?php $this->form->setFieldAttribute('pathname','directory',$this->basemusicfolder); ?>
     		<?php $this->form->setFieldAttribute('getid3onsave','default','1'); ?>
     		
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
     	<?php if (($this->item->id == 0) || (!file_exists($this->item->filepathname)) ) : ?>
        	<div class="row form-vertical">
       			<div class="col-md-6">
       				<p><?php echo Text::_('Select music track')?>
			    	<div id="container"> </div>
        		</div>
        		<div class="col-md-6">
             		<p class="xbit"><?php echo Text::_('Default base folder to find music files from'); ?>
            		 <code><?php echo $this->basemusicfolder; ?></code> 
            		<?php echo Text::_('This is set in xbMusic Options'); ?>
                	</p>
                	<!-- <div id="selected_file">Selected filepath will appear here</div> -->
                	<p> </p>
                	<?php echo $this->form->renderField('filepathname'); ?> 
                 	<?php echo $this->form->renderField('getid3onsave'); ?>
               </div>
     		</div>
        	<div class="row">
        	</div>
    	<?php else: ?>
        	<div class="row">
        		<div class="col-md-6">
        			<p><i><?php echo Text::_('Track folder'); ?></i> : <?php echo $this->item->pathname; ?></p>
        			<p><i><?php echo Text::_('Track file'); ?></i> : <?php echo $this->item->filename; ?></p>
         		</div>
        		<div class="col-md-6">
                    <audio controls>
                    	<?php $localpath = '/xbmusic/'.str_replace($this->basemusicfolder,'',$this->item->pathname); ?>
                      <source src="<?php echo $localpath.$this->item->filename; ?>">
                       Your browser does not support the audio tag.
                    </audio>        		
        			<?php echo $this->form->renderField('getid3onsave'); ?>
                </div>
            </div>
    	<?php endif; ?>
        <div class="hide">
        	<?php echo $this->form->renderField('pathname'); ?> 
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
    						<?php if (!empty($this->item->fileinfo)) : ?>
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
        					<?php else : ?>
        						<p class="xbit"><?php echo Text::_('fileinfo not available from id3 data'); ?></p>
        					<?php endif; ?>
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
           			<?php if (empty($this->item->artwork)) : ?>
           				<?php if ($this->item->id == 0) : ?>
           					<p class="xbit"><?php echo Text::_('When you save file artwork will be loaded from ID3 data if available'); ?></p>
           				<?php else: ?>
           					<p class="xbit"><?php echo Text::_('No artowrk specified. You can either save and load from ID3 if the music file has been updated, or save and copy from the album if one is specified and has a picture, or choose a picture below - this will also become the album image if one does not exist when you save the track.'); ?>
           					<?php $this->form->renderField('picture_options'); ?>
							<?php $this->form->renderField('picturefile'); ?> 
           				<?php endif; ?>
           				<?php echo Text::_('')?>
           			<?php else : ?>
	           			<?php echo Text::_('Artwork'); ?><br/>
						<img src="<?php echo $this->item->artwork; ?>" />
					<?php endif; ?>
				</div>        		
           		<div class="col-12 col-md-7">
					<fieldset id="pv_desc" class="xbbox xbboxwht xbyscroll">
						<legend>Image details</legend>
    					<?php echo $this->form->renderField('image_type'); ?> 
    					<?php echo $this->form->renderField('image_desc'); ?> 
    					<?php if (!empty($this->item->imageinfo)) : ?>
    						<dl class="xbdl">
        						<dt><?php echo Text::_('Type'); ?>:</dt>
        						<dd><?php echo $this->item->imageinfo->image_mime;?></dd>
        						<dt><?php echo Text::_('Dimensions'); ?>:</dt>
        						<dd><?php echo $this->item->imageinfo->image_width;?>&nbsp;x&nbsp;
        						<?php echo $this->item->imageinfo->image_height;?> px</dd>
        						<dt><?php echo Text::_('Size'); ?>:</dt>
        						<dd><?php echo number_format($this->item->imageinfo->datalength/1024, 2);?> kB</dd>
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
					<?php if (!empty($this->item->id3_tags)) : ?>
    					<fieldset id="id3dets" class="xbbox xbboxwht ">
    						<legend>ID3 Comment Tags</legend>
    						<dl class="xbdl">
                        		<?php foreach ($this->item->id3_tags as $key=>$value) : ?>
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
        
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'taggroups', Text::_('Tag Groups')); ?>
			<div class="row">
				<?php echo $this->form->renderFieldset('taggroups'); ?>
    		</div>
         <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('Linked Items')); ?>
			<div class="row">
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
