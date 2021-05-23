<?PHP

/**
*ContentGenerator
*
* @-------------------------------------------
* @title:Request
* @autor:Stefan Wegerhoff
* @description: Object to get data from sessions, requests and plattformcommands 
*
*
*/
require_once("plugin_interface.php");

class Request extends plugin 
{
	private $max_zeros = 5;
private $contentObj;
	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $reqire = array();
var $content = array();
private $file_id = array();
private $file_loc = array();
private $file_str = array();
var $obj = null;
var $tag;
var $request_name = "i";
var $doctype = 'xml';
var $case_folding = 0;
var $page_id = "";
var $setOutput = false;

var $file_handle = null;

private $mime = array(
'dwg' => 'application/acad', 	 //AutoCAD-Dateien (nach NCSA)
'asd' => 'application/astound',
'asn' => 'pplication/astound',
'tsp' => 'application/dsptype', 	//TSP-Dateien
'dxf ' => 'application/dxf ', 		//AutoCAD-Dateien (nach CERN)
'spl' => 'application/futuresplash', //Flash Futuresplash-Dateien
'gz' => 'application/gzip', 		//GNU Zip-Dateien
'js' => 'application/javascript', 	//serverseitige JavaScript-Dateien
'json' => 'application/json',		//enthält einen String in JavaScript-Objekt-Notation
'xls' => 'application/msexcel',		//Microsoft Excel Dateien
'xla' => 'application/msexcel',		//Microsoft Excel Dateien
'hlp' => 'application/mshelp',		//Microsoft Windows Hilfe Dateien
'chm' => 'application/mshelp',		//Microsoft Windows Hilfe Dateien
'ppt' => 'application/mspowerpoint',	//Microsoft Powerpoint Dateien
'ppz' => 'application/mspowerpoint',	//Microsoft Powerpoint Dateien
'pps' => 'application/mspowerpoint',	//Microsoft Powerpoint Dateien
'pot' => 'application/mspowerpoint',	//Microsoft Powerpoint Dateien
'doc' => 'application/msword',		//Microsoft Word Dateien
'dot' => 'application/msword',		//Microsoft Word Dateien
'bin' => 'application/octet-stream',	//Nicht näher spezifizierte Daten, z.B. ausführbare Dateien
'file' => 'application/octet-stream',	//Nicht näher spezifizierte Daten, z.B. ausführbare Dateien
'com' => 'application/octet-stream',	//Nicht näher spezifizierte Daten, z.B. ausführbare Dateien
'oda' => 'application/oda',			//Oda-Dateien
'pdf' => 'application/pdf',			//PDF-Dateien
'ai' => 'application/postscript',		//PostScript-Dateien
'eps' => 'application/postscript',		//PostScript-Dateien
'ps' => 'application/postscript',		//PostScript-Dateien
'rtc' => 'application/rtc',			//RTC-Dateien
'rtf' => 'application/rtf',				//RTF-Dateien
'xlsx' => 'application/vnd.openxmlformats-officedocument. spreadsheetml.sheet',	//Excel (OpenOffice Calc)
'docx' => 'application/vnd.openxmlformats-officedocument. wordprocessingml.document',	//Word (OpenOffice Writer)
'htm' => 'application/xhtml+xml',	//XHTML-Dateien
'html' => 'application/xhtml+xml',	//XHTML-Dateien
'shtml' => 'application/xhtml+xml',	//XHTML-Dateien
'xhtml' => 'application/xhtml+xml',	//XHTML-Dateien
'xml' => 'application/xml',			//XML-Dateien
'z' => 'application/x-compress',		//zlib-komprimierte Dateien
'dvi' => 'application/x-dvi',			//DVI-Dateien
'gtar' => 'application/x-gtar',		//GNU tar-Archivdateien
'php' => 'application/x-httpd-php',	//PHP-Dateien
'phtml' => 'application/x-httpd-php',	//PHP-Dateien
'latex' => 'application/x-latex',		//LaTeX-Quelldateien
'tar' => 'application/x-tar',			//tar-Archivdateien
'tcl' => 'application/x-tcl',			//TCL Scriptdateien
'tex' => 'application/x-tex',			//TeX-Dateien
'zip' => 'application/zip',			//ZIP-Archivdateien
'au' => 'audio/basic',				//Sound-Dateien
'snd' => 'audio/basic',				//Sound-Dateien
'wav' => 'audio/x-wav',				//WAV-Dateien 
'gif' => 'image/gif',					//GIF-Dateien
'png' => 'image/png',				//PNG-Dateien
'jpeg' => 'image/jpeg',				//JPEG-Dateien
'jpg' => 'image/jpeg',
'jpe' => 'image/jpeg',
'svg' => 'image/svg+xml',			//SVG-Dateien
'tiff' => 'image/tiff',					//TIFF-Dateien
'tif' => 'image/tiff'
);
/*

application/listenup 	*.ptlk 	Listenup-Dateien
application/mac-binhex40 	*.hqx 	Macintosh Binärdateien
application/mbedlet 	*.mbd 	Mbedlet-Dateien
application/mif 	*.mif 	FrameMaker Interchange Format Dateien



application/studiom 	*.smp 	Studiom-Dateien
application/toolbook 	*.tbk 	Toolbook-Dateien
application/vocaltec-media-desc 	*.vmd 	Vocaltec Mediadesc-Dateien
application/vocaltec-media-file 	*.vmf 	Vocaltec Media-Dateien

application/x-bcpio 	*.bcpio 	BCPIO-Dateien

application/x-cpio 	*.cpio 	CPIO-Dateien
application/x-csh 	*.csh 	C-Shellscript-Dateien
application/x-director 	*.dcr *.dir *.dxr 	Macromedia Director-Dateien

application/x-envoy 	*.evy 	Envoy-Dateien

application/x-hdf 	*.hdf 	HDF-Dateien

application/x-macbinary 	*.bin 	Macintosh Binärdateien
application/x-mif 	*.mif 	FrameMaker Interchange Format Dateien
application/x-netcdf 	*.nc *.cdf 	Unidata CDF-Dateien
application/x-nschat 	*.nsc 	NS Chat-Dateien
application/x-sh 	*.sh 	Bourne Shellscript-Dateien
application/x-shar 	*.shar 	Shell-Archivdateien
application/x-shockwave-flash 	*.swf *.cab 	Flash Shockwave-Dateien
application/x-sprite 	*.spr *.sprite 	Sprite-Dateien
application/x-stuffit 	*.sit 	Stuffit-Dateien
application/x-supercard 	*.sca 	Supercard-Dateien
application/x-sv4cpio 	*.sv4cpio 	CPIO-Dateien
application/x-sv4crc 	*.sv4crc 	CPIO-Dateien mit CRC

application/x-texinfo 	*.texinfo *.texi 	Texinfo-Dateien
application/x-troff 	*.t *.tr *.roff 	TROFF-Dateien (Unix)
application/x-troff-man 	*.man *.troff 	TROFF-Dateien mit MAN-Makros (Unix)
application/x-troff-me 	*.me *.troff 	TROFF-Dateien mit ME-Makros (Unix)
application/x-troff-ms 	*.me *.troff 	TROFF-Dateien mit MS-Makros (Unix)
application/x-ustar 	*.ustar 	tar-Archivdateien (Posix)
application/x-wais-source 	*.src 	WAIS Quelldateien
application/x-www-form-urlencoded 	  	HTML-Formulardaten an CGI

audio/echospeech 	*.es 	Echospeed-Dateien
audio/tsplayer 	*.tsi 	TS-Player-Dateien
audio/voxware 	*.vox 	Vox-Dateien
audio/x-aiff 	*.aif *.aiff *.aifc 	AIFF-Sound-Dateien
audio/x-dspeeh 	*.dus *.cht 	Sprachdateien
audio/x-midi 	*.mid *.midi 	MIDI-Dateien
audio/x-mpeg 	*.mp2 	MPEG-Audiodateien
audio/x-pn-realaudio 	*.ram *.ra 	RealAudio-Dateien
audio/x-pn-realaudio-plugin 	*.rpm 	RealAudio-Plugin-Dateien
audio/x-qt-stream 	*.stream 	Quicktime-Streaming-Dateien

image/bmp 	*.bmp 	Windows Bitmap-Datei
image/cis-cod 	*.cod 	CIS-Cod-Dateien
image/cmu-raster 	*.ras 	CMU-Raster-Dateien
image/fif 	*.fif 	FIF-Dateien

image/ief 	*.ief 	IEF-Dateien

image/vasa 	*.mcf 	Vasa-Dateien
image/vnd.wap.wbmp 	*.wbmp 	Bitmap-Dateien (WAP)
image/x-freehand 	*.fh4 *.fh5 *.fhc 	Freehand-Dateien
image/x-icon 	*.ico 	Icon-Dateien (z.B. Favoriten-Icons)
image/x-portable-anymap 	*.pnm 	PBM Anymap Dateien
image/x-portable-bitmap 	*.pbm 	PBM Bitmap Dateien
image/x-portable-graymap 	*.pgm 	PBM Graymap Dateien
image/x-portable-pixmap 	*.ppm 	PBM Pixmap Dateien
image/x-rgb 	*.rgb 	RGB-Dateien
image/x-windowdump 	*.xwd 	X-Windows Dump
image/x-xbitmap 	*.xbm 	XBM-Dateien
image/x-xpixmap 	*.xpm 	XPM-Dateien 

*/
	private $rst;
	private $groups = array();
	private $max ;
	private $min = 0;
	private $pos = 0;

	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$contentObj)
	{
		$this->back= &$back;
		$this->treepos = &$treepos;
		$this->contentObj = &$contentObj;
		//$this->id = $value; , &$id
		
		foreach($_REQUEST as $arrays)
			if(is_array($arrays))
				{
				$this->min = min(min(array_keys($arrays)),$this->min);
				$this->max = max(max(array_keys($arrays)),$this->max);
				}

		$this->pos = $this->min;
		//foreach ($_REQUEST as $value)if(is_($value))
	}
	
			
	/**
	*@function: MOVEFIRST = goes to first record
	*/
		
	public function moveFirst()
	{
		if($this->rst)$this->rst->moveFirst();
		$this->pos = $this->min;
		return true;
	
	}
	
	/**
	*@function: MOVELAST = goes to last record
	*/
	public function moveLast()
	{
		if($this->rst)$this->rst->moveLast();
		$this->pos = $this->max - 1 ;
		return true;
	}
	
	public function next()
	{
		
		if($this->pos < $this->max) 
			{
			$this->pos++;
			if($this->rst)$this->rst->next();
			//echo "\n";
			return true;
			}
		if($this->rst)return $this->rst->next();
		//echo "(end)\n";
		return false;
	}
	
		
	/**
	*
	*@-------------------------------------------
	*/
	//parameterausgabe
	public function getAdditiveSource(){}
	
	/**
	*@function: HAS_TAG = returns a boolean value refering to the seeking tag, descripted by xpath 
	* TODO muss auf xpath erweitert werden
	*/
	public function has_Tag()
	{
	
	if(is_null($this->template))
	{
		return 'false';
	}
	
			
	$tmpstamp = $this->generator()->XMLlist->position_stamp();
				
	$this->generator()->XMLlist->change_URI($this->template);
	$this->generator()->XMLlist->set_first_node();
					//$generator->XMLlist->cur_idx(). "id \n";
					
					//$generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']);
	$orginal = &$this->generator()->XMLlist->show_xmlelement();
	$clone = &$this->find($orginal,$value);
	if(is_null($clone->name) )
	{
		$mytemp = "false";
	}
	else
	{
		$mytemp = "true";
	}	
		
	$generator->XMLlist->go_to_stamp($tmpstamp);
	return $mytemp;
	}
	
	public function create_group($group, $members)
	{
		$this->groups[$group] = array();
		$args = explode(',',$members);
		foreach( $args as $var)
		{
			array_push($this->groups[$group],trim($var));
		}
		
	}
	
	public function has_request_value($value, $mode)
	{ 
		$res = true;
		if(!$mode)$mode = 'any';
//var_dump($_REQUEST, $value, $mode);
		if($this->groups[$value])
		{
			
			if($mode == 'any')
			{
				$res = false;
				foreach($this->groups[$value] as $var)$res = $res || boolval($_REQUEST[$var]);
			}else
				foreach($this->groups[$value] as $var)$res = $res && boolval($_REQUEST[$var]);
				
		}
		else
			$res = boolval($_REQUEST[$value]) ;
			
		  return boolval($res) ? 'true' : 'false';

	}

	public function test()
	{
		var_dump($_REQUEST);
	}
	
	public function set_start_index($index)
	{
		$this->min = $index;
		$this->pos = max($this->pos, $this->min); 
	}
	
	public function set_list(&$value)
	{
			
	if(is_object($value))
	{
		$this->rst = &$value;
	}
	}
	
	
	public function iter()
	{return $this;}
	
	
