<?php 
 /*******
 * @package xbMusic
 * @filesource site/tmpl/albums/default.php
 * @version 0.0.63.1 22nd April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Site\Helper\RouteHelper as XbmusicHelperRoute;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['trigger'=>'hover']);

$wa = $this->document->getWebAssetManager();
$wa->useScript('joomla.dialog')
->useScript('multiselect')
->useScript('xbmusic.xbgeneral');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

switch ($listOrder) {
    case 'a.title':
        $orderstr = 'Album Title '.$listDirn;
        break;
    case 'a.sortartist':
        $orderstr = 'Album Artist '.$listDirn;
        break;
    default:
        $orderstr = 'Random Selection';
    break;
}

$albums = $this->items;

$root = Uri::root();
$document = Factory::getApplication()->getDocument();
$document->addScriptOptions('com_xbmusic.uri', array("root" => $root));

?>
<script type="module" src="<?php echo Uri::root(); ?>/media/com_xbmusic/js/xbdialog.js"></script>

<div id="xbcomponent" >

	<h3><i class='fas fa-compact-disc' ></i> <?php echo Text::_('XBMUSIC_ALBUMS'); ?></h3>
	
	<p><?php echo Text::_('XBMUSIC_ALBUM_MOSAIC'); ?></p>

 <form action="<?php echo Route::_('index.php?option=com_xbmusic&view=albums'); ?>" method="post" name="adminForm" id="adminForm">

  	<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
	<div class="pull-right pagination xbm0">
		<?php  echo $this->pagination->getPagesLinks(); ?>
	</div>        
	<div class="clearfix"></div>      
	<div class="pull-right pagination xbm0 xbpr20 xbpt5">
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
                <?php echo Xbtext::_('XB_SORTED_BY',XBSP2 + XBTRL).$orderstr ; ?>
    		</p>
		</div>
		<div class="clearfix"></div>
		
        <div class="xbgallery">
			<?php $pvtit = "'".Text::_('XBMUSIC_ALBUM_DETAILS')."'"; ?>
        	<?php foreach ($albums as $album) : ?>
        	    <div class="xbgallery-item">
        	    	<img src="<?php echo $album->imgurl; ?>" 
        	    		onclick="pvItem(<?php echo $pvtit; ?>,'album','<?php echo $album->albumid; ?>');"
        	    		title="<?php echo $album->albumtitle.' by '.$album->albumartist; ?>"
        	    	/>
        	    </div>
        	<?php endforeach; ?>
        </div>
        
		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>

  </form>
</div>