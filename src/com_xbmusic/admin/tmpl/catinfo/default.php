<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/catinfo/default.php
 * @version 0.0.15.0 12th September 2024
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
//$wa->useScript('table.columns');
$wa->useScript('multiselect');

$app       = Factory::getApplication();
$user  = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');

$item = $this->item;
$celink = 'index.php?option=com_categories&task=category.edit&id=';
$xblink = 'index.php?option=com_xbmusic';
$cinflink = 'index.php?option=com_xbmusic&view=catinfo&id=';
//$celink = 'index.php?option=com_categories&extension=com_xbmusic&task=category.edit&id=';
$albumslink = 'index.php?option=com_xbmusic&view=albums&catid=';
$artistslink = 'index.php?option=com_xbmusic&view=artists&catid=';
$playlistslink = 'index.php?option=com_xbmusic&view=playlists&catid=';
$songslink = 'index.php?option=com_xbmusic&view=songs&catid=';
$trackslink = 'index.php?option=com_xbmusic&view=tracks&catid=';

$longlist = 4;

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=catinfo'); ?>" method="post" name="adminForm" id="adminForm">
		<div class="row">
			<div class="col-sm-6">
        		<h3><?php echo Text::_('XBMUSIC_XBMUSIC_CATEGORY'); ?></h3>
              	<p class="xb095"><?php echo Text::_('XBMUSIC_CATINFOPAGE_SUBTITLE'); ?></p>
			</div>
			<div class= "col-sm-6">
				<a href="<?php echo $celink.$item->id; ?>" class="xbbadge badge-cat xbr15" style="color:#fff;padding:15px;">
					<?php echo $item->title; ?>
				</a>
			</div>
		</div>
		<div class="row xbmb12">
			<div class="col-sm-7">
        		<div class="row">
        			<div class= "col-sm-2">
        				<p><i><?php echo Text::_('XB_CATEGORY').' '.Text::_('XB_DESCRIPTION'); ?>:</i></p>
        			</div>
           			<div class="col-sm-5">
        			<?php if ($item->description != '') : ?>
             			<div class="xbbox xbbgwht" style="max-width:400px;">
            				<?php echo $item->description; ?>
            			</div>
            		<?php else: ?>
            			<p><i>(<?php echo Text::_('XB_NO_DESCRIPTION'); ?>)</i></p>
        			<?php endif; ?>
        			</div>
        			<div class= "col-sm-4">
        				<?php if (!empty($item->note)) : ?>
        					<p><i><?php Text::_('XB_ADMIN_NOTE'); ?>:</i>  <?php echo $item->note; ?></p>
        				<?php endif; ?>
        			</div>
        		</div>
			</div>
			<div class="col-sm-5">
				<div class="row">
		            <div class="col-sm-7">
		                <p><i class="xbpr20"><?php echo Text::_('XB_ALIAS'); ?>:</i> <?php echo $item->alias; ?></p>
		            </div>
					<div class= "col-sm-5">
						<p><i class="xbpr20"><?php echo Text::_('JGRID_HEADING_ID'); ?>:</i><?php echo $item->id; ?></p>
		 			</div>
				</div>
				<div class="row">
					<div class= "col">
							<p>
								<i class="xbpr20"><?php echo Text::_('XB_CATEGORY').' '.Text::_('XB_HIERARCHY'); ?>:</i> 
								<?php $path = str_replace('/', ' - ', $item->path);
								echo ' root - '.$path; ?>
							</p>
					</div>
				</div>
			</div>
		</div>
		<?php if ($item->level > 1) : ?>
				<p class="xbmb5"><i><?php echo Text::_('Parent of this category'); ?></i>
					<a href="<?php echo $cinflink.$item->parent_id; ?>"   
						class="xblabel label-cat xbml15" ><?php echo $item->parent_title; ?></a>
				</p>
		<?php endif; ?>
		<div>		
			<?php if ($item->children) : ?>
				<p class="xbmb5 xbit"><?php echo Text::_('Descendents of this category'); ?></p>
				<ul class="inline xbml20" style="margin-left:20px;">
					<?php foreach ($item->children as $child) : ?>
						<li><a href="<?php echo $cinflink.$child['id']; ?>" 
							class="xblabel label-cat xbml20"><?php echo $child['title']; ?></a>
							(<?php echo $child['itemcnt']['total'];?>)
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
          		<p class="xbtc xbnote xbmb5">Auto close details dropdowns<input  type="checkbox" id="autoclose" name="autoclose" value="yes" checked="true" style="margin:0 5px;" />
          		</p>
          	</div>
		<div class="row">
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->albumcnt>0) ? 'gradyellow' : 'xbbggy'; ?>">
					<p><?php echo ($item->albumcnt==0) ? Text::_('JNO') : $item->albumcnt==0;
					   echo Xbtext::_('XBMUSIC_ALBUMS',3,true).Text::_('XB_IN_CATEGORY'); ?>  
						<span class="xblabel label-cat"><?php echo $item->title; ?></span></p>
					<?php if ($item->albumcnt > 0) : ?>
    					<?php if ($item->albumcnt < $longlist+1) : ?>
    						<ul>
    						<?php foreach ($item->albums as $i=>$bk) { 
    							echo '<li><a href="'.$xblink.'&task=album.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    					<?php else : ?>
    						<ul>
    						<?php for($i=0; $i<$longlist; $i++) {
    						    $bk = $item->albums[$i];
    						    echo '<li><a href="'.$xblink.'&task=album.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    						<details>
    							<summary>see more...</summary>
    							<ul>
    							<?php for($i=$longlist; $i<count($item->albums); $i++) {
        						    $bk = $item->albums[$i];
        						    echo '<li><a href="'.$xblink.'&task=album.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    							</ul>
    						</details>
    					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->artistcnt > 0) ? 'gradblue' : 'xbbggy'; ?>">
					<p><?php echo ($item->artistcnt > 0) ? $item->artistcnt : Text::_('JNO');
					   echo Xbtext::_('XBMUSIC_ARTISTS',3,true).Text::_('XB_IN_CATEGORY'); ?>  
						<span class="xblabel label-cat"><?php echo $item->title; ?></span></p>
					<?php if ($item->artistcnt > 0) : ?>
    					<?php if ($item->artistcnt < $longlist+1) : ?>
    						<ul>
    						<?php foreach ($item->artists as $i=>$bk) { 
    							echo '<li><a href="'.$xblink.'&task=artist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    					<?php else : ?>
    						<ul>
    						<?php for($i=0; $i<$longlist; $i++) {
    						    $bk = $item->artists[$i];
    						    echo '<li><a href="'.$xblink.'&task=artist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    						<details>
    							<summary>see more...</summary>
    							<ul>
    							<?php for($i=$longlist; $i<count($item->artists); $i++) {
        						    $bk = $item->artists[$i];
        						    echo '<li><a href="'.$xblink.'&task=artist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    							</ul>
    						</details>
    					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->playlistcnt > 0) ? 'gradpink' : 'xbbggy'; ?>">
					<p><?php echo ($item->playlistcnt > 0) ? $item->playlistcnt : Text::_('JNO');
        				echo Xbtext::_('XBMUSIC_PLAYLISTS',3,true).Text::_('XB_IN_CATEGORY'); ?>  
						<span class="xblabel label-cat"><?php echo $item->title; ?></span></p>
					<?php if ($item->playlistcnt > 0) : ?>
    					<?php if ($item->playlistcnt < $longlist+1) : ?>
    						<ul>
    						<?php foreach ($item->playlists as $i=>$bk) { 
    							echo '<li><a href="'.$xblink.'&task=playlist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    					<?php else : ?>
    						<ul>
    						<?php for($i=0; $i<$longlist; $i++) {
    						    $bk = $item->playlists[$i];
    						    echo '<li><a href="'.$xblink.'&task=playlist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    						<details>
    							<summary>see more...</summary>
    							<ul>
    							<?php for($i=$longlist; $i<count($item->playlists); $i++) {
        						    $bk = $item->playlists[$i];
        						    echo '<li><a href="'.$xblink.'&task=playlist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    							</ul>
    						</details>
    					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->songcnt>0) ? 'gradcyan' : 'xbbggy'; ?>">
					<p><?php echo ($item->songcnt>0) ? $item->songcnt : Text::_('JNO');
					echo Xbtext::_('XBMUSIC_SONGS',3,true).Text::_('XB_IN_CATEGORY'); ?>  
						<span class="xblabel label-cat"><?php echo $item->title; ?></span></p>
					<?php if ($item->songcnt > 0) : ?>
    					<?php if ($item->songcnt < $longlist+1) : ?>
    						<ul>
    						<?php foreach ($item->songs as $i=>$bk) { 
    							echo '<li><a href="'.$xblink.'&task=song.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    					<?php else : ?>
    						<ul>
    						<?php for($i=0; $i<$longlist; $i++) {
    						    $bk = $item->songs[$i];
    						    echo '<li><a href="'.$xblink.'&task=song.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    						<details>
    							<summary>see more...</summary>
    							<ul>
    							<?php for($i=$longlist; $i<count($item->songs); $i++) {
        						    $bk = $item->songs[$i];
        						    echo '<li><a href="'.$xblink.'&task=song.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    							</ul>
    						</details>
    					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->trackcnt > 0) ? 'gradgreen' : 'xbbggy'; ?>">
					<p><?php echo ($item->trackcnt > 0) ? $item->trackcnt : Text::_('JNO');
					echo Xbtext::_('XBMUSIC_TRACKS',3,true).Text::_('XB_IN_CATEGORY'); ?>  
						<span class="xblabel label-cat"><?php echo $item->title; ?></span></p>
					<?php if ($item->trackcnt > 0) : ?>
    					<?php if ($item->trackcnt < $longlist+1) : ?>
    						<ul>
    						<?php foreach ($item->tracks as $i=>$bk) { 
    							echo '<li><a href="'.$xblink.'&task=track.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    					<?php else : ?>
    						<ul>
    						<?php for($i=0; $i<$longlist; $i++) {
    						    $bk = $item->tracks[$i];
    						    echo '<li><a href="'.$xblink.'&task=track.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
    						} ?>				
    						</ul>
    						<details>
    							<summary>see more...</summary>
    							<ul>
    							<?php for($i=$longlist; $i<count($item->tracks); $i++) {
        						    $bk = $item->tracks[$i];
        						    echo '<li><a href="'.$xblink.'&task=track.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    							</ul>
    						</details>
    					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>		
			<?php // Load the preview modal ?>
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

 
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="tid" value="<?php echo $item->id;?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/closedetails.js" ></script>
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/setifsrc.js" ></script>
    
    <div class="clearfix"></div>
    <?php echo XbmusicHelper::credit('xbMusic');?>
</div>
