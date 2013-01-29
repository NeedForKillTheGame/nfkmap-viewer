<?php

/*
 * This file is part of NFK Map Viewer.
 *
 * (c) 2013 HarpyWar
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NFK\MapViewer\GD;

// https://bitbucket.org/oov/php-bmp/raw/09808861a72ac1619638ed376a0bbffe149ff0cc/GdBmp.php
/**
 * Copyright (c) 2011, oov. All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *  - Neither the name of the oov nor the names of its contributors may be used to
 *    endorse or promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA,
 * OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 * 
 * bmp гѓ•г‚Ўг‚¤гѓ«г‚’ GD гЃ§дЅїгЃ€г‚‹г‚€гЃ†гЃ«
 * 
 * дЅїз”Ёдѕ‹:
 *   //гѓ•г‚Ўг‚¤гѓ«гЃ‹г‚‰иЄ­гЃїиѕјг‚Ђе ґеђ€гЃЇGDгЃ§PNGгЃЄгЃ©г‚’иЄ­гЃїиѕјг‚ЂгЃ®гЃЁеђЊгЃг‚€гЃ†гЃЄж–№жі•гЃ§еЏЇ
 *   $image = imagecreatefrombmp("test.bmp");
 *   imagedestroy($image);
 * 
 *   //ж–‡е­—е€—гЃ‹г‚‰иЄ­гЃїиѕјг‚Ђе ґеђ€гЃЇд»Ґдё‹гЃ®ж–№жі•гЃ§еЏЇ
 *   $image = GdBmp::loadFromString(file_get_contents("test.bmp"));
 *   //и‡Єе‹•е€¤е®љгЃ•г‚Њг‚‹гЃ®гЃ§з ґжђЌгѓ•г‚Ўг‚¤гѓ«гЃ§гЃЄгЃ‘г‚ЊгЃ°гЃ“г‚ЊгЃ§г‚‚дёЉж‰‹гЃЏгЃ„гЃЏ
 *   //$image = imagecreatefrombmp(file_get_contents("test.bmp"));
 *   imagedestroy($image);
 * 
 *   //гЃќгЃ®д»–д»»ж„ЏгЃ®г‚№гѓ€гѓЄгѓјгѓ гЃ‹г‚‰гЃ®иЄ­гЃїиѕјгЃїг‚‚еЏЇиѓЅ
 *   $stream = fopen("http://127.0.0.1/test.bmp");
 *   $image = GdBmp::loadFromStream($stream);
 *   //и‡Єе‹•е€¤е®љгЃ•г‚Њг‚‹гЃ®гЃ§гЃ“г‚ЊгЃ§г‚‚гЃ„гЃ„
 *   //$image = imagecreatefrombmp($stream);
 *   fclose($stream);
 *   imagedestroy($image);
 * 
 * еЇѕеїњгѓ•г‚©гѓјгѓћгѓѓгѓ€
 *   1bit
 *   4bit
 *   4bitRLE
 *   8bit
 *   8bitRLE
 *   16bit(д»»ж„ЏгЃ®гѓ“гѓѓгѓ€гѓ•г‚Јгѓјгѓ«гѓ‰)
 *   24bit
 *   32bit(д»»ж„ЏгЃ®гѓ“гѓѓгѓ€гѓ•г‚Јгѓјгѓ«гѓ‰)
 *   BITMAPINFOHEADER гЃ® biCompression гЃЊ BI_PNG / BI_JPEG гЃ®з”»еѓЏ
 *   гЃ™гЃ№гЃ¦гЃ®еЅўејЏгЃ§гѓ€гѓѓгѓ—гѓЂг‚¦гѓі/гѓњгѓ€гѓ г‚ўгѓѓгѓ—гЃ®дёЎж–№г‚’г‚µгѓќгѓјгѓ€
 *   з‰№ж®ЉгЃЄгѓ“гѓѓгѓ€гѓ•г‚Јгѓјгѓ«гѓ‰гЃ§г‚‚гѓ“гѓѓгѓ€гѓ•г‚Јгѓјгѓ«гѓ‰гѓ‡гѓјг‚їгЃЊж­ЈеёёгЃЄг‚‰иЄ­гЃїиѕјгЃїеЏЇиѓЅ
 *
 * д»Ґдё‹гЃ®г‚‚гЃ®гЃЇйќћеЇѕеїњ
 *   BITMAPV4HEADER гЃЁ BITMAPV5HEADER гЃ«еђ«гЃѕг‚Њг‚‹и‰Із©єй–“гЃ«й–ўгЃ™г‚‹ж§гЂ…гЃЄж©џиѓЅ
 **/
