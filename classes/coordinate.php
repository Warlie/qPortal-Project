<?PHP

function select_page_by_id($id,$cookie,$pos){
echo "$id,$cookie,$pos";


}



//zufallszahl
function random(){
$var =  46 + (mt_rand() % 65);
if ($var >= 60) {$var += 3;}
if ($var >= 91) {$var += 6;}


return $var;
}

function cookie_session($var){

if(!isset($_COOKIE[$var])){

        for($i=0;$i<10;$i++){

                if (($i % 3) == 0){
                $id = $id . (string)(mt_rand() % 10);
                }else{

                $id = $id . chr(random());
        }

}

}else{
$id = $_COOKIE[$var];
}



setCookie($var,$id,time()+3600,'/');
}



?>
