<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Model/ArtistsModel.php
 * @version 0.0.63.2 25th April 2026
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

class AlbumsModel extends ListModel
{
 
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'title', 'a.title',
                'albumartist', 'a.albumartist',
                'sortartist', 'a.sortartist',
                'category_title',
                'rel_date', 'a.rel_date',
                'tagfilt','rand'
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
    protected function populateState($ordering = 'rand', $direction = 'ASC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);


//         $filt = $this->getUserStateFromRequest($this->context . '.filter.tagfilt', 'filter_filter_tagfilt', '');
//         $this->setState('filter.tagfilt', $filt);
        
//         $filt = $this->getUserStateFromRequest($this->context . '.filter.taglogic', 'filter_filter_taglogic', '');
//         $this->setState('filter.taglogic', $filt);
        
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
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'DISTINCT a.id AS albumid, a.title AS albumtitle, a.sortartist, a.albumartist, a.imgurl, '
                .'a.rel_date, a.num_discs, a.tot_tracks, a.catid, '
                .'a.status, a.access' 
                )
            );
        $query->select('(SELECT COUNT(DISTINCT(tk.id)) FROM #__xbmusic_tracks AS tk WHERE tk.album_id = a.id AND tk.status = 1) AS trkcnt');
        $query->from($db->qn('#__xbmusic_albums') . ' AS a');
        
        // Join over the categories.
        $query->select('c.title AS category_title, c.path AS category_path')
            ->join('LEFT', '#__categories AS c ON c.id = a.catid');
            
        // Filter by category
        $categoryId = $this->getState('filter.category_id', '');
        if ($categoryId) $query->where($db->qn('a.catid').' = '.$db->q($categoryId));
        
        // Filter by search in title.
        $search = $this->getState('filter.search');
        if (!empty($search))
        {
            $sin = $db->qn('a.title');
            $search = strtolower( trim($search));
            if (strpos($search,'a:') === 0) {
                $search = substr($search,2);
                $sin = $db->qn('a.albumartist');
            }
            
            $search = '%' . str_replace(' ', '%', $db->escape($search, true) . '%');
            $query->where($sin. ' LIKE :search')
                ->bind(':search', $search, ParameterType::STRING);
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
                    WHERE tmap.type_alias = '.$db->quote('com_xbmusic.album').'
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
        $query->where($db->qn('a.status').' = 1');
        
        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'rand');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        
        if ($orderCol === 'rand') {
            $query->order('rand()');
        } else {
            if ($orderCol === 'title') {
                
                $ordering = [
                    $db->quoteName('a.title') . ' ' . $db->escape($orderDirn),
                ];
            } else {
                $ordering = $db->escape($orderCol);
            }
            if ($orderDirn != '') {
                $ordering .= ' ' . $db->escape($orderDirn);
            }
            
            $query->order($ordering);
        }
        return $query;
    }
    
    public function getItems() {
        $items  = parent::getItems();
        if ($items) {
            $tagsHelper = new TagsHelper;
            foreach ($items as $item) {                
                $item->tags = $tagsHelper->getItemTags('com_xbmusic.album' , $item->albumid);               
            } //end foreach
        } //endif items
        return $items;
        
    } // end getItems
    
}
