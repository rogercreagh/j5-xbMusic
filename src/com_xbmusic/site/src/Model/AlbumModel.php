<?php 
 /*******
 * @package xbMusic
 * @filesource site/src/Model/AlbumModel.php
 * @version 0.0.61.6 13th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

//use Joomla\Utilities\ArrayHelper;
//use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class AlbumModel extends ItemModel {

    protected $_context = 'com_xbmusic.album';
    
    protected function populateState()
    {
        $app = Factory::getApplication();
        
        // Load state from the request.
        $pk = $app->input->getInt('id');
        $this->setState('album.id', $pk);
        
        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);
    }
    
    public function getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int) $this->getState('album.id');
        
        try
        {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
            ->select(
                $this->getState(
                    'item.select', 'a.*'
                    )
                );
            $query->from($db->quoteName('#__xbmusic_albums') . ' AS a')
            ->where($db->quoteName('a.id') . ' = :id')
            ->bind(':id', $pk, ParameterType::INTEGER);
            $query->select('c.title AS category_title, c.path AS category_path');
            $query->join('LEFT', '#__categories AS c ON c.id = a.catid');
            
            $db->setQuery($query);
            
            $data = $db->loadObject();
            
            if (empty($data))
            {
                throw new \Exception(Text::_('XBMUSIC_ERROR_ALBUM_NOT_FOUND').' : '.$pk, 404);
            }
        }
        catch (\Exception $e)
        {
            if ($e->getCode() == 404)
            {
                // Need to go through the error handler to allow Redirect to work.
                throw new \Exception($e->getMessage(), 404);
            }
            else
            {
                $this->setError($e);
                $this->_item[$pk] = false;
            }
        }
        
        $tagsHelper = new TagsHelper;
        $data->tags = $tagsHelper->getItemTags('com_xbmusic.album' , $data->id);
        
        //get tracklist with playlists
        $data->tracks = XbmusicHelper::getAlbumTracks($data->id, true);
        
        return $data;
    }
   
}
