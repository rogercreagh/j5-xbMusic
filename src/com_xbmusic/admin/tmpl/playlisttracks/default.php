        <?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/playlisttracks/default.php
 * @version 0.0.59.17 22nd December 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
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
use Joomla\CMS\Session\Session;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

// HTMLHelper::_('behavior.multiselect');
// HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.popover', '.xbpop', ['trigger'=>'hover']);

$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect')
    ->useScript('xbmusic.xbgeneral');

$app       = Factory::getApplication();
$user  = $app->getIdentity();
$userId    = $user->get('id');
$playlist = $this->playlist;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$saveOrder = ($listOrder == 'ordering');

$rowcnt = (empty($this->items)) ? 0 : count($this->items);

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_xbmusic&task=playlisttracks.saveOrderAjax&tmpl=component&'.Session::getFormToken().'=1';
//    HTMLHelper::_('sortablelist.sortable', 'xbbooksList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
    HTMLHelper::_('draggablelist.draggable');
}

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=playlisttracks&id='.$this->id); ?>" method="post" name="adminForm" id="adminForm">
    	<h2><?php echo Text::_('Tracks in'); ?> 
    		<a href="index.php?option=com_xbmusic&task=playlist.edit&id=<?php echo $playlist->id; ?>">
    			<?php echo $playlist->title; ?> playlist
    		</a>
    	</h2>
    	<h3><span class="xbnorm xbit"><?php echo Text::_('Station'); ?></span> : 
    		<a href="index.php?option=com_xbmusic&task=station.edit&id=<?php echo $this->station['id']; ?>" >
    			<?php echo $this->station['title']; ?></a>
    		<span class="xbnorm xbit"><?php echo Text::_('XBMUSIC_SERVER'); ?></span> <?php echo $this->station['az_url']; ?>
    	</h3>
		<?php
            $waitmessage = 'XBMUSIC_WAITING_SERVER';
            echo LayoutHelper::render('xbmusic.waiter', array('message'=>$waitmessage)); ?>
		<span class="xbnote"><?php echo Text::_('XBMUSIC_PLAYLISTTRACKS_INFO');?></span>
		<p>Playlist details and schedule last sync'd with azuracast at 
			<?php $sync = new DateTime($playlist->lastsync);
			     echo $sync->format('H:i \o\n D jS M Y');?>. 
			Track list last sync'd at <?php if (!is_null($playlist->tracks_sync)) {
              $sync = new DateTime($playlist->tracks_sync);
			     echo $sync->format('H:i \o\n D jS M Y'); } else { echo '<i>Unknown</i>'; } ?>
        </p>
		<details>
			<summary><?php echo Text::_('Playlist Information'); ?></summary>
			<div class="row">
				<div class="col-md-6">
        			<dl class="xbgl">
        				<dt><?php echo Text::_('XB_TYPE'); ?></dt>
        				<dd><?php if ($playlist->az_type < 2) {
        				    echo Text::_('XBMUSIC_AZTYPE'.$playlist->az_type); 
        					} else {
        					    echo Text::sprintf('XBMUSIC_AZTYPE'.$playlist->az_type, $playlist->az_cntper);
        					} ?>
        				</dd>
        				<dt><?php echo Text::_('XB_WEIGHT'); ?></dt>
        				<dd><?php echo $playlist->az_weight; ?>
        				</dd>
        				<dt><?php echo Text::_('XB_ORDERING'); ?></dt>
        				<dd><?php echo ucfirst($playlist->az_order); ?>
        				</dd>					
        			</dl>
                </div>
				<div class="col-md-6">
        			<dl class="xbgl">
        				<dt><?php echo Text::_('Scheduled'); ?></dt>
        				<dd><?php echo $playlist->schedulecnt.Xbtext::_('slots in schedule'); ?>
        				<br /><span class="xbnote"><?php echo Text::_('Check times in') ?> 
        					<a href="index.php?option=com_xbmusic&task=playlist.edit&id=<?php echo $playlist->id; ?>#schedule">
        						Playlist Schedule Tab</a></span>
        				</dd>
        				<dt><?php echo Text::_('Track Count'); ?></dt>
        				<dd><?php echo $playlist->az_num_songs.Xbtext::_('in Azuracast',XBTRL  + XBSP1); ?>
        					<span <?php if ($playlist->az_num_songs != $this->pagination->total) echo 'class="xbred"'; ?>
        					    >, 
        						<?php echo $this->pagination->total.Xbtext::_('in local list', XBTRL + XBSP1); ?>
        					</span>
        				</dd>
        				<dt><?php echo Text::_('Duration'); ?></dt>
        				<dd><?php echo XbcommonHelper::secondsToHms($playlist->az_total_length) ; ?>
        				</dd>
					
        			</dl>
            	</div>
            </div>
		</details>
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
					<?php if ($playlist->az_order == 'sequential') : ?>
						<th scope="col" class="text-center d-none d-md-table-cell" style="width:125px;">
							<?php echo HTMLHelper::_('searchtools.sort', '', 'ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
						</th>
					<?php endif; ?>
						<th class="nowrap center " style="width:95px;" >
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.status', $listDirn, $listOrder); ?>
						</th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'Track Title', 't.title', $listDirn, $listOrder); ?>
						</th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'Artist', 't.sortartist', $listDirn, $listOrder); ?>
						</th>
						<th >
							<?php echo HTMLHelper::_('searchtools.sort', 'Album Title', 'b.title', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap xbtc center " style="width:160px; padding:0;"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'pt.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tbody <?php if ($saveOrder) : ?> 
    					class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" 
    					data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"
					<?php endif; ?>>
				<?php foreach ($this->items as $i => $item) :
    				$item->max_ordering = 0;
    				$ordering   = ($listOrder == 'ordering');
    				$canEdit    = $user->authorise('core.edit',       'com_xbmusic.track.' . $item->id);
    				$canEditOwn = $user->authorise('core.edit.own',   'com_xbmusic.track.' . $item->id) && $item->created_by == $userId;
    				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
    				$canChange  = $user->authorise('core.edit.state', 'com_xbmusic.track.' . $item->id) && $canCheckin;
    				?>
 					<tr class="row<?php echo $i % 2; ?>"  data-draggable-group="1">
						<td class="center" style="width:25px;">
                            <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->track_title); ?>
							<?php //echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
					<?php if ($playlist->az_order == 'sequential') : ?>
                        <td class="text-center d-none d-md-table-cell xbw125">
                            <?php
                            $iconClass = '';
                            if (!$canChange) {
                                $iconClass = ' inactive';
                            } elseif (!$saveOrder) {
                                $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                            }
                            ?>
                            <span class="sortable-handler<?php echo $iconClass; ?>">
                                <span class="icon-ellipsis-v" aria-hidden="true"> </span>
                            </span>
							<input type="text" name="order[]" size="5" readonly="true" 
                            	value="<?php echo $item->ordering; ?>" class="text-area-order xborderip" />
                        </td>
					<?php endif; ?>	
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
                                	<span class="icon-info-circle xbpl5 " style="font-size:1.6rem;" 
                                		title="<?php echo $item->note; ?>"></span>
								<?php endif; ?>
                             </div>
                                   
						</td>
						<td class="has-context">
							<div class="pull-left">
								<p class="xbm0">
								<?php if ($canEdit || $canEditOwn) : ?>
								<?php else : ?>
								<?php endif; ?>
									<a class="hasTooltip" href="
									<?php echo Route::_('index.php?option=com_xbmusic&task=track.edit&id=' . $item->track_id).'&retview=playlists';?>" >
										<?php echo $this->escape($item->track_title); ?></a> 
									
								<?php $pvuri = "'".(Uri::root().'index.php?option=com_xbmusc&view=track&tmpl=component&id='.$item->track_id)."'"; ?>
          						<?php $pvtit = "'".$item->track_title."'"; ?>
                                <span  data-bs-toggle="modal" data-bs-target="#pvModal" data-bs-source="<?php echo $pvuri; ?>" 
                                	data-bs-itemtitle="<?php echo $item->track_title; ?>" title="<?php echo Text::_('XB_MODAL_PREVIEW'); ?>" 
          							onclick="var pv=document.getElementById('pvModal');pv.querySelector('.modal-body .iframe').setAttribute('src',<?php echo $pvuri; ?>);pv.querySelector('.modal-title').textContent=<?php echo $pvtit; ?>;"
                                	><span class="icon-eye xbpl10"></span></span>
								</p>
							</div>
						</td>
						<td><?php echo $item->artists ; ?>
						</td>
						<td><?php echo $item->album_title;?>
						</td>
						<td class="nowrap xbr09" style="padding:6px 0; text-align:center;">
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
