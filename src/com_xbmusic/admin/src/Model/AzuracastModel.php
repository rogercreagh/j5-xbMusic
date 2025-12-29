<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/AzuracastModel.php
 * @version 0.0.59.10 28th November 2025
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
use Crosborne\Component\Xbmusic\Administrator\Helper\XbazuracastHelper;
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
            $keydeets = XbazuracastHelper::getSelectedApiKey();
            $data['apilist'] = ($keydeets) ? $keydeets->id : 0;
        }
        return $data;
    }
    
    public function getItem($pk = null) {
        $item = new \stdClass();
        $server = $this->getServerInfo();
        $item->server = $server;
        return $item;
    }
    
/* Azuracast api function calls. If code set report message else return object  */

    /***
     * @name getAzMe()
     * @desc fetches the azuracast user object for the current apikey
     * @param $api object
     * @return false | azuser object
     */
    public function getAzMe($api=null) {
        $default = new \stdClass();
        $default->name = 'Public Functions Only';
        $default->roles = [];
        if (!isset($api)) {
            $api = new AzApi();
            if ($api->getStatus() == false)  return $default;
        }
        
        $result = $api->azme();
        if (isset($result->code)) {
            Factory::getApplication()->enqueueMessage('API error getting User details. <br />'
                .$result->code.' '.$result->formatted_message,'Error');
            return $default;
        }
        return $result;
    }
    
    /***
     * @name getAzStations()
     * @desc gets all public stations from server with additional admin and sysadmin details per api permissions
     * @param object $api
     * @return boolean|object
     */
    public function getAzStations($api = null) {
        if (!isset($api)) {
            $api = new AzApi();
            if ($api->getStatus() == false)  return false;
        }
        $result = $api->azStations();
        if (isset($result->code)) {
            Factory::getApplication()->enqueueMessage('API error getting stations list details. <br />'
                .$result->code.' '.$result->formatted_message,'Error');
            return false;
        }
        return $result;
    }
    
    /**
     * @name importAzStation()
     * @desc creates a single station with frsh details from Azuracast
     * @desc using the curent API credentials
     * @param int $azstid
     * @param object $api
     * @return int|object - id of new/updated dbstation is ok or error object if fails
     */
    public function importAzStation(int $azstid, $api = null) {
        if (!isset($api)) {
            $api = new AzApi();
            if ($api->getStatus() == false)  return false;
        }
        $azstation = $api->azStation($azstid);
        if (isset($azstation->code)) {
            Factory::getApplication()->enqueueMessage('API error getting station details. <br />'
                .$azstation->code.' '.$azstation->formatted_message,'Error');
            return false;
        }
        $dbstid = $this->getDbStid(trim($azstation->url,'/'), $azstation->id);
        if ($dbstid > 0) {
            // $res = $this->updateDbStation($dbstid,$azstation);
            Factory::getApplication()->enqueueMessage($azstation->name.' already in database with id '.$dbstid. '<br />'
                .'To reload station from server first delete it, then load. To sync with server use Edit button','Info');
            return false;
        } else {
            $res = $this->createDbStation($azstation,$api);
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
    public function createDbStation($azstation,$api) {
        $uncatid = XbcommonHelper::getCatByAlias('uncategorised')->id; //TOD create stations catergory
        $colarr = array('id', 'az_stid', 'title', 'alias',
            'az_url','az_stream','website', 'az_player',
            'catid', 'description', 'az_info',
            'created','created_by', 'created_by_alias',
            'lastsync'
        );
        $db = $this->getDatabase();
        $values=array($db->q(0),$db->q($azstation->id),$db->q($azstation->name),$db->q($azstation->shortcode),
            $db->q($azstation->azurl), $db->q($azstation->listen_url), $db->q($azstation->url),
            $db->q($azstation->public_player_url), $db->q($uncatid),
            $db->q($azstation->description), $db->q(json_encode($azstation)),
            $db->q(Factory::getDate()->toSql()),$db->q($this->getCurrentUser()->id), $db->q('import from Azuracast'),
            $db->q(Factory::getDate()->toSql())
        );
        $query = $db->getQuery(true);
        $query->insert($db->qn('#__xbmusic_azstations'));
        $query->columns(implode(',',$db->qn($colarr)));
        $query->values(implode(',',$values));
        try {
            $db->setQuery($query);
            $res = $db->execute();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return false;
        }
        if ($res == true) {
            $newid = $db->insertid();
            Factory::getApplication()->enqueueMessage(Text::sprintf('XBMUSIC_NEW_STATION_CREATED',$newid,$azstation->name));
            //now import playlists and schedules
            $playlists = $api->azPlaylists($azstation->id);
            if (isset($playlists->code)) {
                Factory::getApplication()->enqueueMessage('API error getting playlists details. <br />'
                    .$playlists->code.' '.$playlists->formatted_message,'Warning');
                return false;
            }
            //             $playlists->playlists = $playlists;
            $cnt = 0;
            foreach ($playlists as $pl) {
                $ok = $this->savePlaylist($newid, $pl);
                if ($ok) $cnt++;
            }
            Factory::getApplication()->enqueueMessage($cnt.' playlists created','Success');
        }
        return ($res) ? $newid : false;
    }
    
    public function savePlaylist($dbstid, $playlist) {
        $uncatid = XbcommonHelper::getCatByAlias('uncategorised')->id; //TOD create stations catergory
        $alias = $playlist->short_name.'-'.$playlist->station_id.'-'.$playlist->id;
        $alias = XbcommonHelper::makeUniqueAlias($alias, '#__xbmusic_azplaylists');
        $az_cntper = 0;
        $type = $playlist->type;
        switch ($type) {
            case 'default':
                $az_type = 1;
                break;
            case 'once_per_x_songs':
                $az_type = 2;
                $az_cntper = $playlist->play_per_songs;
                break;
            case 'once_per_x_minutes':
                $az_type = 3;
                $az_cntper = $playlist->play_per_minutes;
                break;
            case 'once_per_hour':
                $az_type = 4;
                $az_cntper = $playlist->play_per_hour_minute;
                break;
            case 'custom':
                $az_type = -1;
                break;
                
            default:
                $az_type = 0;
                break;
        }
        $az_jingle = ($playlist->is_jingle == 'true') ? 1 : 0;
        $status = ($playlist->is_enabled == 'true') ? 1 : 0;
        $syncdate = date("Y-m-d H:i:s");
        $colarr = array('id', 'title', 'alias',
            'slug','scheduledcnt', 'az_plid',
            'az_name', 'db_stid', 'az_info',
            'az_type','az_cntper','az_order',
            'az_jingle','az_weight','az_num_songs',
            'az_total_length','description',
            'catid', 'status','lastsync',
            'created','created_by', 'created_by_alias'
        );
        $db = $this->getDatabase();
        $values=array($db->q(0),$db->q($playlist->name),$db->q($alias),
            $db->q($playlist->short_name),$db->q(count($playlist->schedule_items)),$db->q($playlist->id),
            $db->q($playlist->name),$db->q($dbstid),$db->q(json_encode($playlist)),
            $db->q($az_type),$db->q($az_cntper),$db->q($playlist->order),
            $db->q($az_jingle),$db->q($playlist->weight),$db->q($playlist->num_songs),
            $db->q($playlist->total_length),$db->q($playlist->description),
            $db->q($uncatid),$db->q($status),$db->q($syncdate),
            $db->q($syncdate),$db->q($this->getCurrentUser()->id),$db->q('import from Azuracast API')
        );        
        $query = $db->getQuery(true);
        $query->insert($db->qn('#__xbmusic_azplaylists'));
        $query->columns(implode(',',$db->qn($colarr)));
        $query->values(implode(',',$values));
        $db->setQuery($query);
        try {
            $res = $db->execute();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return false;
        }
        if ($res) {
            $plid = $db->insertid();
            $this->createSchedules($plid, $playlist);
            Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_PLAYLIST_IMPORT_OK').' '.$plid,'Success');
            return $plid;
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_PLAYLIST_SAVE_FAIL'),'Error');
        }
        return false;
    }
    
    private function createSchedules(int $dbplid, $playlist) {
        $scheduleitems = $playlist->schedule_items;
        $status = ($playlist->is_enabled == true) ? 1 : 0;
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $res = true;
        $cnt = 0;
        $colarr = array('id', 'dbplid', 'az_shid',
            'az_starttime', 'az_endtime',
            'az_days', 'az_loop', 'status',
            'created','created_by','created_by_alias',
            'lastsync','note','az_startdate','az_enddate'
        );
        foreach ($scheduleitems as $schd) {
            //check doesn't exist?
            $azdays = implode(',',$schd->days);
            $azloop = ($schd->loop_once == true) ? 1 : 0;
            $query->clear();
            $query->insert($db->qn('#__xbmusic_azschedules'));
            $query->columns(implode(',',$db->qn($colarr)));
            $valarr = array($db->q(0),$db->q($dbplid),$db->q($schd->id),
                $db->q(date("H:i:s", strtotime($schd->start_time))),
                $db->q(date("H:i:s", strtotime($schd->end_time))),
                $db->q($azdays),$db->q($azloop),$db->q($status),
                $db->q(Factory::getDate()->toSql()),
                $db->q(Factory::getApplication()->getIdentity()->id),
                $db->q('import from Azuracast API'),
                $db->q(Factory::getDate()->toSql()),
                $db->q('imported from Azuracast'),
                'NULL','NULL'
            );
            if ($schd->start_date!='') {$valarr[13] = $db->q($schd->start_date);}
            if ($schd->end_date!='') {$valarr[14] = $db->q($schd->end_date);}
            $query->values(implode(',',$valarr));
            try {
                $db->setQuery($query);
                $res = $db->execute();
            } catch (\Exception $e) {
                Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage(),'Error');
                Factory::getApplication()->enqueueMessage('Problem saving schedule item '. $schd->id, 'Warning');
                Factory::getApplication()->enqueueMessage($cnt.' schedule items saved', 'Warning');
                return false;
            }
            if ($res == false) {
                Factory::getApplication()->enqueueMessage('Problem saving schedule item '. $schd->id, 'Warning');
            } else {
                $cnt ++;
            }
        }
        Factory::getApplication()->enqueueMessage($cnt.' schedule items saved', 'Success');
        return $res;
    }
    
    /***
     * @name deleteDbStation()
     * @desc this will delete a station from the database, playlist and schedule details will be cascade deleted
     * PlaylistTrack links will also be deleted but not the tracks themselves
     * @param int $stid
     * @return boolean
     */
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
            return false;
        }
        return $res;
    }
    
    public function updateDbStation(int $dbstid, object $azstation) {
        Factory::getApplication()->enqueueMessage('Update station is not yet implemented, do it manually by editing station', 'Warning');
        return $dbstid;
    }
    
    public function getServerInfo($api = null) {
        if (!isset($api)) {
            $api = new AzApi();
            if ($api->getStatus() == false)  return false;
        }
        $stationinfo = $api->azServerInfo();
        //$stationinfo->code is set if failed
        return $stationinfo;
    }
    
}