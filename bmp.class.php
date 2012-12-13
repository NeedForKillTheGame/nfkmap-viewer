<?php
// Read 1,4,8,24,32bit BMP files 
// Save 24bit BMP files

// Author: de77
// Licence: MIT
// Webpage: de77.com
// Article about this class: http://de77.com/php/read-and-write-bmp-in-php-imagecreatefrombmp-imagebmp
// First-version: 07.02.2010
// Version: 21.08.2010

class BMP
{
	public static function imagebmp(&$img, $filename = false)
	{
		$wid = imagesx($img);
		$hei = imagesy($img);
		$wid_pad = str_pad('', $wid % 4, "\0");
		
		$size = 54 + ($wid + $wid_pad) * $hei * 3; //fixed
		
		//prepare & save header
		$header['identifier']		= 'BM';
		$header['file_size']		= self::dword($size);
		$header['reserved']			= self::dword(0);
		$header['bitmap_data']		= self::dword(54);
		$header['header_size']		= self::dword(40);
		$header['width']			= self::dword($wid);
		$header['height']			= self::dword($hei);
		$header['planes']			= self::word(1);
		$header['bits_per_pixel']	= self::word(24);
		$header['compression']		= self::dword(0);
		$header['data_size']		= self::dword(0);
		$header['h_resolution']		= self::dword(0);
		$header['v_resolution']		= self::dword(0);
		$header['colors']			= self::dword(0);
		$header['important_colors']	= self::dword(0);
	
		if ($filename)
		{
		    $f = fopen($filename, "wb");
		    foreach ($header AS $h)
		    {
		    	fwrite($f, $h);
		    }
		    
			//save pixels
			for ($y=$hei-1; $y>=0; $y--)
			{
				for ($x=0; $x<$wid; $x++)
				{
					$rgb = imagecolorat($img, $x, $y);
					fwrite($f, self::byte3($rgb));
				}
				fwrite($f, $wid_pad);
			}
			fclose($f);
		}
		else
		{
		    foreach ($header AS $h)
		    {
		    	echo $h;
		    }
		    
			//save pixels
			for ($y=$hei-1; $y>=0; $y--)
			{
				for ($x=0; $x<$wid; $x++)
				{
					$rgb = imagecolorat($img, $x, $y);
					echo self::byte3($rgb);
				}
				echo $wid_pad;
			}
		}	
	}
	
	public static function imagecreatefrombmp($filename)
	{
		$file = fopen($filename, "rb");
		$read = fread($file, 10);
		while (!feof($file) && ($read <> ""))
			$read .= fread($file, 1024);
			
		return imagecreatefrombmpstream($read);
	}
	
	// http://www.craiglotter.co.za/2011/05/06/php-create-image-object-from-bmp-file-with-imagecreatefrombmp-function/
	public static function imagecreatefrombmpstream($stream) 
	{
		$temp = unpack("H*", $stream);
		$hex = $temp[1];
		$header = substr($hex, 0, 108);
		if (substr($header, 0, 4) == "424d") {
			$header_parts = str_split($header, 2);
			$width = hexdec($header_parts[19] . $header_parts[18]);
			$height = hexdec($header_parts[23] . $header_parts[22]);
			unset($header_parts);
		}
		$x = 0;
		$y = 1;
		$img = imagecreatetruecolor($width, $height);
		$body = substr($hex, 108);
		$body_size = (strlen($body) / 2);
		$header_size = ($width * $height);
		$usePadding = ($body_size > ($header_size * 3) + 4);
		for ($i = 0; $i < $body_size; $i+=3) {
			if ($x >= $width) {
				if ($usePadding)
					$i += $width % 4;
				$x = 0;
				$y++;
				if ($y > $height)
					break;
			}
			$i_pos = $i * 2;
			$r = hexdec($body[$i_pos + 4] . $body[$i_pos + 5]);
			$g = hexdec($body[$i_pos + 2] . $body[$i_pos + 3]);
			$b = hexdec($body[$i_pos] . $body[$i_pos + 1]);
			$color = imagecolorallocate($img, $r, $g, $b);
			imagesetpixel($img, $x, $height - $y, $color);
			$x++;
		}
		unset($body);
		return $img;
	}

	private static function byte3($n)
	{
		return chr($n & 255) . chr(($n >> 8) & 255) . chr(($n >> 16) & 255);	
	}
	
	private static function undword($n)
	{
		$r = unpack("V", $n);
		return $r[1];
	}
	
	private static function dword($n)
	{
		return pack("V", $n);
	}
	
	private static function word($n)
	{
		return pack("v", $n);
	}
}

function imagebmp(&$img, $filename = false)
{
	return BMP::imagebmp($img, $filename);
}

function imagecreatefrombmp($filename)
{
	return BMP::imagecreatefrombmp($filename);    
}	
function imagecreatefrombmpstream($stream)
{
	return BMP::imagecreatefrombmpstream($stream);    
}

function hexcoloralloc($im, $hex)
{ 
  $a = hexdec(substr($hex,0,2)); 
  $b = hexdec(substr($hex,2,2)); 
  $c = hexdec(substr($hex,4,2)); 

  return imagecolorallocate($im, $a, $b, $c); 
} 
// draw arrow
function arrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));

    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

    $k = $awidth / $alength;

    $x2o = $x2 - $dx;
    $y2o = $dy - $y2;

    $x3 = $y2o * $k + $dx;
    $y3 = $x2o * $k + $dy;

    $x4 = $dx - $y2o * $k;
    $y4 = $dy - $x2o * $k;

    imageline($im, $x1, $y1, $dx, $dy, $color);
    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
}