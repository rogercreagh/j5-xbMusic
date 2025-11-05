<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/AzApi.php
 * @version 0.0.59.3 5th November 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Button\ActionButton;
use Joomla\Database\DatabaseInterface;
use Joomla\Plugin\Fields\SQL\Extension\SQL;
use \CURLFile;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbazuracastHelper;

/**
 * public functions will return $result as 
 * 
 *
 */
class AzApi {
 
    protected $apifullkey;
    protected $apicomment;
    protected $apiurl;
    protected $authorization;
    protected $status;
    
    /**
     * @desc if a db user id is provided will get the url and apikey from database
     * @desc otherwise will use the first value from userapikeys for given user
     * @param int $apidbid - id of key in userapikeys table
     * @param int $userid - id of user to retrieve first key from
     * @param string  $apifullkey - full 49 char api key
     */
    public function __construct(int $dbapiid = 0, int $userid = 0, $apifullkey = '', $azurl = null ) { //int $dbstid = 0, 
        //check Azuracast enabled
        $app = Factory::getApplication();
        $keyinfo = false;
        $this->status = true;
        $params = ComponentHelper::getParams('com_xbmusic');
        if ($params->get('azuracast',0) == 0) {
            $this->status = false;
            return;        
        }
        //If we're given a server url use it, otherwise use params default
        if (!$azurl) {
            $azurl = trim($params->get('az_url',''),'/');
        }
        if (!filter_var($azurl, FILTER_VALIDATE_URL)) {
            $app->enqueueMessage('Invalid URL : '.$azurl,'Error');
            $this->status = false;
            return;
        } 
        $azurl = trim($azurl,'/').'/api';
        //check server reachable
        $headers = get_headers($azurl);
        $notok = true;
        $n = 0;
        while ($notok && ($n<count($headers))) {
            if (strpos($headers[$n], '200 OK') > 0) $notok = false;
            $n ++;
        }
        if ($notok) {
            $app->enqueueMessage('Server not accessible : '.$azurl,'Error');
            $this->status = false;
            return;
        }
        $this->apiurl = $azurl;
        
        if (strlen($apifullkey) == 49) {
            //we've been given a key, lets test it
            $this->authorization = "Authorization: Bearer ".$apifullkey;
            $url = $this->apiurl.'/frontend/account/api-key/'.strtok($apifullkey,':');
            $result = $this->azApiGet($url);
            if (isset($result->code)) {
                $app->enqueueMessage('Could not login with . '.$apifullkey .
                    '<br />Code: '.$result->code.'  : '.$result->type.' '.$result->message ,'Error');
                $this->status = false;
                return;
            }                //TODO ammend to get selected key and fallback if none selected
            
            $this->apicomment = $result->comment;
            
            $this->apifullkey = $apifullkey;
        } else {
            if ($dbapiid > 0) { //we're getting an apikey from the userapikeys table using the id
                $keyinfo = XbcommonHelper::getItem('#__xbmusic_userapikeys', $dbapiid);
                if (!$keyinfo) {
                    $app->enqueueMessage('Invalid dbkey id '.$dbapiid.'for table userapikeys. '.$azurl,'Error');
                    $this->status = false;
                    return;                  
                }
            } else {
                //if a userid not specified use the current user
                if ($userid == 0) $userid = Factory::getApplication()->getIdentity()->id; //->getSession()->get('user');
                $keyinfo = XbazuracastHelper::getSelectedApiKey($userid);
            }
            if ($keyinfo) {
                $this->apifullkey = $keyinfo->az_apikeyid.':'.$keyinfo->az_apikeyval;
                $this->apicomment = $keyinfo->az_apicomment;
            } else {
                $app->enqueueMessage('Unable to retrieve API key info. ','Error');
                $this->status = false;
                return;
            }
        }
        $this->authorization = "Authorization: Bearer ".$this->apifullkey;           
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getApikey() {
        return $this->apifullkey;
    }
    
    public function getApicomment() {
        return $this->apicomment;
    }
    
    public function getAzurl() {
        return str_replace('/api','',$this->apiurl);
    }
    
    public function getAzstid() {
        return $this->azstid;
    }
    
    /***
     * @name azMe()
     * @desc gets account details for az_api user, false if not logged in, invalid apikey or error
     * user details as object-> email, name, locale, 24hr, id, roles[id,name], avatar[]
     * @return $result  user or error object 
     */
    public function azMe() {
        $url=$this->apiurl.'/frontend/account/me';
        return $this->azApiGet($url);        
    }
    
    /**
     * @name azStations()
     * @desc get list of Az stations on server.
     * Public properties for all stations plus admin properties if avaiable
     * @return object
     */
    public function azStations() {
        //first get public stations info
        $url = $this->apiurl.'/stations';
        $result = $this->azApiGet($url);
        foreach ($result as &$station) {
            //try for station profile
            $url = $this->apiurl.'/station/'.$station->id.'/profile';
            $profile = $this->azApiGet($url);
            //if okay set isadmin and merge with public
            if (!isset($profile->code)) {
                $station->isadmin = true;
                //merge to add services and schedule elements
                $station = (object) array_merge((array) $station, (array) $profile);
            } else {
                $station->isadmin = false;
            }
        }
        return $result;
    }
    
    public function azServer() {
        $url = $this->apiurl.'/admin/settings';
        $result = $this->azApiGet($url);
        return $result;
    }
    
    public function azStationQuota(int $azstid) {
        $url=$this->apiurl.'/station/'.$azstid.'/quota';
        $result = $this->azApiGet($url);
        return $result;
    }
    
    public function azStation(int $azstid) {
        $url=$this->apiurl.'/station/'.$azstid;
        $result = $this->azApiGet($url);
        $admin = false;
        $quota = $this->azStationQuota($azstid);
        if (isset($quota->used)) {
            $admin = true;
        }
        $result->isadmin = $admin;
        return $result;
    }
    
    public function azPlaylists() {
        if ($this->azstid == 0)
            return (object) ['code' => true, 'msg'=>'Station ID not set'];
        $url=$this->apiurl.'/station/'.$this->azstid.'/playlists';
        $result = $this->azApiGet($url);
        return $result;
    }
    
    public function azPlaylist(int $azplid) {
        if ($this->azstid == 0)
            return (object) ['code' => true, 'msg'=>'Station ID not set'];
        $url=$this->apiurl.'/station/'.$this->azstid.'/playlist/'.$azplid;
        $result = $this->azApiGet($url);
        return $result;
    }
       
    public function getAzPlaylistM3u(int $azplid, string $m3ufpathname) {
        if ($this->azstid == 0)
            return (object) ['code' => true, 'msg'=>'Station ID not set'];
        $url=$this->apiurl.'/station/'.$this->azstid.'/playlist/'.$azplid.'/export/m3u';
        $result = $this->azApiDownloadPlaylist($url, $m3ufpathname);
        return $result;
    }
    
    public function putAzPlaylist(int $azplid, string $jsondata) {
        if ($this->azstid == 0)
            return (object) ['code' => true, 'msg'=>'Station ID not set'];
        $url=$this->apiurl.'/station/'.$this->azstid.'/playlist/'.$azplid;
        $result = $this->azApiPut($url, $jsondata);
        return $result;
    }
    
    public function putAzPlaylistM3u(int $azplid, string $m3ufilename) {
        if ($this->azstid == 0)
            return (object) ['code' => true, 'msg'=>'Station ID not set'];
        $url=$this->apiurl.'/station/'.$this->azstid.'/playlist/'.$azplid.'/import';
        $result = $this->azApiPutM3u($url, $m3ufilename);
        return $result;
    }
    
    public function clearAzPlaylistTracks(int $azplid) {
        if ($this->azstid == 0)
            return (object) ['code' => true, 'msg'=>'Station ID not set'];
        $url=$this->apiurl.'/station/'.$this->azstid.'/playlist/'.$azplid.'/empty';
        $result = $this->azApiDel($url);
        return $result;
        
    }
    
    private function azApiGet($url, string $bodytext = '') {
        $ok = false;
        $cnt = 0;
        while (!$ok) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $this->authorization ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if (isset($result->code) && ($result->code == 429)) {
                //we have an api overload so wait 2 secs for a max of 2 times
                if ($cnt >1) {
                    $ok = true;
                } else {
                    sleep(2);
                    $cnt ++;
                }
            } else {
                $ok = true;
            }
        }
        return $result;       //check for other error codes on return to calling function
    }
    
