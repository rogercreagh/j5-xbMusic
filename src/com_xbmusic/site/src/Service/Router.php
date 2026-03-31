<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Service/Router.php
 * @version 0.0.61.0 31st March 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterBase;
// use Joomla\CMS\Application\SiteApplication;
// use Joomla\CMS\Categories\CategoryFactoryInterface;
// use Joomla\CMS\Component\ComponentHelper;
// use Joomla\CMS\Component\Router\RouterView;
// use Joomla\CMS\Component\Router\RouterViewConfiguration;
// use Joomla\CMS\Component\Router\Rules\MenuRules;
// //use Joomla\CMS\Component\Router\Rules\NomenuRules;
// //use J4xdemos\Component\Mywalks\Site\Service\MywalksNomenuRules as NomenuRules;
// use Crosborne\Component\Xbmusic\Site\Service\XbmusicNomenuRules as NomenuRules;
// use Joomla\CMS\Component\Router\Rules\StandardRules;
// use Joomla\CMS\Menu\AbstractMenu;
// use Joomla\Database\DatabaseInterface;

class Router extends RouterBase
{
    public function build(&$query) {
        $segments = [];
        
        if (isset($query['task'])) {
            $segments[] = $query['task'];
            unset($query['task']);
        }
        
        if (isset($query['id'])) {
            $segments[] = $query['id'];
            unset($query['id']);
        }
        
        foreach ($segments as &$segment) {
            $segment = str_replace(':', '-', $segment);
        }
        
        return $segments;
        
    }
    
    public function parse(&$segments) {
 
        $vars  = [];
        
        foreach ($segments as &$segment) {
            $segment = preg_replace('/-/', ':', $segment, 1);
        }
        unset($segment);
        
        // View is always the first element of the array
        $count = \count($segments);
        
        if ($count) {
            $count--;
            $segment = array_shift($segments);
            
            if (is_numeric($segment)) {
                $vars['id'] = $segment;
            } else {
                $vars['task'] = $segment;
            }
        }
        
        if ($count) {
            $segment = array_shift($segments);
            
            if (is_numeric($segment)) {
                $vars['id'] = $segment;
            }
        }
        
        return $vars;
        
    }
    
//     protected $noIDs = false;
//     private $categoryFactory;
//     private $db;
    
//     public function __construct(SiteApplication $app, AbstractMenu $menu,
//         CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
//     {
//         $this->categoryFactory = $categoryFactory;
//         $this->db              = $db;
        
//         $params = ComponentHelper::getParams('com_xbmusic');
//         $this->noIDs = (bool) $params->get('sef_ids');
        
//         $artists = new RouterViewConfiguration('artists');
//         $artists->setKey('id');
//         $this->registerView($artists);
        
//         $artist = new RouterViewConfiguration('artist');
//         $artist->setKey('id');
//         $this->registerView($artist);
        
//         parent::__construct($app, $menu);
        
//         $this->attachRule(new MenuRules($this));
//         $this->attachRule(new StandardRules($this));
//         $this->attachRule(new NomenuRules($this));
//     }
    
//     public function build(&$query)
//     {
//         $segments = [];
        
//         if (isset($query['task'])) {
//             $segments[] = $query['task'];
//             unset($query['task']);
//         }
        
//         if (isset($query['id'])) {
//             $segments[] = $query['id'];
//             unset($query['id']);
//         }
        
//         foreach ($segments as &$segment) {
//             $segment = str_replace(':', '-', $segment);
//         }
        
//         return $segments;
//     }
    
//     public function parse(&$segments)
//     {
//         $vars  = [];
        
//         foreach ($segments as &$segment) {
//             $segment = preg_replace('/-/', ':', $segment, 1);
//         }
//         unset($segment);
        
//         // View is always the first element of the array
//         $count = \count($segments);
        
//         if ($count) {
//             $count--;
//             $segment = array_shift($segments);
            
//             if (is_numeric($segment)) {
//                 $vars['id'] = $segment;
//             } else {
//                 $vars['task'] = $segment;
//             }
//         }
        
//         if ($count) {
//             $segment = array_shift($segments);
            
//             if (is_numeric($segment)) {
//                 $vars['id'] = $segment;
//             }
//         }
        
//         return $vars;
        
//     }

    
}