public function request($name)
 {
 	 global $logger_class;
 	if(is_array($name))
 	$res = $name[0];
 	else
 	$res = $name;
 	$logger_class->setAssert("request $res \n",0);
 	$pair = explode ( "." , $res  );
 	
 	$res = $pair[0];

 	//echo $res;
 	if(in_array($res,$this->file_id))
 	{

 		if($_FILES[$res]['error'] != UPLOAD_ERR_OK) throw new ObjectBlockException('bad upload');
 		if(!is_null($tmp = $_FILES[$res][$pair[1]]))
 		{
 			
		//echo mysql_real_escape_string($tmp);
			return htmlspecialchars($tmp);
			
		}
	return '';
 	}
 	else
 	{
 		

 		if(is_array($_REQUEST[$res]))
 		{

 			if(!is_null($tmp = $_REQUEST[$res][$this->pos]))
 			{
 				//echo "name:$res,(" . $this->pos . ") :"  . $tmp . ", ";
			return htmlspecialchars($tmp);
			}	
		}
		else
			if(!is_null($tmp = $_REQUEST[$res]))
			{

			return htmlspecialchars($tmp);
			}
		}
	return null;
 }
 
 
public function col($request_name)
{	//$this->max

	if(!is_null($tmp = $this->request($request_name)))
	{
		//echo $request_name . " :"  . $tmp . ", ";
		return $tmp;
	}
	
	if($this->rst)
	{
			//echo $request_name . " :"  . $this->rst->col($request_name) . "(Request)\n";
	return $this->rst->col($request_name);
	}
	//echo $request_name . " : null (Request)\n";
	return null;
}

