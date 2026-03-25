<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Service/Router.php
 * @version 0.0.60.0 24th March 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterBase;

class Router extends RouterBase
{
   
    public function build(&$query)
    {
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
    
    public function parse(&$segments)
    {
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

    
}