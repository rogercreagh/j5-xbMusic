<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Helper/RouteHelper.php
 * @version 0.0.61.0 31st March 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\Helper;

defined('_JEXEC') or die;

//use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Language\Multilanguage;

abstract class RouteHelper
{
    public static function getArtistRoute($id, $slug, $language = 0, $layout = null)
    {
        // Create the link
        $link = 'index.php?option=com_myxbmusic&view=artist&id=' . $id . '&slug=' . $slug;
        
        if ($language && $language !== '*' && Multilanguage::isEnabled())
        {
            $link .= '&lang=' . $language;
        }
        
        if ($layout)
        {
            $link .= '&layout=' . $layout;
        }
        
        return $link;
    }
    
    public static function getArtistsRoute($language = 0, $layout = null)
    {
        
        $link = 'index.php?option=com_xbmusic&view=artists';
        
        if ($language && $language !== '*' && Multilanguage::isEnabled())
        {
            $link .= '&lang=' . $language;
        }
        
        if ($layout)
        {
            $link .= '&layout=' . $layout;
        }
        
        return $link;
    }
}

