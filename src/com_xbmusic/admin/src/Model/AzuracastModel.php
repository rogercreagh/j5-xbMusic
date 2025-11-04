<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/AzuracastModel.php
 * @version 0.0.59.2 31st October 2025
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;
use \SimpleXMLElement;
use Joomla\CMS\Filesystem\Folder;

class AzuracastModel extends AdminModel {

     public function getForm($data = array(), $loadData = true) {
        $form = $this->loadForm('com_xbmusic.azuracast', 'azuracast',
            array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }
    
    protected function loadFormData() {
       
        $app  = Factory::getApplication();
        $data = $app->input->get('jform', array(), 'ARRAY');
        if (!isset($data['apilist'])) $data['apilist'] = 0;
        if ($data['apilist']  >0) {
            $keydeets = XbmusicHelper::getSelectedApiKey();
            $data['apilist'] = ($keydeets) ? $keydeets->id : 0;
        }
        return $data;
        
//        if ((!isset($data['apilist'])) || ($data['apilist'] > 0)) {
//            $keydeets = XbmusicHelper::getSelectedApiKey();
//            $data['apilist'] = ($keydeets) ? $keydeets->id : 0;
//        }
//        return $data;
    }
    
/* Azuracast api function calls. If code set report message else return object  */

    /***
     * @name getAzMe()
     * @desc fetches the azuracast user object for the current apikey
     * @param $api object
     * @return false | azuser object
     */
    public function getAzMe($api=null) {
        if (!isset($api)) $api = new AzApi();
        $result = $api->azme();
        if (isset($result->code)) {
            Factory::getApplication()->enqueueMessage('API error getting User details. <br />'
                .$result->code.' '.$result->formatted_message,'Error');
            return false;
        }
        return $result;
    }
    
    public function getAzStations($api = null) {
        if (!isset($api)) $api = new AzApi();
        $result = $api->azStations();
        if (isset($result->code)) {
            Factory::getApplication()->enqueueMessage('API error getting User details. <br />'
                .$result->code.' '.$result->formatted_message,'Error');
            return false;
        }
        return $result;
    }
    
    /**
     * @name importAzStation()
     * @desc creates or updates a single station with details from Azuracast
     * @desc using the curent config credentials
     * @param int $azstid
     * @return int|object - id of new/updated dbstation is ok or error object if fails
     */
    public function importAzStation(int $azstid, $api = null) {
        //        $params = ComponentHelper::getParams('com_xbmusic');
        //        $azurl = $params->get('az_url');
        if (!isset($api)) $api = new AzApi();
        $azstation = $api->azStation($azstid);
        if (isset($azstation->code))
            return $azstation;
        //TODO error check
        //now test if dbstastion exists ? update ELSE create
        $dbstid = $this->getDbStid(trim($azstation->url,'/'), $azstation->id);
        if ($dbstid > 0) {
            $res = $this->updateDbStation($dbstid,$azstation);
        } else {
            $res = $this->createDbStation($azstation);
        }
        return $res;
    }
    
    /**
     * @name getDbStid()
     * @desc returns the id of a station in the db which has the given azurl and azid
     * @param string $azurl
     * @param int $azstid
     * @return int|NULL null if the query fails
     */
    public function getDbStid(string $azurl, int $azstid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__xbmusic_azstations');
        $query->where($db->qn('az_url').' = '.$db->quote($azurl).' AND '.$db->qn('az_stid').' = '.$db->q($azstid));
        $db->setQuery($query);
        return $db->loadResult();
    }

    /**
     * @name createDbStation()
     * @desc given details of an azuracast station creates a matching station in database
     * @param object $station
     * returns boolean|Exception true if ok
     */
    public function createDbStation($azstation) {
        $uncatid = XbcommonHelper::getCatByAlias('uncategorised')->id; //TOD create stations catergory
        //        XbcommonHelper::getCreateCat($catdata)
        $colarr = array('id', 'az_stid', 'title', 'alias',
            'az_url','az_stream','website', 'az_player',
            'catid', 'description', 'az_info',
            'created','created_by', 'created_by_alias'
        );
        $db = $this->getDatabase();
        $values=array($db->q(0),$db->q($azstation->id),$db->q($azstation->name),$db->q($azstation->shortcode),
            $db->q($azstation->listen_url), $db->q($azstation->url),$db->q($azstation->public_player_url),
            $db->q($uncatid),$db->q($azstation->description),$db->q(json_encode($azstation)),
            $db->q(Factory::getDate()->toSql()),$db->q($this->getCurrentUser()->id), $db->q('import from Azuracast')
        );
        //$db = Factory::getContainer()->get(DatabaseInterface::cl
        $query = $db->getQuery(true);
        $query->insert($db->qn('#__xbmusic_azstations'));
        $query->columns(implode(',',$db->qn($colarr)));
        $query->values(implode(',',$values));
        try {
            $db->setQuery($query);
            $res = $db->execute();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return $e;
        }
        if ($res == true) {
            $newid = $db->insertid();
            Factory::getApplication()->enqueueMessage(Text::sprintf('XBMUSIC_NEW_STATION_CREATED',$newid,$azstation->name));
        }
        return ($res) ? $newid : false;
    }
    
    public function deleteDbStation(int $stid) {
        //needs dbstid to delete
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->delete($db->qn('#__xbmusic_azstations'));
        $query->where($db->qn('id').' = '.$db->q($stid));
        try {
            $db->setQuery($query);
            $res = $db->execute();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return $e;
        }
        return $res;
        //needs to also delete stid from playlists, tracks
    }
    
    
}