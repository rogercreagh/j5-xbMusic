<?php
/*******
 * @package xbMusic
 * @filesource mod_xbimages/services/provider.php
 * @version 0.0.2.0 13th February 2026
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Module\Xbimages\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\Dispatcher as JoomlaDispatcher;
//use Joomla\CMS\Dispatcher\DispatcherInterface;
//use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
//use Crosborne\Module\Xbimages\Site\Helper\XbimagesHelper;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

//class Dispatcher implements DispatcherInterface
class Dispatcher extends JoomlaDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;
    protected $module;
    
    protected $app;
    
    public function __construct(\stdClass $module, CMSApplicationInterface $app, Input $input)
    {
        $this->module = $module;
        $this->app = $app;
    }
    
    public function dispatch()
    {
        
        $params = new Registry($this->module->params);
        $img_delay = $params->get('img_delay', 7 );
        $albuminfo = 0;
        $img_source = $params->get('img_source', 0 );
        if ($img_source == 0) {
            $img_folder = $params->get('img_folder', '' );
            $img_exts = $params->get('img_exts', 'jpg' );
            $img_exts = explode( ',', $img_exts);
            $covers = XbimagesHelper::getFilesByExtension(JPATH_ROOT.'/images/'.$img_folder, $img_exts);
        } elseif ($img_source == 1) {
            //check if xbmusic installed
            $albuminfo = $params->get('albuminfo', 0 );
            $albumtags = $params->get('albumtags', 0 );
            $covers = XbimagesHelper::getFilesFromXbmusic($albuminfo, $abumtags);
        }
        
        
        require ModuleHelper::getLayoutPath('mod_xbimages');
   }
}