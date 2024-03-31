<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/DashboardModel.php
 * @version 0.0.0.1 31st March 2024
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
     * @name getArticleCnts
     * @desc gets count of all articles and states and count of articles with tags, in-content imgs, links, and shortcodes
     * @return assoc array 0f count values
     */
    public function getArticleCnts() {
        $artcnts = array('total'=>0, 'published'=>0, 'unpublished'=>0, 'archived'=>0, 'trashed'=>0,
            'catcnt'=>0, 'tagged'=>0, 'embimaged'=>0, 'emblinked'=>0, 'scoded'=>0, 'featured'=>0, 'live'=>0, 'scheduled'=>0
        );
        //get states
        $artcnts = array_merge($artcnts,XbmusicHelper::statusCnts());
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        // get featured and live
        
        $query->clear();
        $query->select('*')->from('#__content_frontpage AS a');
        $query->leftJoin('#__content as b','b.id = a.content_id');
        $query->leftJoin('j5_categories as c on c.id = b.catid');
        // both article & category must be published
        $query->where('b.state = 1 AND c.published = 1');
        $db->setQuery($query);
        $homepage = $db->loadObjectList();
        $artcnts['featured'] = count($homepage);
        // check start and end featured if set
        foreach ($homepage as $art) {
            if (is_null($art->featured_up)) {
                if (is_null($art->featured_down)) {
                    $artcnts['live'] ++;
                } elseif (time() < strtotime($art->featured_down)) {
                    $artcnts['live'] ++;
                } 
            } elseif (time() > strtotime($art->featured_up)) {
                if (is_null($art->featured_down)) {
                    $artcnts['live'] ++;
                } elseif ((time() < strtotime($art->featured_down))) {
                    $artcnts['live'] ++;
                }
            }
        }
                
        //get tagged - articles with tags
        $query->clear();
        $query->select('COUNT(DISTINCT(a.content_item_id)) AS artstagged')
        ->from('#__contentitem_tag_map AS a')
        ->where('a.type_alias = '.$db->q('com_content.article'));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $artcnts['tagged'] = $res;
        
        //get imgcnts - articles with images by type (rel/embed)
        $query->clear();
        $query->select('COUNT(DISTINCT(a.id)) AS relimged')
        ->from('#__content AS a')
        ->where('a.images REGEXP '.$db->q('image_((intro)|(fulltext))\":\"[^,]+\"'));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $artcnts['relimged'] = $res;
        
        $query->clear();
        $query->select('COUNT(DISTINCT(a.id)) AS embimaged')
        ->from('#__content AS a')
        ->where('CONCAT(a.introtext," ",a.fulltext)'.' REGEXP '.$db->q('<img '));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $artcnts['embimaged'] = $res;
        
        //get linkcnts - articles with links by type (art/embed)
        $query->clear();
        $query->select('COUNT(DISTINCT(a.id)) AS emblinked')
        ->from('#__content AS a')
        ->where('CONCAT(a.introtext," ",a.fulltext)'.' REGEXP '.$db->q('<a [^\>]*?href'));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $artcnts['emblinked'] = $res;
                
        //get scode cnts - articles with scodes
        $query->clear();
        $query->select('COUNT(DISTINCT(a.id)) AS embimged')
        ->from('#__content AS a')
        ->where('CONCAT(a.introtext," ",a.fulltext)'.' REGEXP '.$db->q('{[[:alpha:]].+?}'));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $artcnts['scoded'] = $res;
        
        return $artcnts;
    }

    /**
     * @name getCats()
     * @return array of arrays of category titles, states
     */
    public function getCats() {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id, a.title, a.published AS state')->from('#__categories AS a')->where('a.extension = '.$db->q('com_content'));
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
    
    public function getTagCnts() {
        $tagcnts = array('totaltags' =>0, 'tagsused'=>0);
        
        $tagcnts['totaltags'] = XbmusicHelper::getItemCnt('#__tags');
        
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        
        $query->select('COUNT(DISTINCT(a.tag_id)) AS tagsused')
        ->from('#__contentitem_tag_map AS a')
        ->where('a.type_alias = '.$db->q('com_content.article'));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res>0) $tagcnts['tagsused'] = $res;
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
