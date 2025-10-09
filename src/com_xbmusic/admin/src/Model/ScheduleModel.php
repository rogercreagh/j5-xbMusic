<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/ScheduleModel.php
 * @version 0.0.51.8 2nd May 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
//use Joomla\CMS\Form\FormHelper;
//use Joomla\CMS\Helper\TagsHelper;
//use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\ListModel;
//use Joomla\Utilities\ArrayHelper;
// use Joomla\CMS\Uri\Uri;
//use Joomla\CMS\Table\Table;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class ScheduleModel extends ListModel {
    
    public function __construct($config = array())
    {        
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'dbstid', 'startdate', 'numdays', 'starttime', 'numhours', 'displayfmt', 'publiconly',
                'az_startdate','az_starttime'
            );           
        }
        parent::__construct($config);
    }
    
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = parent::getFilterForm($data, $loadData);
        
        return $form;
        
    }
     
    protected function populateState($ordering = 'az_starttime', $direction = 'asc') {
        $app = Factory::getApplication();
        $input = $app->getInput();
        
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout'))
        {
            $this->context .= '.' . $layout;
        }
        
        //$publiconly = 
        $this->getUserStateFromRequest($this->context . '.filter.publiconly', 'filter_publiconly', '0');
        //$displayfmt = 
        $this->getUserStateFromRequest($this->context . '.filter.displayfmt', 'filter_displayfmt', '1');
        //$numhours = 
        $this->getUserStateFromRequest($this->context . '.filter.numhours', 'filter_numhours', '24');
        //$starttime = 
        $this->getUserStateFromRequest($this->context . '.filter.starttime', 'filter_starttime', '00:00');
        //$numdays = 
        $this->getUserStateFromRequest($this->context . '.filter.numdays', 'filter_numdays', '4');
        //$startdate = 
        $this->getUserStateFromRequest($this->context . '.filter.startdate', 'filter_startdate', date('Y-m-d'));
        //$dbstid = 
        $this->getUserStateFromRequest($this->context . '.filter.dbstid', 'filter_dbstid', '');
        
/* 
        $posted = $app->input->post->get('filter');
        $app->enqueueMessage(print_r($posted,true));
       
        if ($posted) {
            
            if (key_exists('publiconly', $posted)) $publiconly = $posted['publiconly'];
            if (key_exists('displayfmt', $posted)) $displayfmt = $posted['displayfmt'];
            if (key_exists('numhours', $posted)) $numhours = $posted['numhours'];
            if (key_exists('starttime', $posted)) $starttime = $posted['starttime'];
            if ($starttime == '') $starttime = strtotime(date('H:i').':00');
            if (key_exists('startdate', $posted)) $startdate = $posted['startdate'];
            //            if ($startdate == '') $startdate = strtotime(date('d m y'));
            if (key_exists('numdays', $posted)) $numdays = $posted['numdays'];
            
        }
        
        $this->setState('filter.publiconly', $publiconly);        
        $this->setState('filter.displayfmt', $displayfmt);        
        $this->setState('filter.numhours', $numhours);        
        $this->setState('filter.starttime', $starttime);       
        $this->setState('filter.numdays', $numdays);        
        $this->setState('filter.startdate', $startdate);        
        $this->setState('filter.dbstid', $dbstid);
        $data = [];
        $data['publiconly'] = $publiconly;
        $data['displayfmt'] = $displayfmt;
        $data['numhours'] = $numhours;
        $data['starttime'] = $starttime;
        $data['numdays'] = $numdays;
        $data['startdate'] = $startdate;
        $data['dbstid'] = $dbstid;
        $form = parent::getFilterForm($data, $loadData);
        $form->bind($data);
 */        
        
//         $numdays = ($display=0) ? $listdays : $tabledays;
        
//         //convert start and end date to timestamp
//         $enddatestamp = strtotime($startdate. '+ '.$days.' days');
//         $startdatestamp = strtotime($startdate);
//         //get start and end times as timestamp
//         $endtimestamp = strtotime($starttime. '+ '.$numhours.' hours');
//         $starttimestamp = strtotime($starttime);
            
                  
        parent::populateState($ordering, $direction);
        
    }
    
    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   1.6
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.publiconly');
        $id .= ':' . serialize($this->getState('filter.displayfmt'));
        $id .= ':' . $this->getState('filter.numhours');
        $id .= ':' . serialize($this->getState('filter.starttime'));
        $id .= ':' . serialize($this->getState('filter.numdays'));
        $id .= ':' . $this->getState('filter.startdate');
        $id .= ':' . serialize($this->getState('filter.dbstid'));
        
        return parent::getStoreId($id);
    }
    
    
