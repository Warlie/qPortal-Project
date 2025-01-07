<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
* @-------------------------------------------
* @title:DBO
* @autor:Stefan Wegerhoff
* @description: Databaseobject, needs only a columndefinition to receive data from other object
*
*/
require_once("plugin_interface.php");

class DBO extends plugin 
{
private $dbclazz = null;
private $bool_op = array();
protected $rst = null;

private $save_result = [];

private $obj = null;
private $dbencode = "utf8"; //ISO-8859-1
private $tag = array();
private $cur = array();
private $order = array();
private $statement = '';
private $last = '';
var $where = array();
var $lvl = array();
private $opStatement = array();
private $testme = array();
var $orderby = array();
var $limit = 0;
var $distinct = false;
var $asc = array(); 
private $testmode = false;

	function __construct (/* System.Database */ &$db)
	{
		$this->dbclazz = &$db;

	}
	


		
public function useProfil($profile)
		{
			$this->dbclazz->change_profile($profile);
		}
	
		/**
		*loads a recordset
		*/
public function sql($sql_Statement)
		{
			//echo $sql_Statement;
			$this->dbclazz->set_db_encode($this->dbencode);
			$this->rst = $this->dbclazz->get_rst($sql_Statement);
			//var_dump($this-rst);
			$res = $this->rst->first_ds();
			$fieldlist = $this->rst->db_field_list();
			/*
			foreach( $fieldlist as $key => $value )
			{
				echo "$key - $value \n";
			}*/
		}
		
public function setStatement($statement, $last = ''){$this->statement = $statement; $this->last= $last;}

public function mod_bool_op($pos, $new_bool)
	{
		$this->bool_op[$pos] = $new_bool;
	}

/* Becomes DNF Concept */ 
public function setWhere($val1,$op,$val2, $isString = true, $bool_op = 'AND', $lvl = 0)
	{
//echo $val2;
	$this->lvl[] = $lvl; 

	if($isString)
		$this->where[] = array($val1, $op, trim($val2)  );
	else
		$this->where[] = array($val1, $op, "'" . trim($val2) . "'" );
	
	if($bool_op)	
		$this->bool_op[] = ' ' . $bool_op . ' ';
	else
		$this->bool_op[] = ' AND ';

	}

public function setSet($val1,$op,$val2, $isString = false)
	{

	if($isString)
		$this->opStatement[] = array($val1, $op, trim($val2) );
	else
		$this->opStatement[] = array($val1, $op, "'" . trim($val2) . "'" );
	
	$this->bool_op[] = ' AND ';


	}
	
