<?PHP

/**
*	search model for XPath
*/

class XPath 
{
	private $data_model;
	
	public function __construct(&$data_model)
	{
		$this->data_model = &$data_model;
	}
	
	public function query(string $statement): array
	{
		return null;
	}

}
?>