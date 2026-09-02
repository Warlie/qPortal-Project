<?PHP

require_once('interface_ControlModul.php');
//liefert die Namensraum-Konstanten der Beschreibungsschicht
require_once(__DIR__ . '/handles/PHP_ast_scan.php');

class TreeEngine implements ControlUnit {
	
private $my_Xml_Object;
private $my_Cont_Object;
private $my_db_Object;
private $system;
private $registry;
private $reg_stamp = '';
private $idx = 1;
private $objectList = array();
private $obj_cur_ref = null;
private $obj_eff_branch = null;


public function get_EffBranch(){ return $this->obj_eff_branch->getdata(0);}
public function set_EffBranch($new){ $this->obj_eff_branch->setdata($new,0);}


public function get_CurRef(){ return $this->obj_cur_ref->getdata(0);}
public function set_CurRef($new){ $this->obj_cur_ref->setdata($new,0);}

	function __construct(ContentGenerator &$nsobj)
	{
	$mtree_inst = &$nsobj->getXMLObj();

	if($mtree_inst instanceof xml_ns)
		{	
	 		$mtree_inst->setControlUnit($this);
	 		$this->my_Xml_Object = &$mtree_inst;
	 		
		}
	else
		die('embeded xml-parser has to be a child of xml_ns');
	
	$this->my_Cont_Object = &$nsobj;
	$this->my_db_Object = $nsobj->getSQLObj();
	}
	
	public function setObjectByID(&$obj,$id)
	{
		if ($id === null) {
        echo "<pre>--- TRACE FÜR NULL ID ---\n";
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        echo "</pre>";
        // Wahlweise: exit; // Falls du das Skript hier anhalten willst
    }
		
		$this->objectList[$id] = &$obj;
	}
	public function getObjectByID($id){return $this->objectList[$id];}
	public function getName(){return 'surface_tree_engine';}
        public function getRegistrySpace(){return $this->registry;}
        public function getSystemSpace(){return $this->system;}
	public function getTreeIdent(){return '@registry_surface_system';}
	public function getIDX(){return $this->idx ;}
	
	function load_preloaded_structur($url,$registry ){}
	
	function load_structur($structur,$registry)
	{
		//var_dump($structur, $registry);
		$this->system = $structur;
		$this->registry = $registry;
		$this->my_Xml_Object->get_context_generator()->set_Reg_NS('http://qportal-project.org/regsys');
		$this->my_Xml_Object->load($structur,0);
		$this->my_Xml_Object->setNewTree($registry);
		$this->my_Xml_Object->set_definition_context('TYPE','XML');
		$this->my_Xml_Object->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
		$this->my_Xml_Object->set_definition_context('DOC','');

		$this->idx = $this->my_Xml_Object->cur_idx();
		//$this->rdf_build();
		$this->build_up();
		$this->idx = $this->my_Xml_Object->uriToIndex('@registry_surface_system');
		if($this->my_Xml_Object->get_context_generator())$this->my_Xml_Object->get_context_generator()->set_template('@registry_surface_system','@registry_surface_system');
	 	//echo $this->idx;
	 	
		$this->my_Xml_Object->change_idx(0);
		
		
	}
	
	private function rdf_build()
	{
		$namespace = array();
		$namespace['xmlns'] = "http://www.w3.org/1999/02/22-rdf-syntax-ns";
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
		
		$this->my_Xml_Object->createTree('http://www.w3.org/1999/02/22-rdf-syntax-ns','rdf:RDF', $namespace);
			
		$this->my_Xml_Object->set_first_node();
		$stamp = $this->my_Xml_Object->position_stamp();
		
		$back = $stamp;
		
		$this->my_Xml_Object->use_ns_def_strict(true);

		$this->my_Xml_Object->go_to_stamp($stamp);
		
	}
	