	/*
		val1 : column
	*/
public function setWhereBetween($val1,$param1,$param2, $format)
	{
		if(!$format)
		{
		$sep = '';
		if(! is_numeric($param1) )$sep = '\''; 
			

		$this->where[] = array( '(' . $val1, "BETWEEN $sep$param1$sep", "AND $sep$param2$sep )" );

		}
		elseif(strtolower($format) == 'date')$this->where[] = array( '(' . $val1, "BETWEEN CAST(\"$param1\" AS DATE)", "AND CAST(\"$param2\" AS DATE) )" ); //"YYYY-MM-DD"
		elseif(strtolower($format) == 'datetime')$this->where[] = array( '(' . $val1, "BETWEEN CAST(\"$param1\" AS DATETIME)", "AND CAST(\"$param2\" AS DATETIME) )" ); //"YYYY-MM-DD HH:MM:SS"
		elseif(strtolower($format) == 'time')$this->where[] = array( '(' . $val1, "BETWEEN CAST(\"$param1\" AS TIME)", "AND CAST(\"$param2\" AS TIME) )" ); //"HH:MM:SS"

		$this->lvl[] = 0; 	
		$this->bool_op[] = ' AND ';
	}

public function setOrderBy($columnName, $sort = 'asc')
	{

		$this->orderby[] = $columnName;
		if(is_null($sort))$sort = 'asc';
		if(strtolower(substr($sort,0,3)) == 'asc' || !$sort)
		  $this->asc[] = true;
		else
		  $this->asc[] = false;
		
	}
	
public function setDistinct()
	{
		$this->distinct = true;
	}
	
public function setLimit($num)
	{

		$this->limit =  $num;
		
	}

public function setWhereRST($name, $op, &$rst, $col, $isString = true)
{
	//echo  $name . " ";
	$bool_op = 'AND';
	$rst->moveFirst();
	do
		{
			$this->setWhere($name,$op,$rst->col($col), $isString, $bool_op,1);
			$bool_op = 'OR';
		}
		while($rst->next());
		
		$this->lvl[count($this->lvl) - 1] = 0;
	
}
	
	
	/**
	* TODO Nested function instead of last
	*/

public function execute()
		{
			global $logger_class;

			$my_lvl = 0; $i = 0;
	

			
			$this->dbclazz->set_db_encode($this->dbencode);

   //var_dump($this->where);
      //var_dump($this->lvl);
			
      
      
			$sql = trim($this->statement);
			if($this->distinct && (strtoupper(substr($sql,0, 6)) == "SELECT"))
				$sql = "SELECT DISTINCT" . substr($sql,6);
				
			
			
			if(count($this->where) > 0)
			{
			$sql .= ' WHERE ';
			for($i = 0; count($this->where) > $i; $i++ )
			{
			if($i <> 0)$sql .= $this->bool_op[$i];
				if($this->lvl[$i] > $my_lvl)
				{
					$sql .= '(';
					$my_lvl =$this->lvl[$i];
				}

			$sql .=  $this->where[$i][0] . ' ' . $this->where[$i][1] . ' ' . $this->where[$i][2];
			
				if($this->lvl[$i] < $my_lvl)
				{
					$sql .= ')';
					$my_lvl =$this->lvl[$i];
				}
			//echo $this->where[$i][0] . ' ' . $this->where[$i][1] . ' ' . $this->where[$i][2];
			}			

				if($my_lvl != 0)
				{
					$sql .= ')';
				}
			
			}

			if(count($this->orderby) > 0)
			{
			$sql .= ' ORDER BY ';
			$tmp = array();
			
			for($i = 0; count($this->orderby) > $i; $i++ )
			{
			if(!$this->asc[$i])
				$tmp[] = $this->orderby[$i] . ' DESC';
			else
				$tmp[] = $this->orderby[$i] . ' ASC';				
			} 

			$sql .=  ' ' . implode(",",$tmp);
			
			}
			
			if($this->limit > 0) $sql .=  ' LIMIT ' .  $this->limit;

			$sql .= $this->last . ';';
			if($this->testmode)echo $sql . "\n";

			$this->rst = $this->dbclazz->get_rst($sql);
			if($this->testmode)echo "sql statement has effected:" . $this->rst->rst_num() . " records!\n";;
			$this->rst->first_ds();
		}

public function execute_no_result()
		{
			$this->dbclazz->set_db_encode($this->dbencode);
			$sql = $this->statement;
			
			if(count($this->opStatement) > 0)
			{
			$sql .= ' SET ';
			for($i = 0; count($this->where) > $i; $i++ )
			{
			if($i <> 0)$sql .= $this->bool_op[$i];
			$sql .=  $this->opStatement[$i][0] . ' ' . $this->opStatement[$i][1] . ' ' . $this->opStatement[$i][2];
			}
			}
			
			if(count($this->where) > 0)
			{
			$sql .= ' WHERE ';
			for($i = 0; count($this->where) > $i; $i++ )
			{
			if($i <> 0)$sql .= $this->bool_op[$i];
			$sql .=  $this->where[$i][0] . ' ' . $this->where[$i][1] . ' ' . $this->where[$i][2];
			}
			}

			if(count($this->orderby) > 0)
			{
			$sql .= ' ORDER BY ';
			$tmp = array();
			for($i = 0; count($this->orderby) > $i; $i++ )
			{
			if($this->asc[$i])
				$tmp[] = $this->orderby[$i] . ' DESC';
			else
				$tmp[] = $this->orderby[$i] . ' ASC';				
			}

			$sql .=  ' ' . implode(",",$tmp);

			}

			$sql .= $this->last . ';';
			//echo $sql . " booooooooooooja";

			$this->dbclazz->SQL($sql);
			
		}
		
public function loadfile($filename)
		{	
			$this->dbclazz->loadfile($filename);
		}
		
/**
*@parameter: dbEncode = selects formats like UTF-8
*/
public function dbEncode($encoding)
		{
			
			$this->dbencode = $encoding;
			
			
		}
		
/**
*@function: MOVEFIRST = goes to first record
*/
public function moveFirst()
		{
			if($this->obj)$this->obj->moveFirst();
		return $this->rst->first_ds();}
		
		
		/**
		*@function: MOVELAST = goes to last record
		*/
public function moveLast()
		{
		if($this->obj)$this->obj->moveLast();
		return $this->rst->last_ds();}
		
		/**
		*@function: FREESQL = Free SQL Statement without result
		*/
public function freeSQL($sql_statement)
		{
			
			$this->dbclazz->SQL($sql_statement);
			
			
		}
		
		public function set_list(&$value)
		{
			
		if(is_object($value))
		{

			$this->obj = &$value;
		}
		}
		
		/**
		*@parameter: LIST = gets an object to receive data
		*/
public function get_list(&$instance )
		{
		
			if( $instance instanceof plugin )
				$this->obj = &$instance;
				
		}
		
