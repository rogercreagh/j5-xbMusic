<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/AzuracastModel.php
 * @version 0.0.59.5 12th November 2025
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
        
//        if ((!isset($data['apilist'])) || ($data['apilist'] > 0)) {
//            $keydeets = XbmusicHelper::getSelectedApiKey();
//            $data['apilist'] = ($keydeets) ? $keydeets->id : 0;
//        }
//        return $data;
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
    
    public function getAzStations($api = null) {
        if (!isset($api)) {
            $api = new AzApi();
            if ($api->getStatus() == false)  return false;
        }
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
        if (!isset($api)) {
            $api = new AzApi();
            if ($api->getStatus() == false)  return false;
        }
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
            $db->q($azstation->azurl), $db->q($azstation->listen_url), $db->q($azstation->url),
            $db->q($azstation->public_player_url), $db->q($uncatid),
            $db->q($azstation->description), $db->q(json_encode($azstation)),
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
        /*****
        // /admin/api-keys
         [
  {
    "id": "string",
    "verifier": "string",
    "user": {
      "id": 0,
      "email": "demo@azuracast.com",
      "auth_password": "",
      "name": "Demo Account",
      "locale": "en_US",
      "show_24_hour_time": true,
      "two_factor_secret": "A1B2C3D4",
      "created_at": 1609480800,
      "updated_at": 1609480800,
      "roles": [
        "string"
      ]
    },
    "comment": "string",
    "links": {
      "additionalProp1": "string",
      "additionalProp2": "string",
      "additionalProp3": "string"
    }
  }
]
         
 //admin/backups
  [
  {
    "path": "automatic_backup_20251106_033300.tgz",
    "basename": "automatic_backup_20251106_033300.tgz",
    "pathEncoded": "MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA2XzAzMzMwMC50Z3o=",
    "timestamp": 1762399983,
    "size": 10973578,
    "storageLocationId": 1,
    "links": {
      "download": "/api/admin/backups/download/MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA2XzAzMzMwMC50Z3o=",
      "delete": "/api/admin/backups/delete/MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA2XzAzMzMwMC50Z3o="
    }
  },
  {
    "path": "automatic_backup_20251105_033200.tgz",
    "basename": "automatic_backup_20251105_033200.tgz",
    "pathEncoded": "MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA1XzAzMzIwMC50Z3o=",
    "timestamp": 1762313523,
    "size": 10917096,
    "storageLocationId": 1,
    "links": {
      "download": "/api/admin/backups/download/MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA1XzAzMzIwMC50Z3o=",
      "delete": "/api/admin/backups/delete/MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA1XzAzMzIwMC50Z3o="
    }
  },
  {
    "path": "automatic_backup_20251104_033101.tgz",
    "basename": "automatic_backup_20251104_033101.tgz",
    "pathEncoded": "MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA0XzAzMzEwMS50Z3o=",
    "timestamp": 1762227063,
    "size": 10864589,
    "storageLocationId": 1,
    "links": {
      "download": "/api/admin/backups/download/MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA0XzAzMzEwMS50Z3o=",
      "delete": "/api/admin/backups/delete/MXxhdXRvbWF0aWNfYmFja3VwXzIwMjUxMTA0XzAzMzEwMS50Z3o="
    }
  }
]

       //admin/users
        *[
  {
    "email": "rogercreagh@hotmail.com",
    "auth_password": "",
    "name": "Roger CO",
    "locale": "default",
    "show_24_hour_time": null,
    "two_factor_secret": null,
    "created_at": 1735746698,
    "updated_at": 1761898011,
    "roles": [
      {
        "name": "Super Administrator",
        "id": 1
      }
    ],
    "api_keys": [
      {
        "user": "Roger CO",
        "comment": "Xbmusic",
        "id": "28b19c397616ae17"
      },
      {
        "user": "Roger CO",
        "comment": "Android App",
        "id": "ec8c33b0acfe030a"
      }
    ],
    "passkeys": [],
    "login_tokens": [],
    "id": 1,
    "is_me": true,
    "links": {
      "self": "https://radio.xbone.uk/api/admin/user/1",
      "masquerade": "https://radio.xbone.uk/login-as/1/zYDug08bSP"
    }
  },
  {
    "email": "roger@crosborne.co.uk",
    "auth_password": "",
    "name": "RR Manager",
    "locale": null,
    "show_24_hour_time": null,
    "two_factor_secret": null,
    "created_at": 1740767136,
    "updated_at": 1761897794,
    "roles": [
      {
        "name": "RR Station Manager",
        "id": 3
      },
      {
        "name": "XbmusicAdmin",
        "id": 4
      }
    ],
    "api_keys": [
      {
        "user": "RR Manager",
        "comment": "RR Real Manager",
        "id": "11d89d798e405417"
      }
    ],
    "passkeys": [],
    "login_tokens": [],
    "id": 2,
    "is_me": false,
    "links": {
      "self": "https://radio.xbone.uk/api/admin/user/2",
      "masquerade": "https://radio.xbone.uk/login-as/2/zYDug08bSP"
    }
  }
]

//admin/settings
 {
  "app_unique_identifier": "3e56a67c-c851-11ef-80e2-0242ac130002",
  "base_url": "https://radio.xbone.uk",
  "instance_name": "Xbone Server",
  "prefer_browser_url": true,
  "use_radio_proxy": true,
  "history_keep_days": 730,
  "always_use_ssl": true,
  "api_access_control": null,
  "enable_static_nowplaying": true,
  "analytics": "all",
  "check_for_updates": true,
  "update_results": {
    "current_release": "0.23.1",
    "latest_release": "0.23.1",
    "needs_rolling_update": false,
    "needs_release_update": false,
    "rolling_updates_available": 0,
    "can_switch_to_stable": false
  },
  "update_last_run": 1762438620,
  "public_theme": null,
  "hide_album_art": false,
  "homepage_redirect_url": null,
  "default_album_art_url": null,
  "use_external_album_art_when_processing_media": false,
  "use_external_album_art_in_apis": false,
  "last_fm_api_key": null,
  "hide_product_name": false,
  "public_custom_css": null,
  "public_custom_js": null,
  "internal_custom_css": null,
  "backup_enabled": true,
  "backup_time_code": "321",
  "backup_exclude_media": true,
  "backup_keep_copies": 3,
  "backup_storage_location": 1,
  "backup_format": "tgz",
  "backup_last_run": 1762399981,
  "backup_last_output": "Exited with code 0:\nAzuraCast Backup\n================\n\nPlease wait while a backup is generated...\n\nBacking up MariaDB...\n---------------------\n\n\nCreating backup archive...\n--------------------------\n\nvar/azuracast/storage/uploads/\nvar/azuracast/storage/uploads/background.png\nvar/azuracast/storage/uploads/browser_icon/\nvar/azuracast/storage/uploads/browser_icon/180.png\nvar/azuracast/storage/uploads/browser_icon/original.png\nvar/azuracast/storage/uploads/browser_icon/114.png\nvar/azuracast/storage/uploads/browser_icon/96.png\nvar/azuracast/storage/uploads/browser_icon/48.png\nvar/azuracast/storage/uploads/browser_icon/144.png\nvar/azuracast/storage/uploads/browser_icon/57.png\nvar/azuracast/storage/uploads/browser_icon/16.png\nvar/azuracast/storage/uploads/browser_icon/72.png\nvar/azuracast/storage/uploads/browser_icon/152.png\nvar/azuracast/storage/uploads/browser_icon/36.png\nvar/azuracast/storage/uploads/browser_icon/120.png\nvar/azuracast/storage/uploads/browser_icon/60.png\nvar/azuracast/storage/uploads/browser_icon/32.png\nvar/azuracast/storage/uploads/browser_icon/192.png\nvar/azuracast/storage/uploads/browser_icon/76.png\nvar/azuracast/storage/uploads/album_art.png\ntmp/azuracast_backup_mariadb/db.sql\n\nCleaning up temporary files...\n------------------------------\n\n\n [OK] Backup complete in 1.74 seconds.",
  "setup_complete_time": 1735808700,
  "sync_disabled": false,
  "sync_last_run": 1762459861,
  "external_ip": null,
  "geolite_license_key": null,
  "geolite_last_run": 1762454520,
  "mail_enabled": true,
  "mail_sender_name": "Xbone Azuracast",
  "mail_sender_email": "xbone@crosborne.co.uk",
  "mail_smtp_host": "smtp.ionos.co.uk",
  "mail_smtp_port": 587,
  "mail_smtp_username": "roger@crosborne.co.uk",
  "mail_smtp_password": "Wm0itcr&s",
  "mail_smtp_secure": false,
  "avatar_service": "disabled",
  "avatar_default_url": "https://www.azuracast.com/img/avatar.png",
  "acme_email": "rogercreagh@hotmail.com",
  "acme_domains": "radio.xbone.uk",
  "ip_source": "local"
}

//admin/storage-locations
 
 {
  "media_storage_location": [
    {
      "value": 2,
      "text": "Local: /var/azuracast/stations/wreckers_radio/media",
      "description": null
    },
    {
      "value": 5,
      "text": "Local: /var/azuracast/stations/radioroger/media",
      "description": null
    }
  ],
  "recordings_storage_location": [
    {
      "value": 3,
      "text": "Local: /var/azuracast/stations/wreckers_radio/recordings",
      "description": null
    },
    {
      "value": 6,
      "text": "Local: /var/azuracast/stations/radioroger/recordings",
      "description": null
    }
  ],
  "podcasts_storage_location": [
    {
      "value": 4,
      "text": "Local: /var/azuracast/stations/wreckers_radio/podcasts",
      "description": null
    },
    {
      "value": 7,
      "text": "Local: /var/azuracast/stations/radioroger/podcasts",
      "description": null
    }
  ]
}


         */
    
}