public function fields()
{
	$res = array();
	foreach ($_REQUEST as $key => $value)
		{
			$res[] = $key;
		}
	return $res;


}

/*
public function in($request_name, $value)
{
	$this->back->heap['request'][$request_name] = $value;
}

public function to_eval($statement)
{
	return eval($statement);
}
public function sessionOut($session_index)
{
		if(!is_null($tmp = $_SESSION[$session_index]))
		{
		
			return $tmp;
		}
		else
		{
			$tmp = "";
			return $tmp;
		}
		
}

* @function: 	sessionIn 	: overwrites sessions, previous needs NAME to set key to value
* @param: 	$session_index 	: sessionname
* @param:	$value		: new value

public function sessionIn($session_index,$value)
		{

			$this->back->heap['session'][$session_index] = trim($value);
			$_SESSION[$session_index] = trim($value);
			
		}
		*/


		

		/**
		*@parameter: OUTPUT = boolean value true/false for giving out
		*/
/*		if($type == "OUTPUT")
		{
			if($value == 'true')
			{
				$this->setOutput = true;
				
			}
			else
			{
				$this->setOutput = false;
				
			}
		} */

		/**
		*@parameter: NAME = sets the key for requests and sessions
		*@parameter: CASE_FOLDING = sets case folding for dynamic loaded documents
		*@parameter: DOCTYPE = select parsertype for dynamic loaded documents
		*@parameter: BUILD_XML = reads a file, needs NAME to write it into session or request or a fileserviceobject to deal with data. By missing both, it loads file as a new tree
		*/

		public function build_tree($request, $doc_name, $case_folding, $doctype )
		{
			
			if(
			($_REQUEST[$request])
			|| $this->file_handle)
			{
				
				
			//$xmlinstanz = &$this->back->XMLlist;
//echo get_class($this->back);
			$this->back->setNewTree($doc_name);
				
				//if(!$this->file_handle)
				//{
				
					if($_REQUEST[$request])
					$stringBuffer = $_REQUEST[$request];
					
				
				//	else
				//	return false;
				//	$stringBuffer = $_SESSION[$id];
				//}
			

			
			//if(!$this->file_handle)
			//{

				$this->back->load_Stream(stripslashes($stringBuffer),$case_folding, $doctype);
			//}
/*
			else
			{
				if(is_subclass_of($this->file_handle,'Readable'))
				{
					//echo "ich bin hier" . get_class($this->file_handle->get_file_handler());
					$this->back->load_Stream($this->file_handle->get_file_handler(),$case_folding, $doctype ,1);
				}
			}*/
			$this->back->heap['template'][$doc_name] = $doc_name;

/*			
			if($this->setOutput)
			{
				
				$backinstanz->out_template = $value;
				
			}
*/
			
			
			}

		}

		private function get_pre_post($filename)
		{
			$file = explode ( "." , $filename  );
			$tmp = array_pop($file);
			$res[0] = implode(".", $file);
			$res[1] = $tmp;
			return $res;

			
		}
		
		/**
		*
		*	Token: 
		*		$pre			: prefix name
		*		$post			: postfix name
		*		$dec, $hex, $a	: Increment
		*		$usr, $for, $sur				: user
		*		$s, $m, $h, $d, $m, $y		:date
		*
		*	example:
		*	"$pre_$d$d$d$d$d_$u.$post"
		*/
		
		private function complete_pattern($filename, $pattern, $number)
		{
				$dec = strval($number);
				$dec = str_repeat(0, max($this->max_zeros - strlen($dec), 0)) . $dec;
				
				
				$hex =dechex($number); 
				$hex = str_repeat(0, max($this->max_zeros - strlen($hex), 0)) . $hex;
				$file = $this->get_pre_post($filename  );
			
				$needle = array( '$pre', '$post', '$usr', '$for', '$sur', '$s' , '$i', '$hex', '$h', '$dec', '$d', '$m', '$y' );
				$replace = array( $file[0], $file[1], $this->contentObj->getUser(),
					$this->contentObj->getForename(),
					$this->contentObj->getSurname(),
					date('s'),
					date('i'),
					$hex,
					date('H'),
					$dec,
					date('d'),
					date('m'),
					date('y')
					);



				$res = str_replace($needle, $replace, $pattern);

				return $res;
	
				
		}
