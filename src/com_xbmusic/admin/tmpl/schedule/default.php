<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/schedule/default.php
 * @version 0.0.51.6 26th April 2025
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

$app       = Factory::getApplication();
$user  = Factory::getApplication()->getIdentity();
$userId    = $user->get('id');

$wa = $this->document->getWebAssetManager();
$wa->useScript('xbmusic.xbtimefunctions')

?>
<div id="xbcomponent" >
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=schedule'); ?>" method="post" name="adminForm" id="adminForm">
		<?php  if ($this->dbstid == '') : ?>
			<h3 class="xbred"><?php echo Text::_('Please select station to continue'); ?></h3>
			<div style="80vh;"> </div>
		<?php else: ?>
			<h3><?php echo Text::sprintf('Schedule for %s at %s', $this->station['title'], $this->station['az_url']); ?></h3>
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
		</div>
		<div class="row form-horizontal xblblcompact">
			<div class="col-md-4">
				<?php echo $this->filterForm->renderField('displayfmt', 'filter'); ?>
			</div>
			<div class="col-md-4">
				<?php echo $this->filterForm->renderField('publiconly', 'filter'); ?>
			</div>
			<div class="col-md-4">
    			<button id="btnsub" class="btn btn-success" type="button" 
            		onclick="Joomla.submitbutton();" >
    				<i class="icon-clock"></i> &nbsp;
            			<?php echo Text::_('Set Display'); ?>
            	</button>        		
			</div>
		</div>
		<p class="xbtr xbmr50">
		</p>
		<hr />
		<pre><?php echo print_r($this->items,true); ?>
		</pre>
		<?php if ($this->displayfmt == 1) :?>	
		<i>freeze header table with a row for each hour and a column for each day. cells show start and end and title (poss colour coded background)</i>
		<table>
			<thead>
				<tr>
					<th>Time</th>
					<?php foreach ($this->items as $key=>$day) : ?>
    					<th><?php echo $key; ?></th>					    
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php $firsthr = (int)substr($this->starttime,0,2);
				for ($hour = $firsthr; $hour < ($firsthr + $this->numhours); $hour++) : ?>
					<tr>
					<td><?php echo $hour.':00'; ?></td>
					<?php foreach ($this->items as $dayslots) : ?>
    					<td>				    
						<?php foreach ($dayslots as $slot) {						    
						    if (substr($slot->az_starttime,0,2) == $hour ) {
						        echo '<div class="schbox">';
						        echo $slot->az_starttime.'<br >';
						        echo $slot->pltitle.'<br />';
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
			
		<?php else : ?>
			<i>List of slots with date separator, start time, end time, title, decription</i>
						<?php foreach ($this->items as $key=>$dayslots) : ?>
						    <h4><?php echo $key; ?></h4>
							<?php foreach ($dayslots as $slot) {						    
						        echo '<div class="schbox">';
						        echo $slot->az_starttime.'<br >';
						        echo $slot->pltitle.'<br />';
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
						'title'  => Text::_('Set Station for Schedule'),
						'footer' => $this->loadTemplate('batch_footer'),
					    'modalWidth' => '50',
					),
					$this->loadTemplate('batch_body')
				); ?>
			<?php endif; ?>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
    </form>
    <div class="clearfix"></div>
    <?php echo XbcommonHelper::credit('xbMusic');?>
</div>
