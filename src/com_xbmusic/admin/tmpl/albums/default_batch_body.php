<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/albums/default_batch_body.php
 * @version 0.1.0.0 10th October 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

?>
<div class="container-fluid">
	<div class="xbbatchgrid">
		<div class="xbbatchgrid-item">
            <?php echo LayoutHelper::render('joomla.html.batch.item', ['extension' => 'com_xbmusic']); ?>
 		</div>
		<div class="xbbatchgrid-item">
			<?php //get group tags
                $groups = XbcommonHelper::getTagGroupsAliases('album');
                foreach ($groups as $grpname) : ?>
                	<div class="control-group">
    					<div class="controls">
    						<?php echo LayoutHelper::render('xbmusic.batch.childtags', array('parent'=>$grpname)); ?>
    					</div>
					</div>
			<?php endforeach; ?>
		</div>
	</div>
  
	<div class="xbbatchgrid">
		<div class="xbbatchgrid-item">
			<div class="control-group">
				<div class="controls">
					<?php echo LayoutHelper::render('xbmusic.batch.untag', array()); ?>
 	           </div>
			</div>
		</div>
		<div class="xbbatchgrid-item">
			<div class="control-group">
				<div class="controls">
                  <?php echo LayoutHelper::render('joomla.html.batch.tag', array()); ?>
				</div>
			</div>
		</div>
	</div>
</div>
