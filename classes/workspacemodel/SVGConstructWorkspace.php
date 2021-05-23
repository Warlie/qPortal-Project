<?PHP



class SVGConstructWorkspace implements ControlUnit {
	
private $my_Xml_Object;
private $workspace;
private $system;
private $registry;
private $reg_stamp = '';
private $idx = 1;
private $objectList = array();

	function __construct(xml_ns &$nsobj,$workspaceName)
	{
	$this->workspace = $workspaceName;
	$nsobj->setControlUnit($this);
	$this->my_Xml_Object = &$nsobj;
	$this->registry = $nsobj->get_context_generator()->get_Reg_NS();
	$this->system = $nsobj->get_context_generator()->getXMLStructur();
	
	$this->my_Xml_Object->setNewTree($this->workspace);
	$this->my_Xml_Object->set_definition_context('TYPE','XML');
	$this->my_Xml_Object->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
	$this->my_Xml_Object->set_definition_context('DOC','');
	$this->idx = $this->my_Xml_Object->cur_idx();
	$this->build_up();
		
	$this->my_Xml_Object->change_idx(0);
	
	}
	
	public function setObjectByID(&$obj,$id)
	{
		$this->objectList[$id] = &$obj;
	}
	public function getObjectByID($id){return $this->objectList[$id];}
	public function getName(){return $this->workspace;}
        public function getRegistrySpace(){return $this->registry;}
        public function getSystemSpace(){return $this->system;}
	public function getTreeIdent(){return $this->workspace;}
	public function getIDX(){return $this->workspace;}
	
	function load_structur($stuctur,$registry)
	{
		//echo $stuctur . 'booh';
		$this->system = $structur;
		$this->registry = $registry;
/*
		$this->my_Xml_Object->setNewTree($this->workspace);
		$this->my_Xml_Object->set_definition_context('TYPE','XML');
		$this->my_Xml_Object->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
		$this->my_Xml_Object->set_definition_context('DOC','');
		$this->idx = $this->my_Xml_Object->cur_idx();
		$this->build_up();
		
		$this->my_Xml_Object->change_idx(0);
*/		
		
	}
	
