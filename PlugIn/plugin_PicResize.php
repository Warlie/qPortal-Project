<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class PicResize extends plugin 
{

	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $reqire = array();
var $content = array();
var $altcontent = array();
var $newpath;
var $unique;
var $mysize;
var $obj = null;
var $tag;
var $test = false;

	function GK3(){}
	
	function set($type, $value)
	{
		parent::set($type, $value);
		//echo $type . ' ' . $value;

		if($type == "TEST")
		{
			
			$this->test = true;		
					
		}
		
		/**
		*@parameter: LIST = gets an object to receive data
		*/
		if($type == "LIST")
		{
			
			if(is_object($value))
			{
				$this->obj = &$value;
				
			}
		}
		/**
		*@parameter: ITER = gives out a object to LIST-parameter
		*/
		if($type == "ITER"){$this->param_out($this);$this->get_require_fields();}
		
		/**
		*@parameter: COL = gives out data to an field
		*/
		if($type == "COL")
		{
			
			if(!isset($this->res[$value]))
			{
				$this->obj->set('COL',$value);
			
				$tmp=$this->obj->out();
				
			}
			else
			{
				
				$tmp = $this->res[$value];
				
			}
			//echo '<br>' . $tmp . ' : ' . $value;
			$this->param_out($tmp);
		}



		/**
		*@parameter: CONTENT = columnname to call the filename for resizing pictures
		*/
		if($type == "CONTENT")
		{
			
			$this->content[count($this->content)] = $value;
			
			
		}
		/**
		*@parameter: ALTCONTENT = optional columnname for filenames of allready resized pictures. Original is available by contentname
		*/
		if($type == "ALTCONTENT")
		{
			
			$this->altcontent[count($this->altcontent)] = $value;
			
			
		}

		/**
		*@parameter: NEWPATH = path for resized pictures
		*/
		if($type == "NEWPATH")
		{
			
			$this->newpath=$value;
			
			
		}
		/**
		*@parameter: UNIQUE = extension of the prefix f.e. img_3442.png -> img_3442_mini.png
		*/
		if($type == "UNIQUE")
		{
			
			$this->unique=$value;
			
			
		}
		/**
		*@parameter: SIZE = WIDTH to resize picture
		*/
		if($type == "SIZE")
		{
			
			$this->mysize=$value;
			
			
		}
		
		if($type == "RUN")
		{

		}
	}
	
	function check_type($type)
	{
	if($type == "SQL")return true;
	if($type == "XMLTEMPLATE")return true;
	if($type == "COL")return true;
	//if($type == "")return true;
	return parent::check_type($type);
	}

	function next(){$return = $this->obj->next();if($return)$this->get_require_fields();return $return;}

	function get_require_fields()
	{
		
		
		$this->obj->set('COL',$this->content[0]);
		$path_string = $this->obj->out();
		
		//es werden keine unguelltigen dateien verarbeitet
		if(!file_exists($path_string))
		{
		echo "Path does not exist: $path_string (Plugin:PicResize)!\n";
			return false;
		}
		
		$path = pathinfo($path_string);
		$only_path = $path["dirname"];
		$whole_file = $path["basename"];
		$extension = $path["extension"];
		
		$file_name = basename($path_string,$path["extension"]);
		
		$file_name = substr($whole_file,0,strpos($whole_file, '.'));
		
		
		if(!$this->newpath)$this->newpath = $only_path . '/mod';
		if(!$this->unique)$this->unique = '_pic';
		if(!$this->mysize)$this->mysize = 100;
		
		$newfile = $file_name . $this->unique . '.' . $extension;
		
		if(file_exists($this->newpath . '/' . $newfile))
		{
			if(!$this->altcontent[0])
			{
				
			$this->res[$this->content[0]] = $this->newpath . '/' . $newfile;
			}
			else
			{
			
				$this->res[$this->altcontent[0]] = $this->newpath . '/' . $file_name . $this->unique . '.' . $extension;
			}			
		}
		else
		{
			$this->resize_pic($only_path . '/',	$this->newpath  . '/' , $whole_file , $newfile, $this->mysize);
			if(!$this->altcontent[0])
			{
			
				$this->res[$this->content[0]] = $this->newpath . '/' . $file_name . $this->unique . '.' . $extension;
			}
			else
			{
			
				$this->res[$this->altcontent[0]] = $this->newpath . '/' . $file_name . $this->unique . '.' . $extension;
			}
		}
		
		
	}


function resize_pic($PicPathIn,	$PicPathOut, $bild, $bild2 , $neueBreite	)
{
echo $PicPathIn;
// Bilddaten ermitteln
$size= GetImageSize("$PicPathIn"."$bild");
$breite=$size[0];
$hoehe=$size[1];
$neueHoehe= intval($hoehe*$neueBreite/$breite);

if($size[2]==1) {
// GIF
$altesBild= imagecreatefromgif("$PicPathIn"."$bild");
$neuesBild= imagecreate($neueBreite,$neueHoehe);
 imageCopyResized($neuesBild,$altesBild,0,0,0,0,$neueBreite,$neueHoehe,$breite,$hoehe);
 imageGIF($neuesBild,"$PicPathOut"."$bild2");
}

if($size[2]==2) {
// JPG
$altesBild= ImageCreateFromJPEG("$PicPathIn"."$bild");
$neuesBild= imagecreate($neueBreite,$neueHoehe);
 imageCopyResized($neuesBild,$altesBild,0,0,0,0,$neueBreite,$neueHoehe,$breite,$hoehe);
 ImageJPEG($neuesBild,"$PicPathOut"."$bild2");
}

if($size[2]==3) {
// PNG
$altesBild= ImageCreateFromPNG("$PicPathIn"."$bild");
$neuesBild= imagecreate($neueBreite,$neueHoehe);
 imageCopyResized($neuesBild,$altesBild,0,0,0,0,$neueBreite,$neueHoehe,$breite,$hoehe);
 ImagePNG($neuesBild,"$PicPathOut"."$bild2");
}
		
}
	
	
	function decription(){return "no description avaiable!";}
}
?>
