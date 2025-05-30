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
// module.graphic.png.php                                      //
// module for analyzing PNG Image files                        //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////

if (!defined('GETID3_INCLUDEPATH')) { // prevent path-exposing attacks that access modules directly on public webservers
	exit;
}

class getid3_png extends getid3_handler
{
	/**
	 * If data chunk is larger than this do not read it completely (getID3 only needs the first
	 * few dozen bytes for parsing).
	 *
	 * @var int
	 */
	public $max_data_bytes = 10000000;

	/**
	 * @return bool
	 */
	public function Analyze() {

		$info = &$this->getid3->info;

		// shortcut
		$info['png'] = array();
		$thisfile_png = &$info['png'];

		$info['fileformat']          = 'png';
		$info['video']['dataformat'] = 'png';
		$info['video']['lossless']   = false;

		$this->fseek($info['avdataoffset']);
		$PNGfiledata = $this->fread($this->getid3->fread_buffer_size());
		$offset = 0;

		$PNGidentifier = substr($PNGfiledata, $offset, 8); // $89 $50 $4E $47 $0D $0A $1A $0A
		$offset += 8;

		if ($PNGidentifier != "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
			$this->error('First 8 bytes of file ('.getid3_lib::PrintHexBytes($PNGidentifier).') did not match expected PNG identifier');
			unset($info['fileformat']);
			return false;
		}

		while ((($this->ftell() - (strlen($PNGfiledata) - $offset)) < $info['filesize'])) {
			$chunk = array();
			$chunk['data_length'] = getid3_lib::BigEndian2Int(substr($PNGfiledata, $offset, 4));
			if ($chunk['data_length'] === false) {
				$this->error('Failed to read data_length at offset '.$offset);
				return false;
			}
			$offset += 4;
			$truncated_data = false;
			while (((strlen($PNGfiledata) - $offset) < ($chunk['data_length'] + 4)) && ($this->ftell() < $info['filesize'])) {
				if (strlen($PNGfiledata) < $this->max_data_bytes) {
					$str = $this->fread($this->getid3->fread_buffer_size());
					if (strlen($str) > 0) {
						$PNGfiledata .= $str;
					} else {
						$this->warning('At offset '.$offset.' chunk "'.substr($PNGfiledata, $offset, 4).'" no more data to read, data chunk will be truncated at '.(strlen($PNGfiledata) - 8).' bytes');
						break;
					}
				} else {
					$this->warning('At offset '.$offset.' chunk "'.substr($PNGfiledata, $offset, 4).'" exceeded max_data_bytes value of '.$this->max_data_bytes.', data chunk will be truncated at '.(strlen($PNGfiledata) - 8).' bytes');
					break;
				}
			}
			$chunk['type_text']   =                           substr($PNGfiledata, $offset, 4);
			$offset += 4;
			$chunk['type_raw']    = getid3_lib::BigEndian2Int($chunk['type_text']);
			$chunk['data']        =                           substr($PNGfiledata, $offset, $chunk['data_length']);
			$offset += $chunk['data_length'];
			$chunk['crc']         = getid3_lib::BigEndian2Int(substr($PNGfiledata, $offset, 4));
			$offset += 4;

			$chunk['flags']['ancilliary']   = (bool) ($chunk['type_raw'] & 0x20000000);
			$chunk['flags']['private']      = (bool) ($chunk['type_raw'] & 0x00200000);
			$chunk['flags']['reserved']     = (bool) ($chunk['type_raw'] & 0x00002000);
			$chunk['flags']['safe_to_copy'] = (bool) ($chunk['type_raw'] & 0x00000020);

			// shortcut
			$thisfile_png[$chunk['type_text']] = array();
			$thisfile_png_chunk_type_text = &$thisfile_png[$chunk['type_text']];

			switch ($chunk['type_text']) {

				case 'IHDR': // Image Header
					$thisfile_png_chunk_type_text['header'] = $chunk;
					$thisfile_png_chunk_type_text['width']                     = getid3_lib::BigEndian2Int(substr($chunk['data'],  0, 4));
					$thisfile_png_chunk_type_text['height']                    = getid3_lib::BigEndian2Int(substr($chunk['data'],  4, 4));
					$thisfile_png_chunk_type_text['raw']['bit_depth']          = getid3_lib::BigEndian2Int(substr($chunk['data'],  8, 1));
					$thisfile_png_chunk_type_text['raw']['color_type']         = getid3_lib::BigEndian2Int(substr($chunk['data'],  9, 1));
					$thisfile_png_chunk_type_text['raw']['compression_method'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 10, 1));
					$thisfile_png_chunk_type_text['raw']['filter_method']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 11, 1));
					$thisfile_png_chunk_type_text['raw']['interlace_method']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 12, 1));

					$thisfile_png_chunk_type_text['compression_method_text']   = $this->PNGcompressionMethodLookup($thisfile_png_chunk_type_text['raw']['compression_method']);
					$thisfile_png_chunk_type_text['color_type']['palette']     = (bool) ($thisfile_png_chunk_type_text['raw']['color_type'] & 0x01);
					$thisfile_png_chunk_type_text['color_type']['true_color']  = (bool) ($thisfile_png_chunk_type_text['raw']['color_type'] & 0x02);
					$thisfile_png_chunk_type_text['color_type']['alpha']       = (bool) ($thisfile_png_chunk_type_text['raw']['color_type'] & 0x04);

					$info['video']['resolution_x']    = $thisfile_png_chunk_type_text['width'];
					$info['video']['resolution_y']    = $thisfile_png_chunk_type_text['height'];

					$info['video']['bits_per_sample'] = $this->IHDRcalculateBitsPerSample($thisfile_png_chunk_type_text['raw']['color_type'], $thisfile_png_chunk_type_text['raw']['bit_depth']);
					break;


				case 'PLTE': // Palette
					$thisfile_png_chunk_type_text['header'] = $chunk;
					$paletteoffset = 0;
					for ($i = 0; $i <= 255; $i++) {
						//$thisfile_png_chunk_type_text['red'][$i]   = getid3_lib::BigEndian2Int(substr($chunk['data'], $paletteoffset++, 1));
						//$thisfile_png_chunk_type_text['green'][$i] = getid3_lib::BigEndian2Int(substr($chunk['data'], $paletteoffset++, 1));
						//$thisfile_png_chunk_type_text['blue'][$i]  = getid3_lib::BigEndian2Int(substr($chunk['data'], $paletteoffset++, 1));
						$red   = getid3_lib::BigEndian2Int(substr($chunk['data'], $paletteoffset++, 1));
						$green = getid3_lib::BigEndian2Int(substr($chunk['data'], $paletteoffset++, 1));
						$blue  = getid3_lib::BigEndian2Int(substr($chunk['data'], $paletteoffset++, 1));
						$thisfile_png_chunk_type_text[$i] = (($red << 16) | ($green << 8) | ($blue));
					}
					break;


				case 'tRNS': // Transparency
					$thisfile_png_chunk_type_text['header'] = $chunk;
					switch ($thisfile_png['IHDR']['raw']['color_type']) { // @phpstan-ignore-line
						case 0:
							$thisfile_png_chunk_type_text['transparent_color_gray']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 2));
							break;

						case 2:
							$thisfile_png_chunk_type_text['transparent_color_red']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 2));
							$thisfile_png_chunk_type_text['transparent_color_green'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 2, 2));
							$thisfile_png_chunk_type_text['transparent_color_blue']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 2));
							break;

						case 3:
							for ($i = 0; $i < strlen($chunk['data']); $i++) {
								$thisfile_png_chunk_type_text['palette_opacity'][$i] = getid3_lib::BigEndian2Int(substr($chunk['data'], $i, 1));
							}
							break;

						case 4:
						case 6:
							$this->error('Invalid color_type in tRNS chunk: '.$thisfile_png['IHDR']['raw']['color_type']);
							break;

						default:
							$this->warning('Unhandled color_type in tRNS chunk: '.$thisfile_png['IHDR']['raw']['color_type']); // @phpstan-ignore-line
							break;
					}
					break;


				case 'gAMA': // Image Gamma
					$thisfile_png_chunk_type_text['header'] = $chunk;
					$thisfile_png_chunk_type_text['gamma']  = getid3_lib::BigEndian2Int($chunk['data']) / 100000;
					break;


				case 'cHRM': // Primary Chromaticities
					$thisfile_png_chunk_type_text['header']  = $chunk;
					$thisfile_png_chunk_type_text['white_x'] = getid3_lib::BigEndian2Int(substr($chunk['data'],  0, 4)) / 100000;
					$thisfile_png_chunk_type_text['white_y'] = getid3_lib::BigEndian2Int(substr($chunk['data'],  4, 4)) / 100000;
					$thisfile_png_chunk_type_text['red_y']   = getid3_lib::BigEndian2Int(substr($chunk['data'],  8, 4)) / 100000;
					$thisfile_png_chunk_type_text['red_y']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 12, 4)) / 100000;
					$thisfile_png_chunk_type_text['green_y'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 16, 4)) / 100000;
					$thisfile_png_chunk_type_text['green_y'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 20, 4)) / 100000;
					$thisfile_png_chunk_type_text['blue_y']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 24, 4)) / 100000;
					$thisfile_png_chunk_type_text['blue_y']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 28, 4)) / 100000;
					break;


				case 'sRGB': // Standard RGB Color Space
					$thisfile_png_chunk_type_text['header']                 = $chunk;
					$thisfile_png_chunk_type_text['reindering_intent']      = getid3_lib::BigEndian2Int($chunk['data']);
					$thisfile_png_chunk_type_text['reindering_intent_text'] = $this->PNGsRGBintentLookup($thisfile_png_chunk_type_text['reindering_intent']);
					break;


				case 'iCCP': // Embedded ICC Profile
					$thisfile_png_chunk_type_text['header']                  = $chunk;
					list($profilename, $compressiondata)                     = explode("\x00", $chunk['data'], 2);
					$thisfile_png_chunk_type_text['profile_name']            = $profilename;
					$thisfile_png_chunk_type_text['compression_method']      = getid3_lib::BigEndian2Int(substr($compressiondata, 0, 1));
					$thisfile_png_chunk_type_text['compression_profile']     = substr($compressiondata, 1);

					$thisfile_png_chunk_type_text['compression_method_text'] = $this->PNGcompressionMethodLookup($thisfile_png_chunk_type_text['compression_method']);
					break;


				case 'tEXt': // Textual Data
					$thisfile_png_chunk_type_text['header']  = $chunk;
					list($keyword, $text) = explode("\x00", $chunk['data'], 2);
					$thisfile_png_chunk_type_text['keyword'] = $keyword;
					$thisfile_png_chunk_type_text['text']    = $text;

					$thisfile_png['comments'][$thisfile_png_chunk_type_text['keyword']][] = $thisfile_png_chunk_type_text['text'];
					break;


				case 'zTXt': // Compressed Textual Data
					$thisfile_png_chunk_type_text['header']                  = $chunk;
					list($keyword, $otherdata)                               = explode("\x00", $chunk['data'], 2);
					$thisfile_png_chunk_type_text['keyword']                 = $keyword;
					$thisfile_png_chunk_type_text['compression_method']      = getid3_lib::BigEndian2Int(substr($otherdata, 0, 1));
					$thisfile_png_chunk_type_text['compressed_text']         = substr($otherdata, 1);
					$thisfile_png_chunk_type_text['compression_method_text'] = $this->PNGcompressionMethodLookup($thisfile_png_chunk_type_text['compression_method']);
					switch ($thisfile_png_chunk_type_text['compression_method']) {
						case 0:
							$thisfile_png_chunk_type_text['text']            = gzuncompress($thisfile_png_chunk_type_text['compressed_text']);
							break;

						default:
							// unknown compression method
							break;
					}

					if (isset($thisfile_png_chunk_type_text['text'])) {
						$thisfile_png['comments'][$thisfile_png_chunk_type_text['keyword']][] = $thisfile_png_chunk_type_text['text'];
					}
					break;


				case 'iTXt': // International Textual Data
					$thisfile_png_chunk_type_text['header']                  = $chunk;
					list($keyword, $otherdata)                               = explode("\x00", $chunk['data'], 2);
					$thisfile_png_chunk_type_text['keyword']                 = $keyword;
					$thisfile_png_chunk_type_text['compression']             = (bool) getid3_lib::BigEndian2Int(substr($otherdata, 0, 1));
					$thisfile_png_chunk_type_text['compression_method']      = getid3_lib::BigEndian2Int(substr($otherdata, 1, 1));
					$thisfile_png_chunk_type_text['compression_method_text'] = $this->PNGcompressionMethodLookup($thisfile_png_chunk_type_text['compression_method']);
					list($languagetag, $translatedkeyword, $text)                        = explode("\x00", substr($otherdata, 2), 3);
					$thisfile_png_chunk_type_text['language_tag']            = $languagetag;
					$thisfile_png_chunk_type_text['translated_keyword']      = $translatedkeyword;

					if ($thisfile_png_chunk_type_text['compression']) {

						switch ($thisfile_png_chunk_type_text['compression_method']) {
							case 0:
								$thisfile_png_chunk_type_text['text']        = gzuncompress($text);
								break;

							default:
								// unknown compression method
								break;
						}

					} else {

						$thisfile_png_chunk_type_text['text']                = $text;

					}

					if (isset($thisfile_png_chunk_type_text['text'])) {
						$thisfile_png['comments'][$thisfile_png_chunk_type_text['keyword']][] = $thisfile_png_chunk_type_text['text'];
					}
					break;


				case 'bKGD': // Background Color
					$thisfile_png_chunk_type_text['header']                   = $chunk;
					switch ($thisfile_png['IHDR']['raw']['color_type']) { // @phpstan-ignore-line
						case 0:
						case 4:
							$thisfile_png_chunk_type_text['background_gray']  = getid3_lib::BigEndian2Int($chunk['data']);
							break;

						case 2:
						case 6:
							$thisfile_png_chunk_type_text['background_red']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0 * $thisfile_png['IHDR']['raw']['bit_depth'], $thisfile_png['IHDR']['raw']['bit_depth']));
							$thisfile_png_chunk_type_text['background_green'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 1 * $thisfile_png['IHDR']['raw']['bit_depth'], $thisfile_png['IHDR']['raw']['bit_depth']));
							$thisfile_png_chunk_type_text['background_blue']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 2 * $thisfile_png['IHDR']['raw']['bit_depth'], $thisfile_png['IHDR']['raw']['bit_depth']));
							break;

						case 3:
							$thisfile_png_chunk_type_text['background_index'] = getid3_lib::BigEndian2Int($chunk['data']);
							break;

						default:
							break;
					}
					break;


				case 'pHYs': // Physical Pixel Dimensions
					$thisfile_png_chunk_type_text['header']                 = $chunk;
					$thisfile_png_chunk_type_text['pixels_per_unit_x']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 4));
					$thisfile_png_chunk_type_text['pixels_per_unit_y']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 4));
					$thisfile_png_chunk_type_text['unit_specifier']         = getid3_lib::BigEndian2Int(substr($chunk['data'], 8, 1));
					$thisfile_png_chunk_type_text['unit']                   = $this->PNGpHYsUnitLookup($thisfile_png_chunk_type_text['unit_specifier']);
					break;


				case 'sBIT': // Significant Bits
					$thisfile_png_chunk_type_text['header'] = $chunk;
					switch ($thisfile_png['IHDR']['raw']['color_type']) { // @phpstan-ignore-line
						case 0:
							$thisfile_png_chunk_type_text['significant_bits_gray']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
							break;

						case 2:
						case 3:
							$thisfile_png_chunk_type_text['significant_bits_red']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
							$thisfile_png_chunk_type_text['significant_bits_green'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 1, 1));
							$thisfile_png_chunk_type_text['significant_bits_blue']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 2, 1));
							break;

						case 4:
							$thisfile_png_chunk_type_text['significant_bits_gray']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
							$thisfile_png_chunk_type_text['significant_bits_alpha'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 1, 1));
							break;

						case 6:
							$thisfile_png_chunk_type_text['significant_bits_red']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
							$thisfile_png_chunk_type_text['significant_bits_green'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 1, 1));
							$thisfile_png_chunk_type_text['significant_bits_blue']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 2, 1));
							$thisfile_png_chunk_type_text['significant_bits_alpha'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 3, 1));
							break;

						default:
							break;
					}
					break;


				case 'sPLT': // Suggested Palette
					$thisfile_png_chunk_type_text['header']                           = $chunk;
					list($palettename, $otherdata)                                    = explode("\x00", $chunk['data'], 2);
					$thisfile_png_chunk_type_text['palette_name']                     = $palettename;
					$sPLToffset = 0;
					$thisfile_png_chunk_type_text['sample_depth_bits']                = getid3_lib::BigEndian2Int(substr($otherdata, $sPLToffset, 1));
					$sPLToffset += 1;
					$thisfile_png_chunk_type_text['sample_depth_bytes']               = $thisfile_png_chunk_type_text['sample_depth_bits'] / 8;
					$paletteCounter = 0;
					while ($sPLToffset < strlen($otherdata)) {
						$thisfile_png_chunk_type_text['red'][$paletteCounter]       = getid3_lib::BigEndian2Int(substr($otherdata, $sPLToffset, $thisfile_png_chunk_type_text['sample_depth_bytes']));
						$sPLToffset += $thisfile_png_chunk_type_text['sample_depth_bytes'];
						$thisfile_png_chunk_type_text['green'][$paletteCounter]     = getid3_lib::BigEndian2Int(substr($otherdata, $sPLToffset, $thisfile_png_chunk_type_text['sample_depth_bytes']));
						$sPLToffset += $thisfile_png_chunk_type_text['sample_depth_bytes'];
						$thisfile_png_chunk_type_text['blue'][$paletteCounter]      = getid3_lib::BigEndian2Int(substr($otherdata, $sPLToffset, $thisfile_png_chunk_type_text['sample_depth_bytes']));
						$sPLToffset += $thisfile_png_chunk_type_text['sample_depth_bytes'];
						$thisfile_png_chunk_type_text['alpha'][$paletteCounter]     = getid3_lib::BigEndian2Int(substr($otherdata, $sPLToffset, $thisfile_png_chunk_type_text['sample_depth_bytes']));
						$sPLToffset += $thisfile_png_chunk_type_text['sample_depth_bytes'];
						$thisfile_png_chunk_type_text['frequency'][$paletteCounter] = getid3_lib::BigEndian2Int(substr($otherdata, $sPLToffset, 2));
						$sPLToffset += 2;
						$paletteCounter++;
					}
					break;


				case 'hIST': // Palette Histogram
					$thisfile_png_chunk_type_text['header'] = $chunk;
					$hISTcounter = 0;
					while ($hISTcounter < strlen($chunk['data'])) {
						$thisfile_png_chunk_type_text[$hISTcounter] = getid3_lib::BigEndian2Int(substr($chunk['data'], $hISTcounter / 2, 2));
						$hISTcounter += 2;
					}
					break;


				case 'tIME': // Image Last-Modification Time
					$thisfile_png_chunk_type_text['header'] = $chunk;
					$thisfile_png_chunk_type_text['year']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 2));
					$thisfile_png_chunk_type_text['month']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 2, 1));
					$thisfile_png_chunk_type_text['day']    = getid3_lib::BigEndian2Int(substr($chunk['data'], 3, 1));
					$thisfile_png_chunk_type_text['hour']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 1));
					$thisfile_png_chunk_type_text['minute'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 5, 1));
					$thisfile_png_chunk_type_text['second'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 6, 1));
					$thisfile_png_chunk_type_text['unix']   = gmmktime($thisfile_png_chunk_type_text['hour'], $thisfile_png_chunk_type_text['minute'], $thisfile_png_chunk_type_text['second'], $thisfile_png_chunk_type_text['month'], $thisfile_png_chunk_type_text['day'], $thisfile_png_chunk_type_text['year']);
					break;


				case 'oFFs': // Image Offset
					$thisfile_png_chunk_type_text['header']         = $chunk;
					$thisfile_png_chunk_type_text['position_x']     = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 4), false, true);
					$thisfile_png_chunk_type_text['position_y']     = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 4), false, true);
					$thisfile_png_chunk_type_text['unit_specifier'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 8, 1));
					$thisfile_png_chunk_type_text['unit']           = $this->PNGoFFsUnitLookup($thisfile_png_chunk_type_text['unit_specifier']);
					break;


				case 'pCAL': // Calibration Of Pixel Values
					$thisfile_png_chunk_type_text['header']             = $chunk;
					list($calibrationname, $otherdata)                              = explode("\x00", $chunk['data'], 2);
					$thisfile_png_chunk_type_text['calibration_name']   = $calibrationname;
					$pCALoffset = 0;
					$thisfile_png_chunk_type_text['original_zero']      = getid3_lib::BigEndian2Int(substr($chunk['data'], $pCALoffset, 4), false, true);
					$pCALoffset += 4;
					$thisfile_png_chunk_type_text['original_max']       = getid3_lib::BigEndian2Int(substr($chunk['data'], $pCALoffset, 4), false, true);
					$pCALoffset += 4;
					$thisfile_png_chunk_type_text['equation_type']      = getid3_lib::BigEndian2Int(substr($chunk['data'], $pCALoffset, 1));
					$pCALoffset += 1;
					$thisfile_png_chunk_type_text['equation_type_text'] = $this->PNGpCALequationTypeLookup($thisfile_png_chunk_type_text['equation_type']);
					$thisfile_png_chunk_type_text['parameter_count']    = getid3_lib::BigEndian2Int(substr($chunk['data'], $pCALoffset, 1));
					$pCALoffset += 1;
					$thisfile_png_chunk_type_text['parameters']         = explode("\x00", substr($chunk['data'], $pCALoffset));
					break;


				case 'sCAL': // Physical Scale Of Image Subject
					$thisfile_png_chunk_type_text['header']         = $chunk;
					$thisfile_png_chunk_type_text['unit_specifier'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
					$thisfile_png_chunk_type_text['unit']           = $this->PNGsCALUnitLookup($thisfile_png_chunk_type_text['unit_specifier']);
					list($pixelwidth, $pixelheight)                             = explode("\x00", substr($chunk['data'], 1));
					$thisfile_png_chunk_type_text['pixel_width']    = $pixelwidth;
					$thisfile_png_chunk_type_text['pixel_height']   = $pixelheight;
					break;


				case 'gIFg': // GIF Graphic Control Extension
					$gIFgCounter = count($thisfile_png_chunk_type_text);
					$thisfile_png_chunk_type_text[$gIFgCounter]['header']          = $chunk;
					$thisfile_png_chunk_type_text[$gIFgCounter]['disposal_method'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
					$thisfile_png_chunk_type_text[$gIFgCounter]['user_input_flag'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 1, 1));
					$thisfile_png_chunk_type_text[$gIFgCounter]['delay_time']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 2, 2));
					break;


				case 'gIFx': // GIF Application Extension
					$gIFxCounter = count($thisfile_png_chunk_type_text);
					$thisfile_png_chunk_type_text[$gIFxCounter]['header']                 = $chunk;
					$thisfile_png_chunk_type_text[$gIFxCounter]['application_identifier'] = substr($chunk['data'],  0, 8);
					$thisfile_png_chunk_type_text[$gIFxCounter]['authentication_code']    = substr($chunk['data'],  8, 3);
					$thisfile_png_chunk_type_text[$gIFxCounter]['application_data']       = substr($chunk['data'], 11);
					break;


				case 'IDAT': // Image Data
					$idatinformationfieldindex = count($thisfile_png['IDAT']);
					unset($chunk['data']);
					$thisfile_png_chunk_type_text[$idatinformationfieldindex]['header'] = $chunk;
					break;

				case 'IEND': // Image Trailer
					$thisfile_png_chunk_type_text['header'] = $chunk;
					break;

				case 'acTL': // Animation Control chunk
					// https://wiki.mozilla.org/APNG_Specification#.60acTL.60:_The_Animation_Control_Chunk
					$thisfile_png['animation']['num_frames'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 4)); // Number of frames
					$thisfile_png['animation']['num_plays']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 4)); // Number of times to loop this APNG.  0 indicates infinite looping.

					unset($chunk['data']);
					$thisfile_png_chunk_type_text['header'] = $chunk;
					break;

				case 'fcTL': // Frame Control chunk
					// https://wiki.mozilla.org/APNG_Specification#.60fcTL.60:_The_Frame_Control_Chunk
					$fcTL = array();
					$fcTL['sequence_number'] = getid3_lib::BigEndian2Int(substr($chunk['data'],  0, 4)); // Sequence number of the animation chunk, starting from 0
					$fcTL['width']           = getid3_lib::BigEndian2Int(substr($chunk['data'],  4, 4)); // Width of the following frame
					$fcTL['height']          = getid3_lib::BigEndian2Int(substr($chunk['data'],  8, 4)); // Height of the following frame
					$fcTL['x_offset']        = getid3_lib::BigEndian2Int(substr($chunk['data'], 12, 4)); // X position at which to render the following frame
					$fcTL['y_offset']        = getid3_lib::BigEndian2Int(substr($chunk['data'], 16, 4)); // Y position at which to render the following frame
					$fcTL['delay_num']       = getid3_lib::BigEndian2Int(substr($chunk['data'], 20, 2)); // Frame delay fraction numerator
					$fcTL['delay_den']       = getid3_lib::BigEndian2Int(substr($chunk['data'], 22, 2)); // Frame delay fraction numerator
					$fcTL['dispose_op']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 23, 1)); // Type of frame area disposal to be done after rendering this frame
					$fcTL['blend_op']        = getid3_lib::BigEndian2Int(substr($chunk['data'], 23, 1)); // Type of frame area rendering for this frame
					if ($fcTL['delay_den']) {
						$fcTL['delay'] = $fcTL['delay_num'] / $fcTL['delay_den'];
					}
					$thisfile_png['animation']['fcTL'][$fcTL['sequence_number']] = $fcTL;

					unset($chunk['data']);
					$thisfile_png_chunk_type_text['header'] = $chunk;
					break;

				case 'fdAT': // Frame Data chunk
					// https://wiki.mozilla.org/APNG_Specification#.60fcTL.60:_The_Frame_Control_Chunk
					// "The `fdAT` chunk has the same purpose as an `IDAT` chunk. It has the same structure as an `IDAT` chunk, except preceded by a sequence number."
					unset($chunk['data']);
					$thisfile_png_chunk_type_text['header'] = $chunk;
					break;

				default:
					//unset($chunk['data']);
					$thisfile_png_chunk_type_text['header'] = $chunk;
					$this->warning('Unhandled chunk type: '.$chunk['type_text']);
					break;
			}
		}
		if (!empty($thisfile_png['animation']['num_frames']) && !empty($thisfile_png['animation']['fcTL'])) {
			$info['video']['dataformat'] = 'apng';
			$info['playtime_seconds'] = 0;
			foreach ($thisfile_png['animation']['fcTL'] as $seqno => $fcTL) {
				$info['playtime_seconds'] += $fcTL['delay'];
			}
		}
		return true;
	}

	/**
	 * @param int $sRGB
	 *
	 * @return string
	 */
	public function PNGsRGBintentLookup($sRGB) {
		static $PNGsRGBintentLookup = array(
			0 => 'Perceptual',
			1 => 'Relative colorimetric',
			2 => 'Saturation',
			3 => 'Absolute colorimetric'
		);
		return (isset($PNGsRGBintentLookup[$sRGB]) ? $PNGsRGBintentLookup[$sRGB] : 'invalid');
	}

	/**
	 * @param int $compressionmethod
	 *
	 * @return string
	 */
	public function PNGcompressionMethodLookup($compressionmethod) {
		static $PNGcompressionMethodLookup = array(
			0 => 'deflate/inflate'
		);
		return (isset($PNGcompressionMethodLookup[$compressionmethod]) ? $PNGcompressionMethodLookup[$compressionmethod] : 'invalid');
	}

	/**
	 * @param int $unitid
	 *
	 * @return string
	 */
	public function PNGpHYsUnitLookup($unitid) {
		static $PNGpHYsUnitLookup = array(
			0 => 'unknown',
			1 => 'meter'
		);
		return (isset($PNGpHYsUnitLookup[$unitid]) ? $PNGpHYsUnitLookup[$unitid] : 'invalid');
	}

	/**
	 * @param int $unitid
	 *
	 * @return string
	 */
	public function PNGoFFsUnitLookup($unitid) {
		static $PNGoFFsUnitLookup = array(
			0 => 'pixel',
			1 => 'micrometer'
		);
		return (isset($PNGoFFsUnitLookup[$unitid]) ? $PNGoFFsUnitLookup[$unitid] : 'invalid');
	}

	/**
	 * @param int $equationtype
	 *
	 * @return string
	 */
	public function PNGpCALequationTypeLookup($equationtype) {
		static $PNGpCALequationTypeLookup = array(
			0 => 'Linear mapping',
			1 => 'Base-e exponential mapping',
			2 => 'Arbitrary-base exponential mapping',
			3 => 'Hyperbolic mapping'
		);
		return (isset($PNGpCALequationTypeLookup[$equationtype]) ? $PNGpCALequationTypeLookup[$equationtype] : 'invalid');
	}

	/**
	 * @param int $unitid
	 *
	 * @return string
	 */
	public function PNGsCALUnitLookup($unitid) {
		static $PNGsCALUnitLookup = array(
			0 => 'meter',
			1 => 'radian'
		);
		return (isset($PNGsCALUnitLookup[$unitid]) ? $PNGsCALUnitLookup[$unitid] : 'invalid');
	}

	/**
	 * @param int $color_type
	 * @param int $bit_depth
	 *
	 * @return int|false
	 */
	public function IHDRcalculateBitsPerSample($color_type, $bit_depth) {
		switch ($color_type) {
			case 0: // Each pixel is a grayscale sample.
				return $bit_depth;

			case 2: // Each pixel is an R,G,B triple
				return 3 * $bit_depth;

			case 3: // Each pixel is a palette index; a PLTE chunk must appear.
				return $bit_depth;

			case 4: // Each pixel is a grayscale sample, followed by an alpha sample.
				return 2 * $bit_depth;

			case 6: // Each pixel is an R,G,B triple, followed by an alpha sample.
				return 4 * $bit_depth;
		}
		return false;
	}

}
