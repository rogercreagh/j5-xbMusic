<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Service/XbmusicNomenuRules.php
 * @version 0.0.61.0 31st March 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\RulesInterface;

class XbmusicNomenuRules implements RulesInterface
{

    protected $router;
    

    public function __construct(RouterView $router)
    {
        $this->router = $router;
    }
    

    public function preprocess(&$query)
    {
        $test = 'Test';
    }
    

    public function parse(&$segments, &$vars)
    {
        //with this url: http://localhost/j4x/my-walks/mywalk-n/walk-title.html
        // segments: [[0] => mywalk-n, [1] => walk-title]
        // vars: [[option] => com_mywalks, [view] => mywalks, [id] => 0]
        
        $vars['view'] = 'artist';
        $vars['id'] = substr($segments[0], strpos($segments[0], '-') + 1);
        array_shift($segments);
        array_shift($segments);
        return;
    }
    
    public function build(&$query, &$segments)
    {
        // content of $query ($segments is empty or [[0] => mywalk-3])
        // when called by the menu: [[option] => com_mywalks, [Itemid] => 126]
        // when called by the component: [[option] => com_mywalks, [view] => mywalk, [id] => 1, [Itemid] => 126]
        // when called from a module: [[option] => com_mywalks, [view] => mywalks, [format] => html, [Itemid] => 126]
        // when called from breadcrumbs: [[option] => com_mywalks, [view] => mywalks, [Itemid] => 126]
        
        // the url should look like this: /site-root/mywalks/walk-n/walk-title.html
        
        // if the view is not mywalk - the single walk view
        if (!isset($query['view']) || (isset($query['view']) && $query['view'] !== 'artist') || isset($query['format']))
        {
            return;
        }
        $segments[] = $query['view'] . '-' . $query['id'];
        // the last part of the url may be missing
        if (isset($query['slug'])) {
            $segments[] = $query['slug'];
            unset($query['slug']);
        }
        unset($query['view']);
        unset($query['id']);
    }
}