    /**
     * @name azApiDownlloadPlaylist()
     * @desc NB the downloaded playlist is always called Y-m-d.m3u and is created in /xbmusic-dat/m3u/
     * @desc the calling function should rename the file as required to prevent subsequent calls overwriting it.
     * @param string $url
     * @param string $bodytext
     * @return mixed
     */
    private function azApiDownloadPlaylist(string $url, string $m3ufpathname) {
        $ok = false;
        $cnt = 0;
 //       $fpathname = JPATH_ROOT."/xbmusic-data/m3u/".date('Y-m-d-Hi').".m3u";
        $fh = fopen($m3ufpathname, "w+");
        while (!$ok) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream' , $this->authorization ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FILE, $fh);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if (isset($result->code) && ($result->code == 429)) {
                //we have an api overload so wait 2 secs for a max of 2 times
                if ($cnt >1) {
                    $ok = true;
                } else {
                    sleep(2);
                    $cnt ++;
                }
            } else {
                $ok = true;
            }
        }
        fclose($fh);
        chmod($m3ufpathname, 0775);
        return $result;       //check for other error codes on return to calling function
    }
    
    private function azApiPut($url, string $data_json = '') {
        $ok = false;
        $cnt = 0;
        while (!$ok) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $this->authorization,'Content-Length: ' . strlen($data_json) ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if (isset($result->code) && ($result->code == 429)) {
                //we have an api overload so wait 2 secs for a max of 2 times
                if ($cnt >1) {
                    $ok = true;
                } else {
                    sleep(2);
                    $cnt ++;
                }
            } else {
                $ok = true;
            }
        }
        return $result;       //check for other error codes on return to calling function
    }
    
    private function azApiPutM3u($url, string $filename ) {
        $ok = false;
        $cnt = 0;
        $ch = curl_init();
//        $cfile = curl_file_create($filename,'audio/x-mpegurl','playlist_file');
        $cfile = new CURLFile($filename,'text/plain','playlist_file');
        $data = array('playlist_file' => $cfile);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data', $this->authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        while (!$ok) {
                
            $result = curl_exec($ch);
            $result = json_decode($result);
            if (isset($result->code) && ($result->code == 429)) {
                //we have an api overload so wait 2 secs for a max of 2 times
                if ($cnt >1) {
                    curl_close($ch);
                    $ok = true;
                } else {
                    sleep(2);
                    $cnt ++;
                }
            } else {
                curl_close($ch);
                $ok = true;
            }
        }
        return $result;       //check for other error codes on return to calling function
    }
    
    private function azApiDel($url) {
        $ok = false;
        $cnt = 0;
        while (!$ok) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $this->authorization ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if (isset($result->code) && ($result->code == 429)) {
                //we have an api overload so wait 2 secs for a max of 2 times
                if ($cnt >1) {
                    $ok = true;
                } else {
                    sleep(2);
                    $cnt ++;
                }
            } else {
                $ok = true;
            }
        }
        return $result;       //check for other error codes on return to calling function        
    }
    
}
    
