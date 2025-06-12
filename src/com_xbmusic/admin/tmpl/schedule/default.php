<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/schedule/default.php
 * @version 0.0.51.8 2nd May 2025
s * @author Roger C-O
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

$app       = Factory::getApplication();
$user  = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');

$wa = $this->document->getWebAssetManager();
$wa->useScript('xbmusic.xbtimefunctions')

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=schedule'); ?>" method="post" name="adminForm" id="adminForm">
<?php if ($this->azuracast == 0 ) : ?>
    <div class="xbbox gradpink xbht200 xbflexvc">
        <div class="xbcentre"><h3><?php echo Text::_('XBMUSIC_AZURACAST_NOT_ENABLED')?>
        </h3></div>
    </div>
<?php elseif (!($this->xbstations)) : ?>
    <div class="xbbox gradyellow xbht200 xbflexvc">
        <div class="xbcentre"><h3><?php echo Text::_('XBMUSIC_AZURACAST_NOT_LOADED')?>
        </h3></div>
    </div>
<?php else: ?>
		<?php  if ($this->dbstid == '') : ?>
			<h3 class="xbred"><?php echo Text::_('XBMUSIC_SELECT_STATION_CONTINUE'); ?></h3>
			<div style="80vh;"> </div>
		<?php else: ?>
			<h3><?php echo Text::sprintf('XBMUSIC_SCHED_FOR_STATION', $this->station['title'], $this->station['az_url']); ?></h3>
		<?php // Search tools bar
		  //echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<div class="row form-vertical">
			<div class="col-md-3">
				<?php echo $this->filterForm->renderField('startdate', 'filter'); ?>
			</div>
			<div class="col-md-2">
				<?php echo $this->filterForm->renderField('numdays', 'filter'); ?>
			</div>
			<div class="col-md-2">
				<?php echo $this->filterForm->renderField('starttime', 'filter'); ?>
        	</div>
			<div class="col-md-2">
				<?php echo $this->filterForm->renderField('numhours', 'filter'); ?>
        	</div>
			<div class="col-md-1">
              </div>
			<div class="col-md-2">
    			<button id="btnsub" class="btn btn-success" type="button" 
            		onclick="Joomla.submitbutton();" >
    				<i class="icon-clock"></i> &nbsp;
            			<?php echo Text::_('XBMUSIC_SET_DISPLAY'); ?>
            	</button>        		
			</div>
		</div>
		<div class="row form-horizontal xblblcompact">
			<div class="col-md-3">
				<?php echo $this->filterForm->renderField('displayfmt', 'filter'); ?>
			</div>
			<div class="col-md-3">
				<?php echo $this->filterForm->renderField('publiconly', 'filter'); ?>
			</div>
			<div class="col-md-3">
			</div>
			<div class="col-md-1">
              </div>
			<div class="col-md-2">
    			<button id="btnclr" class="btn btn-primary" type="button" 
            		onclick="document.getElementById('task').value='schedule.clearFilter';Joomla.submitbutton();" >
    				<i class="icon-clock"></i> &nbsp;
            			<?php echo Text::_('XBMUSIC_CLEAR_FILTERS'); ?>
            	</button>        		
			</div>
		</div>
		<p class="xbtr xbmr50">
		</p>
		<hr />
		
		<?php if ($this->displayfmt == 1) :?>	
		<div class="table-scroll">
    		<table class="table-freeze xbwp100">
    			<thead>
    				<tr>
    					<th class="xbpt10 xbpr20 xbpb10 xbpl20">Time</th>
    					<?php foreach ($this->items as $key=>$day) : ?>
        					<th class="xbtc"><?php echo date('jS M Y\\<\\b\\r\\>l',$key); ?></th>					    
    					<?php endforeach; ?>
    				</tr>
    			</thead>
    			<tbody>
    				<?php $firsthr = (int)substr($this->starttime,0,2);
    				for ($hour = $firsthr; $hour < ($firsthr + $this->numhours); $hour++) : ?>
    					<tr>
    					<td class="xbbgwhite xbtc" style="vertical-align:top;"><?php echo $hour.':00'; ?></td>
    					<?php foreach ($this->items as $dayslots) : ?>
        					<td>				    
    						<?php foreach ($dayslots as $slot) {						    
    						    if (substr($slot->az_starttime,0,2) == $hour ) {
    						        echo '<div class="schbox xb09 ">';
    						        echo $slot->az_starttime.'<br >';
    						        echo '<span class="xbpl20 xbbold">'.$slot->pltitle.'</span><br />';
    						        echo $slot->az_endtime;
    						        echo '</div>';
    						    }
    						} ?>
    						</td>    
    					<?php endforeach; ?>					
    					</tr>				
    				<?php endfor; ?>
    			</tbody>
    		</table>
		</div>			
		<?php else : ?>
						<?php foreach ($this->items as $key=>$dayslots) : ?>
						    <h4><?php echo date('jS M Y\\<\\b\\r\\>l',$key); ?></h4>
							<?php foreach ($dayslots as $slot) {						    
						        echo '<div class="schbox">';
						        echo $slot->az_starttime.'<br >';
						        echo '<span class="xbpl20 xbbold">'.$slot->pltitle.'</span><br />';
						        echo $slot->az_endtime;
						        echo '</div>';
						    } ?>
						    <hr />
						<?php endforeach; ?> 
			
		<?php endif; //displayfmt?>
		
		<?php endif; //dbstid ?>
		<?php //Factory::getApplication()->enqueuemessage('<pre>'.print_r($this->activeFilters,true).'</pre>'); ?>

			<?php // Load the batch processing form. ?>
			<?php if ($user->authorise('core.create', 'com_xbmusic')
				&& $user->authorise('core.edit', 'com_xbmusic')
				&& $user->authorise('core.edit.state', 'com_xbmusic')) : ?>
				<?php echo HTMLHelper::_(
					'bootstrap.renderModal',
					'collapseModal',
					array(
						'title'  => Text::_('XBMUSIC_SELECT_STATION'),
						'footer' => $this->loadTemplate('batch_footer'),
					    'modalWidth' => '50',
					),
					$this->loadTemplate('batch_body')
				); ?>
			<?php endif; ?>
<?php endif; ?>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
