<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/View/Artist/HtmlView.php
 * @version 0.0.60.2 26th March 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\View\Artist;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $state;
    protected $item;
    
    public function display($tpl = null)
    {
        $this->state      = $this->get('State');
        $this->item       = $this->get('Item');
        
        if (count($errors = $this->get('Errors')))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
        
        return parent::display($tpl);
    }
}
