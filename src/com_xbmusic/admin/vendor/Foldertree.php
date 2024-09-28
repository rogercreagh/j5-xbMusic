<?php
/*******
 * @package xbMusic
 * @filesource admin/tmpl/track/Foldertree.php
 * @version 0.0.11.1 10th July 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

//defined('_JEXEC') or die;

/***** original source and copyright:
 Folder Tree with PHP and jQuery.
 R. Savoul Pelister
 http://techlister.com
*****/

class treeview {

	private $files;
	private $folder;
	
	/**
	 * @name __construct()
	 * @desc webserver user/group must have read rights in the folder tree
	 * @param string $path - the absolute base path from which to construct the tree
	 */
	function __construct( $path ) {
		
		$files = array();	
		
		if( file_exists( $path)) {
			if( $path[ strlen( $path ) - 1 ] ==  '/' )
				$this->folder = $path;
			else
				$this->folder = $path . '/';
			
			$this->dir = opendir( $path );
			while(( $file = readdir( $this->dir ) ) != false )
                $this->files[] = $file;
			closedir( $this->dir );
		}
	}

	/**
	 * @name create_tree()
	 * @param string $filt - an optional extension filter (without the .) to restrict the list to folders and a single file type
	 * @return string - list of folders snd files sorted by name
	 */
	function create_tree($filt = '') {
			
		if( count( $this->files ) > 2 ) { /* First 2 entries are . and ..  -skip them */
			natcasesort( $this->files );
			$list = '<ul class="filetree" style="display: none;">';
			// Group folders first
			foreach( $this->files as $file ) {
				if( file_exists( $this->folder . $file ) && $file != '.' && $file != '..' && is_dir( $this->folder . $file )) {
				    
					$list .= '<li class="folder collapsed"><a href="#" rel="' . htmlentities( $this->folder . $file ) . '/">' . htmlentities( $file ) . '</a></li>';
				}
			}
			// Group all files
			foreach( $this->files as $file ) {
				if( file_exists( $this->folder . $file ) && $file != '.' && $file != '..' && !is_dir( $this->folder . $file )) {
				    $ext = pathinfo($file, PATHINFO_EXTENSION);
				    if (($filt == '') || ($ext == $filt)) {
				        $list .= '<li class="file ext_' . $ext . '"><a href="#" rel="' . htmlentities( $this->folder . $file ) . '">' . htmlentities( $file ) . '</a></li>';
				    }
				}
			}
			$list .= '</ul>';	
			return $list;
		}
	}
}

$path = urldecode( $_REQUEST['dir'] );
$filt = $_REQUEST['ext'];
$tree = new treeview( $path );
echo $tree->create_tree($filt);

?>