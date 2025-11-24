<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/station/edit.php
 * @version 0.0.59.2 22nd Novemeber 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

/*
 changes needed
 add tabs for playlists and schedule based on existing views but specific to one station
 move azuracast info to top section and remove tab
 add links to general tab
 
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
// use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Button\PublishedButton;
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
//$wa->useScript('table.columns');

$user  = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');

$dateOrderCol = array('created', 'modified','lastsync');

// Create shortcut to parameters.
//$params = clone $this->state->get('params');
//$params->merge(new Registry($this->item->attribs));

$artistelink = 'index.php?option=com_xbmusic&task=artist.edit&id=';
$albumelink = 'index.php?option=com_xbmusic&task=album.edit&id=';
$trackelink = 'index.php?option=com_xbmusic&task=track.edit&id=';
$cvlink = 'index.php?option=com_xbmusic&view=catinfo&id=';
$tvlink = 'index.php?option=com_xbmusic&view=taginfo&id=';

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
    <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=station&layout=edit&id='. (int) $item->id); ?>"
    	method="post" name="adminForm" id="item-form" class="form-validate" >
    	<div class="row form-vertical">
    		<div class="col-md-4">
     			<?php echo $this->form->renderField('title'); ?> 
    		</div>
    		<div class="col-md-2">
    			  <?php echo $this->form->renderField('alias'); ?> 
    		</div>
    		<div class="col-md-3">
    			  <?php echo $this->form->renderField('slug'); ?> 
    		</div>
    		<div class="col-md-3">
                <div class="pull-left xbmr50" style="width:130px;">
    			  <?php echo $this->form->renderField('id'); ?> 
                </div>
                <div class="pull-left" style="width:130px;">
    			  <?php echo $this->form->renderField('az_stid'); ?>
                </div>
            </div>
    	</div>
		<div class="row">
			<div class="col-12 col-md-4 ">
        		<?php echo $this->form->renderField('mediapath');?>
			</div>
			<div class="col-12 col-md-4 ">
        		<?php echo $this->form->renderField('az_url');?>
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

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'links', Text::_('Links')); ?>
		<?php echo $this->form->renderField('stationlinksnote'); ?>
		<div class="row">
			<div class="col-12 col-md-6 ">
        		<?php echo $this->form->renderField('az_stream');?>
			</div>
			<div class="col-12 col-md-6 ">
        		<?php echo $this->form->renderField('az_player');?>
			</div>
		</div>
		<div class="row">
			<div class="col-12 col-md-6 ">
        		<?php echo $this->form->renderField('website');?>
			</div>
			<div class="col-12 col-md-6 ">
        		<?php echo $this->form->renderField('webplayer');?>
			</div>
		</div>
			    
		<?php echo $this->form->renderField('ext_links');?>
       		
     <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'playlists', Text::_('Playlists')); ?>
   		<?php $dateCol = 0; ?>
    <div class="pull-right"> 
    <div class="clearfix"></div>
    </div>
    	<div class="table-scroll">
			<table class="table table-striped table-hover xbtablelist table-freeze" id="xbsongList">
				<thead>
					<tr>
						<th class="nowrap center" style="width:95px;" >
							<?php echo Text::_('JSTATUS'); ?>
						</th>
						<th >
							<?php echo Text::_('JGLOBAL_TITLE'); ?>
						</th>
							<th><?php echo Text::_('Schedule'); ?>
							</th>
						<th><?php echo Text::_('XBMUSIC_TRACKS'); ?>
						</th>
						<th class="nowrap" style="width:110px;" >
							<?php echo Text::_('XB_CATEGORY'); ?>							
							 &amp; <?php echo Text::_('XB_TAGS'); ?>
						</th>
						<th class="nowrap xbtc center xbw150" style="padding:0 10px;">
							<?php echo $this->form->renderField('dateorder','params'); ?>

						</th>
						<th class="xbtc xbw125"><?php echo Text::_('XbID'); ?>
							 &amp; <?php echo Text::_('AzID'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($item->playlists as $i => $pl) :
				   $canCheckin = $user->authorise('core.manage','com_checkin') || $pl->checked_out == $userId || $pl->checked_out == 0;
				   $canChange  = $user->authorise('core.edit.state', 'com_xbmusic.playlist.' . $pl->id) && $canCheckin;
				   $canEdit    = $user->authorise('core.edit','com_xbmusic.playlist.' . $pl->id);
				   $canEditOwn = $user->authorise('core.edit.own','com_xbmusic.playlist.'.$pl->id) && $pl->created_by == $userId;
				   $canChange  = $user->authorise('core.edit.state', 'com_xbmusic.playlist.' . $pl->id) && $canCheckin;
				   ?>
 					<tr class="row<?php echo $i % 2; ?>" >
						<td class="song-status nowrap center">
							<div style="float:left;">
                                <?php
                                    $options = [
                                        'task_prefix' => 'playlist.',
                                        'disabled' => !$canChange,
                                        'id' => 'state-' . $pl->id,
                                    ];
                                    echo (new PublishedButton())->render((int) $pl->status, $i, $options);
                                ?>
                            </div>
                            <?php if ($pl->status == -2) : ?>
                            	<div style="float:left;">
                            		<a href="index.php?option=com_xbmusic&task=song.delete&cid=<?php echo $pl->id?>">
                            			<span class="fas fa-xmark xbred xbpl5" style="font-size:1.6rem;"></span>
                            		</a>
                            	</div>
                            <?php else: ?>
	                            <div>
    	                            <?php if ($pl->note !='') :?>
        	                        	<span class="icon-info-circle xbpl5 " style="font-size:1.6rem; color:#78f;" 
                                    		title="<?php echo $pl->note; ?>"></span>
									<?php endif; ?>
                    	         </div>
                    	     <?php endif; ?>
						</td>
						<td class="has-context">
							<div class="pull-left">
								<p class="xbm0">
								<?php if ($pl->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $pl->editor, $pl->checked_out_time, 'artimgs.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit || $canEditOwn) : ?>
									<a class="hasTooltip" href="
									<?php echo Route::_('index.php?option=com_xbmusic&task=playlist.edit&id=' . $pl->id).'&retview=playlists';?>
									" title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($pl->alias)); ?>">
										<?php echo $this->escape($pl->title); ?></a> 
								<?php else : ?>
									<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($pl->alias)); ?>">
										<?php echo $this->escape($pl->title); ?></span>
								<?php endif; ?>
								<?php $pvuri = "'".(Uri::root().'index.php?option=com_xbmusc&view=playlist&tmpl=component&id='.$pl->id)."'"; ?>
          						<?php $pvtit = "'".$pl->title."'"; ?>
                                <span  data-bs-toggle="modal" data-bs-target="#pvModal" data-bs-source="<?php echo $pvuri; ?>" 
                                	data-bs-itemtitle="<?php echo $pl->title; ?>" title="<?php echo Text::_('XB_MODAL_PREVIEW'); ?>" 
          							onclick="var pv=document.getElementById('pvModal');pv.querySelector('.modal-body .iframe').setAttribute('src',<?php echo $pvuri; ?>);pv.querySelector('.modal-title').textContent=<?php echo $pvtit; ?>;"
                                	><span class="icon-eye xbpl10"></span></span>
								</p>
								<p class="xb09r"><i>Alias</i>: &nbsp;<?php echo $pl->alias; ?>
								</p>
							</div>
						</td>
						<td class="xbmt5 xbmh400 xbyscroll <?php if(!$pl->publicschd) echo 'xbdim'; ?>" onclick="stopProp(event);">
							<?php if ($pl->az_plid) : ?>
								on <?php echo $pl->azstation; ?>
								<?php if ($pl->scheduledcnt > 0) : ?>
									<details>
										<summary class="xbnit"><?php echo $pl->scheduledcnt; 
										echo ($pl->scheduledcnt==1) ? Xbtext::_('XBMUSIC_SHD_TIME',XBSP1+XBLC1+XBTRL) : Xbtext::_('XBMUSIC_SHD_TIMES',XBSP1+XBLC1+XBTRL); ?>											
										</summary>
										<div class="xbmt5 xbmh400 xbyscroll">
											<ul style="margin:5px;">
            									<?php foreach ($pl->schedules as $schd) : ?>
            										<li><?php echo $schd['az_starttime'];
            										if ($schd['az_days'] != '') { 
            										    echo '<i> on </i>'.$schd['az_days'];
            										} else {
            										    echo '<i>'.Xbtext::_('XBMUSIC_EVERYDAY',XBLC1+XBTRL).'</i>';
            										}
            										if ($schd['az_startdate']) {
            										    echo '<span class="xbpl20 xbit">from </span>'
                                                            .$schd['az_startdate'].'<i> to </i>'.$schd['az_enddate'];
            										} else {
            										    echo '<span class="xbpl20 xbit">'
                                                            .Xbtext::_('XBMUSIC_ALWAYS',XBLC1+XBTRL).'</span>';
            										}
            										?></li>
            									<?php endforeach; ?>
    										</ul>										
    									</div>
									</details>
								<?php else: ?>
									<br /><i><?php echo Text::_('No scheduled times'); ?></i>
								<?php endif; ?>
							<?php endif; ?>
							<?php if (!$pl->publicschd) echo '<i><b>'.Text::_('XBMUSIC_NOT_PUB_SCHD').'</b></i>'; ?>
						</td>
						<td class="xbr09" onclick="stopProp(event);">
							<?php if($pl->trkcnt > 0): ?>
								<details>
									<summary class="xbnit"><?php echo $pl->trkcnt; 
									echo ($pl->trkcnt==1)? Xbtext::_('XBMUSIC_TRACK',XBSP1 + XBLC1 + XBTRL) : Xbtext::_('XBMUSIC_TRACKS',XBSP1 + XBLC1 + XBTRL); ?>
								    </summary>
								    <div class="xbmh400 xbyscroll">
    									<ul style="margin:5px;list-style: none;">
    									<?php foreach ($pl->tracks as $track) : ?>
    										<li><?php echo $track['tracktitle'].' - <i>'.$track['artistname'].'</i>'; ?></li>
    									<?php endforeach; ?>
    									</ul>
								    </div>
								</details>
							<?php else: ?>
								<p class="xbit"><?php echo Text::_('XBMUSIC_NO_PLAYLIST_ITEMS'); ?></p>
							<?php endif; ?>
						</td>
						<td class="nowrap">
						<?php if ($pl->catid > 0) : ?>
    						<p>
    							<a class="xblabel label-cat" href="<?php echo $cvlink.$pl->catid; ?>"  target="_blank"
    								title="<?php echo Text::_( 'XB_VIEW_CATEGORY' );?>::<?php echo $pl->category_title; ?>">
    								<?php echo $pl->category_title; ?>
    							</a>							
							</p>						
						<?php endif; ?>
						<ul class="inline">
						<?php foreach ($pl->tags as $t) : ?>
							<li><a href="<?php echo $tvlink.$t->id; ?>" target="_blank" class="xblabel label-tag">
								<?php echo $t->title; ?></a>
							</li>												
						<?php endforeach; ?>
						</ul>						    											
						</td>
						<td class="nowrap xbr09 xbtc">
							<?php $date = $pl->{$dateOrderCol[$item->params['dateorder']]};
							echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('D d M \'y H:i')) : 'n/a';
							?>
						</td>
						<td><?php echo (int) $pl->id; ?> --- 
							<?php echo (int) $pl->az_plid; ?>
						</td>
                <?php endforeach; //item ?>				
				</tbody>
			</table>
		</div>
    

	<?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'azuracast', Text::_('Azuracast Data')); ?>
	<div class="form-vertical">
		<div class="row">
			<div class="col-12 col-md-6 " style="white-space: pre-wrap;">
        		<pre><?php echo print_r(json_decode($azinfo),true);?></pre>
			</div>
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
                    <?php echo $this->form->renderField('lastsync')?>
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
    <input type="hidden" name="task" id="task" value="station.edit" />
    <?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
<script>
       updatePvMd();
       document.getElementById("jform_description").addEventListener("input", (event) => updatePvMd());
</script>
