<?PHP

/*
irrelevant old file. Should be removed from project 
*/

        define("SPLIT", "\r\n");

               function convertKeyFromWrapper($line){
                $result = trim(substr($line, 0, strpos($line, ":")));
                
                return $result;
        }
        
       function convertValueFromWrapper($line){
                $result = trim(substr($line, strpos($line, ":")+1));
                
                return $result;
        }
        
        function createFromWrapper($wrapper_data){
                $result = new Header();
                
                for($i=0; $i<count($wrapper_data); $i++){
                        $result->putValue(convertKeyFromWrapper($wrapper_data[$i]), convertValueFromWrapper($wrapper_data[$i]));
                }
                
                return $result;
        }
        
        function convertKeysForInput($key){
                $result = strtolower($key);
                
                if($result == "set-cookie"){
                        $result = "cookie";
                }
                
                return $result;
        }

                define("METHOD","method");
                define("CONTENT", "content");
                define("HEADER", "header");
                define("METHOD_POST", "POST");
                define("METHOD_GET", "GET");
                
class HTTP{


        var $method = "";
        var $content = "";
        var $header = null;
        
        function __construct($method = METHOD_GET, $content = "", $header = null){
                $this->method = $method;
                $this->content = $content;
                $this->header = $header;
        }
        
        function convertForParams(){
                $result = array();
                
                $result[METHOD] = $this->method;
                $result[CONTENT] = $this->content;
                
                if($this->header){
                        $result[HEADER] = $this->header->convertForHTTP();
                }
                
                return $result;
        }
}

                define("HTTP", "http");

class Params{
 
        
        var $http = null;
        
        function __construct($http = null){
                $this->http = $http;
        }
        
        function convertStream(){
                $result = array();
                
                if($this->http){
                        $result[HTTP] = $this->http->convertForParams();
                }
                
                return $result;
        }
}

class Header{
        

        

        var $value = array();
        
        function __construct(){}
       
        function putValue($key, $value){
                $this->value[convertKeysForInput($key)] = $value;
        }
        
        function getValue($key){
                return $this->value[convertKeysForInput($key)];
        }
        

                
        function convertKeysForOutPut($key){
                $result = "";
                
                $tmp = split("-", $key);
                for($i=0; $i<count($tmp); $i++){
                        if(strlen($result) > 0){
                                $result.= "-";
                        }
                        $result.= strtoupper(substr($tmp[$i],0,1)).strtolower(substr($tmp[$i],1));
                }
                
                return $result;
        }
        
        function convertForHTTP(){
                $result = "";
                
                foreach($this->value as $key => $value){
                        $result.= Header::convertKeysForOutPut($key).": ".$value. SPLIT;
                }
                
                return $result;
        }
        

        

}


        
        //define("ATTRIBUTE","");
        
        define("HOST_KV",""); // HTTPS
        define("HOST_IBE","");// <-- Default
        define("HOST_DEMO","");
        define("HOST_LOCAL","localhost:8080");

        define("PATH_HOST_KV","");
        define("PATH_HOST_IBE","");
        define("PATH_HOST_DEMO","");
        define("PATH_HOST_LOCAL","");
                
                
        function savePaxXML($paxxml){
                $_SESSION[ATTRIBUTE] = serialize($paxxml);
        }
         
        function createXML($host = HOST_KV, $httpRequest =METHOD_POST){
                session_start();
        
                $interface = isset($_SESSION[ATTRIBUTE]) ? unserialize($_SESSION[ATTRIBUTE]) : null;
                if($interface == null){
                        $interface = new Dynamic_XML($host, $httpRequest);
                }
                
                return $interface;
        }
        
        function getPath($host){
                $result = "";
        
                if($host == HOST_KV){
                        $result = PATH_HOST_KV;
                }else if($host == HOST_IBE){
                        $result = PATH_HOST_IBE;
                }else if($host == HOST_DEMO){
                        $result = PATH_HOST_DEMO;
                }else if($host == HOST_LOCAL){
                        $result = PATH_HOST_LOCAL;
                }
        
                return $result;
        }
        
                
class Dynamic_XML{

        
         var $host = "";
         var $httpRequest = "";
         var $infoLastHeader = "";
         
         var $cookie = null;
         
         function PaxXML($host = HOST_IBE, $httpRequest = METHOD_POST){
                 $this->host = $host;
                 $this->httpRequest = $httpRequest;
         }
         
         function getInfoLastHeader(){
                 return $this->infoLastHeader;
         }
         
         function getHost(){
                 return $this->host;
         }
         
         function getStreamContext($data){
                 $data_to_send = ATTRIBUTE."=".$data;

                 $header = new Header();
                 {
                         if($this->cookie){
                                 $header->putValue("Cookie", $this->getSessionType()."=".$this->getSessionId());
                         }
                         $header->putValue("Content-Length", strlen($data_to_send));
                         $header->putValue("Content-Type", "application/x-www-form-urlencoded");
                         //$header->putValue("Connection", "close");
                 }

                 $http = new HTTP($this->httpRequest, $data_to_send, $header);
                 $params = new Params($http);

                 return @stream_context_create($params->convertStream());
         }
         
         function getCookie(){
                 return $this->cookie;
         }
         
         function getSessionId(){
                 $result = "";
                 if($this->getCookie()){
                         $tmp = split("=", $this->getCookie());
                         $tmp = split(";", $tmp[1]);
                         $result = trim($tmp[0]);
                 }
                 return $result;
         }
         
         function getSessionType(){
                 $result = "";
                 if($this->getCookie()){
                         $tmp = split("=", $this->getCookie());
                         $result = trim($tmp[0]);
                 }
                 return $result;
         }
         
         function getSessionSavePath(){
                 $result = "";
                 if($this->getCookie()){
                         $tmp = split(";", $this->getCookie());
                         $result = trim($tmp[1]);
                 }
                 return $result;
         }
         
         function getResponse($data, $returnAsXML = false, $encodeXML = "utf-8"){
                 $result = null;
                 
                 if(get_class($data) == "DOMDocument"){
                         $data = $data->saveXML();
                 }
                 
                 { // loading
                         $url = "http://".$this->host.getPath($this->host);
                         
                         $handle = @fopen($url, 'rb', false, $this->getStreamContext($data));
                        
                         $buffer = "";
                         while (!feof($handle)) {
                                 $buffer .= fgets($handle, 4096);
                                
                         }
                         
                         $result = $buffer;
                         //$result = urldecode($buffer);
                         return $result;
                }
                 

         }
         

 }




?>
