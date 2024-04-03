<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dashboard/default.php
 * @version 0.0.2.2 2nd April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=dashboard'); ?>" method="post" name="adminForm" id="adminForm">

		<h3><?php echo Text::_('XB_STATUS_SUM'); ?></h3>
		<div class="xbwp100">
        	<div class="xbwp60 pull-left xbpr20">
				<div class="xbbox gradgreen">
					<h4 class="xbmb20"><?php echo Text::_('XBMUSIC_TRACKS'); ?></h4>
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
								<span class="xbnit xbpl10"><?php echo Text::_('categories used'); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->trackcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo Text::_('tags used'); ?></span>
							</td>
						</tr>
						<tr>
							<td><?php echo Text::_('XB_CATEGORY_BRANCH'); ?>: <span class="xbbadge badge-cat"><?php echo $this->rootcat_track; ?></span>
								<br /><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?>: <span class="xbbadge badge-cat"><?php echo $this->defcat_track; ?></span>
							</td>
							<td><?php echo Text::_('XB_TAGGING_CONSTRAINTS'); ?>: <?php echo $this->tracktagparents; ?>
							</td>
						</tr>
					</table>
				</div>

				<div class="xbbox gradcyan">
					<h4 class="xbmb20"><?php echo Text::_('XBMUSIC_SONGS'); ?></h4>
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
								<span class="xbnit xbpl10"><?php echo Text::_('categories used'); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->songcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo Text::_('tags used'); ?></span>
							</td>
						</tr>
						<tr>
							<td><?php echo Text::_('XB_CATEGORY_BRANCH'); ?>: <span class="xbbadge badge-cat"><?php echo $this->rootcat_song; ?></span>
								<br /><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?>: <span class="xbbadge badge-cat"><?php echo $this->defcat_song; ?></span>
							</td>
							<td><?php echo Text::_('XB_TAGGING_CONSTRAINTS'); ?>: <?php echo $this->songtagparents; ?>
							</td>
						</tr>
					</table>
				</div>

				<div class="xbbox gradblue">
					<h4 class="xbmb20"><?php echo Text::_('XBMUSIC_ARTISTS'); ?></h4>
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
								<span class="xbnit xbpl10"><?php echo Text::_('categories used'); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->artistcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo Text::_('tags used'); ?></span>
							</td>
						<tr>
							<td><?php echo Text::_('XB_CATEGORY_BRANCH'); ?>: <span class="xbbadge badge-cat"><?php echo $this->rootcat_artist; ?></span>
								<br /><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?>: <span class="xbbadge badge-cat"><?php echo $this->defcat_artist; ?></span>
							</td>
							<td><?php echo Text::_('XB_TAGGING_CONSTRAINTS'); ?>: <?php echo $this->artisttagparents; ?>
							</td>
						</tr>
						</tr>
					</table>
				</div>

				<div class="xbbox gradyellow">
					<h4 class="xbmb20"><?php echo Text::_('XBMUSIC_ALBUMS'); ?></h4>
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
								<span class="xbnit xbpl10"><?php echo Text::_('categories used'); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->albumcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo Text::_('tags used'); ?></span>
							</td>
						</tr>
						<tr>
							<td><?php echo Text::_('XB_CATEGORY_BRANCH'); ?>: <span class="xbbadge badge-cat"><?php echo $this->rootcat_album; ?></span>
								<br /><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?>: <span class="xbbadge badge-cat"><?php echo $this->defcat_album; ?></span>
							</td>
							<td><?php echo Text::_('XB_TAGGING_CONSTRAINTS'); ?>: <?php echo $this->albumtagparents; ?>
							</td>
						</tr>
					</table>
					</p>
				</div>

				<div class="xbbox gradpink">
					<h4 class="xbmb20"><?php echo Text::_('XBMUSIC_PLAYLISTS'); ?></h4>
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
								<span class="xbnit xbpl10"><?php echo Text::_('categories used'); ?></span>
							</td>
							<td class="xbwp50">
								<span class="xbbadge badge-cyan"><?php echo $this->playlistcnts['tagcnt'];?></span>
								<span class="xbnit xbpl10"><?php echo Text::_('tags used'); ?></span>
							</td>
						</tr>
						<tr>
							<td><?php echo Text::_('XB_CATEGORY_BRANCH'); ?>: <span class="xbbadge badge-cat"><?php echo $this->rootcat_plist; ?></span>
								<br /><?php echo Text::_('XB_DEFAULT_CATEGORY'); ?>: <span class="xbbadge badge-cat"><?php echo $this->defcat_plist; ?></span>
							</td>
							<td><?php echo Text::_('XB_TAGGING_CONSTRAINTS'); ?>: <?php echo $this->plisttagparents; ?>
							</td>
						</tr>
					</table>
				</div>

				<div class="xbbox gradcat">
					<h4 class="xbmb20"><?php echo Text::_('XB_CATEGORIES'); ?></h4>
					<p>
						<span class="xbnit"><?php echo Text::_('XB_DEFINED'); ?></span>
						<span class="xbbadge badge-success"><?php echo '1171'; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo (1==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo '1001';?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo (1==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo '235';?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo (1==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo '57';?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo (1==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo '2';?></span></span>
					</p>
				</div>
				
				<div class="xbbox gradtag">
					<h4 class="xbmb20"><?php echo Text::_('XB_TAGS'); ?></h4>
					<p>
						<span class="xbnit"><?php echo Text::_('XB_USED'); ?></span>
						<span class="xbbadge badge-success"><?php echo '1171'; ?></span>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XB_STATUS_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo (1==0) ? 'label-grey' : 'label-green';?>"
            			 title="<?php echo Text::_('XB_PUBLISHED'); ?>">&nbsp;&nbsp;<?php echo '1001';?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo (1==0) ? 'label-grey':'label-orange';?>"
            			 title="<?php echo Text::_('XB_UNPUBLISHED'); ?>">&nbsp;&nbsp;<?php echo '235';?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo (1==0) ? 'label-grey' : 'label-black';?>"
            			 title="<?php echo Text::_('XB_ARCHIVED'); ?>">&nbsp;&nbsp;<?php echo '57';?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo (1==0) ? 'label-grey' : 'label-pink';?>"
            			 title="<?php echo Text::_('XB_TRASHED'); ?>">&nbsp;&nbsp;<?php echo '2';?></span></span>
					</p>
				</div>				

          	</div>
          	
			<div id="xbinfo" class="xbwp40 pull-left" style="max-width:400px;">
		        	<?php echo HTMLHelper::_('bootstrap.startAccordion', 'slide-dashboard', array('active' => 'sysinfo')); ?>
	        		<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('System Info.'), 'sysinfo','xbaccordion'); ?>
            			<p><b><?php echo Text::_( 'xbMusic' ); ?></b>
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
                      	<p><b><?php echo Text::_( 'XB_YOUR_CLIENT'); ?></b>
    						<br/><?php echo Text::_( 'XB_PLATFORM' ).' '.$this->client['platform'].'<br/>'.Text::_( 'XB_BROWSER').' '.$this->client['browser']; ?>
                     	</p>
    				<?php echo HtmlHelper::_('bootstrap.endSlide'); ?>
	        		<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_KEY_CONFIG_OPTIONS'), 'keyconfig','xbaccordion'); ?>
	        			<p><?php echo Text::_('XBMUSIC_CONFIG_SETTINGS'); ?>:
	        			</p>
	        			<dl class="xbdlinline">
	        				<dt><?php echo Text::_('XBMUSIC_TAG_GROUPS'); ?>: </dt> 
	        					<dd>blah</dd>
	        			</dl>
        			<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
    				<?php echo HtmlHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_ABOUT'), 'about','xbaccordion'); ?>
						<p><?php echo Text::_( 'XBMUSIC_ABOUT' ); ?></p>
					<?php echo HtmlHelper::_('bootstrap.endSlide'); ?>
					<?php echo HtmlHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_LICENCE'), 'license','xbaccordion'); ?>
						<p><?php echo Text::_( 'XB_LICENSE_GPL' ); ?>
							<br><?php echo Text::sprintf('XB_LICENSE_INFO','xbMusic');?>
							<br /><?php echo $this->xmldata['copyright']; ?>
						</p>		        		
        			<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
	        		<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-dashboard', Text::_('XB_REGINFO'), 'reginfo','xbaccordion'); ?>
                        <?php  if (XbmusicHelper::penPont()) {
                            echo Text::_('XB_BEER_THANKS'); 
                        } else {
                            echo Text::_('XB_BEER_LINK');
                        }?>
        			<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
					<?php echo HTMLHelper::_('bootstrap.endAccordion'); ?>
			</div>
			<div class="clearfix"></div>
		</div>	
    	<input type="hidden" name="task" value="" />
    	<input type="hidden" name="boxchecked" value="0" />
    	<?php echo HTMLHelper::_('form.token'); ?>
    
    </form>
    <p>&nbsp;</p>
    <?php echo XbmusicHelper::credit('xbMusic');?>
</div>
    <?php
    echo HTMLHelper::_(
        'bootstrap.renderModal',
        'changelogModal',
        [
            'title' => Text::_('XB_FULL_CHANGELOG')
        ],
        '<div style="margin:10px 30px;">'.$this->changelog.'</div>'
    );
    ?>
                      	

