<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/playlists/default.php
 * @version 0.0.50.2 4th April 2025
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
HTMLHelper::_('bootstrap.popover', '.xbpop', ['trigger'=>'hover']);

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
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=playlists'); ?>" method="post" name="adminForm" id="adminForm">
		<h3><?php echo Text::_('XBMUSIC_PLAYLISTS'); ?></h3>
		
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
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<?php if($this->azuracast) : ?>
							<th>Azuracast
							</th>
						<?php endif; ?>
						<th><?php echo Text::_('XBMUSIC_TRACKS'); ?>
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
    				$canEdit    = $user->authorise('core.edit',       'com_xbmusic.playlist.' . $item->id);
    				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
    				$canEditOwn = $user->authorise('core.edit.own',   'com_xbmusic.playlist.' . $item->id) && $item->created_by == $userId;
    				$canChange  = $user->authorise('core.edit.state', 'com_xbmusic.playlist.' . $item->id) && $canCheckin;
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
                                        'task_prefix' => 'playlist.',
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
						<td class="has-context">
							<div class="pull-left">
								<p class="xbm0">
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'artimgs.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit || $canEditOwn) : ?>
									<a class="hasTooltip" href="
									<?php echo Route::_('index.php?option=com_xbmusic&task=playlist.edit&id=' . $item->id).'&retview=playlists';?>
									" title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->title); ?></a> 
								<?php else : ?>
									<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->title); ?></span>
								<?php endif; ?>
								<?php $pvuri = "'".(Uri::root().'index.php?option=com_xbmusc&view=playlist&tmpl=component&id='.$item->id)."'"; ?>
          						<?php $pvtit = "'".$item->title."'"; ?>
                                <span  data-bs-toggle="modal" data-bs-target="#pvModal" data-bs-source="<?php echo $pvuri; ?>" 
                                	data-bs-itemtitle="<?php echo $item->title; ?>" title="<?php echo Text::_('XB_MODAL_PREVIEW'); ?>" 
          							onclick="var pv=document.getElementById('pvModal');pv.querySelector('.modal-body .iframe').setAttribute('src',<?php echo $pvuri; ?>);pv.querySelector('.modal-title').textContent=<?php echo $pvtit; ?>;"
                                	><span class="icon-eye xbpl10"></span></span>
								</p>
								<p class="xb09r"><i>Alias</i>: &nbsp;<?php echo $item->alias; ?>
								</p>
							</div>
						</td>
						<?php if($this->azuracast) : ?>
							<td class="xbmt5 xbmh400 xbyscroll <?php if(!$item->publicschd) echo 'xbdim'; ?>" onclick="stopProp(event);">
    							<?php if ($item->az_id) : ?>
    								on <?php echo $item->azstation; ?>
    								<?php if ($item->scheduledcnt > 0) : ?>
    									<details>
    										<summary class="xbnit"><?php echo $item->scheduledcnt; 
    										echo ($item->scheduledcnt==1) ? Xbtext::_('XBMUSIC_SHD_TIME',XBSP1+XBLC1+XBTRL) : Xbtext::_('XBMUSIC_SHD_TIMES',XBSP1+XBLC1+XBTRL); ?>											
    										</summary>
    										<div class="xbmt5 xbmh400 xbyscroll">
    											<ul style="margin:5px;">
                									<?php foreach ($item->schedules as $schd) : ?>
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
    							<?php if (!$item->publicschd) echo '<i><b>'.Text::_('XBMUSIC_NOT_PUB_SCHD').'</b></i>'; ?>
							</td>
						<?php endif; ?>
						<td class="xbr09" onclick="stopProp(event);">
							<?php if($item->trkcnt > 0): ?>
								<details>
									<summary class="xbnit"><?php echo $item->trkcnt; 
									echo ($item->trkcnt==1)? Xbtext::_('XBMUSIC_TRACK',XBSP1 + XBLC1 + XBTRL) : Xbtext::_('XBMUSIC_TRACKS',XBSP1 + XBLC1 + XBTRL); ?>
								    </summary>
								    <div class="xbmh400 xbyscroll">
    									<ul style="margin:5px;list-style: none;">
    									<?php foreach ($item->tracks as $track) : ?>
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
					'title'  => Text::_('XBMUSIC_PLAYLIST_PREVIEW'),
					'footer' => '',
				    'height' => '900vh',
				    'bodyHeight' => '90',
				    'modalWidth' => '80',
				    'url' => Uri::root().'index.php?option=com_xbmusic&view=playlist&id='.'x'
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
