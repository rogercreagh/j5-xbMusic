<?php
/*******
 * @package xbMusic
 * @filesource admin/layouts/playlist.php
 * @version 0.0.19.2 26th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
//use Joomla\CMS\HTML\HTMLHelper;

$options = array('value'=>'','text'=>'No change');
$db = Factory::getDbo();
$query  = $db->getQuery(true);
$query->select('id AS value, title AS text')->from('#__xbmusic_playlists');
$query->order('title ASC');
$db->setQuery($query);
$result = $db->loadObjectList();
if (!is_null($result)) {
    if (count($result) > 5) {
        //now get most recent 3 for top of list
        $query->clear('order');
        $query->order('modified DESC')->setLimit('3');
        $db->setQuery($query);
        $recent = $db->loadObjectList();
        //add a separator between recent and alpha
        $blank = new stdClass();
        $blank->value = 0;
        $blank->text = '------------';
        $recent[] = $blank;
        $options = array_merge($options, $recent, $result);
    } else {
        $options = array_merge($options, $result);
    }
}


?>
<fieldset>

<label id="batch-playlist-lbl" for="batch-playlist" >
	<?php echo Text::_('Add to Playlist'); ?>	
</label>
<select name="batch[playlist]" class= "form-select" id="batch-playlist">
	<option value=""><?php echo Text::_('JLIB_HTML_BATCH_TAG_NOCHANGE'); ?></option>
	<?php //echo HTMLHelper::_('select.options', HTMLHelper::_('tag.tags', array('filter.published' => array(1))), 'value', 'text'); ?>
	<?php foreach ($options as $option) {
	    echo '<option value="'.$option->value.'">'.$option->text.'</option>';
	} ?>
</select>
</fieldset>
