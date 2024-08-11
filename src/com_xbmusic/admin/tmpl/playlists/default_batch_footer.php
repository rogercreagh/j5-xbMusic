<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/artists/default_batch_footer.php
 * @version 0.0.9.0 21st June 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<button type="button" class="btn" onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-tag-id').value=''" data-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('artist.batch');">
	<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
