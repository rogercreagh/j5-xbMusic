<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/taginfo/default.php
 * @version 0.0.52.5 3rd June 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
// use Joomla\CMS\Button\PublishedButton;
// use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
// use Joomla\CMS\Layout\LayoutHelper;
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

$item = $this->item;
$telink = 'index.php?option=com_tags&task=tag.edit&id=';
$xblink = 'index.php?option=com_xbmusic';
$tinflink = 'index.php?option=com_xbmusic&view=taginfo&id=';
//$celink = 'index.php?option=com_categories&extension=com_xbmusic&task=category.edit&id=';
$albumslink = 'index.php?option=com_xbmusic&view=albums&tagid=';
$artistslink = 'index.php?option=com_xbmusic&view=artists&tagid=';
$playlistslink = 'index.php?option=com_xbmusic&view=playlists&tagid=';
$songslink = 'index.php?option=com_xbmusic&view=songs&tagid=';
$trackslink = 'index.php?option=com_xbmusic&view=tracks&tagid=';

$longlist = 4;

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=taginfo'); ?>" method="post" name="adminForm" id="adminForm">
		<div class="row">
			<div class="col-sm-6">
        		<h3><?php echo Text::_('XBMUSIC_XBMUSIC_TAGINFO'); ?></h3>
              	<p class="xb095"><?php echo Text::_('XBMUSIC_TAGINFOPAGE_SUBTITLE'); ?></p>
			</div>
			<div class= "col-sm-6">
				<a href="<?php echo $telink.$item->id; ?>" class="xbbadge badge-tag xbr15" style="color:#fff;padding:15px;">
					<?php echo $item->title; ?>
				</a>
			</div>
		</div>
		<div class="row xbmb12">
			<div class="col-sm-7">
        		<div class="row">
        			<div class= "col-sm-2">
        				<p><i><?php echo Text::_('XB_TAG').' '.Text::_('XB_DESCRIPTION'); ?>:</i></p>
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
								<i class="xbpr20"><?php echo Text::_('XB_TAG').' '.Text::_('XB_HIERARCHY'); ?>:</i> 
								<?php $path = str_replace('/', ' - ', $item->path);
								echo ' root - '.$path; ?>
							</p>
					</div>
				</div>
			</div>
		</div>
		<?php if ($item->level > 1) : ?>
				<p class="xbmb5"><i><?php echo Text::_('Parent of this tag'); ?></i>
					<a href="<?php echo $tinflink.$item->parent_id; ?>"   
						class="xblabel label-tag xbml15" ><?php echo $item->parent_title; ?></a>
				</p>
		<?php endif; ?>
		<div>		
			<?php if ($item->children) : ?>
				<p class="xbmb5 xbit"><?php echo Text::_('Descendents of this tag'); ?></p>
				<ul class="inline xbml20" style="margin-left:20px;">
					<?php foreach ($item->children as $child) : ?>
						<li><a href="<?php echo $tinflink.$child['id']; ?>" 
							class="xblabel label-tag xbml20"><?php echo $child['title']; ?></a>
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
				<div class="xbbox 
				    <?php echo ($item->albumcnt>0) ? 'gradyellow' : 'xbbggy'; ?>">
					<p><?php echo ($item->albumcnt==0) ? Text::_('JNO') : $item->albumcnt;
					echo Xbtext::_('XBMUSIC_ALBUMS',XBSP3 + XBTRL).Text::_('XB_TAGGED'); ?>  
						<span class="xblabel label-tag"><?php echo $item->title; ?></span></p>
					<?php if ($item->albumcnt > 0) : ?>
    					<div 
    					   <?php if ($item->albumcnt > $longlist) : ?>
								class="xbyscroll" style="max-height:<?php echo $longlist * 25; ?>px;"
    					   <?php endif; ?>
							> 
    						<ul>
        						<?php foreach ($item->albums as $i=>$bk) { 
        							echo '<li><a href="'.$xblink.'&task=album.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    						</ul>
    					</div> 
					<?php endif; ?>
				</div>
			</div>
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->artistcnt > 0) ? 'gradblue' : 'xbbggy'; ?>">
					<p><?php echo ($item->artistcnt > 0) ? $item->artistcnt : Text::_('JNO');
					echo Xbtext::_('XBMUSIC_ARTISTS',XBSP3 + XBTRL).Text::_('XB_TAGGED'); ?>  
						<span class="xblabel label-tag"><?php echo $item->title; ?></span></p>
					<?php if ($item->artistcnt > 0) : ?>
    					<div 
    					   <?php if ($item->artistcnt > $longlist) : ?>
								class="xbyscroll" style="max-height:<?php echo $longlist * 25; ?>px;"
    					   <?php endif; ?>
							> 
    						<ul>
        						<?php foreach ($item->artists as $i=>$bk) { 
        							echo '<li><a href="'.$xblink.'&task=artist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    						</ul>
    					</div> 
					<?php endif; ?>
				</div>
			</div>
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->playlistcnt > 0) ? 'gradpink' : 'xbbggy'; ?>">
					<p><?php echo ($item->playlistcnt > 0) ? $item->playlistcnt : Text::_('JNO');
					echo Xbtext::_('XBMUSIC_PLAYLISTS',XBSP3 + XBTRL).Text::_('XB_TAGGED'); ?>  
						<span class="xblabel label-tag"><?php echo $item->title; ?></span></p>
					<?php if ($item->playlistcnt > 0) : ?>
    					<div 
    					   <?php if ($item->playlistcnt > $longlist) : ?>
								class="xbyscroll" style="max-height:<?php echo $longlist * 25; ?>px;"
    					   <?php endif; ?>
							> 
    						<ul>
        						<?php foreach ($item->playlists as $i=>$bk) { 
        							echo '<li><a href="'.$xblink.'&task=playlist.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    						</ul>
    					</div> 
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->songcnt>0) ? 'gradcyan' : 'xbbggy'; ?>">
					<p><?php echo ($item->songcnt>0) ? $item->songcnt : Text::_('JNO');
					echo Xbtext::_('XBMUSIC_SONGS',XBSP3 + XBTRL).Text::_('XB_TAGGED'); ?>  
						<span class="xblabel label-tag"><?php echo $item->title; ?></span></p>
					<?php if ($item->songcnt > 0) : ?>
    					<div 
    					   <?php if ($item->songcnt > $longlist) : ?>
								class="xbyscroll" style="max-height:<?php echo $longlist * 25; ?>px;"
    					   <?php endif; ?>
							> 
    						<ul>
        						<?php foreach ($item->albums as $i=>$bk) { 
        							echo '<li><a href="'.$xblink.'&task=song.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    						</ul>
    					</div> 
					<?php endif; ?>
				</div>
			</div>
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->trackcnt > 0) ? 'gradgreen' : 'xbbggy'; ?>">
					<p><?php echo ($item->trackcnt > 0) ? $item->trackcnt : Text::_('JNO');
					echo Xbtext::_('XBMUSIC_TRACKS',XBSP3 + XBTRL).Text::_('XB_TAGGED'); ?>  
						<span class="xblabel label-tag"><?php echo $item->title; ?></span></p>
					<?php if ($item->trackcnt > 0) : ?>
    					<div 
    					   <?php if ($item->trackcnt > $longlist) : ?>
								class="xbyscroll" style="max-height:<?php echo $longlist * 25; ?>px;"
    					   <?php endif; ?>
							> 
    						<ul>
        						<?php foreach ($item->tracks as $i=>$bk) { 
        							echo '<li><a href="'.$xblink.'&task=track.edit&id='.$bk['id'].'">'.$bk['title'].'</a></li> ';
        						} ?>				
    						</ul>
    					</div> 
					<?php endif; ?>
				</div>
			</div>
			<div class= "col-sm-4">
				<div class="xbbox <?php echo ($item->othercnt > 0) ? 'gradgrey' : 'xbbggy'; ?>">
					<p><?php echo ($item->othercnt > 0) ? $item->othercnt : Text::_('JNO');
					echo Xbtext::_('Other Items',XBSP3 + XBTRL).Text::_('XB_TAGGED'); ?>  
						<span class="xblabel label-tag"><?php echo $item->title; ?></span></p>
					<?php if ($item->othercnt > 0) : ?>
    					<div 
    					   <?php if ($item->othercnt > $longlist) : ?>
								class="xbyscroll" style="max-height:<?php echo $longlist * 25; ?>px;"
    					   <?php endif; ?>
							> 
    						<ul>
        						<?php foreach ($item->others as $i=>$oth) { 
        						    $comp = substr($oth->type_alias, 0,strpos($oth->type_alias, '.'));
        						    $ctype = substr($oth->type_alias,strpos($oth->type_alias, '.')+1);
        						    echo '<li><a href="index.php?option='.$comp.'">'.$comp.'</a> - '.$ctype.': '.$oth->core_title.'</li> ';
        						} ?>				
    						</ul>
    					</div> 
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
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
