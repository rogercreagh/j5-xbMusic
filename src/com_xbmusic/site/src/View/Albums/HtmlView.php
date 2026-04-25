<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/View/Albums/HtmlView.php
 * @version 0.0.62.1 22nd April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\View\Albums;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    protected $state;
    
    protected $items;
    
    protected $pagination;
    
    protected $params = null;
    
    public function display($tpl = null)
    {
        // Get data from the model.
        $this->state      = $this->get('State');
        $this->items      = $this->get('Items');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->pagination = $this->get('Pagination');
        
        $app = Factory::getApplication();
        $comparams = ComponentHelper::getParams('com_xbmusic');
        $activeMenuItem = $app->getMenu()->getActive();
        if ($activeMenuItem->link == 'index.php?option=com_xbmusic&view=albums') {
            $mymenu = $activeMenuItem;
        } else {
            $mymenu = $app->getMenu()->getItems('link','index.php?option=com_xbmusic&view=albums');
            if (!empty($mymenu)) $mymenu = $mymenu[0];
        }
        parent::display($tpl);
    }
}
