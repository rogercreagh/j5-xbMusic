<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/XcommonHelper.php
 * @version 0.0.59.11 29th November 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use Joomla\Filter\OutputFilter;
use DateTime;
use Exception;
use Tuf\Key;

class XbazuracastHelper extends ComponentHelper
{
   
    public static function getUserApiKeys($userid = 0) {
        if ($userid == 0) {
            $userid = Factory::getApplication()->getIdentity()->id; //->getSession()->get('user');
        }
        //$db = Factory::getContainer()->get(DatabaseInterface::class);
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
        ->from($db->qn('#__xbmusic_userapikeys'))
        ->where('user_id = '.$db->q($userid));
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    
    /**
     * @name getSelectedApiKey()
     * @desc gets the selected api details for the user, or null if none selected
     * @param number $userid
     * @return mixed|NULL
     */
    public static function getSelectedApiKey($userid = 0) {
        if ($userid == 0) {
            $userid = Factory::getApplication()->getIdentity()->id; //->getSession()->get('user');
        }
        //$db = Factory::getContainer()->get(DatabaseInterface::class);
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
        ->from($db->qn('#__xbmusic_userapikeys'))
        ->where('user_id = '.$db->q($userid))->where('selected = 1');
        $db->setQuery($query);
        return $db->loadObject();
    }
    
    /**
     * @name getStations()
     * @desc returns array of stations in database with playlist & schedule counts
     * @return array|null
     */
    public static function getStations() {
        $dbstations = [];
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__xbmusic_azstations');
        $db->setQuery($query);
        $dbstations = $db->loadAssocList('id');
        if (is_array($dbstations)) {
            foreach ($dbstations AS &$station) {
                $query->clear();
                $query->select('COUNT(id) AS plcnt , SUM(scheduledcnt) AS schtot, COUNT(CASE WHEN scheduledcnt > 0 THEN 1 ELSE NULL END) AS schlists');
                $query->from('#__xbmusic_azplaylists');
                $query->where('db_stid = '.$db->q($station['id']));
                $db->setQuery($query);
                $cnts = $db->loadAssoc();
                $station['plcnt'] = $cnts['plcnt'];
                $station['schlists'] = $cnts['schlists'];
                $station['schtot'] = ($station['schlists']==0) ? 0 : $cnts['schtot'];
                //create a unique identifier for server + station id
                $station['azurlid'] = $station['az_url'].'-'.$station['az_stid'];
            }
        }
        if (!is_array($dbstations)) $dbstations = [];
        return $dbstations;
    }
    
    /**
     * @name getDbStationId()
     * @desc given the id returns staion details from database
     * @see playlist.view, schedule.view
     * @param int $dbstid - the id column value
     * @return mixed|NULL - associative array or null
     */
    public static function getDbStation(int $dbstid) {
        //$db = Factory::getContainer()->get(DatabaseInterface::cl
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__xbmusic_azstations');
        $query->where($db->qn('id').' = '. $dbstid);
        $db->setQuery($query);
        return $db->loadAssoc();
    }
    
    /**
     * @name getAzStation()
     * @see none
     * @desc given the azstid returns staion details from database
     * @param int $azstid - the az_stid column value
     * @return mixed|NULL - associative array or null
     */
    public static function getAzStation(int $azstid) {
        //$db = Factory::getContainer()->get(DatabaseInterface::cl
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__xbmusic_azstations');
        $query->where($db->qn('az_stid').' = '. $azstid);
        $db->setQuery($query);
        $station = $db->loadAssoc();
        return $station;
    }
    
    /**
     * @name getAzstationPlaylists()
     * @see none
     * @desc gets playlists assigned to a given Az Station ID
     * @param int $azstid
     * @return mixed|NULL
     */
    public static function getAzstationPlaylists(int $dbstid) {
        //$db = Factory::getContainer()->get(DatabaseInterface::cl
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__xbmusic_azplaylists');
        $query->where($db->qn('db_stid').' = '. $dbstid); //?????????
        $db->setQuery($query);
        return $db->loadAssoc();
        
    }
    
    /**
     * @name singleStation()
     * @desc if only a single station is in db returns dbid otherwise false
     * @see schedule.model, schedule.view
     */
    public static function singleStationId() {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('s.id AS id, (SELECT(COUNT(s2.id)) FROM #__xbmusic_azstations AS s2) AS stncnt');
        $query->from('#__xbmusic_azstations AS s');
        $db->setQuery($query);
        $stn = $db->loadAssoc();
        if ($stn['stncnt'] == 1) {
            return $stn['id'];
        } else {
            return 0;
        }
    }
    
    /**
     * @name setSelectedApi()
     * @see azuracast.controller
     * @desc sets the selected api for the user to the given db key id (not the id part of the key!)
     * @param int $keyid - dbid of selected Key
     * @param int userid - optional user id otherwise use current user
     * @return boolean - true if success
     */
    public static function setSelectedApi(int $keyid, $userid = 0) {
        if ($userid == 0) $userid = Factory::getApplication()->getIdentity()->id;
        $db = Factory::getDbo(); //Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->update($db->qn('#__xbmusic_userapikeys'));
        $query->where($db->qn('user_id').' = '. $db->q($userid));
        $query->set($db->qn('selected'). ' = (id='.$keyid.')');
        try {
            $db->setQuery($query);
            $res = $db->execute();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return false;
        }
        return $res;
    }
    
    /**
     * @name saveApiUserkey()
     * @see azuracast.controller
     * @desc 
     * @param unknown $fullkey
     * @param number $selected
     * @param number $userid
     * @return boolean|boolean|mixed|NULL
     */
    public static function saveApiUserkey($fullkey, $selected = 1, $userid = 0) {
        
        if ($userid == 0) $userid = Factory::getApplication()->getIdentity()->id;
        if ((strlen($fullkey) != 49) || (strpos($fullkey, ':') != 16)) {
            Factory::getApplication()->enqueueMessage('New key incorrect length or format','Error');
            return false;
        }
        //check if key is valid and if so get comment
        $api = new AzApi(0,0,$fullkey);
        if ($api->getStatus() == false) {
            //error messages already printed by api::_construct
            return false;
        }
        $splitkey = explode(':', $fullkey);
        $keyid = $splitkey[0];
        $keyval = $splitkey[1];
        $comment = $api->getApicomment();
        $azurl = $api->getAzurl();
        
        //check if key id already exists for user and server
        $dbid = self::checkKeyExists($keyid,$userid,$azurl);
        if ($dbid > 0) {
            Factory::getApplication()->enqueueMessage('API key already exists for this user.','Info');
            return $dbid;
        }
        $colarr = array('id', 'user_id', 'az_url', 'az_apikeyid', 'az_apikeyval',
            'az_apicomment'
        );
        $db = Factory::getDbo(); //Factory::getContainer()->get(DatabaseInterface::class);
        $values=array($db->q(0),$db->q($userid),$db->q($azurl),$db->q($keyid),$db->q($keyval),
            $db->q($comment)
        );
        $query = $db->getQuery(true);
        $query->insert($db->qn('#__xbmusic_userapikeys'));
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
            if ($selected) {
                self::setSelectedApi($newid, $userid);
            }
            Factory::getApplication()->enqueueMessage(Text::_('new api key created and selected').' : '.$newid);
        }
        return $res;
    }
    
