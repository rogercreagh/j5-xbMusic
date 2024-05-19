<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/songs/default.php
 * @version 0.0.6.3 19th May 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Session\Session;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

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
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';

$cvlink = '';
$tvlink = '';

$rowcnt = (empty($this->items)) ? 0 : count($this->items);

if (strpos($listOrder, 'modified') !== false) {
    $dateOrderCol = 'modified';
} elseif (strpos($listOrder, 'created') !== false) {
    $dateOrderCol = 'created';
} else {
    $dateOrderCol = 'modified';
}

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_xbmusic&task=songs.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=songs'); ?>" method="post" name="adminForm" id="adminForm">
		<h3><?php echo Text::_('XBMUSIC_XBMUSIC_SONGS'); ?></h3>
		
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
			<?php $rowcnt = count($this->items); ?>	
			<div class="pull-left">
        		<p class="xbmb5">              
                    <?php echo 'Sorted by '.$listOrder.' '.$listDirn ; ?>
        		</p>
			</div>
			<div class="pull-left" style="width:60%">
          		<p class="xbtr xbnote xbmb5">Auto close details dropdowns<input  type="checkbox" id="autoclose" name="autoclose" value="yes" checked="true" style="margin:0 5px;" />
          		</p>
          	</div>
			
			<table class="table table-striped table-hover xbtablelist" id="xbsongList">
    			<colgroup>
					<col class="center hidden-phone" style="width:25px;"><!-- checkbox -->
					<col class="nowrap center hidden-phone" style="width:25px;"><!-- ordering -->
					<col class="nowrap center" style="width:55px;"><!-- status -->
    				<col ><!-- title, alias -->
    				<col ><!-- performers -->
    				<col ><!-- tracks -->
    				<col class="nowrap hidden-phone" ><!-- albums -->
    				<col class="nowrap hidden-phone" ><!-- playlists -->
    				<col class="nowrap hidden-phone" style="width:110px;" ><!-- category & tags -->
    				<col class="nowrap hidden-phone xbtc" style="width:160px; padding:0;"><!-- date & id -->
    			</colgroup>	
				<thead>
					<tr>
						<th >
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
						</th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
						</th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th>Recordings
						</th>
						<th>Performers
						</th>
						<th>albums
						</th>
						<th>playlists
						</th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'XB_CATEGORY', 'category_title', $listDirn, $listOrder); ?>							
							<span class="xbnit xb09">(<?php echo lcfirst(Text::_('XB_GROUP')); ?>)</span> - <?php echo Text::_('XB_TAGS'); ?>
						</th>
						<th style="padding:0; text-align:center;"><span class="xb09">
							<?php echo HTMLHelper::_('searchtools.sort', 'XBMUSIC_HEADING_DATE_' . strtoupper($dateOrderCol), 'a.' . $dateOrderCol, $listDirn, $listOrder); ?>
							<br /><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</span>
						</th>
					</tr>
				</thead>
				<?php if ($rowcnt > 9) : ?>
					<tfoot>
					</tfoot>
				<?php endif; //rowcnt ?>
				<body>
				<?php foreach ($this->items as $i => $item) :
    				$item->max_ordering = 0;
    				$ordering   = ($listOrder == 'a.ordering');
    				$canCreate  = $user->authorise('core.create',     'com_xbmusic.category.' . $item->catid);
    				$canEdit    = $user->authorise('core.edit',       'com_xbmusic.song.' . $item->id);
    				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
    				$canEditOwn = $user->authorise('core.edit.own',   'com_xbmusic.song.' . $item->id) && $item->created_by == $userId;
    				$canChange  = $user->authorise('core.edit.state', 'com_xbmusic.song.' . $item->id) && $canCheckin;
    				$canEditCat    = $user->authorise('core.edit',       'com_xbmusic.category.' . $item->catid);
    				$canEditOwnCat = $user->authorise('core.edit.own',   'com_xbmusic.category.' . $item->catid) && $item->category_uid == $userId;
    				$canEditParCat    = $user->authorise('core.edit',       'com_xbmusic.category.' . $item->parent_category_id);
    				$canEditOwnParCat = $user->authorise('core.edit.own',   'com_xbmusic.category.' . $item->parent_category_id) && $item->parent_category_uid == $userId;
    				?>
 					<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">
						<td>
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="order">
							<?php
							$iconClass = '';
							$numclass = '';
							if (!$canChange) {
							    $iconClass = ' inactive';
							    $numclass = 'xbgrey';
							} elseif (!$saveOrder) {
							    $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
							    $numclass = 'xbgrey';
							}
							?>
                            <span class="sortable-handler<?php echo $iconClass ?>">
                                <span class="icon-ellipsis-v" aria-hidden="true"></span>
                            </span>
                            <?php if ($canChange && $saveOrder) : ?>
                                <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                            <?php endif; ?>             
							<span class="<?php echo $numclass; ?>"><?php echo $item->ordering;?></span>
						</td>
						<td class="song-status">
							<div style="float:left;">
                                <?php
                                    $options = [
                                        'task_prefix' => 'songs.',
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
									<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'artimgs.', $canCheckin); ?>
								<?php endif; ?>
								<?php if ($canEdit || $canEditOwn) : ?>
									<a class="hasTooltip" href="
									<?php echo Route::_('index.php?option=com_xbmusic&task=song.edit&id=' . $item->id).'&retview=songs';?>
									" title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->title); ?></a> 
								<?php else : ?>
									<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
										<?php echo $this->escape($item->title); ?></span>
								<?php endif; ?>
								<?php $pvuri = "'".(Uri::root().'index.php?option=com_xbmusc&view=song&tmpl=component&id='.$item->id)."'"; ?>
          						<?php $pvtit = "'".$item->title."'"; ?>
                                <span  data-bs-toggle="modal" data-bs-target="#pvModal" data-bs-source="<?php echo $pvuri; ?>" 
                                	data-bs-itemtitle="<?php echo $item->title; ?>" title="<?php echo Text::_('XB_MODAL_PREVIEW'); ?>" 
          							onclick="var pv=document.getElementById('pvModal');pv.querySelector('.modal-body .iframe').setAttribute('src',<?php echo $pvuri; ?>);pv.querySelector('.modal-title').textContent=<?php echo $pvtit; ?>;"
                                	><span class="icon-eye xbpl10"></span></span>
								</p>
								<?php if ($item->note != '') : ?>
									<p class="xbpl20 xb085 xbmb5"><i><?php echo Text::_('XB_NOTE'); ?></i> <b><?php echo $item->note; ?></b>
									</p>
								<?php endif; ?>
							</div>
						</td>
						<td><?php if (count($item->tracks) > 1) : ?>
								<details class="xb09">
									<summary><?php echo Text::sprintf('XBMUSIC_SONG_RECORDINGS',count($item->tracks)); ?></summary>
									<ul>
									<?php foreach ($item->tracks as $track) : ?>
										<li><a href="index.php?option=com_xbmusic&task=track.edit&retview=songs&id=<?php echo $track['id']; ?>">
							                <?php echo $track['title']; ?></a>
							                <br /><span class="xb09">(<?php echo $track['artists']; ?>)</span></li>
									<?php endforeach; ?>
									</ul>
								</details>
							<?php elseif (count($item->tracks)==1) : ?>
								<p class="xb09"><i><?php echo Text::_('Recording'); ?></i>: 
								<a href="index.php?option=com_xbmusic&task=track.edit&retview=songs&id=<?php echo $track['id']; ?>">
									<?php echo $item->tracks[0]['title']; ?>
								</a>
								<br /><span class="xb09"><?php echo Text::sprintf('XBMUSIC_PERF_BY',$track['artists']); ?></p>
							<?php endif; ?>						
						</td>
						<td>Performers
							
						</td>
						<td>albums
						</td>
						<td>playlists
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
						<td class="nowrap xb09" style="padding:6px 0; text-align:center;">
							<?php
							$date = $item->{$dateOrderCol};
							echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('D d M \'y')) : '-';
							?><br />
							<?php echo (int) $item->id; ?>
						</td>
                <?php endforeach; //item ?>				
				</body>
			
			</table>
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
					'title'  => Text::_('XBMUSIC_SONG_PREVIEW'),
					'footer' => '',
				    'height' => '900vh',
				    'bodyHeight' => '90',
				    'modalWidth' => '80',
				    'url' => Uri::root().'index.php?option=com_xbmusic&view=song&id='.'x'
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
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/setifsrc.js" ></script>
    
    <div class="clearfix"></div>
    <?php echo XbmusicHelper::credit('xbMusic');?>
</div>