class GdBmp
{
	public static function load($filename_or_stream_or_binary){
		if (is_resource($filename_or_stream_or_binary)){
			return self::loadFromStream($filename_or_stream_or_binary);
		} else if (is_string($filename_or_stream_or_binary) && strlen($filename_or_stream_or_binary) >= 26){
			$bfh = unpack("vtype/Vsize", $filename_or_stream_or_binary);
			if ($bfh["type"] == 0x4d42){
				return self::loadFromString($filename_or_stream_or_binary);
			}
		}
		return self::loadFromFile($filename_or_stream_or_binary);
	}
	private static function loadFromFile($filename){
		$fp = fopen($filename, "rb");
		if ($fp === false){
			return false;
		}

		$bmp = self::loadFromStream($fp);

		fclose($fp);
		return $bmp;
	}

	private static function loadFromString($str){
		//data scheme г‚€г‚ЉеЏ¤гЃ„гѓђгѓјг‚ёгѓ§гѓігЃ‹г‚‰еЇѕеїњгЃ—гЃ¦гЃ„г‚‹г‚€гЃ†гЃЄгЃ®гЃ§ php://memory г‚’дЅїгЃ†
		$fp = fopen("php://memory", "r+b");
		if ($fp === false){
			return false;
		}

		if (fwrite($fp, $str) != strlen($str)){
			fclose($fp);
			return false;
		}

		if (fseek($fp, 0) === -1){
			fclose($fp);
			return false;
		}

		$bmp = self::loadFromStream($fp);

		fclose($fp);
		return $bmp;
	}

	private static function loadFromStream($stream){
		$buf = fread($stream, 14); //2+4+2+2+4
		if ($buf === false){
			return false;
		}

		//г‚·г‚°гѓЌгѓЃгѓЈгѓЃг‚§гѓѓг‚Ї
		if ($buf[0] != 'B' || $buf[1] != 'M'){
			return false;
		}

		$bitmap_file_header = unpack(
			//BITMAPFILEHEADERж§‹йЂ дЅ“
			"vtype/".
			"Vsize/".
			"vreserved1/".
			"vreserved2/".
			"Voffbits", $buf
		);
		
		return self::loadFromStreamAndFileHeader($stream, $bitmap_file_header);
	}

