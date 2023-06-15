<?php

if($_REQUEST['key'] != "JADH812730hjasd09812u3hjndas089u123jn")	die('Missing key argument');


error_reporting(-1);
ini_set('display_errors', 1);
require_once("php_pel/autoload.php");
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelTiff;
use lsolesen\pel\PelEntryUserComment;

echo "<pre>";

$stamp = imagecreatefrompng("wp-content/uploads/2013/12/logo_sdis-serine.png");
$sx = imagesx($stamp);
$sy = imagesy($stamp);
$marge_right = 20;
$marge_bottom = 20;

$photographers = array(
	"XX" => "",
	"BW" => "Bastien Wahlen",
	"GM" => "Geoffrey Meier", 
	"RB" => "Roxane Stella Bolay", 
	"FM" => "Ferdinand Martin",
	"DT" => "Davide Troiano",
	"AJ" => "Aurélien Joye",
	"DC" => "Daniel Cho",
	"LW" => "Léonie Wahlen",
	"MN" => "Mathilde Nicoud",
	"SG" => "Sabrina Gervaix",
	"SC" => "Stéphanie Callier",
	"XM" => "Xavier Muff",
	"DM" => "Damien Mann",
	"DC" => "Daniel Chavez",
	"DR" => "David Romero",
	"VB" => "Véronique Baechler",
	"ND" => "Nikhil Duella",
	"JV" => "Julien Vogt",
	"CM" => "Chiara Magnenat"
	"CI" => "Chef d'intervention"
);

$files = glob("wp-content/photos/*/*/*.*" );
$files = preg_grep("/.jpg$/i",$files);
//print_r($files);die();
ini_set('memory_limit', '512M');

foreach($files as $f){

	$fs = explode("/", $f);

		if(exif_imagetype($f) == IMAGETYPE_JPEG) {
			$exif = exif_read_data ($f);
			$exifRead = true;
		} else {
			$exifRead = false;
		}
		if( $exifRead && (!isset($exif['COMPUTED']['UserComment']) || $exif['COMPUTED']['UserComment'] != "SDIS Copyright")){
			if( ! preg_match('/^(COC|EXE|INT|MAN|VEC)_20[0-9]{2}-[0-9]{2}-[0-9]{2}_[^_]+_[A-Z]{3}_[A-Z]{2}_[0-9]{3,6}.(jpg|JPG)$/', $fs[4])){
				echo "Format nom incorrect : ".$fs[4]."\n";
				continue;
			}
			$p = explode('_', $fs[4]);

			if( ! isset($photographers[$p[4]])){
				echo "Photographe inconnu : ".$p[4]." - ".$fs[4]."\n";
				continue;
			}

			$vigdir = $fs[0]."/".$fs[1]."/".$fs[2]."/".$fs[3]."/vig";
			if( ! is_dir($vigdir)){
				mkdir($vigdir); 
			}
			$d = explode('-', $p[1]);


			$im = imagecreatefromjpeg($f);
			$w = imagesx($im);
			$h = imagesy($im);
			$new_w_t = 400;
			$new_h_t = (400 * $h) / $w;
			$new_w_i = min(2000,$w);
			$new_h_i = ($new_w_i * $h) / $w;

			$thumb = imagecreatetruecolor($new_w_t, $new_h_t);
			$image = imagecreatetruecolor($new_w_i, $new_h_i);
			
			imagecopyresized($image, $im, 0, 0, 0, 0, $new_w_i, $new_h_i, $w, $h);



			$text = "© SDIS Gland Serine - ".$d[0]." - ".$photographers[$p[4]];
			$font = "arial.ttf";
			$white = imagecolorallocate($image, 255, 255, 255);
			$black = imagecolorallocate($image, 0,0,0);

			$fontsize = (int)(($new_h_i/100)*1.5);
			$dimensions = imagettfbbox($fontsize, 0, $font, $text);
			$textWidth = abs($dimensions[4] - $dimensions[0]);
			$x = $new_w_i - $textWidth;
			imagettftext($image, $fontsize, 0, $x - 29, $new_h_i - 29, $black, $font, $text);		
			imagettftext($image, $fontsize, 0, $x - 30, $new_h_i - 30, $white, $font, $text);		
			imagejpeg($image, $f,80);
		
			imagecopyresized($thumb, $im, 0, 0, 0, 0, $new_w_t, $new_h_t, $w, $h);
			$fontsize = 8;
			$dimensions = imagettfbbox($fontsize, 0, $font, $text);
			$textWidth = abs($dimensions[4] - $dimensions[0]);
			$x = $new_w_t - $textWidth;
			imagettftext($thumb, $fontsize, 0, $x - 9, $new_h_t - 9, $black, $font, $text);		
			imagettftext($thumb, $fontsize, 0, $x - 10, $new_h_t - 10, $white, $font, $text);		
			imagejpeg($thumb, $vigdir."/".$fs[4],80);
	
		
		    $jpeg = new PelJpeg($f);
		    $exif = new PelExif();
		    $jpeg->setExif($exif);
		    $tiff = new PelTiff();
		    $exif->setTiff($tiff);
		    $ifd0 = new PelIfd(PelIfd::IFD0);
		    $tiff->setIfd($ifd0);
		
		    $exif_ifd = new PelIfd(PelIfd::EXIF);
		    $exif_ifd->addEntry(new PelEntryUserComment("SDIS Copyright"));
		    $ifd0->addSubIfd($exif_ifd);
		    file_put_contents($f, $jpeg->getBytes());
			echo $fs[4]." - ok\n";
		}
			
	


}
die('-- DONE --');