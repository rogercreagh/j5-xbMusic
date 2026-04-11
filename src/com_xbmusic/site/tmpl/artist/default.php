<?php 
 /*******
 * @package xbMusic
 * @filesource site/tmpl/Artist/default.php
 * @version 0.0.61.2 2nd April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Site\Helper\RouteHelper as XbmusicHelperRoute;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

$item = $this->item;

?>
<style type="text/css" media="screen">
	.xbpvmodal .modal-content {padding:15px;max-height:calc(100vh - 190px); overflow:scroll; }
</style>
<div class="xbcomponent">
<h3><i class='fas fa-user' ></i> <?php echo $this->item->name; ?></h3>

<ul>
	<li>if description</li>
	<li>if sortname</li>
	<li>if img</li>
	<li>cat</li>
	<li>tags</li>
	<li>songs & tracks & play</li>
</ul>
</div>
