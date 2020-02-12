<?php


$mess = "mixa hurgin";

$im = imagecreate (169, 30);
imageinterlace ($im, 0);
//echo imageinterlace ($im);
$cfon     = ImageColorAllocate ($im, 0, 0, 255);
$cborder1 = ImageColorAllocate ($im, 192, 192, 192);
$cborder2 = ImageColorAllocate ($im, 128, 128, 128);
$ctext    = ImageColorAllocate ($im, 255, 255, 0);

$text = "Testing...Omega: &#937;";
$fontname = "../fonts/cour.ttf";
$fontname = "../fonts/vgas1255.fon";
$fontsize = 12;

ImageLine($im, 0, 0, imagesx($im), 0, $cborder2);
ImageLine($im, 0, 1, imagesx($im), 1, $cborder2);

ImageLine($im, 0, 0, 0, imagesy($im), $cborder2);
ImageLine($im, 1, 0, 1, imagesy($im), $cborder2);

ImageLine($im, imagesx($im)-1, 0, imagesx($im)-1, imagesy($im), $cborder1);
ImageLine($im, imagesx($im)-2, 0, imagesx($im)-2, imagesy($im)-1, $cborder1);

ImageLine($im, 0, imagesy($im)-1, imagesx($im)-1, imagesy($im)-1, $cborder1);
ImageLine($im, 0, imagesy($im)-2, imagesx($im), imagesy($im)-2, $cborder1);


$pic = imagecreatefromjpeg ("../img/ftp-client.jpg");
ImageCopy ($im, $pic, 15, (imagesy($im) - imagesy($pic)) / 2, 0, 0, imagesx($pic)-1, imagesy($pic)-1);

//$text_bbox = imagettfbbox ($fontsize, 0 , $fontname, $text);
//$text_pos_y = (imagesy($im) - ($text_bbox[1] * 3 - $text_bbox[7])) / 2 - $text_bbox[7];



//ImageColorTransparent($im, $cfon);
//imagettftext ($im, $fontsize, 0, 40, $text_pos_y, $ctext, $fontname, $text);

Header ("Content-type: image/jpeg");
imagejpeg ($im);
//image2wbmp ($im, "om.bmp");

ImageDestroy ($im);
ImageDestroy ($pic);

?>
