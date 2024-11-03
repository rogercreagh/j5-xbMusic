<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/songs/default.php
 * @version 0.0.18.6 31st October 2024
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
			<div class="pull-left">
        		<p class="xbmb5">              
                    <?php echo Xbtext::_('XB_SORTED_BY',2).$listOrder.' '.$listDirn ; ?>
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
						<th><?php echo Text::_('Recordings'); ?>
						</th>
						<th><?php echo Text::_('Playlists'); ?>
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
						<td class="center " style="width:25px;">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="song-status nowrap center">
							<div style="float:left;">
                                <?php
                                    $options = [
                                        'task_prefix' => 'song.',
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
								<?php if($item->trkcnt > 0): ?>
    								<p class="xbr09 xbnit"><?php echo Xbtext::_('Found on',2).$item->trkcnt; 
    								    echo ($item->trkcnt==1)? Xbtext::_('track',1) : Xbtext::_('tracks',1); ?>
    								</p>
								<?php endif; ?>
							</div>
						</td>
						<td class="xbr09" onclick="stopProp(event);"><?php if (count($item->tracks) > 1) : ?>
								<details>
									<summary><?php echo Text::sprintf('XBMUSIC_SONG_RECORDINGS',count($item->tracks)); ?></summary>
									<ul style="margin:5px;">
									<?php foreach ($item->tracks as $track) : ?>
										<li><a href="index.php?option=com_xbmusic&task=track.edit&retview=songs&id=<?php echo $track['trackid']; ?>">
							                <?php echo $track['trackname']; ?></a> 
							                <?php if($track['rel_date']) echo ' ('.$track['rel_date'].') '; ?>
            				                <?php if($track['artists']) : ?>
            				                	<br /><span class="xbit xbpl20"><?php echo Xbtext::_('by',2); ?></span>
            				                	<?php foreach ($track['artists'] as $artist) : ?>
                				                	<a href="index.php?option=com_xbmusic&task=artist.edit&retview=songs&id=<?php echo $artist['artistid']; ?>">
                				                	<?php echo $artist['name']; ?></a>				                	    
            				                	<?php endforeach; ?>
            				                <?php endif; ?>			                
							                <?php if($track['albumid']>0) : ?>
							                	<br /><span class="xbit xbpl20"><?php echo Xbtext::_('on',2); ?>
							                	<a href="index.php?option=com_xbmusic&task=album.edit&retview=songs&id=<?php echo $track['albumid']; ?>">
							                	<?php echo $track['albumtitle']; ?></a>
							                <?php endif; ?>
							            </li>
									<?php endforeach; ?>
									</ul>
								</details>
							<?php elseif (count($item->tracks)==1) : ?>
								<?php $track = $item->tracks[0]; ?>
								<a href="index.php?option=com_xbmusic&task=track.edit&retview=songs&id=<?php echo $item->tracks[0]['trackid']; ?>">
				                <?php echo $track['trackname']; ?></a> 
				                <?php if($track['rel_date']) echo ' ('.$track['rel_date'].') '; ?>
				                <?php if($track['artists']) : ?>
				                	<br /><span class="xbit xbpl20"><?php echo Xbtext::_('by',2); ?></span>
				                	<?php foreach ($track['artists'] as $artist) : ?>
    				                	<a href="index.php?option=com_xbmusic&task=artist.edit&retview=songs&id=<?php echo $artist['artistid']; ?>">
    				                	<?php echo $artist['name']; ?></a>				                	    
				                	<?php endforeach; ?>
				                <?php endif; ?>			                
				                <?php if($track['albumid']>0) : ?>
				                	<br /><span class="xbit xbpl20"><?php echo Xbtext::_('on',2); ?></span>
				                	<a href="index.php?option=com_xbmusic&task=album.edit&retview=songs&id=<?php echo $track['albumid']; ?>">
				                	<?php echo $track['albumtitle']; ?></a>
				                <?php endif; ?>
							<?php else : ?>
								<p class="xbnit xbr09"><?php echo Text::_('no tracks connected'); ?>
							<?php endif; ?>
						</td>
						<td onclick="stopProp(event);">playlists
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

    <div class="clearfix"></div>
    <?php echo XbmusicHelper::credit('xbMusic');?>
</div>
