<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/catlist/default.php
 * @version 0.0.18.8 8th November 2024
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
//$wa->useScript('table.columns');
$wa->useScript('multiselect');

$app       = Factory::getApplication();
$user  = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
//$saveOrder = $listOrder == 'a.ordering';

$celink = 'index.php?option=com_categories&extension=com_xbmusic&task=category.edit&id=';
$catlink = 'index.php?option=com_xbmusic&view=catinfo&id=';
$albumslink = 'index.php?option=com_xbmusic&view=albums&catid=';
$artistslink = 'index.php?option=com_xbmusic&view=artists&catid=';
$playlistslink = 'index.php?option=com_xbmusic&view=playlists&catid=';
$songslink = 'index.php?option=com_xbmusic&view=songs&catid=';
$trackslink = 'index.php?option=com_xbmusic&view=tracks&catid=';

//$rowcnt = (empty($this->items)) ? 0 : count($this->items);

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=catlist'); ?>" method="post" name="adminForm" id="adminForm">
		<h3><?php echo Text::_('XBMUSIC_XBMUSIC_CATEGORIES'); ?></h3>
      	<p class="xb095"><?php echo Text::_('XBMUSIC_CATSPAGE_SUBTITLE'); ?></p>
		
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
                    <?php echo Xbtext::_('XB_SORTED_BY',XBT_SP_LAST).$listOrder.' '.$listDirn ; ?>
        		</p>
			</div>
			<div class="table-scroll">
			<table class="table table-striped table-hover xbtablelist table-freeze" id="xbcategoryList">
            	<thead>
            		<tr>
            			<th class="hidden-phone center" style="width:25px;">
            				<?php echo HTMLHelper::_('grid.checkall'); ?>
            			</th>
            			<th style="width:70px;">
            				<?php echo Text::_('JSTATUS'); ?>
            			</th>
            			<th>
            				<?php echo HTMLHelper::_('searchtools.sort', 'XB_HIERARCHY', 'path', $listDirn, $listOrder );?>&nbsp;
            				<?php echo HTMLHelper::_('searchtools.sort', 'XB_TITLE', 'title', $listDirn, $listOrder );?>
            			</th>
            			<th>
            				<?php echo Text::_('XB_DESCRIPTION') ;?>
            			</th>
            			<th style="text-align:center;">
            				<?php echo HTMLHelper::_('searchtools.sort', ('XBMUSIC_ALBUMS'), 'albumcnt', $listDirn, $listOrder );?>
            			</th>
            			<th style="text-align:center;">
            				<?php echo HTMLHelper::_('searchtools.sort', ('XBMUSIC_ARTISTS'), 'artistcnt', $listDirn, $listOrder );?>
            			</th>
            			<th style="text-align:center;">
            				<?php echo HTMLHelper::_('searchtools.sort', ('XBMUSIC_PLAYLISTS'), 'playlistcnt', $listDirn, $listOrder );?>
            			</th>
            			<th style="text-align:center;">
            				<?php echo HTMLHelper::_('searchtools.sort', ('XBMUSIC_SONGS'), 'songcnt', $listDirn, $listOrder );?>
            			</th>
            			<th style="text-align:center;">
            				<?php echo HTMLHelper::_('searchtools.sort', ('XBMUSIC_TRACKS'), 'trackcnt', $listDirn, $listOrder );?>
            			</th>
            			<th class="nowrap hidden-tablet hidden-phone" style="width:45px;">
            				<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder );?>
            			</th>
            		</tr>
            	</thead>
        		<tbody>
        			<?php foreach ($this->items as $i => $item) : 
        				$canEdit    = $user->authorise('core.edit',       'com_categories.category.' . $item->id);
        				$canCheckin = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
        				$canEditOwn = $user->authorise('core.edit.own',   'com_categories.category.' . $item->id) && $item->created_user_id == $userId;
        				$canChange  = $user->authorise('core.edit.state', 'com_categories.category.' . $item->id) && $canCheckin;
        			?>
        			<tr class="row<?php echo $i % 2; ?>" >	
        					<td class="center hidden-phone">
        						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
        					</td>
    						<td class="album-status nowrap center">
    							<div style="float:left;">
                                    <?php
                                        $options = [
                                            'task_prefix' => 'category.',
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
     						<td>
    							<?php if ($item->checked_out) : ?>
    								<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'artimgs.', $canCheckin); ?>
    							<?php endif; ?>
    							<?php $slashes = substr_count($item->path,'/'); 
    							$prefix = '';
        						if ($slashes >0) :?>
                    				<span class="xbnote"> 
                 					<?php if ($listOrder=='path') {
                       				    $prefix .= '<span style="padding-left:'.($slashes*15).'px">';
                       				    $prefix .= '└─&nbsp;</span>';					        
                   					} else {
                                        $prefix = substr($item->path,0,strrpos($item->path, '/')).'<br />';
                                    }       
                                    echo $prefix; ?>
    								</span>
                                <?php endif; ?>
            					<a href="<?php echo Route::_($catlink . $item->id); ?>" title="Details" 
            						class="xblabel label-cat" style="padding:2px 8px;">
            						<span class="xb11"><?php echo $item->title; ?></span>
            					</a>
        					</td>
                			<td class="xb09">
                				<?php echo $item->description; ?>
                			</td>
                			<td style="text-align:center;">
               					<?php if ($item->albumcnt >0) : ?> 
               						<span class="xbbadge badge-yellow">
               							<a href="<?php echo $albumslink.$item->id;?>"><?php echo $item->albumcnt; ?>
               						</a></span>
               					<?php endif; ?>
               				</td>
                			<td style="text-align:center;">
               					<?php if ($item->artistcnt >0) : ?> 
               						<span class="xbbadge badge-ltblue">
               							<a href="<?php echo $artistslink.$item->id;?>"><?php echo $item->artistcnt; ?>
               						</a></span>
               					<?php endif; ?>
               				</td>
                			<td style="text-align:center;">
               					<?php if ($item->playlistcnt >0) : ?> 
               						<span class="xbbadge badge-pink">
               							<a href="<?php echo $playlistslink.$item->id;?>"><?php echo $item->playlistcnt; ?>
               						</a></span>
               					<?php endif; ?>
               				</td>
                			<td style="text-align:center;">
               					<?php if ($item->songcnt >0) : ?> 
               						<span class="xbbadge badge-cyan">
               							<a href="<?php echo $songslink.$item->id;?>"><?php echo $item->songcnt; ?>
               						</a></span>
               					<?php endif; ?>
               				</td>
                			<td style="text-align:center;">
               					<?php if ($item->trackcnt >0) : ?> 
               						<span class="xbbadge badge-ltgreen">
               							<a href="<?php echo $trackslink.$item->id;?>"><?php echo $item->trackcnt; ?>
               						</a></span>
               					<?php endif; ?>
               				</td>
              				<td>
            					<?php echo $item->id; ?>
            				</td>
            			</tr>
            			<?php endforeach; ?>
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
			<?php // Load the album preview modal ?>
			<?php echo HTMLHelper::_(
				'bootstrap.renderModal',
				'pvModal',
				array(
					'title'  => Text::_('XBMUSIC_ALBUM_PREVIEW'),
					'footer' => '',
				    'height' => '900vh',
				    'bodyHeight' => '90',
				    'modalWidth' => '80',
				    'url' => Uri::root().'index.php?option=com_xbmusic&view=album&id='.'x'
				),
			); ?>

 		<?php endif; ?>

		<?php echo $this->pagination->getListFooter(); ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/setifsrc.js" ></script>
    
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
