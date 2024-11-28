<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/tracks/default_batch_body.php
 * @version 0.0.19.2 27th November 2023
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$catfilt = $this->state->get('filter.category_id','');
//$published = $this->state->get('filter.published');
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-lg-6">
		<?php if ($catfilt == '') : ?>
			<div class="control-group" style="margin-top:20px;">
				<div class="controls">
                    <?php echo LayoutHelper::render('joomla.html.batch.item', ['extension' => 'com_xbmusic']); ?>
				</div>
			</div>
        <?php else: ?>
        	<div  style="margin-top:20px;"><p><?php echo Text::_('XB_CHANGE_CAT_FILTER') ?></p></div>
		<?php endif; ?>
		</div>
		<div class="col-lg-6">
    		<div class="control-group span6">
    			<div class="controls">
                    <?php echo LayoutHelper::render('joomla.html.batch.tag', array()); ?>
    			</div>
    		</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-6">
			<div class="controls">
				<?php echo LayoutHelper::render('playlists', array()); ?>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="controls">
				<?php echo LayoutHelper::render('untag', array()); ?>
			</div>
		</div>
	</div>
</div>
