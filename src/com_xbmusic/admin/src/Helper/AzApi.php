<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/AzApi.php
 * @version 0.0.55.5 8th July 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Button\ActionButton;
use Joomla\Plugin\Fields\SQL\Extension\SQL;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;


class AzApi {
 
    protected $apikey;
    protected $apiname;
    protected $apiurl;
    protected $azstid;
    protected $authorization;
    
    /**
     * @desc if a db station id is provided will get the url and apikey from database
     * @desc otherwise will use the values set in component parameters
     * @param int $dbstid
     */
    public function __construct(int $dbstid = 0) {
        $params = ComponentHelper::getParams('com_xbmusic');
        if ($params->get('azuracast',0) == 0) return false;
        if ($dbstid == 0) {
            $this->apiurl = trim($params->get('az_url',''),'/').'/api';
            $this->apikey = $params->get('az_apikey',''); 
            $this->apiname = $params->get('az_apiname'.'');
            $this->azstid = 0;
        } else {
            $station = XbmusicHelper::getDbStation($dbstid);
            if ($station) {
                $this->apiurl = $station['az_url'].'/api';
                $this->apikey = $station['az_apikey'];
                $this->apiname = $station['az_apiname'];
                $this->azstid = $station['az_id'];
            } else {
                $this->apiurl = trim($params->get('az_url',''),'/').'/api';
                $this->apikey = $params->get('az_apikey','');
                $this->apiname = $params->get('az_apiname','');
                $this->azstid = 0;
            }
        }
        $this->apikey =  str_replace(' ', '', $this->apikey);
        $this->authorization = "Authorization: Bearer ".$this->apikey;
    }
    
    public function getApikey() {
        return $this->apikey;
    }
    
    public function getApiname() {
        return $this->apiname;
    }
    
    public function getAzurl() {
        return str_replace('/api','',$this->apiurl);
    }
    
    public function getAzstid() {
        return $this->azstid;
    }
    
    public function azStations() {
        $url=$this->apiurl.'/stations';
        $result = $this->azApiGet($url);
        return $result;
    }
    
    public function azStation(int $azstid) {
        $url=$this->apiurl.'/station/'.$azstid;
        $result = $this->azApiGet($url);
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
       
    public function getAzPlaylistM3u(int $azplid) {
        if ($this->azstid == 0)
                return (object) ['code' => true, 'msg'=>'Station ID not set'];
            $url=$this->apiurl.'/station/'.$this->azstid.'/playlist/'.$azplid.'/export/m3u';
            $result = $this->azApiDownloadPlaylist($url);
            return $result;
    }
    
    public function putAzPlaylist(int $azplid, string $jsondata) {
        if ($this->azstid == 0)
                return (object) ['code' => true, 'msg'=>'Station ID not set'];
            $url=$this->apiurl.'/station/'.$this->azstid.'/playlist/'.$azplid;
            $result = $this->azApiPut($url, $jsondata);
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
    private function azApiDownloadPlaylist(string $url, string $bodytext = '') {
        $ok = false;
        $cnt = 0;
        $fpathname = JPATH_ROOT."/xbmusic-data/m3u/".date('Y-m-d-Hi').".m3u";
        $fh = fopen($fpathname, "w+");
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
        chmod($fpathname, 0775);
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
    
}
    