	private function build_up()
	{
		$namespace = array();
		$namespace['xmlns'] = $this->registry;
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
		//Beschreibungsschicht: Dublin Core fuer title/creator/description, pedl-desc fuer den Rest
		$namespace['xmlns:dc']   = PHP_Ast_Scan::NS_DC;
		$namespace['xmlns:desc'] = PHP_Ast_Scan::NS_DESC;
		/* tree gehoert hierher, obwohl der Bogen keinen tree-Tag schreibt: die Aussagen am
		*  Ende zeigen per rdf:about auf tree-Knoten. Ein xmlns laesst den Namensraum ueber
		*  My_NameSpace_factory registrieren (xml_multitree_ns.php:592) - mit den ECHTEN
		*  Knoten aus classes/ns/tree/class_index.php. Ohne das haenge die Sache an der
		*  Ladereihenfolge: waere tree hier noch unbekannt, wuerde die erste Aussage den
		*  Namen selbst belegen und TREE_tree spaeter still verdraengt (der
		*  Ueberschreibschutz greift dann zugunsten des Falschen). */
		$namespace['xmlns:tree'] = 'http://www.trscript.de/tree';

		//echo get_Class($this->my_Xml_Object);
	
		
		$this->my_Xml_Object->createTree('http://qportal-project.org/regsys','rdf:RDF', $namespace);
			
		$this->my_Xml_Object->set_first_node();
		$stamp = $this->my_Xml_Object->position_stamp();
		
		$this->reg_stamp = $stamp;
		
		$this->my_Xml_Object->use_ns_def_strict(true);
		
		/* Das Vokabular kommt aus einem Dokument, nicht mehr aus tag_open()-Folgen.
		*  Es ist reine Deklaration - was hier frueher stand, steht jetzt in
		*  ontologies/registry_surface.owl; der Pfad in config/default.ini [runtime].
		*
		*  Die Reihenfolge ist Bedingung, nicht Geschmack: NACH createTree(), weil erst
		*  das den Namensraum mit seinem nativen Knoten anlegt (sonst bricht das Laden
		*  mit "native namespace is missing" ab), und VOR dem Bindungsblock, weil der
		*  Tags benutzt, die hier erst gepraegt werden.
		*
		*  ⚠ Ein fehlendes Dokument bricht ab, und das ist Absicht. Ohne Vokabular
		*  praegt niemand, und use_ns_def_strict(true) WIRFT NICHT bei einem
		*  ungepraegten Tag - es faellt auf einen generischen Interface_node zurueck.
		*  Die Seite wuerde weiter rendern und die Bedeutung waere still weg. */
		$vokabular = defined('REGISTRY_VOCABULARY')
		           ? REGISTRY_VOCABULARY
		           : __DIR__ . '/../ontologies/registry_surface.owl';

		if(!is_readable($vokabular))
			throw new Exception('TreeEngine: Vokabular des Registrierungsbogens nicht '
			                  . 'lesbar: "' . $vokabular . '" (config/default.ini, '
			                  . '[runtime] REGISTRY_VOCABULARY).');

		$vokabular_xml = file_get_contents($vokabular);
		$this->my_Xml_Object->load_Stream($vokabular_xml, 0, "XML");

		$attrib = null;
		$this->my_Xml_Object->tag_open($this, "System", $attrib);
		
			$this->my_Xml_Object->tag_open($this, "pedl:hasParameter", $attrib);
			
				$this->my_Xml_Object->tag_open($this, "pedl:ParameterCollection", $attrib);
		
					$this->my_Xml_Object->tag_open($this, "System.Parser", $attrib);
					$this->my_Xml_Object->cdata_ref($this,$this->my_Xml_Object);
					$this->my_Xml_Object->tag_close($this, "System.Parser");
				
					$this->my_Xml_Object->tag_open($this, "System.Database", $attrib);
					$this->my_Xml_Object->cdata_ref($this,$this->my_db_Object);
					$this->my_Xml_Object->tag_close($this, "System.Parser");
				
					$this->my_Xml_Object->tag_open($this, "System.FuncTree", $attrib);
					$this->my_Xml_Object->cdata_ref($this,$this);
					$this->my_Xml_Object->tag_close($this, "System.FuncTree");
				
					$this->my_Xml_Object->tag_open($this, "System.Content", $attrib);
					$this->my_Xml_Object->cdata_ref($this,$this->my_Cont_Object);
					$this->my_Xml_Object->tag_close($this, "System.Content");
				
					$this->my_Xml_Object->tag_open($this, "System.CurRef", $attrib);
					$this->obj_cur_ref = $this->my_Xml_Object->get_Element();
//					$this->my_Xml_Object->cdata($this,null);
					$this->my_Xml_Object->tag_close($this, "System.CurRef");
					
					$this->my_Xml_Object->tag_open($this, "System.EffBranch", $attrib);
					$this->obj_eff_branch = $this->my_Xml_Object->get_Element();
//					$this->my_Xml_Object->cdata($this,null);
					$this->my_Xml_Object->tag_close($this, "System.EffBranch");
					
					
					$this->my_Xml_Object->tag_open($this, "System.Exception", $attrib);
//					$this->my_Xml_Object->cdata($this,null);
					$this->my_Xml_Object->tag_close($this, "System.Exception");
				
				$this->my_Xml_Object->tag_close($this, "pedl:ParameterCollection");
				
			$this->my_Xml_Object->tag_close($this, "pedl:hasParameter");
				
		$this->my_Xml_Object->tag_close($this, "PhpParameter");
		
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
	public function __toString(){return "Class:TreeEngine";}

	
    public function __debugInfo() {
        return ['Class:TreeEngine'];
    }
}
?>
