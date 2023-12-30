<?PHP

/**
*	search model for internal
*	
*/

class Internal_Searching_Model 
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
	
	public function requestArray($namespace, $tag, $attributes, $values): array 
	{
		
	}

}
?>