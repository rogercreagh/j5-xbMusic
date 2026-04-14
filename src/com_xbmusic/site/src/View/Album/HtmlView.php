<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/View/Album/HtmlView.php
 * @version 0.0.61.6 13th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\View\Album;

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
        
        if ($this->item->ext_links) $this->item->ext_links = json_decode($this->item->ext_links);
        $this->item->ext_links_cnt = 0;
        if(is_object($this->item->ext_links)) {
            $this->item->ext_links_cnt = count((array)$this->item->ext_links);
        }
        
        return parent::display($tpl);
    }
}
