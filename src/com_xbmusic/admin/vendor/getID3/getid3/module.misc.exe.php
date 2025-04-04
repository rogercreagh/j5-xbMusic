<?php
/*******
 * @package xbMusic
 * @filesource admin/vendor/getID3...
 * @version 0.0.0.1 31st March 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at https://github.com/JamesHeinrich/getID3       //
//            or https://www.getid3.org                        //
//            or http://getid3.sourceforge.net                 //
//  see readme.txt for more details                            //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.misc.exe.php                                         //
// module for analyzing EXE files                              //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

if (!defined('GETID3_INCLUDEPATH')) { // prevent path-exposing attacks that access modules directly on public webservers
	exit;
}

class getid3_exe extends getid3_handler
{
	/**
	 * @return bool
	 */
	public function Analyze() {
		$info = &$this->getid3->info;

		$this->fseek($info['avdataoffset']);
		$EXEheader = $this->fread(28);

		$magic = 'MZ';
		if (substr($EXEheader, 0, 2) != $magic) {
			$this->error('Expecting "'.getid3_lib::PrintHexBytes($magic).'" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes(substr($EXEheader, 0, 2)).'"');
			return false;
		}

		$info['fileformat'] = 'exe';
		$info['exe']['mz']['magic'] = 'MZ';

		$info['exe']['mz']['raw']['last_page_size']          = getid3_lib::LittleEndian2Int(substr($EXEheader,  2, 2));
		$info['exe']['mz']['raw']['page_count']              = getid3_lib::LittleEndian2Int(substr($EXEheader,  4, 2));
		$info['exe']['mz']['raw']['relocation_count']        = getid3_lib::LittleEndian2Int(substr($EXEheader,  6, 2));
		$info['exe']['mz']['raw']['header_paragraphs']       = getid3_lib::LittleEndian2Int(substr($EXEheader,  8, 2));
		$info['exe']['mz']['raw']['min_memory_paragraphs']   = getid3_lib::LittleEndian2Int(substr($EXEheader, 10, 2));
		$info['exe']['mz']['raw']['max_memory_paragraphs']   = getid3_lib::LittleEndian2Int(substr($EXEheader, 12, 2));
		$info['exe']['mz']['raw']['initial_ss']              = getid3_lib::LittleEndian2Int(substr($EXEheader, 14, 2));
		$info['exe']['mz']['raw']['initial_sp']              = getid3_lib::LittleEndian2Int(substr($EXEheader, 16, 2));
		$info['exe']['mz']['raw']['checksum']                = getid3_lib::LittleEndian2Int(substr($EXEheader, 18, 2));
		$info['exe']['mz']['raw']['cs_ip']                   = getid3_lib::LittleEndian2Int(substr($EXEheader, 20, 4));
		$info['exe']['mz']['raw']['relocation_table_offset'] = getid3_lib::LittleEndian2Int(substr($EXEheader, 24, 2));
		$info['exe']['mz']['raw']['overlay_number']          = getid3_lib::LittleEndian2Int(substr($EXEheader, 26, 2));

		$info['exe']['mz']['byte_size']          = (($info['exe']['mz']['raw']['page_count'] - 1)) * 512 + $info['exe']['mz']['raw']['last_page_size'];
		$info['exe']['mz']['header_size']        = $info['exe']['mz']['raw']['header_paragraphs'] * 16;
		$info['exe']['mz']['memory_minimum']     = $info['exe']['mz']['raw']['min_memory_paragraphs'] * 16;
		$info['exe']['mz']['memory_recommended'] = $info['exe']['mz']['raw']['max_memory_paragraphs'] * 16;

		$this->error('EXE parsing not enabled in this version of getID3() ['.$this->getid3->version().']');
		return false;

	}

}