		/**
		*@parameter: ITER = gives out a object to LIST-parameter
		*/
public function &iter()
{

return $this;}

public function test(){echo "test (" . $this->many() . ")";}

public function setTestmode($bool){	$this->testmode = boolval($bool);	}
		/**
		*@parameter: COL = gives out data to an field
		*/
public function get_col($col_name){//echo $col_name . " " .  strval($this->rst->value($col_name)) . '-' .  $this->many(); 
	return strval($this->rst->value($col_name));// . "blah";
}
		//TODO Problem mit 0 und Baum
public function col($col_name){return $this->rst->value($col_name);}

public function datatype($columnname){return $this->rst-> type($columnname);}
public function fields()
{
	$res = $this->rst->db_field_list();
	if(count($res)>0)$res = $res[0];
	return $res;
}
		/** 
		*@parameter: tag_name = name for column in plug in
		*@parameter: field_name = fieldname in db
		*@parameter: content = name of column in piped in plugin [optional]
		*@parameter: value = constant value in column [optional]
		*/
		
public function set_Column_config($tag_name, $field_name,$content, $value, $datatype)
{
	
	global $logger_class;
	//echo "Argumente tag_name: $tag_name, field_name: $field_name, content: $content, value: $value \n";
	$logger_class->setAssert("Argumente tag_name: $tag_name, field_name: $field_name, content: $content, value: $value \n",0);
	$this->cur = $tag_name;
	$this->order[] = $tag_name;
	$this->tag[$this->cur]['field'] = $field_name;
	if($content)$this->tag[$this->cur]['content'] = $content;
	if($value)$this->tag[$this->cur]['value'] = $value;
	if($datatype)$this->tag[$this->cur]['datatype'] = $datatype;
}



		/**
		*
		*@parameter: MANY = many of rows
		*@-------------------------------------------
		*/
public function many(){if(!$this->rst)return -1; return $this->rst->rst_num();}

public function entry_exists($column, $value)
	{
		return ($this->rst->find($column, $value) !== false)?'true':'false';
	}

public function set_mode($mode)
{

	if($mode == 'UpdateAndInsert')$this->rst->set_mode(WorkModes::WMUpdateAndInsert);
	if($mode == 'Update')$this->rst->set_mode(WorkModes::WMUpdate);
	if($mode == 'Insert')$this->rst->set_mode(WorkModes::WMInsert);
	if($mode == 'Delete')$this->rst->set_mode(WorkModes::WMDelete);
}
	
public function saves_dataset_back()
		{
		 global $logger_class;

		 if($this->testmode)echo "booho";
		//	$this->rst->show_content();


		// starts, when a plugin is connected
		if(!is_Null($this->obj))
		{

			if(!$this->obj->moveFirst())return false;

			        //moves to first recordset
				$this->rst->first_ds();
				
				$h = 0;
				$field = array();
				

				// create an associative list of values
				do{

				$field[$h] = array();
				for($i=0;$i < count($this->order);$i++)


					// runns through all names and checks their type to be content, which calls it's value and the static value
					if(!is_null($this->tag[$this->order[$i]]['content']) && is_null($this->tag[$this->order[$i]]['value']))

						//content case
						$field[$h][$this->tag[$this->order[$i]]['field']] = $this->convert($this->obj->col($this->tag[$this->order[$i]]['content']),  $this->rst->type($this->tag[$this->order[$i]]['field']));

					else
						//value case
						$field[$h][$this->tag[$this->order[$i]]['field']] = $this->convert( $this->tag[$this->order[$i]]['value'],  $this->rst->type($this->tag[$this->order[$i]]['field']) );


					
					
					$h++;
				}while($this->obj->next());


				
				$prim = $this->rst->prim_field();

				
		/*
		*	cycle, that runs over an array ([num][column-name] = value) of collected data. 
		*/
		for($hr = 0;$hr < count($field);$hr++)
		{			


	
							for($i=0;$i < count($this->order);$i++)
							{
								$this->rst->setValue(
									$this->tag[$this->order[$i]]['field'], //Tag contains the field name in relation to it's tag name 
									$field[$hr][$this->tag[$this->order[$i]]['field']]	// and its value						
									);

							}
							$this->rst->update();
				
			}	

			if($this->testmode)

			$this->rst->show_content();
			else
			{
				$res = $this->dbclazz->insert_rst($this->rst);
				
				foreach($res as $line)
					{
						$this->save_result[$line['tbl']] = $line['ID'];
					}
			}

			
		}
		else
			$logger_class->setAssert("plugin_dbo.php#saves_dataset_back: no plugin Obj found",0);
				
	}
		
	public function is_new_record($tbl)
	{
		return array_key_exists($tbl,$this->save_result ) && $this->save_result[$tbl] != 0;
	}

	public function next_ID($tbl)
	{
		return $this->save_result[$tbl];
	}
	
	private function convert($in, $dataset)
	{
		/*
		if(false !== strpos($dataset , 'tinyint') )
		{
			if($in)
				return 'true';
			else
				return 'false';
		}
		*/
			
		return $in;
	}
	
	function check_type($type)
	{
	if($type == "SQL")return true;
	if($type == "XMLTEMPLATE")return true;
	if($type == "COL")return true;
	//if($type == "")return true;
	return parent::check_type($type);
	}

	public function next(){$this->rst->next_ds();return !$this->rst->EOF();}
	public function prev(){$this->rst->prev_ds();return !$this->rst->BOF();}
	function decription(){return "no description avaiable!";}
	public function getAdditiveSource(){}
}
?>
