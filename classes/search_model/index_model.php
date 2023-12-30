<?PHP

require_once('xpath/xpath_model.php');

class SearchingModelObject
{

	var $nativ;
	var $node = array();
	var $attrib;
	public static array $models = [];
	public static $treeRef;
	
	public static function init_models()
	{
		
		echo get_class(SearchingModelObject::$treeRef) . "\n";
		    $filescanner = new File_Scan();
			
			//$filescanner->insert_str($str_source, $this->attribute_values['URI']);
			$filescanner->add_path('classes/search_model/', 1);
			$filescanner->add_fix('*.php');
			$filescanner->add_fix('*.xml');
			//$filescanner->add_tag('class ');
			//$filescanner->add_tag('function ');
			//$filescanner->switch_cross_seek(array('include("','")'));
			//$filescanner->switch_cross_seek(array('require("','")'));
			//$filescanner->switch_cross_seek(array('require_once("','")'));
			$filescanner->seeking();
			print_r($filescanner->result());

	}
	
	public static function &model_factory($model_name)
	{

		
		switch($model_name)
		{
			
			case 'xpath' :
			return new XPath();

			default :
				
			return null;
		}
	}
	


	
}

?>