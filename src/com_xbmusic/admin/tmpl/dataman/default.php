<?php 
/*******
 * @package xbMusic
 * @filesource admin/tmpl/dataman/default.php
 * @version 0.0.3.0 3rd April 2024
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
	<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=dataman'); ?>" method="post" name="adminForm" id="adminForm">
<h3>xbMusic Data Manager</h3>
<p>Functionality expected here:</p>
<ol>
<li>Import from csv</li>
<li>Show orphan artists & songs without track</li>
<li>Show orphan tracks without playlist, album</li>
<li>id3 import by file or folder
	<ul>
		<li>for each file read id3 and get artist album track and song
		 - look for existing
	</ul>
</li>

<li></li>
</ol>
	</form>
    <p>&nbsp;</p>
    <?php echo XbmusicHelper::credit('xbMusic');?>
</div>