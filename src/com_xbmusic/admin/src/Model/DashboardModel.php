<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/DashboardModel.php
 * @version 0.0.2.1 1st April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Changelog\Changelog;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use DOMDocument;
use ReflectionClass;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use CBOR\OtherObject\TrueObject;

class DashboardModel extends ListModel {
    
    protected $arttexts;
    
    public function __construct() {

        parent::__construct();
    }
    
    /**
     * @name getClient()
     * @desc gets info about the client browser 
     * @return assoc array of client info
     */
    public function getClient() {
        $result = array();
        $client = Factory::getApplication()->client;
        $class = new ReflectionClass('Joomla\Application\Web\WebClient');
        $constants = array_flip($class->getConstants());
        
        $result['browser'] = $constants[$client->browser].' '.$client->browserVersion;
        $result['platform'] = $constants[$client->platform].($client->mobile ? ' (mobile)' : '');
        $result['mobile'] = $client->mobile;
        return $result;
    }
    
    /**
     * @name getTrackCnts and also other ItemtypeCnts
     * @desc gets count of all tracks/artists etc 
     * @return assoc array of count values
     */
    public function getTrackCnts() {
        return $this->ItemCnts('track','#__xbmusic_tracks');
    }
    public function getSongCnts() {
        return $this->ItemCnts('song','#__xbmusic_songs');
    }
    public function getPlaylistCnts() {
        return $this->ItemCnts('playlist','#__xbmusic_playlists');
    }
    public function getArtistCnts() {
        return $this->ItemCnts('artist','#__xbmusic_artists');
    }
    public function getAlbumCnts() {
        return $this->ItemCnts('album','#__xbmusic_albums');
    }  
    private function ItemCnts(string $item, string $table ) {
        $cnts = array('total'=>0, 'published'=>0, 'unpublished'=>0, 'archived'=>0, 'trashed'=>0,
            'catcnt'=>0, 'tagcnt'=>0
        );
        //get states
        $cnts = array_merge($cnts,XbmusicHelper::statusCnts($table,'status','com_xbmusic'));
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        // get catcnt       
        $query->select('COUNT(DISTINCT(catid)) AS catcnt');
        $query->from($db->qn($table));
        // both article & category must be published
        $db->setQuery($query);
        $res = $db->loadResult;
        if ($res > 0) $cnts['catcnt'] = $res;
                
        //get tagcnt
        $query->clear();
        $query->select('COUNT(DISTINCT(a.tag_id)) AS tagcnt')
        ->from('#__contentitem_tag_map AS a')
        ->where('a.type_alias = '.$db->q('com_xbmusic.'.$item));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $cnts['tagcnt'] = $res;
        
        return $cnts;
    }

    /**
     * @name getCats()
     * @return array of arrays of category titles, states
     */
    public function getCats() {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id, a.title, a.published AS status')->from('#__categories AS a')->where('a.extension = '.$db->q('com_xbmusic'));
        $query->order('title ASC');
        $db->setQuery($query);
        $cats = $db->loadAssocList('id');
        foreach ($cats as $key => $cat) {
            $query->clear();
            $query->select('COUNT(a.id) AS artcnt')->from('#__content AS a')->where('a.catid = '.$db->q($key));
            $db->setQuery($query);
            $cats[$key]['artcnt'] = $db->loadResult();
        }
        return $cats;        
    }
    
    public function getCatCnts() {
        $cnts = array('total'=>0, 'published'=>0, 'unpublished'=>0, 'archived'=>0, 'trashed'=>0);
        $cnts = array_merge($cnts,XbmusicHelper::statusCnts('#__categories','published','com_xbmusic'));        
        return $cnts;
    }
    
    public function getTagCnts() {
        $tagcnts = array('total' =>0, 'used'=>0);
        
        $tagcnts['total'] = XbmusicHelper::getItemCnt('#__tags');
        
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        $query->select('COUNT(DISTINCT(a.tag_id)) AS tagsused')
        ->from('#__contentitem_tag_map AS a')
        ->where('a.type_alias LIKE '.$db->q('com_xbmusic%'));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $tagcnts['used'] = $res;
        return $tagcnts;
    }    
    
    public function getChangelog() {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select($db->qn('changelogurl'))->from('#__extensions')->where($db->qn('name').' = '.$db->q('com_xbmusic'));
        $db->setQuery($query);
        $url = $db->loadResult();
        $xml = simplexml_load_file($url, null , LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        return $array;
    }

}
