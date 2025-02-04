<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/tracks/default.php
 * @version 0.0.20.2 2nd February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Button\PublishedButton;
// use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
// use Joomla\Registry\Registry;
// use Joomla\CMS\Session\Session;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

// HTMLHelper::_('behavior.multiselect');
// HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['trigger'=>'hover']);
//HTMLHelper::_('webcomponent', '/webcomponents/joomla-field-fancy-select.min.js', ['version' => 'auto', 'relative' => true]);

$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$app       = Factory::getApplication();
$user  = $app->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$celink = 'index.php?option=com_categories&extension=com_xbmusic&task=category.edit&id=';
$cvlink = 'index.php?option=com_xbmusic&view=catinfo&id=';

$tvlink = 'index.php?option=com_xbmusic&view=taginfo&id=';

$rowcnt = (empty($this->items)) ? 0 : count($this->items);

if (strpos($listOrder, 'modified') !== false) {
    $dateOrderCol = 'modified';
} elseif (strpos($listOrder, 'created') !== false) {
    $dateOrderCol = 'created';
} else {
    $dateOrderCol = 'modified';
}

?>
<script>
function stopProp(event) {
	event.stopPropagation();
}
</script>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=tracks'); ?>" method="post" name="adminForm" id="adminForm">
		<h3><?php echo Text::_('XBMUSIC_XBMUSIC_TRACKS'); ?></h3>
		
		<?php // Search tools bar
		  echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
        <div class="pull-right pagination xbm0">
    		<?php  echo $this->pagination->getPagesLinks(); ?>
    	</div>
   		<div class="pull-right pagination" style="margin:25px 10px 0 0;">
    		<?php  echo $this->pagination->getResultsCounter(); ?> 
    	</div>
        <div class="clearfix"></div>      
              
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<div class="pull-left">
        		<p class="xbmb5">              
                    <?php echo Xbtext::_('XB_SORTED_BY',XBSP2 + XBTRL).$listOrder.' '.$listDirn ; ?>
        		</p>
			</div>
			<div class="pull-left" style="width:60%">
          		<p class="xbtr xbnote xbmb5">Auto close details dropdowns<input  type="checkbox" id="autoclose" name="autoclose" value="yes" checked="true" style="margin:0 5px;" />
          		</p>
          	</div>
			<div class="table-scroll">
			<table class="table table-striped table-hover xbtablelist table-freeze" id="xbtrackList">
				<thead>
					<tr>
						<th class="center " style="width:25px;" >
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="nowrap center " style="width:95px;" >
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.status', $listDirn, $listOrder); ?>
						</th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>, 
							<span class="xbr09">
								<?php echo Text::_('Filename'); ?>, 
								<?php echo HTMLHelper::_('searchtools.sort', 'Dates', 'a.rel_date', $listDirn, $listOrder); ?>
								<?php echo HTMLHelper::_('searchtools.sort', 'Artist', 'a.sortartist', $listDirn, $listOrder); ?>
							</span>
						</th>
						<th style="width:105px;"><?php echo Text::_('Artwork'); ?>
						</th>
						<th class=""><?php echo HTMLHelper::_('searchtools.sort', 'Album', 'album.title', $listDirn, $listOrder); ?>, 
						<?php echo Text::_('Song(s)'); ?>,
						<?php echo Text::_('Performers'); ?>, 
						<?php echo Text::_('Playlists'); ?>
						</th>
						<th class="nowrap " style="width:110px;" >
							<?php echo HTMLHelper::_('searchtools.sort', 'XB_CATEGORY', 'category_title', $listDirn, $listOrder); ?>							
							&amp; <?php echo Text::_('XB_TAGS'); ?>
						</th>
						<th class="nowrap xbtc center " style="width:160px; padding:0;"><span class="xbr09">
							<?php echo HTMLHelper::_('searchtools.sort', 'XBMUSIC_HEADING_DATE_' . strtoupper($dateOrderCol), 'a.' . $dateOrderCol, $listDirn, $listOrder); ?>
							<br /><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</span>
						</th>
					</tr>
				</thead>
				<body>
				<?php foreach ($this->items as $i => $item) :
    				$item->max_ordering = 0;
    				$ordering   = ($listOrder == 'a.ordering');
    				$canCreate  = $user->authorise('core.create',     'com_xbmusic.category.' . $item->catid);
    				$canEdit    = $user->authorise('core.edit',       'com_xbmusic.track.' . $item->id);
    				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
    				$canEditOwn = $user->authorise('core.edit.own',   'com_xbmusic.track.' . $item->id) && $item->created_by == $userId;
    				$canChange  = $user->authorise('core.edit.state', 'com_xbmusic.track.' . $item->id) && $canCheckin;
    				$canEditCat    = $user->authorise('core.edit',       'com_xbmusic.category.' . $item->catid);
    				$canEditOwnCat = $user->authorise('core.edit.own',   'com_xbmusic.category.' . $item->catid) && $item->category_uid == $userId;
    				$canEditParCat    = $user->authorise('core.edit',       'com_xbmusic.category.' . $item->parent_category_id);
    				$canEditOwnParCat = $user->authorise('core.edit.own',   'com_xbmusic.category.' . $item->parent_category_id) && $item->parent_category_uid == $userId;
    				?>
 					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">
						<td>
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="track-status">
							<div style="float:left;">
                                <?php
                                    $options = [
                                        'task_prefix' => 'track.',
                                        'disabled' => !$canChange,
                                        'id' => 'state-' . $item->id,
                                    ];
                                    echo (new PublishedButton())->render((int) $item->status, $i, $options);
                                ?>
                            </div>
                            <div>
                                <?php if ($item->note !='') :?>
                                	<span class="icon-info-circle xbpl5 xbblue" style="font-size:1.6rem;" 
                                		title="<?php echo $item->note; ?>"></span>
								<?php endif; ?>
                             </div>
                                   
						</td>
						<td class="has-context">
							<div class="pull-left">
								<p class="xbm0">
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'track.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit || $canEditOwn) : ?>
									<a class="hasTooltip" href="
									<?php echo Route::_('index.php?option=com_xbmusic&task=track.edit&id=' . $item->id).'&retview=tracks';?>
									" title="<?php echo Text::sprintf('XB_ALIAS_LABEL_TIP', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->title); ?></a> 
								<?php else : ?>
									<span title="<?php echo Text::sprintf('XB_ALIAS_LABEL_TIP', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->title); ?></span>
								<?php endif; ?>
								<?php $pvuri = "'".(Uri::root().'index.php?option=com_xbmusc&view=track&tmpl=component&id='.$item->id)."'"; ?>
          						<?php $pvtit = "'".$item->title."'"; ?>
                                <span  data-bs-toggle="modal" data-bs-target="#pvModal" data-bs-source="<?php echo $pvuri; ?>" 
                                	data-bs-itemtitle="<?php echo $item->title; ?>" title="<?php echo Text::_('XB_MODAL_PREVIEW'); ?>" 
          							onclick="var pv=document.getElementById('pvModal');pv.querySelector('.modal-body .iframe').setAttribute('src',<?php echo $pvuri; ?>);pv.querySelector('.modal-title').textContent=<?php echo $pvtit; ?>;"
                                	><span class="icon-eye xbpl10"></span></span>
								<br />
								<span title="<?php echo $item->pathname; ?>">
									<code><?php echo $item->filename;?></code>
								</span>
								<br />
								<span class="xbr09">
    								<?php if($item->rec_date != '') : ?>
    									<span class="xbpr20">
        									<?php echo Text::_('Rec.'); ?>: <?php echo XbcommonHelper::strDateReformat($item->rec_date); ?>
    									</span>
    								<?php endif; ?>
    								<?php if($item->rel_date != '') : ?>									
    									<?php echo Text::_('Rel.'); ?>: <?php echo XbcommonHelper::strDateReformat($item->rel_date); ?>
    								<?php endif; ?>
								</span>
								<br /><span class="xbr09"><span class="xbit"><?php echo Text::_('Artist')?></span>: 
								<?php echo $item->sortartist; ?></span>
								</p>
							</div>
						</td>
						<td><?php if ($item->imgurl != '') : ?>
        						<?php  $src = trim($item->imgurl);
        						  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
       								$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
        						?>
    							<img class="hasPopover" title="" 
    								data-bs-content="<?php echo $tip; ?>"
    								src="<?php echo $src; ?>"  style="height:100px;"
    								border="0" alt="" />							                          
    	                    	<?php  endif; ?>	                    
							<?php endif; ?>
    					</td>
						<td class="xbr09" onclick="stopProp(event);"><!--   onclick="stopProp(event);" can be removed once fix is in next J5 release-->
							<i><?php echo Text::_('Album'); ?></i>: 
							<?php if ($item->albumid >0) : ?>
    							<a href="index.php?option=com_xbmusic&task=album.edit&retview=tracks&id=<?php echo $item->albumid; ?>" title="Edit">
    								<?php echo $item->albumtitle; ?>
    							</a>
							<?php else: ?>
								<i><?php echo Text::_('album not listed'); ?></i>
							<?php endif; ?>
							
							<hr class="xbmt5 xbmb5" />
							<?php if(count($item->songs)>0) : ?>
    							<?php if (count($item->songs) > 1) : ?>
    								<details>
    									<summary><?php echo Text::sprintf('XBMUSIC_MEDLEY_OF_SONGS',count($item->songs)); ?></summary>
    									<ul>
    									<?php foreach ($item->songs as $song) : ?>
    										<li><a href="index.php?option=com_xbmusic&task=song.edit&retview=tracks&id=<?php echo $song['id']; ?>" title="Edit">
    							                <?php echo $song['title']; ?></a></li>
    									<?php endforeach; ?>
    									</ul>
    								</details>
    							<?php elseif (count($item->songs)==1) : ?>
    								<i><?php echo Text::_('Song title'); ?></i>: 
    								<a href="index.php?option=com_xbmusic&task=song.edit&retview=tracks&id=<?php echo $item->songs[0]['id']; ?>" title="Edit">
    									<?php echo $item->songs[0]['title']; ?>
    								</a>
    							<?php endif; ?>
							<?php else: ?>
								<span class="xbnit"><?php echo Text::_('no song linked'); ?></span>
							<?php endif; ?>
							
							<hr class="xbmt5 xbmb5" />
							<?php if(count($item->artists)>0) : ?>
    							<?php if (count($item->artists) > 1) : ?>
    								<details>
    									<summary><?php echo count($item->artists).' '.Text::_('Performers listed'); ?></summary>
    									<ul>
    									<?php foreach ($item->artists as $artist) : ?>
    										<li><a href="index.php?option=com_xbmusic&task=artist.edit&retview=tracks&id=<?php echo $artist['id']; ?>">
    							                <?php echo $artist['name']; ?></a></li>
    									<?php endforeach; ?>
    									</ul>
    								</details>
    							<?php elseif (count($item->artists)==1) : ?>
    								<i><?php echo Text::_('Artists'); ?></i>: 
    								<a href="index.php?option=com_xbmusic&task=artist.edit&retview=tracks&id=<?php echo $item->artists[0]['id']; ?>">
    									<?php echo $item->artists[0]['name']; ?>
    								</a>
    							<?php endif; ?>
							<?php elseif($item->sortartist !='') : ?>
    							<i><?php echo Text::_('Album Artist'); ?></i>: 
    								<?php echo $item->sortartist; ?>
    						<?php else : ?>
								<span class="xbnit"><?php echo Text::_('no artist linked'); ?></span>
							<?php endif; ?>
							
								
							<hr class="xbmt5 xbmb5" />
							<i><?php echo Text::_('Playlists'); ?></i>: 
							<?php if (count($item->playlists) >0) : ?>
								<i><?php echo Text::_('Playlist'); ?></i>:
							<?php else: ?>
								<i><?php echo Text::_('not assigned to any playlist'); ?></i>
							<?php endif; ?> 
						</td>
						<td>
						<?php if ($item->catid > 0) : ?>
    						<p>
    							<a class="xblabel label-cat" href="<?php echo $cvlink.$item->catid; ?>" 
    								title="<?php echo Text::_( 'XB_VIEW_CATEGORY' );?>::<?php echo $item->category_title; ?>">
    								<?php echo $item->category_title; ?>
    							</a>							
							</p>						
						<?php endif; ?>
						<ul class="inline">
						<?php foreach ($item->tags as $t) : ?>
							<li><a href="<?php echo $tvlink.$t->id; ?>" class="xblabel label-tag">
								<?php echo $t->title; ?></a>
							</li>												
						<?php endforeach; ?>
						</ul>						    											
						</td>
						<td class="nowrap xbr09" style="padding:6px 0; text-align:center;">
							<?php
							$date = $item->{$dateOrderCol};
							echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('D d M \'y')) : '-';
							?><br />
							<?php echo (int) $item->id; ?>
						</td>
                <?php endforeach; //item ?>				
				</body>
			
			</table>
			</div>
			<?php // Load the batch processing form. ?>
			<?php if ($user->authorise('core.create', 'com_xbmusic')
				&& $user->authorise('core.edit', 'com_xbmusic')
				&& $user->authorise('core.edit.state', 'com_xbmusic')) : ?>
				<?php echo HTMLHelper::_(
					'bootstrap.renderModal',
					'collapseModal',
					array(
						'title'  => Text::_('XB_BATCH_OPTIONS'),
						'footer' => $this->loadTemplate('batch_footer'),
					    'modalWidth' => '50',
					),
					$this->loadTemplate('batch_body')
				); ?>
			<?php endif; ?>
			<?php // Load the track preview modal ?>
			<?php echo HTMLHelper::_(
				'bootstrap.renderModal',
				'pvModal',
				array(
					'title'  => Text::_('XBMUSIC_TRACK_PREVIEW'),
					'footer' => '',
				    'height' => '900vh',
				    'bodyHeight' => '90',
				    'modalWidth' => '80',
				    'url' => Uri::root().'index.php?option=com_xbmusic&view=track&id='.'x'
				),
			); ?>

 		<?php endif; ?>

		<?php echo $this->pagination->getListFooter(); ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/closedetails.js" ></script>
<!--     <script language="JavaScript" type="text/javascript" -->
<!--      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/setifsrc.js" ></script>  -->
    
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>