	private static function loadFromStreamAndFileHeader($stream, array $bitmap_file_header){
		if ($bitmap_file_header["type"] != 0x4d42){
			return false;
		}

		//жѓ…е ±гѓгѓѓгѓЂг‚µг‚¤г‚єг‚’е…ѓгЃ«еЅўејЏг‚’еЊєе€ҐгЃ—гЃ¦иЄ­гЃїиѕјгЃї
		$buf = fread($stream, 4);
		if ($buf === false){
			return false;
		}
		list(,$header_size) = unpack("V", $buf);


		if ($header_size == 12){
			$buf = fread($stream, $header_size - 4);
			if ($buf === false){
				return false;
			}

			extract(unpack(
				//BITMAPCOREHEADERж§‹йЂ дЅ“ - OS/2 Bitmap
				"vwidth/".
				"vheight/".
				"vplanes/".
				"vbit_count", $buf
			));
			//йЈ›г‚“гЃ§гЃ“гЃЄгЃ„е€†гЃЇ 0 гЃ§е€ќжњџеЊ–гЃ—гЃ¦гЃЉгЃЏ
			$clr_used = $clr_important = $alpha_mask = $compression = 0;

			//гѓћг‚№г‚ЇйЎћгЃЇе€ќжњџеЊ–гЃ•г‚ЊгЃЄгЃ„гЃ®гЃ§гЃ“гЃ“гЃ§е‰Іг‚ЉеЅ“гЃ¦гЃ¦гЃЉгЃЏ
			$red_mask   = 0x00ff0000;
			$green_mask = 0x0000ff00;
			$blue_mask  = 0x000000ff;
		} else if (124 < $header_size || $header_size < 40) {
			//жњЄзџҐгЃ®еЅўејЏ
			return false;
		} else {
			//гЃ“гЃ®ж™‚з‚№гЃ§36гѓђг‚¤гѓ€иЄ­г‚Ѓг‚‹гЃ“гЃЁгЃѕгЃ§гЃЇг‚ЏгЃ‹гЃЈгЃ¦гЃ„г‚‹
			$buf = fread($stream, 36); //ж—ўгЃ«иЄ­г‚“гЃ йѓЁе€†гЃЇй™¤е¤–гЃ—гЃ¤гЃ¤BITMAPINFOHEADERгЃ®г‚µг‚¤г‚єгЃ гЃ‘иЄ­г‚Ђ
			if ($buf === false){
				return false;
			}

			//BITMAPINFOHEADERж§‹йЂ дЅ“ - Windows Bitmap
			extract(unpack(
				"Vwidth/".
				"Vheight/".
				"vplanes/".
				"vbit_count/".
				"Vcompression/".
				"Vsize_image/".
				"Vx_pels_per_meter/".
				"Vy_pels_per_meter/".
				"Vclr_used/".
				"Vclr_important", $buf
			));
			
			// HarpyWar: fix stream size if wrong
			$pos = ftell($stream);
			rewind($stream);
			$bitmap_file_header["size"] = strlen( stream_get_contents($stream) );
			fseek($stream, $pos);
			
			//иІ гЃ®ж•ґж•°г‚’еЏ—гЃ‘еЏ–г‚‹еЏЇиѓЅжЂ§гЃЊгЃ‚г‚‹г‚‚гЃ®гЃЇи‡Єе‰ЌгЃ§е¤‰жЏ›гЃ™г‚‹
			if ($width  & 0x80000000){ $width  = -(~$width  & 0xffffffff) - 1; }
			if ($height & 0x80000000){ $height = -(~$height & 0xffffffff) - 1; }
			if ($x_pels_per_meter & 0x80000000){ $x_pels_per_meter = -(~$x_pels_per_meter & 0xffffffff) - 1; }
			if ($y_pels_per_meter & 0x80000000){ $y_pels_per_meter = -(~$y_pels_per_meter & 0xffffffff) - 1; }

			//гѓ•г‚Ўг‚¤гѓ«гЃ«г‚€гЃЈгЃ¦гЃЇ BITMAPINFOHEADER гЃ®г‚µг‚¤г‚єгЃЊгЃЉгЃ‹гЃ—гЃ„пј€ж›ёгЃЌиѕјгЃїй–“йЃ•гЃ„пјџпј‰г‚±гѓјг‚№гЃЊгЃ‚г‚‹
			//и‡Єе€†гЃ§гѓ•г‚Ўг‚¤гѓ«г‚µг‚¤г‚єг‚’е…ѓгЃ«йЂ†з®—гЃ™г‚‹гЃ“гЃЁгЃ§е›ћйЃїгЃ§гЃЌг‚‹гЃ“гЃЁг‚‚гЃ‚г‚‹гЃ®гЃ§е†ЌиЁ€з®—гЃ§гЃЌгЃќгЃ†гЃЄг‚‰ж­ЈеЅ“жЂ§г‚’иЄїгЃ№г‚‹
			//г‚·гѓјг‚ЇгЃ§гЃЌгЃЄгЃ„г‚№гѓ€гѓЄгѓјгѓ гЃ®е ґеђ€е…ЁдЅ“гЃ®гѓ•г‚Ўг‚¤гѓ«г‚µг‚¤г‚єгЃЇеЏ–еѕ—гЃ§гЃЌгЃЄгЃ„гЃ®гЃ§гЂЃ$bitmap_file_headerгЃ«г‚µг‚¤г‚єз”іе‘ЉгЃЊгЃЄгЃ‘г‚ЊгЃ°г‚„г‚‰гЃЄгЃ„
			if ($bitmap_file_header["size"] != 0){
				$colorsize = $bit_count == 1 || $bit_count == 4 || $bit_count == 8 ? ($clr_used ? $clr_used : pow(2, $bit_count))<<2 : 0;
				$bodysize = $size_image ? $size_image : ((($width * $bit_count + 31) >> 3) & ~3) * abs($height);
				$calcsize = $bitmap_file_header["size"] - $bodysize - $colorsize - 14;
				//жњ¬жќҐгЃ§гЃ‚г‚ЊгЃ°дёЂи‡ґгЃ™г‚‹гЃЇгЃљгЃЄгЃ®гЃ«еђ€г‚ЏгЃЄгЃ„ж™‚гЃЇгЂЃеЂ¤гЃЊгЃЉгЃ‹гЃ—гЃЏгЃЄгЃ•гЃќгЃ†гЃЄг‚‰пј€BITMAPV5HEADERгЃ®зЇ„е›Іе†…гЃЄг‚‰пј‰иЁ€з®—гЃ—гЃ¦ж±‚г‚ЃгЃџеЂ¤г‚’жЋЎз”ЁгЃ™г‚‹
				if ($header_size < $calcsize && 40 <= $header_size && $header_size <= 124){
					$header_size = $calcsize;
				}
				
				// HarpyWar: fix offset if wrong
				if ( $bitmap_file_header["offbits"] != ($bitmap_file_header["size"] - $bodysize) )
				{
					fseek($stream, $bitmap_file_header["size"] - $bodysize);
					
					// set header size to pass next condition
					$header_size = 40;
				}
			}
			
			//BITMAPV4HEADER г‚„ BITMAPV5HEADER гЃ®е ґеђ€гЃѕгЃ иЄ­г‚ЂгЃ№гЃЌгѓ‡гѓјг‚їгЃЊж®‹гЃЈгЃ¦гЃ„г‚‹еЏЇиѓЅжЂ§гЃЊгЃ‚г‚‹
			if ($header_size - 40 > 0){
				$buf = fread($stream, $header_size - 40);
				if ($buf === false){
					return false;
				}

				extract(unpack(
					//BITMAPV4HEADERж§‹йЂ дЅ“(Windows95д»Ґй™Ќ)
					//BITMAPV5HEADERж§‹йЂ дЅ“(Windows98/2000д»Ґй™Ќ)
					"Vred_mask/".
					"Vgreen_mask/".
					"Vblue_mask/".
					"Valpha_mask", $buf . str_repeat("\x00", 120)
				));
			} else {
				$alpha_mask = $red_mask = $green_mask = $blue_mask = 0;
			}

			//гѓ‘гѓ¬гѓѓгѓ€гЃЊгЃЄгЃ„гЃЊг‚«гѓ©гѓјгѓћг‚№г‚Їг‚‚гЃЄгЃ„ж™‚
			if (
				($bit_count == 16 || $bit_count == 24 || $bit_count == 32)&&
				$compression == 0 &&
				$red_mask == 0 && $green_mask == 0 && $blue_mask == 0
			){
				//г‚‚гЃ—г‚«гѓ©гѓјгѓћг‚№г‚Їг‚’ж‰ЂжЊЃгЃ—гЃ¦гЃ„гЃЄгЃ„е ґеђ€гЃЇ
				//и¦Џе®љгЃ®г‚«гѓ©гѓјгѓћг‚№г‚Їг‚’йЃ©з”ЁгЃ™г‚‹
				switch($bit_count){
				case 16:
					$red_mask   = 0x7c00;
					$green_mask = 0x03e0;
					$blue_mask  = 0x001f;
					break;
				case 24:
				case 32:
					$red_mask   = 0x00ff0000;
					$green_mask = 0x0000ff00;
					$blue_mask  = 0x000000ff;
					break;
				}
			}
		}
		
		if (
			($width  == 0)||
			($height == 0)||
			($planes != 1)||
			(($alpha_mask & $red_mask  ) != 0)||
			(($alpha_mask & $green_mask) != 0)||
			(($alpha_mask & $blue_mask ) != 0)||
			(($red_mask   & $green_mask) != 0)||
			(($red_mask   & $blue_mask ) != 0)||
			(($green_mask & $blue_mask ) != 0)
		){
			//дёЌж­ЈгЃЄз”»еѓЏ
			return false;
		}

		//BI_JPEG гЃЁ BI_PNG гЃ®е ґеђ€гЃЇ jpeg/png гЃЊгЃќгЃ®гЃѕгЃѕе…ҐгЃЈгЃ¦г‚‹гЃ гЃ‘гЃЄгЃ®гЃ§гЃќгЃ®гЃѕгЃѕеЏ–г‚Ље‡єгЃ—гЃ¦гѓ‡г‚ігѓјгѓ‰гЃ™г‚‹
		if ($compression == 4 || $compression == 5){
			$buf = stream_get_contents($stream, $size_image);
			if ($buf === false){
				return false;
			}
			return imagecreatefromstring($buf);
		}

		//з”»еѓЏжњ¬дЅ“гЃ®иЄ­гЃїе‡єгЃ—
		//1иЎЊгЃ®гѓђг‚¤гѓ€ж•°
		$line_bytes = (($width * $bit_count + 31) >> 3) & ~3;
		//е…ЁдЅ“гЃ®иЎЊж•°
		$lines = abs($height);
		//yи»ёйЂІиЎЊй‡Џпј€гѓњгѓ€гѓ г‚ўгѓѓгѓ—гЃ‹гѓ€гѓѓгѓ—гѓЂг‚¦гѓігЃ‹пј‰
		$y = $height > 0 ? $lines-1 : 0;
		$line_step = $height > 0 ? -1 : 1;

		//256и‰Ід»Ґдё‹гЃ®з”»еѓЏгЃ‹пјџ
		if ($bit_count == 1 || $bit_count == 4 || $bit_count == 8){
			$img = imagecreatetruecolor($width, $lines);

			//з”»еѓЏгѓ‡гѓјг‚їгЃ®е‰ЌгЃ«гѓ‘гѓ¬гѓѓгѓ€гѓ‡гѓјг‚їгЃЊгЃ‚г‚‹гЃ®гЃ§гѓ‘гѓ¬гѓѓгѓ€г‚’дЅњж€ђгЃ™г‚‹
			$palette_size = $header_size == 12 ? 3 : 4; //OS/2еЅўејЏгЃ®е ґеђ€гЃЇ x гЃ«з›ёеЅ“гЃ™г‚‹з®‡ж‰ЂгЃ®гѓ‡гѓјг‚їгЃЇжњЂе€ќгЃ‹г‚‰зўєдїќгЃ•г‚ЊгЃ¦гЃ„гЃЄгЃ„
			$colors = $clr_used ? $clr_used : pow(2, $bit_count); //и‰Іж•°
			$palette = array();
			for($i = 0; $i < $colors; ++$i){
				$buf = fread($stream, $palette_size);
				if ($buf === false){
					imagedestroy($img);
					return false;
				}
				extract(unpack("Cb/Cg/Cr/Cx", $buf . "\x00"));
				$palette[] = imagecolorallocate($img, $r, $g, $b);
			}

			$shift_base = 8 - $bit_count;
			$mask = ((1 << $bit_count) - 1) << $shift_base;

			//ењ§зё®гЃ•г‚ЊгЃ¦гЃ„г‚‹е ґеђ€гЃЁгЃ•г‚ЊгЃ¦гЃ„гЃЄгЃ„е ґеђ€гЃ§гѓ‡г‚ігѓјгѓ‰е‡¦зђ†гЃЊе¤§гЃЌгЃЏе¤‰г‚Џг‚‹
			if ($compression == 1 || $compression == 2){
				$x = 0;
				$qrt_mod2 = $bit_count >> 2 & 1;
				for(;;){
					//г‚‚гЃ—жЏЏе†™е…€гЃЊзЇ„е›Іе¤–гЃ«гЃЄгЃЈгЃ¦гЃ„г‚‹е ґеђ€гѓ‡г‚ігѓјгѓ‰е‡¦зђ†гЃЊгЃЉгЃ‹гЃ—гЃЏгЃЄгЃЈгЃ¦гЃ„г‚‹гЃ®гЃ§жЉњгЃ‘г‚‹
					//е¤‰гЃЄгѓ‡гѓјг‚їгЃЊжёЎгЃ•г‚ЊгЃџгЃЁгЃ—гЃ¦г‚‚жњЂж‚ЄгЃЄг‚±гѓјг‚№гЃ§255е›ћзЁ‹еє¦гЃ®з„Ўй§„гЃЄгЃ®гЃ§з›®г‚’зћ‘г‚‹
					if ($x < -1 || $x > $width || $y < -1 || $y > $height){
						imagedestroy($img);
						return false;
					}
					$buf = fread($stream, 1);
					if ($buf === false){
						imagedestroy($img);
						return false;
					}
					switch($buf){
					case "\x00":
						$buf = fread($stream, 1);
						if ($buf === false){
							imagedestroy($img);
							return false;
						}
						switch($buf){
						case "\x00": //EOL
							$y += $line_step;
							$x = 0;
							break;
						case "\x01": //EOB
							$y = 0;
							$x = 0;
							break 3;
						case "\x02": //MOV
							$buf = fread($stream, 2);
							if ($buf === false){
								imagedestroy($img);
								return false;
							}
							list(,$xx, $yy) = unpack("C2", $buf);
							$x += $xx;
							$y += $yy * $line_step;
							break;
						default:     //ABS
							list(,$pixels) = unpack("C", $buf);
							$bytes = ($pixels >> $qrt_mod2) + ($pixels & $qrt_mod2);
							$buf = fread($stream, ($bytes + 1) & ~1);
							if ($buf === false){
								imagedestroy($img);
								return false;
							}
							for ($i = 0, $pos = 0; $i < $pixels; ++$i, ++$x, $pos += $bit_count){
								list(,$c) = unpack("C", $buf[$pos >> 3]);
								$b = $pos & 0x07;
								imagesetpixel($img, $x, $y, $palette[($c & ($mask >> $b)) >> ($shift_base - $b)]);
							}
							break;
						}
						break;
					default:
						$buf2 = fread($stream, 1);
						if ($buf2 === false){
							imagedestroy($img);
							return false;
						}
						list(,$size, $c) = unpack("C2", $buf . $buf2);
						for($i = 0, $pos = 0; $i < $size; ++$i, ++$x, $pos += $bit_count){
							$b = $pos & 0x07;
							imagesetpixel($img, $x, $y, $palette[($c & ($mask >> $b)) >> ($shift_base - $b)]);
						}
						break;
					}
				}
			} else {
				for ($line = 0; $line < $lines; ++$line, $y += $line_step){
					$buf = fread($stream, $line_bytes);
					if ($buf === false){
						imagedestroy($img);
						return false;
					}

					$pos = 0;
					for ($x = 0; $x < $width; ++$x, $pos += $bit_count){
						list(,$c) = unpack("C", $buf[$pos >> 3]);
						$b = $pos & 0x7;
						imagesetpixel($img, $x, $y, $palette[($c & ($mask >> $b)) >> ($shift_base - $b)]);
					}
				}
			}
		} else {
			$img = imagecreatetruecolor($width, $lines);
			imagealphablending($img, false);
			if ($alpha_mask)
			{
				//О±гѓ‡гѓјг‚їгЃЊгЃ‚г‚‹гЃ®гЃ§йЂЏйЃЋжѓ…е ±г‚‚дїќе­гЃ§гЃЌг‚‹г‚€гЃ†гЃ«
				imagesavealpha($img, true);
			}

			//xи»ёйЂІиЎЊй‡Џ
			$pixel_step = $bit_count >> 3;
			$alpha_max    = $alpha_mask ? 0x7f : 0x00;
			$alpha_mask_r = $alpha_mask ? 1/$alpha_mask : 1;
			$red_mask_r   = $red_mask   ? 1/$red_mask   : 1;
			$green_mask_r = $green_mask ? 1/$green_mask : 1;
			$blue_mask_r  = $blue_mask  ? 1/$blue_mask  : 1;

			for ($line = 0; $line < $lines; ++$line, $y += $line_step){
				$buf = fread($stream, $line_bytes);
				if ($buf === false){
					imagedestroy($img);
					return false;
				}

				$pos = 0;
				for ($x = 0; $x < $width; ++$x, $pos += $pixel_step){
					list(,$c) = unpack("V", substr($buf, $pos, $pixel_step). "\x00\x00");
					$a_masked = $c & $alpha_mask;
					$r_masked = $c & $red_mask;
					$g_masked = $c & $green_mask;
					$b_masked = $c & $blue_mask;
					
					$a = $alpha_max - ((($a_masked<<7) - $a_masked) * $alpha_mask_r);
					$r = (($r_masked<<8) - $r_masked) * $red_mask_r;
					$g = (($g_masked<<8) - $g_masked) * $green_mask_r;
					$b = (($b_masked<<8) - $b_masked) * $blue_mask_r;

					
					// debug
					#var_dump("<br>", dechex($r_masked>>16), dechex($g_masked>>8), dechex($b_masked));
					
					
					if ($bit_count == 16)
						imagesetpixel($img, $x, $y, ($a<<24)|($r<<16)|($g<<8)|$b);
					else
					{
						// HarpyWar: fix for 24/32 bit color
						$color = imagecolorallocate($img, $r_masked>>16, $g_masked>>8, $b_masked);
						imagesetpixel($img, $x, $y, $color);
					}
				}
			}
			imagealphablending($img, true); //гѓ‡гѓ•г‚©гѓ«гѓ€еЂ¤гЃ«ж€»гЃ—гЃ¦гЃЉгЃЏ
		}
		return $img;
	}
}