    /**
     * @name checkKeyExists()
     * @see self
     * @desc checks if 16char keyid exists in table, optionall with specified user and server
     * @param string $keyid
     * @param number $userid
     * @param unknown $azurl
     * @return boolean|mixed|NULL
     */
    private static function checkKeyExists(string $keyid, $userid = 0, $azurl = null) {
        $db = Factory::getDbo(); //Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__xbmusic_userapikeys');
        $query->where($db->qn('az_apikeyid').' = '. $db->q($keyid));
        if ($userid > 0) $query->where($db->qn('user_id').' = '.$db->q($userid));
        if ($azurl) $query->where($db->qn('az_url').' = '.$db->q($azurl));
        $db->setQuery($query);
        $result = $db->loadResult();
        return (is_null($result)) ? false : $result;
    }
    
    /**
     * @name getApikeyByKeyid
     * @see not used
     * @desc given the 16char ApiKeyId part returns the details from userapikeys
     * @param string $keyid
     * @return boolean|object
     */
    public static function getApikeyByKeyid(string $keyid) {
        $db = Factory::getDbo(); //Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__xbmusic_userapikeys');
        $query->where($db->qn('az_apikeyid').' = '. $db->q($keyid));
        $db->setQuery($query);
        $result = $db->loadObject();
        if (is_null($result)) return false;
        return $result;
    }
    
}
