<?php
/*******
 * @package xbMusic
 * @filesource admin/layouts/indgrp.php
 * @version 0.0.30.2 7th February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @desc allows set individual/group for artists
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

?>
<fieldset>

<label id="batch-indgrp-lbl" for="batch-indgrp" >
	<?php echo Text::_('XBMUSIC_SET_INDIVIDUAL_GROUP'); ?>	
</label>
<select name="batch[indgrp]" class= "form-select" id="batch-indgrp">
	<option value=""><?php echo Text::_('XBMUSIC_NO_CHANGE'); ?></option>
	<option value="1"><?php echo Text::_('XB_PERSON'); ?></option>
	<option value="2"><?php echo Text::_('XBMUSIC_DUO'); ?></option>
	<option value="3"><?php echo Text::_('XBMUSIC_TRIO'); ?></option>
	<option value="4"><?php echo Text::_('XB_GROUP'); ?></option>
</select>
</fieldset>
