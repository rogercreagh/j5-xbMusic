<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/AzApi.php
 * @version 0.0.40.1 26th February 2025
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


class AzApi {
 
    protected $apikey;
    protected $azurl;
    protected $authorization;
    

    public function __construct() {
        $params = ComponentHelper::getParams('com_xbmusic');
        $this->authorization = "Authorization: Bearer ".$params->get('az_apikey');
        $this->azurl = trim($params->get('az_url',''),'/').'/api';
        $this->apikey = $params->get('az_apikey');        
    }
    
    public function azStations() {
        $url=$this->azurl.'/stations';
        $result = $this->azApiGet($url);
        return $result;
    }
    
    public function azPlaylists(int $stid) {
        $url=$this->azurl.'/station/'.$stid.'/playlists';
        $result = $this->azApiGet($url);
        return $result;
    }
    
    
    public function azPlaylistPls(int $stid,  int $plid) {
        $url=$this->azurl.'/station/'.$stid.'/playlist/'.$plid.'/export/pls';
        $result = $this->azApiGet($url);
        return $result;
    }
    
    private function azApiGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $this->authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if (isset($result->code)){
            Factory::getApplication()->enqueueMessage('Azuracast API Error: code '.$result->code.' - '.$result->type.
                '<br />'.$result->formatted_message.'<br />'.'URL: '.$url,'Error');
            $result = (object) ['error' => true];
        }
        return $result;       
    }
    
}
    
