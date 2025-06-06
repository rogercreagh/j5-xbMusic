<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/artists/default.php
 * @version 0.0.30.4 12th February 2025
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

$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns');
$wa->useScript('multiselect');

$app       = Factory::getApplication();
$user  = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
//$saveOrder = $listOrder == 'a.ordering';

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

//if ($saveOrder && !empty($this->items)) {
//    $saveOrderingUrl = 'index.php?option=com_xbmusic&task=songs.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
//    HTMLHelper::_('draggablelist.draggable');
//}

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=artists'); ?>" method="post" name="adminForm" id="adminForm">
		<h3><?php echo Text::_('XBMUSIC_XBMUSIC_ARTISTS'); ?></h3>
		
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
			<table class="table table-striped table-hover xbtablelist table-freeze" id="xbsongList">
				<thead>
					<tr>
						<th class="center " style="width:25px;" >
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th class="nowrap center" style="width:95px;" >
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.status', $listDirn, $listOrder); ?>
						</th>
						<th style="width75px;"><!-- image --></th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'XB_NAME', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th><?php echo Text::_('XBMUSIC_ALBUMS'); ?>
						</th>
						<th><?php echo Text::_('XBMUSIC_SONGS'); ?>
						</th>
						<th class="nowrap" style="width:110px;" >
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
				<tbody>
				<?php foreach ($this->items as $i => $item) :
    				$item->max_ordering = 0;
    				$ordering   = ($listOrder == 'a.ordering');
    				$canCreate  = $user->authorise('core.create',     'com_xbmusic.category.' . $item->catid);
    				$canEdit    = $user->authorise('core.edit',       'com_xbmusic.artist.' . $item->id);
    				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
    				$canEditOwn = $user->authorise('core.edit.own',   'com_xbmusic.artist.' . $item->id) && $item->created_by == $userId;
    				$canChange  = $user->authorise('core.edit.state', 'com_xbmusic.artist.' . $item->id) && $canCheckin;
    				$canEditCat    = $user->authorise('core.edit',       'com_xbmusic.category.' . $item->catid);
    				$canEditOwnCat = $user->authorise('core.edit.own',   'com_xbmusic.category.' . $item->catid) && $item->category_uid == $userId;
    				$canEditParCat    = $user->authorise('core.edit',       'com_xbmusic.category.' . $item->parent_category_id);
    				$canEditOwnParCat = $user->authorise('core.edit.own',   'com_xbmusic.category.' . $item->parent_category_id) && $item->parent_category_uid == $userId;
    				?>
 					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">
						<td class="center " style="width:25px;">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="song-status nowrap center">
							<div style="float:left;">
                                <?php
                                    $options = [
                                        'task_prefix' => 'artist.',
                                        'disabled' => !$canChange,
                                        'id' => 'state-' . $item->id,
                                    ];
                                    echo (new PublishedButton())->render((int) $item->status, $i, $options);
                                ?>
                            </div>
                            <?php if ($item->status == -2) : ?>
                            	<div style="float:left;">
                            		<a href="index.php?option=com_xbmusic&task=song.delete&cid=<?php echo $item->id?>">
                            			<span class="fas fa-xmark xbred xbpl5" style="font-size:1.6rem;"></span>
                            		</a>
                            	</div>
                            <?php else: ?>
                                <div>
                                    <?php if ($item->note !='') :?>
                                    	<span class="icon-info-circle xbpl5 " style="font-size:1.6rem; color:#78f;" 
                                    		title="<?php echo $item->note; ?>"></span>
    								<?php endif; ?>
                                 </div>
							<?php endif; ?>
						</td>
						<td>
							<?php if (!empty($item->imgurl)) : ?>
								<?php  $src = trim($item->imgurl);
        						  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
       								$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
        						?>
								<img src="<?php echo $src; ?>" alt="<?php echo $item->name; ?>" 
									style="width:75px;"
									class="hasPopover" title="" 
									data-bs-content="<?php echo $tip; ?>" />
								<?php endif; ?>
							<?php endif; ?>
						</td>
						<td class="has-context">
							<div class="pull-left">
								<p class="xbm0">
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'artimgs.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit || $canEditOwn) : ?>
									<a class="hasTooltip" href="
									<?php echo Route::_('index.php?option=com_xbmusic&task=artist.edit&id=' . $item->id).'&retview=artists';?>
									" title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->name); ?></a> 
								<?php else : ?>
									<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->name); ?></span>
								<?php endif; ?>
								<?php $pvuri = "'".(Uri::root().'index.php?option=com_xbmusc&view=artist&tmpl=component&id='.$item->id)."'"; ?>
          						<?php $pvtit = "'".$item->name."'"; ?>
                                <span  data-bs-toggle="modal" data-bs-target="#pvModal" data-bs-source="<?php echo $pvuri; ?>" 
                                	data-bs-itemtitle="<?php echo $item->name; ?>" title="<?php echo Text::_('XB_MODAL_PREVIEW'); ?>" 
          							onclick="var pv=document.getElementById('pvModal');pv.querySelector('.modal-body .iframe').setAttribute('src',<?php echo $pvuri; ?>);pv.querySelector('.modal-title').textContent=<?php echo $pvtit; ?>;"
                                	><span class="icon-eye xbpl10"></span></span>
								</p>
								<?php if(!empty($item->type)) : ?>
									<span class="xbnit xbpl20">
								    	<?php echo ($item->type == 2)? Text::_('XB_GROUP') : Text::_('XB_PERSON'); ?>
									</span><br />
									<?php else : ?>
									<span class="xbnit xbred xbpl20"><?php echo Text::_('XBMUSIC_SET_TYPE'); ?></span><br />
								<?php endif; ?>
							
								<?php if($item->trkcnt > 0): ?>
    								<span class="xbr09 xbnit"><?php echo Xbtext::_('XBMUSIC_FOUND_ON',XBSP2 + XBTRL); 
    								if ($item->trkcnt == 1) {
    								    echo '1 '.Xbtext::_('XBMUSIC_TRACK',XBSP1 + XBTRL);
    								} else {
    								    echo $item->trkcnt.' '.Xbtext::_('XBMUSIC_TRACKS',XBSP1 + XBTRL); 
    								} ?>
    								</span><br />
    							<?php endif; ?> 
    							<?php if(count($item->albums) > 0) : ?>    								    
    								<span class="xbr09 xbnit"><?php echo Xbtext::_('XB_ON', XBSP2 + XBTRL); ?>
    									<?php if (count($item->albums) == 1) {
    									    echo '1 '.Xbtext::_('XBMUSIC_ALBUM',XBSP1 + XBTRL);
    									} else {
    									   echo count($item->albums).' '.Xbtext::_('XBMUSIC_ALBUMS',XBSP1 + XBTRL);
    									} ?>
    								</span><br />
    							<?php endif; ?>
    							<?php if (count($item->singles) > 0) : ?>
    								<span class="xbr09 xbnit"> <?php echo count($item->singles) . Xbtext::_('XBMUSIC_SINGLES',XBSP1 + XBTRL);?>
    								</span><br />
								<?php endif; ?>
							</div>
						</td>
						<td class="xbr09" onclick="stopProp(event);">
							<?php if (count($item->albums) > 1) : ?>
								<details>
									<summary><?php echo Text::sprintf('XBMUSIC_N_ALBUMS',count($item->albums)); ?></summary>
									<div class="xbyscroll" style="max-height:180px;">
									<ul style="margin:5px;list-style: none;">
									<?php foreach ($item->albums as $album) : ?>
										<li>							
											<?php if($album['imgurl']!='') :?>
                								<?php  $src = trim($album['imgurl']);
                        						  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
                       								$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
                        						?>
												<img src="<?php echo $src; ?>" alt="<?php echo $album['albumtitle']; ?>" 
													style="height:50px;margin: 2px 5px 8px 0;"
													class="hasPopover" title="" 
    												data-bs-content="<?php echo $tip; ?>" />
												<?php endif; ?>
											<?php endif; ?>
											<a href="index.php?option=com_xbmusic&task=album.edit&retview=artists&id=<?php echo $album['albumid']; ?>">
							                <?php echo $album['albumtitle']; ?></a> 
							                <?php if($album['rel_date']) echo ' ('.$album['rel_date'].') '; ?>
							            </li>
									<?php endforeach; ?>
									</ul>
									</div>
								</details>
							<?php elseif (count($item->albums)==1) : ?>
								<?php $album = $item->albums[0]; ?>
								<p><span class="xbit"><?php echo Text::_('XBMUSIC_ALBUM'); ?></span><br />
								<?php if($album['imgurl']!='') :?>
    								<?php  $src = trim($album['imgurl']);
            						  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
           								$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
            						?>
									<img src="<?php echo $src; ?>" alt="<?php echo $album['albumtitle']; ?>" 
										style="height:50px;margin: 2px 5px 8px 0;"
										class="hasPopover" title="" 
										data-bs-content="<?php echo $tip; ?>" />
									<?php endif; ?>
								<?php endif; ?>
								<a href="index.php?option=com_xbmusic&task=album.edit&retview=artists&id=<?php echo $album['albumid']; ?>">
					                <?php echo $album['albumtitle']; ?></a> 
					                <?php if($album['rel_date']) echo ' ('.$album['rel_date'].') '; ?>
					            </p>
							<?php else : ?>
								<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_ALBUMS_LISTED'); ?>
							<?php endif; ?>
							<?php if (count($item->singles) > 1) : ?>
								<details>
									<summary><?php echo Text::sprintf('XBMUSIC_N_SINGLES',count($item->singles)); ?></summary>
									<div class="xbyscroll" style="max-height:180px;">
									<ul style="margin:5px;">
									<?php foreach ($item->singles as $single) : ?>
										<li>
											<?php if($single['imgurl']!='') :?>
    								<?php  $src = trim($single['imgurl']);
            						  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
           								$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
            						?>
									<img src="<?php echo $src; ?>" alt="<?php echo $album['albumtitle']; ?>" 
										style="height:50px;margin: 2px 5px 8px 0;"
										class="hasPopover" title="" 
										data-bs-content="<?php echo $tip; ?>" />
									<?php endif; ?>
								<?php endif; ?>
											<a href="index.php?option=com_xbmusic&task=track.edit&retview=artists&id=<?php echo $single['trackid']; ?>">
							                <?php echo $single['albumtitle']; ?></a> 
							                <?php if($single['rel_date']) echo ' ('.$single['rel_date'].') '; ?>
							            </li>
									<?php endforeach; ?>
									</ul>
									</div>
								</details>
							<?php elseif (count($item->singles)==1) : ?>
								<?php $single = $item->singles[0]; ?>
								<p><span class="xbit"><?php echo Text::_('XBMUSIC_SINGLE'); ?></span><br />
								<?php if($single['imgurl']!='') :?>
    								<?php  $src = trim($single['imgurl']);
            						  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
           								$tip = '<img src=\''.$src.'\' style=\'width:400px;\' />';
            						?>
									<img src="<?php echo $src; ?>" alt="<?php echo $single['tracktitle']; ?>" 
										style="height:50px;margin: 2px 5px 8px 0;"
										class="hasPopover" title="" 
										data-bs-content="<?php echo $tip; ?>" />
									<?php endif; ?>
								<?php endif; ?>
								<a href="index.php?option=com_xbmusic&task=track.edit&retview=artists&id=<?php echo $single['trackid']; ?>">
					                <?php echo $single['tracktitle']; ?></a> 
					                <?php if($single['rel_date']) echo ' ('.$single['rel_date'].') '; ?>
					            </p>
							<?php endif; ?>
						</td>
						<td onclick="stopProp(event);">
							<?php if (count($item->songs) > 1) : ?>
								<details>
									<summary><?php echo Text::sprintf('XBMUSIC_N_SONGS',count($item->songs)); ?></summary>
									<div class="xbyscroll" style="max-height:180px;">
									<ul style="margin:5px;">
									<?php foreach ($item->songs as $song) : ?>
										<li>
											<a href="index.php?option=com_xbmusic&task=song.edit&retview=artists&id=<?php echo $song['songid']; ?>">
							                <?php echo $song['songtitle']; ?></a> 
							                <?php if($song['composer']) echo ' ('.$song['composer'].') '; ?>
							            </li>
									<?php endforeach; ?>
									</ul>
									</div>
								</details>
							<?php elseif (count($item->songs)==1) : ?>
								<?php $song = $item->songs[0]; ?>
								<p><span class="xbit"><?php echo Text::_('XBMUSIC_SONGS'); ?></span><br />
									<a href="index.php?option=com_xbmusic&task=song.edit&retview=artists&id=<?php echo $song['songid']; ?>">
					                <?php echo $song['songtitle']; ?></a> 
					                <?php if($song['composer']) echo ' ('.$song['composer'].') '; ?>
					            </p>
							<?php else : ?>
								<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_SONGS_LISTED'); ?>
							<?php endif; ?>
						</td>
						<td class="nowrap">
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
				</tbody>
			
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
			<?php // Load the song preview modal ?>
			<?php echo HTMLHelper::_(
				'bootstrap.renderModal',
				'pvModal',
				array(
					'title'  => Text::_('XBMUSIC_ARTIST_PREVIEW'),
					'footer' => '',
				    'height' => '900vh',
				    'bodyHeight' => '90',
				    'modalWidth' => '80',
				    'url' => Uri::root().'index.php?option=com_xbmusic&view=artist&id='.'x'
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
    
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
