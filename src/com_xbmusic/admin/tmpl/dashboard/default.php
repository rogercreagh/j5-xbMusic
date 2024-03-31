<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dashboard/default.php
 * @version 0.0.0.1 31st March 2024
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
				<div class="xbbox gradgrey">
					<h4 class="xbmb20><span class="xbbadge badge-success" style="font-size:1rem;"><?php echo '1171'; ?></span> 
						<?php echo Text::_('Tracks'); ?>
            			<span class="xbpl50 xbnit"><?php echo Text::_('XBMUSIC_STATE_CNTS'); ?> : </span>
            			<span class="xbpl20"></span><span class="icon-check xblabel <?php echo (1==0) ? 'label-grey' : 'label-green';?>"
            			 title="Published">&nbsp;&nbsp;<?php echo '1001';?></span></span>
            			<span class="xbpl50"><span class="icon-times xblabel <?php echo (1==0) ? 'label-grey':'label-orange';?>"
            			 title="Unpublished">&nbsp;&nbsp;<?php echo '235';?></span></span>
            			<span class="xbpl50"><span class="icon-archive xblabel <?php echo (1==0) ? 'label-grey' : 'label-black';?>"
            			 title="Archived">&nbsp;&nbsp;<?php echo '57';?></span></span>
            			<span class="xbpl50"><span class="icon-trash xblabel <?php echo (1==0) ? 'label-grey' : 'label-pink';?>"
            			 title="Trashed">&nbsp;&nbsp;<?php echo '2';?></span></span>
					</h4>
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
                      	

