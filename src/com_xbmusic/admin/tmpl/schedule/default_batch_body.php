<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/schedule/default_batch_body.php
 * @version 0.0.51.4 19th April 2025s
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

?>

<div class="container-fluid">
	<div class="row">
		<div class="col-6-lg">
			<div class="controls">
				<p>&nbsp;</p>
				<?php // echo LayoutHelper::render('stations', array()); ?>
				<?php echo $this->filterForm->renderField('dbstid', 'filter'); ?>
			</div>
		</div>
	</div>
</div>
