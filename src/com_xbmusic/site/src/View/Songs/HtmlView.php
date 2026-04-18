<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/View/Sons/HtmlView.php
 * @version 0.0.62.0 17th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\View\Songs;

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
        
        $this->indexfield = $this->filterForm->getGroup('filter')['filter_idx'];
        
        $app = Factory::getApplication();
        $comparams = ComponentHelper::getParams('com_xbmusic');
        $activeMenuItem = $app->getMenu()->getActive();
        if ($activeMenuItem->link == 'index.php?option=com_xbmusic&view=songs') {
            $mymenu = $activeMenuItem;
        } else {
            $mymenu = $app->getMenu()->getItems('link','index.php?option=com_xbmusic&view=songs');
            if (!empty($mymenu)) $mymenu = $mymenu[0];
        }
        if (empty($mymenu)) { //we are unable to find a relevant menu item
            $this->showimg = $comparams->get('showimg');
            $this->showlinks = $comparams->get('showlinks');
            $this->showcat = $comparams->get('showcat');
            $this->showtags = $comparams->get('showtags');
            $this->showplay = $comparams->get('showplay');
            $this->playtime = $comparams->get('playtime');
        } else {
            $menuparams = $mymenu->getParams();
            $this->showimg = $menuparams->get('showimg');
            if ($this->showimg == '') $this->showimg = $comparams->get('showimg');
            $this->showlinks = $menuparams->get('showlinks');
            if ($this->showlinks == '') $this->showlinks = $comparams->get('showlinks');
            $this->showcat = $menuparams->get('showcat');
            if ($this->showcat == '') $this->showcat = $comparams->get('showcat');
            $this->showtags = $menuparams->get('showtags');
            if ($this->showtags == '') $this->showtags = $comparams->get('showtags');
            $this->showplay = $menuparams->get('showplay');
            $this->playtime = $menuparams->get('playtime');
            if ($this->showplay == '') {
                $this->showplay = $comparams->get('showplay');
                $this->playtime = $comparams->get('playtime');
            }           
        }
        
        parent::display($tpl);
    }
}
