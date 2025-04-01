<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/playlists/default_batch_footer.php
 * @version 0.0.50.0 27th March 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2019
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<button type="button" class="btn" onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-tag-id').value=''" data-bs-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('playlist.batch');">
	<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
