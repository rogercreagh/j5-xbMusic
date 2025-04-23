<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/schedule/default_batch_footer.php
 * @version 0.0.51.4 19th April 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<button type="button" class="btn" onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-tag-id').value=''" data-bs-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('schedule.setStaion');">
	<i class="fas fa-radio"></i> &nbsp;
	<?php echo Text::_('XBMUSIC_SET_STATION'); ?>
</button>
