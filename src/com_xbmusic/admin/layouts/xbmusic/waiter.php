<?php
/*******
 * @package xbMusic
 * @filesource admin/layouts/xbmusic/waiter.php
 * @version 0.0.59.17 21st December 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @desc Layout for showEl div. Insert where required on page (usuall top) and use javascript showEl('azwaiter') to reveal it. Hidden by default.
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
//use MongoDB\BSON\Javascript;

/**
 * Layout variables
 * -----------------
 * @var   string  $message  The message to display
 * 
 * message can either be set in the displayData or it can be changed by Javascript
 * document.getElementById('waitmessage').innerHTML = newmessage; 
 */
if (!is_array($displayData)) {
    $message = "Please wait, I'm terribly busy you know";
} elseif (key_exists('message',$displayData)) {
    $message = $displayData['message'];
} else {
    $message = "";
}
?>
    	<div id="azwaiter" class="xbbox alert-info" style="display:none;">
          <table style="width:100%">
              <tr>
              	  <?php $waitpic = Uri::root().'/media/com_xbmusic/images/waiting.gif'; ?>
                  <td style="width:200px;"><img src="<?php echo $waitpic; ?>" style="height:100px" /> </td>
                  <td style="vertical-align:middle;"><b><span id="waitmessage"><?php echo $message; ?></span></b> </td>
              </tr>
          </table>
    	</div>