/*     
    public function loadFormData(){
        $this->get('State');
        $res = parent::loadFormData();
        Factory::getApplication()->enqueueMessage('<pre>'.print_r($res,true).'</pre>','warning');
        return $res;
    }
    
 */    
    protected function getListQuery() {
        
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $app = Factory::getApplication();
        $user  = $app->getIdentity();
		$dbstid = $this->getState('filter.dbstid',0);
        // if there is only one station we'll make sure it is default
        if ($dbstid == 0) {
            $dbstid = XbmusicHelper::singleStationId();
            $this->setState('filter.dbstid', $dbstid);
        }
       /*
        SELECT a.id AS plid , a.title AS pltitle, a.az_jingle, a.publicschd
FROM `j512_xbmusic_playlists` AS a
LEFT JOIN `j512_xbmusic_azschedules` as sh ON a.id = sh.dbplid
LEFT JOIN `j512_xbmusic_azstations` AS st ON a.db_stid = st.id
WHERE st.id = 2
        */
        
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id AS plid , a.title AS pltitle, a.az_jingle, a.publicschd'
                .', IF(a.status = "1", "1", "0") as enabled'
                .', sh.az_startdate AS az_startdate, sh.az_enddate AS az_enddate'
                .', sh.az_starttime AS az_starttime, sh.az_endtime AS az_endtime'
                .', sh.az_days AS az_days, sh.az_loop AS az_loop'
                )
            );
        $query->from('#__xbmusic_playlists AS a');
        $query->join('LEFT', $db->qn('#__xbmusic_azschedules').' AS sh','a.id = sh.dbplid');
        $query->join('LEFT', $db->qn('#__xbmusic_azstations').' AS st',' a.db_stid = st.id');
        
		
		if ($dbstid>0) $query->where('st.id = '.$dbstid);
		$query->where('a.scheduledcnt > 0');

        //get filters
	    $publiconly = $this->getState('filter.publiconly',0);		
		if ($publiconly > 0) $query->where('a.publicschd = '.$db->q((int) $publiconly));
		$startdate = $this->getState('filter.startdate',date('Y-m-d'));
		$numdays = $this->getState('filter.numdays','4');
		$enddate = date('Y-m-d', strtotime('+'.$numdays.' days', strtotime($startdate)));
		if ($startdate != '') {
		    $query->where('(sh.az_startdate IS NULL OR sh.az_startdate <= '.$db->q( $enddate)
		        .') AND (sh.az_enddate IS NULL OR sh.az_enddate >= '.$db->q( $startdate).')');		    
		}
		$starttime = $this->getState('filter.starttime','00:00:00');
		if (strlen($starttime < 7)) $starttime .= ':00';
		$numhours = (int)$this->getState('filter.numhours','24');
		$endhour = $numhours + (int)substr($starttime,0,2);
		$endtime = ($endhour > 23) ? '24:00:00' : date('H:i:s', strtotime($starttime . ' + '.$numhours.' hours'));
		$query->where('sh.az_starttime BETWEEN '.$db->q($starttime). ' AND '.$db->q($endtime));
		
		//we'll check az_days when building schedule
		
		
//		$orderCol  = 'sh.az_starttime';
//		$orderDirn = 'ASC';
		$orderCol  = $this->state->get('list.ordering', 'sh.az_starttime');
		$orderDirn = $this->state->get('list.direction', 'ASC');
		
		
		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
		
		return $query;
    } //end getlistquery

    /**
     * @name getItems()
     * @desc We have an array of playlist schedule items for the station
     * @desc we need to transform into array of dates per filters with only valid schedule items per day/time
     * {@inheritDoc}
     * @see \Joomla\CMS\MVC\Model\ListModel::getItems()
     */
    public function getItems() {
        $items  = parent::getItems();
        $today = date('Y-m-d');
        $numdays = $this->getState('filter.numdays',4);
        $startdate = $this->getState('filter.startdate',$today);
        $thisdate = strtotime($startdate);
        $schedule = array();
        for ($i = 0; $i < $numdays; $i++) {
            $dayofweek = date('N', $thisdate);
            $d = $thisdate;
            $schedule[$d] = [];
            if (($this->getState('filter.dbstid',0) > 0) AND ($items)) {
                $schitems = [];
                foreach ($items as $item) {
                    $ok = true;
                    if ($item->az_days != '') { //if daysofweek specified check valid
                        if (strpos($item->az_days,$dayofweek) === false) $ok = false;
                    }               
                    if ($item->az_startdate) { // check startdate is before this date
                        if (strtotime($item->az_startdate) > $thisdate) $ok = false;
                    }
                    if ($item->az_enddate) { //check if enddate after this date
                        if (strtotime($item->az_enddate) < $thisdate) $ok = false;
                    }
                    //times have already been dealt with in the main query
                    if ($ok) $schitems[] = $item;
                } //endforeach item
                $schedule[$d] = $schitems;
            } //end if items
            $thisdate += 24*60*60;
        } //endforeach day
        
        return $schedule;            

    } // end getItems
    
}