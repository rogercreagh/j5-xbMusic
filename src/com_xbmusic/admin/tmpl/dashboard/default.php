<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dashboard/default.php
 * @version 0.0.40.1 26th February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
// use Joomla\CMS\Layout\LayoutHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=dashboard'); ?>" method="post" name="adminForm" id="adminForm">

		<h2><i class='icon-info-circle'></i> <?php echo Text::_('XB_STATUS_SUM'); ?></h2>
		<div class="xbwp100">
        	<div class="xbwp60 pull-left xbpr20">
				<div class="xbbox gradgreen">
					<h3 class="xbmb20"><i class='fas fa-guitar' ></i> <a href="index.php?option=com_xbmusic&view=tracks"><?php echo Text::_('XBMUSIC_TRACKS'); ?></a></h3>
					<p><span class="xbnit"><?php echo Text::_('XB_TOTAL'); ?></span>
						<span class="xbbadge badge-green"><?php echo $this->trackcnts['total']; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo ($this->trackcnts['published']==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->trackcnts['published'];?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo ($this->trackcnts['unpublished']==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->trackcnts['unpublished'];?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo ($this->trackcnts['archived']==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo $this->trackcnts['archived'];?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo ($this->trackcnts['trashed']==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo $this->trackcnts['trashed'];?></span></span>
					</p>
					<table class="xbwp100">
						<tr>
							<td class="xbwp50 xbpl20">
								<span class="xbbadge badge-ltgreen"><?php echo $this->trackcnts['catcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_CATS_USED')); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->trackcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_TAGS_USED')); ?></span>
							</td>
						</tr>
						<tr>
							<td><span class="xbnit"><?php echo Text::_('XB_CATEGORY_BRANCH'); ?></span>: <?php echo $this->rootcat_track; ?>
								<br /><span class="xbnit"><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?>:</span> <span class="xbbadge badge-cat"><?php echo $this->defcat_track; ?></span>
							</td>
							<td><span class="xbnit"><?php echo Text::_('XB_TAG_GROUPS'); ?></span>: <?php echo $this->tracktagparents; ?>
							</td>
						</tr>
					</table>
				</div>

				<div class="xbbox gradcyan">
					<h3 class="xbmb20"><i class='fas fa-music' ></i> <a href="index.php?option=com_xbmusic&view=songs"><?php echo Text::_('XBMUSIC_SONGS'); ?></a></h3>
					<p><span class="xbnit"><?php echo Text::_('XB_TOTAL'); ?></span>
						<span class="xbbadge badge-cyan"><?php echo $this->songcnts['total']; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo ($this->songcnts['published']==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->songcnts['published'];?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo ($this->songcnts['unpublished']==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->songcnts['unpublished'];?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo ($this->songcnts['archived']==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo $this->songcnts['archived'];?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo ($this->songcnts['trashed']==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo $this->songcnts['trashed'];?></span></span>
					</p>
					<table class="xbwp100">
						<tr>
							<td class="xbwp50 xbpl20">
								<span class="xbbadge badge-ltgreen"><?php echo $this->songcnts['catcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_CATS_USED')); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->songcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_TAGS_USED')); ?></span>
							</td>
						</tr>
						<tr>
							<td><span class="xbnit"><?php echo Text::_('XB_CATEGORY_BRANCH'); ?></span>: <?php echo $this->rootcat_song; ?>
								<br /><span class="xbnit"><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?></span>: <span class="xbbadge badge-cat"><?php echo $this->defcat_song; ?></span>
							</td>
							<td><span class="xbnit"><?php echo Text::_('XB_TAG_GROUPS'); ?></span>: <?php echo $this->songtagparents; ?>
							</td>
						</tr>
					</table>
				</div>

				<div class="xbbox gradblue">
					<h3 class="xbmb20"><i class='fas fa-users-line' ></i> <a href="index.php?option=com_xbmusic&view=artists"><?php echo Text::_('XBMUSIC_ARTISTS'); ?></a></h3>
					<p><span class="xbnit"><?php echo Text::_('XB_TOTAL'); ?></span>
						<span class="xbbadge badge-blue"><?php echo $this->artistcnts['total']; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo ($this->artistcnts['published']==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->artistcnts['published'];?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo ($this->artistcnts['unpublished']==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->artistcnts['unpublished'];?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo ($this->artistcnts['archived']==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo $this->artistcnts['archived'];?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo ($this->artistcnts['trashed']==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo $this->artistcnts['trashed'];?></span></span>
					</p>
					<table class="xbwp100">
						<tr>
							<td class="xbwp50 xbpl20">
								<span class="xbbadge badge-ltgreen"><?php echo $this->artistcnts['catcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_CATS_USED')); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->artistcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_TAGS_USED')); ?></span>
							</td>
						<tr>
							<td><span class="xbnit"><?php echo Text::_('XB_CATEGORY_BRANCH'); ?></span>: <?php echo $this->rootcat_artist; ?>
								<br /><span class="xbnit"><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?></span>: <span class="xbbadge badge-cat"><?php echo $this->defcat_artist; ?></span>
							</td>
							<td><span class="xbnit"><?php echo Text::_('XB_TAG_GROUPS'); ?></span>: <?php echo $this->artisttagparents; ?>
							</td>
						</tr>
						</tr>
					</table>
				</div>

				<div class="xbbox gradyellow">
					<h3 class="xbmb20"><i class='fas fa-compact-disc' ></i> <a href="index.php?option=com_xbmusic&view=albums"><?php echo Text::_('XBMUSIC_ALBUMS'); ?></a></h3>
					<p><span class="xbnit"><?php echo Text::_('XB_TOTAL'); ?></span>
						<span class="xbbadge badge-yellow"><?php echo $this->albumcnts['total']; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo ($this->albumcnts['published']==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->albumcnts['published'];?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo ($this->albumcnts['unpublished']==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->albumcnts['unpublished'];?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo ($this->albumcnts['archived']==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo $this->albumcnts['archived'];?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo ($this->albumcnts['trashed']==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo $this->albumcnts['trashed'];?></span></span>
					<table class="xbwp100">
						<tr>
							<td class="xbwp50 xbpl20">
								<span class="xbbadge badge-ltgreen"><?php echo $this->albumcnts['catcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_CATS_USED')); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->albumcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_TAGS_USED')); ?></span>
							</td>
						</tr>
						<tr>
							<td><span class="xbnit"><?php echo Text::_('XB_CATEGORY_BRANCH'); ?></span>: <?php echo $this->rootcat_album; ?>
								<br /><span class="xbnit"><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?></span>: <span class="xbbadge badge-cat"><?php echo $this->defcat_album; ?></span>
							</td>
							<td><span class="xbnit"><?php echo Text::_('XB_TAG_GROUPS'); ?></span>: <?php echo $this->albumtagparents; ?>
							</td>
						</tr>
					</table>
					</p>
				</div>

				<div class="xbbox gradpink">
					<h3 class="xbmb20"><i class='fas fa-headphones' ></i> <a href="index.php?option=com_xbmusic&view=playlists"><?php echo Text::_('XBMUSIC_PLAYLISTS'); ?></a></h3>
					<p><span class="xbnit"><?php echo Text::_('XB_TOTAL'); ?></span>
						<span class="xbbadge badge-pink"><?php echo $this->playlistcnts['total']; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo ($this->playlistcnts['published']==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->playlistcnts['published'];?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo ($this->playlistcnts['unpublished']==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->playlistcnts['unpublished'];?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo ($this->playlistcnts['archived']==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo $this->playlistcnts['archived'];?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo ($this->playlistcnts['trashed']==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo $this->playlistcnts['trashed'];?></span></span>
					</p>
					<table class="xbwp100">
						<tr>
							<td class="xbwp50 xbpl20">
								<span class="xbbadge badge-ltgreen"><?php echo $this->playlistcnts['catcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_CATS_USED')); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->playlistcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo lcfirst(Text::_('XBMUSIC_TAGS_USED')); ?></span>
							</td>
						</tr>
						<tr>
							<td><span class="xbnit"><?php echo Text::_('XB_CATEGORY_BRANCH'); ?></span>: <?php echo $this->rootcat_playlist; ?></span>
								<br /><span class="xbnit"><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?></span>: <span class="xbbadge badge-cat"><?php echo $this->defcat_playlist; ?></span>
							</td>
							<td><span class="xbnit"><?php echo Text::_('XB_TAG_GROUPS'); ?></span>: <?php echo $this->plisttagparents; ?>
							</td>
						</tr>
					</table>
				</div>

				<div class="xbbox gradcat">
					<h3 class="xbmb20"><i class='fas fa-folder-tree' ></i> <a href="index.php?option=com_xbmusic&view=catlist"><?php echo Text::_('XB_CATEGORIES'); ?></a></h3>
					<p><span class="xbnit"><?php echo Text::_('XBMUSIC_XBMUSIC_CATEGORIES'); ?></span>
						<span class="xbbadge badge-cat"><?php echo $this->catcnts['total']; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo ($this->catcnts['published']==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->catcnts['published'];?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo ($this->catcnts['unpublished']==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo $this->catcnts['unpublished'];?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo ($this->catcnts['archived']==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo $this->catcnts['archived'];?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo ($this->catcnts['trashed']==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo $this->catcnts['trashed'];?></span></span>
					</p>
				</div>
				
				<div class="xbbox gradtag">
					<h3 class="xbmb20"><i class='fas fa-tags' ></i> <a href="index.php?option=com_xbmusic&view=tags"><?php echo Text::_('XB_TAGS'); ?></a></h3>
					<table class="xbwp100">
						<tr>
							<td class="xbwp50 xbpl20">
								<span class="xbnit xbpl10"><?php echo Text::_('XBMUSIC_TAGS_TOTAL'); ?></span>
								<span class="xbbadge badge-tag"><?php echo $this->tagcnts['total'];?></span>
							</td>
							<td class="xbwp50">
								<span class="xbnit xbpl10"><?php echo Text::_('XBMUSIC_TAGS_USED'); ?></span>
								<span class="xbbadge badge-ltblue"><?php echo $this->tagcnts['used'];?></span>
							</td>
						</tr>
					</table>
				</div>				

          	</div>
          	
			<div id="xbinfo" class="xbwp40 pull-left" style="max-width:400px;">
				<div>
		        	<?php echo HTMLHelper::_('bootstrap.startAccordion', 'slide-dashboard', array('active' => 'sysinfo')); ?>
	        		<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_SYS_INFO'), 'sysinfo','xbaccordion'); ?>
            			<p><b>xbMusic</b>
    						<br /><?php echo Text::_('XB_VERSION').': <b>'.$this->xmldata['version'].'</b> '.
    							$this->xmldata['creationDate'];?>
                      	</p>
                      	<div class="pull-left">
                      		<a href="#changelogModal" class="changelogModal btn btn-success"  data-bs-toggle="modal">
								<?php echo Text::_('XB_CHANGELOG');?></a>
						</div>
						<?php if ($this->updatable) { 
						    echo '<div class="pull-right"><a href="http://j5.localhost/administrator/index.php?option=com_installer&view=update"
                                class="btn btn-warning">'.Text::_('XB_UPDATE_AVAILABLE').'</a></div>'; 
						} ?>
                        <div class="clearfix"></div>
                        <hr />
                      	<p><b><?php echo Text::_( 'XB_YOUR_CLIENT'); ?></b><br />
                      		<?php echo Text::_('XB_YOUR_IP').Xbtext::_($this->client['userip'],XBSP1+XBBR); 
    						  echo Text::_( 'XB_PLATFORM' ).Xbtext::_($this->client['platform'],XBSP1+XBBR); 
    						  echo Text::_( 'XB_BROWSER').Xbtext::_($this->client['browser'],XBSP1); ?>
                     	</p>
    				<?php echo HtmlHelper::_('bootstrap.endSlide'); ?>
	        		<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_KEY_CONFIG_OPTIONS'), 'keyconfig','xbaccordion'); ?>
	        			<p><?php echo Text::_('XBMUSIC_CONFIG_SETTINGS'); ?>:
	        			</p>
	        			<dl class="xbdlinline">
	        				<dt><?php echo Text::_('XBMUSIC_MUSIC_FOLDER'); ?>: </dt>
	        					<dd><?php echo XbmusicHelper::$musicBase; ?></dd>
	        				<dt><?php echo 'ID3 Genre'; ?></dt>
	        					<dd><?php echo $this->id3genreuse; ?></dd>
	        			</dl>
        			<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
    				<?php echo HtmlHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_ABOUT'), 'about','xbaccordion'); ?>
						<p><?php echo Text::_( 'XBMUSIC_ABOUT' ); ?></p>
					<?php echo HtmlHelper::_('bootstrap.endSlide'); ?>
					<?php echo HtmlHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_LICENCE'), 'license','xbaccordion'); ?>
						<p><?php echo Text::_( 'XB_LICENSE_GPL' ); ?>
							<br><?php echo Text::sprintf('XB_LICENSE_INFO','<b>xbMusic</b>');?>
							<br /><?php echo $this->xmldata['copyright']; ?>
						</p>		        		
        			<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
	        		<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_REGINFO'), 'reginfo','xbaccordion'); ?>
                        <?php  if (XbcommonHelper::penPont()) {
                            echo Text::_('XB_BEER_THANKS'); 
                        } else {
                            echo Text::_('XB_BEER_LINK');
                        }?>
        			<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
				<?php if($this->azuracast == 1) : ?>
                      <?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-dashboard','Azuracast Stations' , 'stations','xbaccordion'); ?>
					<?php if($this->stations) : ?>
						<?php foreach($this->stations as $station) : ?>
							<h4><?php echo $station->name; ?></h4>               
                        	<dl class="xbdl">
                        		<dt>Az ID</dt>
                         		<dd><?php echo $station->id; ?></dd>
                        		<dt>Website</dt>
                         		<dd><?php echo $station->url; ?></dd>
                        		<dt>Listen</dt>
                         		<dd><?php echo $station->listen_url; ?></dd>
                        		<dt>Player</dt>
                         		<dd><?php echo $station->public_player_url; ?></dd>
                         		<?php if ($station->playlists) : ?>
                         		<dt><?php echo count($station->playlists); ?> Playlists</dt>
                         			<dd><?php foreach($station->playlists as $playlist) {
                         			    if ($playlist->is_enabled) {
                             			    echo $playlist->name.' ('.$playlist->num_songs.' tracks';
                         			        if ($playlist->schedule_items) echo ' Scheduled';
                         			        echo '<br />';
                         			    }
                         			}?>
                         		<?php endif; ?>
                         		</dd>
                         		}?>
                       		</dl>                                
 						<?php endforeach; ?>
 					<?php else : ?>
                  		<p><i>No stations found at <code><?php echo $this->az_url; ?></code><br />Please check Azuracast URL and API key in config settings.</i>
					<?php endif; ?>
                      	<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
				<?php endif; ?>


						<?php echo HTMLHelper::_('bootstrap.endAccordion'); ?>
					<?php else : ?>
                  		<p><i>No stations found at <?php echo $this->az_url; ?>. Please check Azuracast URL and API key.</i>
					<?php endif; ?>
				</div>				
				<?php endif; ?>
			</div>
			<div class="clearfix"></div>
		</div>	
    	<input type="hidden" name="task" value="" />
    	<input type="hidden" name="boxchecked" value="0" />
    	<?php echo HTMLHelper::_('form.token'); ?>
    
    </form>
    <p>&nbsp;</p>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
    <?php
    echo HTMLHelper::_(
        'bootstrap.renderModal',
        'changelogModal',
        [
            'title' => Text::sprintf('XB_FULL_CHANGELOG','xbMusic')
        ],
        '<div style="margin:10px 30px;">'.$this->changelog.'</div>'
    );
    ?>
                      	

