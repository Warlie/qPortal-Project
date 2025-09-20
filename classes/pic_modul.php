<?
$PicPathIn="../bilder/";
$PicPathOut="../bilder/out/";

// Orginalbild
$bild="Foto.jpg";

// Bilddaten ermitteln
$size= GetImageSize("$PicPathIn"."$bild");
$breite=$size[0];
$hoehe=$size[1];
$neueBreite=100;
$neueHoehe= intval($hoehe*$neueBreite/$breite);

if($size[2]==1) {
// GIF
$altesBild= imagecreatefromgif("$PicPathIn"."$bild");
$neuesBild= imagecreate($neueBreite,$neueHoehe);
 imageCopyResized($neuesBild,$altesBild,0,0,0,0,$neueBreite,$neueHoehe,$breite,$hoehe);
 imageGIF($neuesBild,"$PicPathOut"."TN"."$bild");
}

if($size[2]==2) {
// JPG
$altesBild= ImageCreateFromJPEG("$PicPathIn"."$bild");
$neuesBild= imagecreate($neueBreite,$neueHoehe);
 imageCopyResized($neuesBild,$altesBild,0,0,0,0,$neueBreite,$neueHoehe,$breite,$hoehe);
 ImageJPEG($neuesBild,"$PicPathOut"."TN"."$bild");
}

if($size[2]==3) {
// PNG
$altesBild= ImageCreateFromPNG("$PicPathIn"."$bild");
$neuesBild= imagecreate($neueBreite,$neueHoehe);
 imageCopyResized($neuesBild,$altesBild,0,0,0,0,$neueBreite,$neueHoehe,$breite,$hoehe);
 ImagePNG($neuesBild,"$PicPathOut"."TN"."$bild");
}

echo "Altes Bild:<BR>";
echo "<IMG SRC=\"$PicPathIn$bild\" WIDTH=\"$breite\" HEIGHT=\"$hoehe\"><BR><BR>";
echo "Neues Bild:<BR>";
$Thumbnail=$PicPathOut."TN".$bild;
echo "<IMG SRC=\"$Thumbnail\" WIDTH=\"$neueBreite\" HEIGHT=\"$neueHoehe\">";
?> 
