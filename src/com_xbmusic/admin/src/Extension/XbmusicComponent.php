<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/Extension/XbmusicComponent.php
 * @version 0.0.19.4 7th January 2025
 * @since 0.0.0.1 31st March 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;

require_once('defines.php');

class XbmusicComponent extends MVCComponent implements
BootableExtensionInterface, RouterServiceInterface
{
    use RouterServiceTrait;
    use HTMLRegistryAwareTrait;
    
    public function boot(ContainerInterface $container)
    {
        $params = ComponentHelper::getParams('com_xbmusic');
        $doc = Factory::getApplication()->getDocument();
        $wa = $doc->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_xbmusic');
        $wa->useStyle('xbmusic.styles');
        $wa->useStyle('xbcommon.styles');
        // alternative method to load file
//       $wa->registerAndUseStyle('xbmusicCore', 'com_xbmusic/xbmusic.css');
// oldschool method to load file - deprecated
//         $cssPath = Uri::root(true)."/media/com_xbmusic/css/";
//         $doc->addStyleSheet($cssPath.'xbmusic.css');

        Factory::getApplication()->getLanguage()->load('xbcommon', JPATH_ADMINISTRATOR.'/components/com_xbmusic');
        
    }
    
}