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
        $this->azurl = trim($params->get('az_url',''),'/').'/api/stations';
        $this->apikey = $params->get('az_apikey');        
    }
    
    public function azStations() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $this->authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->azurl);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
    
    public function azPlaylists(int $stid) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $this->authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->azurl.'/'.$stid.'/playlists');
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
    
    public function azPlaylistPls(int $stid,  int $plid) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $this->authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->azurl.'/'.$stid.'/playlist/'.$plid.'/export/pls');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
}
    
