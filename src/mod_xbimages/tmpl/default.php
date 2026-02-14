<?php
/*******
 * @package xbMusic
 * @filesource mod_xbimages/tmpl/default.php
 * @version 0.0.2.0 13th February 2026
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

$document = $this->app->getDocument();
$wa = $document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('mod_xbimages');
$wa->useScript('mod_xbimages.new-cover');

// Pass the options down to js
$document->addScriptOptions('mod_xbimages.vars', ['covers' => $covers,'delay' => $img_delay, 'albuminfo' => $albuminfo]);

?>

<img id="coverimg" src="/media/mod_xbimages/images/WreckersCircleLogo-500x500.png" />
<?php switch ($albuminfo) {
    case 1:
        echo '<span id="albumtitle"></span>';
        break;
    case 2:
        echo '<span id="albumartist"></span>';
        break;
    case 3:
        echo '<span id="albumtitle"></span><br />';
        echo '<span id="albumartist"></span>';        
        break;        
    default:
        
    break;
}
?>
