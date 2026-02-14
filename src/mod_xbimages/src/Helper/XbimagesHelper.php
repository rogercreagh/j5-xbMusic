<?php
/*******
 * @package xbMusic
 * @filesource mod_xbimages/services/provider.php
 * @version 0.0.2.0 13th February 2026
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Module\Xbimages\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class XbimagesHelper
{
    public static function getLoggedonUsername(string $default)
    {
        $user = Factory::getApplication()->getIdentity();
        if ($user->id !== 0)  // found a logged-on user
        {
            return $user->username;
        }
        else
        {
            return $default;
        }
    }
    
    public static function getFilesByExtension($directory, $extarr) {
        $files = [];
        if (!is_array($extarr)) $extarr = [strtolower($extarr)];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
        
        foreach ($iterator as $file) {
            $typeok = in_array(strtolower($file->getExtension()),$extarr);
            if ($file->isFile() && $typeok) {
                //we don't have title or artist so return empty fields for them
                $files[] = array(str_replace(JPATH_ROOT, '', $file->getPathname()),'t','a');
            }
        }
        
        return $files;
    }
    
    public static function getFilesFromXbmusic($albuminfo, $albumtags) {
        return [];
    }
    
}

