<?php
/*******
 * @package xbMusic
 * @filesource admin/layouts/xbmusic/batch/childtags.php
 * @version 0.1.0.0 10th October 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
//use Crosborne\Component\Xbmusic\Administrator\Helper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

?>
<fieldset>
    <?php $parent = $displayData['parent']; ?>
    <label id="batch-<?php echo $parent;?>child-lbl" for="batch-<?php echo $parent;?>child" >
    	<?php echo Text::_('XB_TAG_GROUP').' : '.$parent; ?>	
    </label>
    <select name="batch[<?php echo $parent;?>child]" class= "form-select" id="batch-<?php echo $parent;?>child">
    	<option value=""><?php echo Text::_('JLIB_HTML_BATCH_TAG_NOCHANGE'); ?></option>
    	 <?php 
    	 $options = XbcommonHelper::getTagChildOpts($parent,0);
        	 echo $options;
    	 ?>   	
    </select>
</fieldset>