	private function build_up()
	{
		$namespace = array();
		$namespace['xmlns'] = $this->workspace;
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
		
		//echo get_Class($this->my_Xml_Object);
	
		
		$this->my_Xml_Object->createTree($this->workspace,'rdf:RDF', $namespace);
			
		$this->my_Xml_Object->set_first_node();
		$stamp = $this->my_Xml_Object->position_stamp();
		
		$this->reg_stamp = $stamp;
		
		$this->my_Xml_Object->use_ns_def_strict(true);
		
		$attrib = array('rdf:about' => $this->registry);
		$this->my_Xml_Object->tag_open($this, "owl:Ontology", $attrib);
		$this->my_Xml_Object->tag_close($this, "owl:Ontology");
		
		$attrib = array('rdf:ID' => 'PhpClass');
		$this->my_Xml_Object->tag_open($this, "pedl:Object_Class", $attrib);
		$this->my_Xml_Object->tag_close($this, "pedl:Object_Class");
		
		$attrib = array('rdf:ID' => 'PhpMethod');
		$this->my_Xml_Object->tag_open($this, "pedl:Object_Funktion", $attrib);
		$this->my_Xml_Object->tag_close($this, "pedl:Object_Funktion");
		
		$attrib = array('rdf:ID' => 'PhpConstructor');
		$this->my_Xml_Object->tag_open($this, "pedl:Object_Constructor", $attrib);
		$this->my_Xml_Object->tag_close($this, "pedl:Object_Constructor");
		
		$attrib = array('rdf:ID' => 'PhpParameter');
		$this->my_Xml_Object->tag_open($this, "pedl:Object_Parameter", $attrib);
		$this->my_Xml_Object->tag_close($this, "pedl:Object_Parameter");
		
		$attrib = array('rdf:ID' => 'System');
		$this->my_Xml_Object->tag_open($this, "PhpClass", $attrib);
		$this->my_Xml_Object->tag_close($this, "PhpClass");
		
		$attrib = array('rdf:ID' => 'Variable');
		$this->my_Xml_Object->tag_open($this, "PhpParameter", $attrib);
		$this->my_Xml_Object->tag_close($this, "PhpParameter");
		
		$attrib = array('rdf:ID' => 'System.Parser');
		$this->my_Xml_Object->tag_open($this, "Variable", $attrib);
		$this->my_Xml_Object->tag_close($this, "Variable");
		
		$attrib = array('rdf:ID' => 'System.CurRef');
		$this->my_Xml_Object->tag_open($this, "Variable", $attrib);
		$this->my_Xml_Object->tag_close($this, "Variable");
		
		$attrib = null;
		$this->my_Xml_Object->tag_open($this, "System", $attrib);
				
				$this->my_Xml_Object->tag_open($this, "System.Parser", $attrib);
				$this->my_Xml_Object->cdata($this,$this->my_Xml_Object);
				$this->my_Xml_Object->tag_close($this, "System.Parser");
				$this->my_Xml_Object->tag_open($this, "System.CurRef", $attrib);
				$this->my_Xml_Object->cdata($this,null);
				$this->my_Xml_Object->tag_close($this, "System.CurRef");
		$this->my_Xml_Object->tag_close($this, "PhpParameter");
		
		$attrib = array('rdf:ID' => 'Class_Collection');
		$this->my_Xml_Object->tag_open($this, "owl:Class", $attrib);
		$this->my_Xml_Object->tag_close($this, "owl:Class");
		
		$attrib = array('rdf:ID' => 'Class_Instance');
		$this->my_Xml_Object->tag_open($this, "owl:Class", $attrib);
		$this->my_Xml_Object->tag_close($this, "owl:Class");
		
		
		//$this->my_Xml_Object->create_Ns_Node("owl:Class");
		//$this->my_Xml_Object->set_node_attrib('rdf:ID','Class_Collection');
		//$this->my_Xml_Object->parent_node();
		
		$attrib = array('rdf:about' => '#has_method');
		$this->my_Xml_Object->tag_open($this, "rdf:Property", $attrib);
		
			$attrib = array('rdf:resource' => '#PhpClass');
			$this->my_Xml_Object->tag_open($this, "rdfs:domain", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => '#PhpMethod');
			$this->my_Xml_Object->tag_open($this, "rdfs:range", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdfs:range");
			
		$this->my_Xml_Object->tag_close($this, "rdf:Property");
		
		$attrib = array('rdf:about' => '#has_constructor');
		$this->my_Xml_Object->tag_open($this, "rdf:Property", $attrib);
		
			$attrib = array('rdf:resource' => '#PhpClass');
			$this->my_Xml_Object->tag_open($this, "rdfs:domain", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => '#PhpConstructor');
			$this->my_Xml_Object->tag_open($this, "rdfs:range", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdfs:range");
			
		$this->my_Xml_Object->tag_close($this, "rdf:Property");

		$attrib = array('rdf:about' => '#has_parameter');
		$this->my_Xml_Object->tag_open($this, "rdf:Property", $attrib);
		
			$attrib = array('rdf:resource' => '#PhpMethod');
			$this->my_Xml_Object->tag_open($this, "rdfs:domain", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => '#PhpParameter');
			$this->my_Xml_Object->tag_open($this, "rdfs:range", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdfs:range");
			
		$this->my_Xml_Object->tag_close($this, "rdf:Property");
		
		
		$attrib = null;
		$this->my_Xml_Object->tag_open($this, "Class_Collection", $attrib);
		
			$this->my_Xml_Object->tag_open($this, "rdf:Bag", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdf:Bag");

		$this->my_Xml_Object->tag_close($this, "Class_Collection");
		
		$this->my_Xml_Object->tag_open($this, "Class_Instance", $attrib);
		
			$this->my_Xml_Object->tag_open($this, "rdf:Bag", $attrib);
			$this->my_Xml_Object->tag_close($this, "rdf:Bag");

		$this->my_Xml_Object->tag_close($this, "Class_Instance");
		
		$this->my_Xml_Object->use_ns_def_strict(false);
		/*
		$this->my_Xml_Object->create_Ns_Node("owl:Ontology");
		$this->my_Xml_Object->set_node_attrib('rdf:about',$this->registry);
		$this->my_Xml_Object->parent_node();
		ok
		$this->my_Xml_Object->create_Ns_Node("pedl:Object_Class");
		$this->my_Xml_Object->set_node_attrib('rdf:ID','PhpClass');
		$this->my_Xml_Object->parent_node();
		
		$this->my_Xml_Object->create_Ns_Node("pedl:Object_Funktion");
		$this->my_Xml_Object->set_node_attrib('rdf:ID','PhpMethod');
		$this->my_Xml_Object->parent_node();
		

		
		$this->my_Xml_Object->create_Ns_Node("pedl:Object_Constructor");
		$this->my_Xml_Object->set_node_attrib('rdf:ID','PhpConstructor');
		$this->my_Xml_Object->parent_node();
		
		$this->my_Xml_Object->create_Ns_Node("pedl:Object_Parameter");
		$this->my_Xml_Object->set_node_attrib('rdf:ID','PhpParameter');
		$this->my_Xml_Object->parent_node();
		
		
		//$this->my_Xml_Object->create_Ns_Node("owl:Class");
		//$this->my_Xml_Object->set_node_attrib('rdf:ID','Class_Collection');
		//$this->my_Xml_Object->parent_node();
		
		
		$this->my_Xml_Object->create_Ns_Node("owl:Class");
		$this->my_Xml_Object->set_node_attrib('rdf:ID','Class_Instance');
		$this->my_Xml_Object->parent_node();

		
		$this->my_Xml_Object->create_Ns_Node("rdf:Property");
		$this->my_Xml_Object->set_node_attrib('rdf:about','#has_method');
			$this->my_Xml_Object->create_Ns_Node("rdfs:domain");
			$this->my_Xml_Object->set_node_attrib('rdf:resource','#PhpClass');
			$this->my_Xml_Object->parent_node();
			$this->my_Xml_Object->create_Ns_Node("rdfs:range");
			$this->my_Xml_Object->set_node_attrib('rdf:resource','#PhpMethod');
			$this->my_Xml_Object->parent_node();
		$this->my_Xml_Object->parent_node();
		
		$this->my_Xml_Object->create_Ns_Node("rdf:Property");
		$this->my_Xml_Object->set_node_attrib('rdf:about','#has_constructor');
			$this->my_Xml_Object->create_Ns_Node("rdfs:domain");
			$this->my_Xml_Object->set_node_attrib('rdf:resource','#PhpClass');
			$this->my_Xml_Object->parent_node();
			$this->my_Xml_Object->create_Ns_Node("rdfs:range");
			$this->my_Xml_Object->set_node_attrib('rdf:resource','#PhpConstructor');
			$this->my_Xml_Object->parent_node();
		$this->my_Xml_Object->parent_node();
		
		$this->my_Xml_Object->create_Ns_Node("rdf:Property");
		$this->my_Xml_Object->set_node_attrib('rdf:about','#has_parameter');
			$this->my_Xml_Object->create_Ns_Node("rdfs:domain");
			$this->my_Xml_Object->set_node_attrib('rdf:resource','#PhpMethod');
			$this->my_Xml_Object->parent_node();
			$this->my_Xml_Object->create_Ns_Node("rdfs:range");
			$this->my_Xml_Object->set_node_attrib('rdf:resource','#PhpParameter');
			$this->my_Xml_Object->parent_node();
		$this->my_Xml_Object->parent_node();
		
		$this->my_Xml_Object->create_Ns_Node("Class_Collection");
		
			$this->my_Xml_Object->create_Ns_Node("rdf:Bag");
			$this->my_Xml_Object->parent_node();
		$this->my_Xml_Object->parent_node();
		
		$this->my_Xml_Object->create_Ns_Node("Class_Instance");
		
			$this->my_Xml_Object->create_Ns_Node("rdf:Bag");
			$this->my_Xml_Object->parent_node();
		$this->my_Xml_Object->parent_node();
		
		*/
		
		//echo $this->my_Xml_Object->cur_node();
		//
		
		//$this->my_Xml_Object->create_Ns_Node("owl:Ontology");
		
		//$this->my_Xml_Object->create_Ns_Node("owl:Ontology");
		$this->my_Xml_Object->go_to_stamp($stamp);
	}
	
	
	public function getPositionStampReg()
	{
		return $this->reg_stamp;
	}
	
	public function getClassTag()
	{
		return 'Class_Collection';
	}
	
	public function getInstanceTag()
	{
		return 'Class_Instance';
	}
}
?>