/*		
		private function consistence($string, $pattern)
		{
		
			
		$needle = array( '$d', '$h', '$a');

		$pattern = str_replace($needle, "$d", $insert_rule);
			$res = explode ( "$d" , $pattern  );
			
			
			
		}
*/		
	public function set_as_transfer($identifier, $destination, $insert_rule)
	{
		global $_FILES;

		 // Undefined | Multiple Files | $_FILES Corruption Attack
		 // If this request falls under any of them, treat it invalid.
		 if (!isset($_FILES[$identifier]['error']) || is_array($_FILES[$identifier]['error'])) 
		 	{
		 		throw new ProgramBlockException('Invalid parameters.');
		 	}
		
		 // Check $_FILES[$identifier]['error'] value.
		 switch ($_FILES[$identifier]['error']) 
		 {
		 	 

		 	case UPLOAD_ERR_OK:
		 		break;
		 	case UPLOAD_ERR_NO_FILE:
		 		throw new ProgramBlockException('No file sent.');
		 	case UPLOAD_ERR_INI_SIZE:
		 	case UPLOAD_ERR_FORM_SIZE:
		 		throw new ProgramBlockException('Exceeded filesize limit.');
		 	case UPLOAD_ERR_PARTIAL:
		 		throw new ProgramBlockException('Upload just parts of the file');
		 	case UPLOAD_ERR_NO_TMP_DIR:
		 		throw new ProgramBlockException('Cannot create temporaty dirctory');
		 	case UPLOAD_ERR_CANT_WRITE:
		 		throw new ProgramBlockException('Write access denied');
		 	case UPLOAD_ERR_EXTENSION:
		 		throw new ProgramBlockException('A php excension throws an error');
		 	default:
		 		throw new ProgramBlockException('Unknown error:' . $_FILES[$identifier]['error']);
		 }
		 	
		$this->file_id[] = $identifier;
		$this->file_loc[] = $destination;
		$this->file_str[] = $insert_rule;
		
		if(false === ($scanned_directory = scandir($destination)))
			throw new ProgramBlockException('not a valid destination');
		else
		{
			sort($scanned_directory);
			
			$i = 1;
			$new_name = $this->complete_pattern($_FILES[$identifier]['name'], $insert_rule, $i++);
			while(false!==($found = array_search($new_name,$scanned_directory)))
				{
					$new_name = $this->complete_pattern($_FILES[$identifier]['name'], $insert_rule, $i++);
				}
					//echo  $destination . "/" . $new_name;
				if(move_uploaded_file( $_FILES[$identifier]['tmp_name']  , $destination . "/" . $new_name  )) 
				{
					//echo "send";
					$file = $this->get_pre_post($_FILES[$identifier]['name']  );
					$_FILES[$identifier]['name'] = $new_name;
					$_FILES[$identifier]['full'] = $destination . "/" . $new_name;
					$_FILES[$identifier]['mime'] = $this->get_mime($file[1]);
					$_FILES[$identifier]['datetime'] = date ("Y-m-d H:i:s", filemtime($destination . "/" . $new_name));
					$_FILES[$identifier]['hash'] = md5_file ( $destination . "/" . $new_name );

				}
				else
				{
					 $_FILES[$identifier]['error'] =  UPLOAD_ERR_EXTENSION;
					throw new ProgramBlockException('Failed to move uploaded file.');


				}
			
			
			//foreach($scanned_directory as $myfile)
			//{
			//	$myfile */
		}	
			
		//echo $this->complete_pattern($_FILES[$identifier]['name'], $insert_rule, 1);
			
//		$directory = '/path/to/my/directory';
//$scanned_directory = array_diff(scandir($directory), array('..', '.'));
		
		/*
		$needle = array( '$pre', '$post', '$i', '$u' );
		$replace = array( 'bla', '.txt', '01', 'ich' );
		$pre = " it is a test";
		$post = "post";
		$i = "01";
		$u = "me";
		$res = str_replace($needle, $replace, $insert_rule);
		echo $identifier . " is on $test " . $destination . " and the insert_rule $res  \n"; */
	}
		
	private function get_mime($ending)
	{
		if($this->mime[$ending])
				return $this->mime[$ending];
			else
				return "";
	}
	
	public function set_name($name)
	{
	$this->page_id = $value;
	}
	
	public function get_client()
	{
	if(!is_null($tmp = $_FILES[$this->page_id]['name']))
		{
		return $tmp;
		}	
		return false;
	}
	
	public function get_size()
	{
	if(!is_null($tmp = $_FILES[$this->page_id]['size']))
		{
		return $tmp;
		}	
		return false;
	}
	
	public function get_server()
	{
	if(!is_null($tmp = $_FILES[$this->page_id]['tmp_name']))
		{
		return $tmp;
		}	
		return false;
	}
	


		
}
?>
