<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Model/SongsModel.php
 * @version 0.0.60.2 4th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\ParameterType;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class SongsModel extends ListModel
{
 
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 's.id',
                'title', 's.title',
                'category_title',
                'idxtitle', 
         
            );
        }
        
        parent::__construct($config);
    }
    
    /**
     * Method to auto-populate the model state.
     *
     * This method should only be called once per instantiation and is designed
     * to be called on the first call to the getState() method unless the model
     * configuration flag to ignore the request is set.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   3.0.1
     */
    protected function populateState($ordering = 'idxtitle', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        $idx = $this->getUserStateFromRequest($this->context . '.filter.idx', 'filter_idx');
        $this->setState('filter.idx', $idx);
        
        // List state information.
        parent::populateState($ordering, $direction);
    }
    
    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   1.6
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        
        return parent::getStoreId($id);
    }
    
    protected function getListQuery()
    {
        
        $app = Factory::getApplication();
        $db = Factory::getDbo();
        // Create a new query object.
        //$db    = $this->getDatabase();
        $query = $db->getQuery(true);
        
        // Select the required fields from the table.
        $pattern = '^[(].*[)] *';
        $query->select('s.id, s.title AS title, s.ext_links,s.catid AS catid, REGEXP_REPLACE(LCASE(s.title), "'.$pattern.'", "") AS idxtitle');
        $query->from($db->qn('#__xbmusic_songs') . ' AS s');
        
        // Join over the categories.
        $query->select('c.title AS category_title, c.path AS category_path');
        $query->leftJoin('#__categories AS c','c.id = s.catid');
            
        // Filter by category
        $categoryId = $this->getState('filter.category_id', '');
        if ($categoryId) $query->where($db->qn('s.catid').' = '.$db->q($categoryId));
        
        // Filter by search in title.
        $search = $this->getState('filter.search');
        if (!empty($search))
        {
            $search = '%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%');
            $query->where($db->qn('s.title'). ' LIKE :search')
                ->bind(':search', $search, ParameterType::STRING);
        }
        
        // filter by index
        $idx = $this->getState('filter.idx');
        if ($idx) {
            $idx = substr($idx,0,1);
            if (($idx == '3') || (ctype_alpha($idx))) {
                //$pattern =  '^'.$idx.'|^([(].*[)] *)'.$idx;
                $query->having("idxtitle LIKE '".$idx."%'");
            }            
        }
        

        
        //filter by tags
        $tagfilt = '';
        //is tagid in query string. If so use it and ignore tag filters. Negative tagid to exclude tag
        $tagId = (int) $app->getUserStateFromRequest('tagid', 'tagid','');
        $app->setUserState('tagid', '');
        if (!empty($tagId)) {
            $tagfilt = array(abs($tagId));
            $taglogic = $tagId>0 ? 0 : 2;
        } else {
            $tagfilt = $this->getState('filter.tagfilt');
            $taglogic = $this->getState('filter.taglogic',0);  //0=ANY 1=ALL 2= None
        }
        if (!is_array($tagfilt)) {
            $tagfilt = array($tagfilt);
        }
        if ($tagfilt[0] > 0) {
            $tagfilt = ArrayHelper::toInteger($tagfilt);
            $subquery = '(SELECT tmap.tag_id AS tlist FROM #__contentitem_tag_map AS tmap
                    WHERE tmap.type_alias = '.$db->quote('com_xbmusic.song').'
                    AND tmap.content_item_id = a.id)';
            switch ($taglogic) {
                case 1: //all tags must be matched
                    for ($i = 0; $i < count($tagfilt); $i++) {
                        $query->where($tagfilt[$i].' IN '.$subquery);
                    }
                    break;
                case 2: //none of the tags must be matched
                    for ($i = 0; $i < count($tagfilt); $i++) {
                        $query->where($tagfilt[$i].' NOT IN '.$subquery);
                    }
                    break;
                case 0: //any match will do
                    if (count($tagfilt) == 1) {
                        $query->where($tagfilt[0].' IN '.$subquery);
                    } else {
                        $tagIds = implode(',', $tagfilt);
                        if ($tagIds) {
                            $subQueryAny = '(SELECT DISTINCT content_item_id AS cid FROM #__contentitem_tag_map
                                    WHERE tag_id IN ('.$tagIds.') AND type_alias = '.$db->quote('com_xbmusic.album').')';
                            $query->innerJoin('(' . (string) $subQueryAny . ') AS tm ON tm.cid = a.id');
                        }
                    } //end else
                    break;
            } //endswitch
        }
        
        // filter only published
        $query->where($db->qn('s.status').' = 1');
        
        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        
        if ($orderCol === 'title') {
            $ordering = [
                $db->qn('s.title') . ' ' . $db->escape($orderDirn),
            ];
        } else {
            $ordering = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);
        }
        
        $query->order($ordering);
        return $query;
    }
    
    public function getItems() {
        $items  = parent::getItems();
        if ($items) {
            $tagsHelper = new TagsHelper;
            foreach ($items as $item) {
                if($item->ext_links) $item->ext_links = json_decode($item->ext_links);
                $item->ext_links_list ='';
                $item->ext_links_cnt = 0;
                if(is_object($item->ext_links)) {
                    $item->ext_links_cnt = count((array)$item->ext_links);
                    if ($item->ext_links_cnt > 0) {
                        $item->ext_links_list ='<ul>';
                        foreach($item->ext_links as $lnk) {
                            $item->ext_links_list .= '<li><a href="'.$lnk->link_url.'" target="_blank">'.$lnk->link_text.'</a></li>';
                        }
                        $item->ext_links_list = $item->ext_links_list.'</ul>';
                    }
                } //end if is_object
                $item->tracks = XbmusicHelper::getSongTracks($item->id);
                
                $item->tags = $tagsHelper->getItemTags('com_xbmusic.song' ,$item->id );
                
            } //end foreach
        } //endif items
        return $items;
        
    } // end getItems
    
}
