<?php

/**  PEDL_turtle - semantische Beschreibung in Turtle (3.2.15)
*
*	Traegt Turtle-Text, woertlich, als Aussage neben dem Elternknoten - in einem
*	Dokument als <pedl:turtle><![CDATA[ ... ]]></pedl:turtle>, in PHP als Kopf
*	"pedl:turtle::" im /*@ ... @*\/-Block (der Scanner nimmt ihn dort schon heute
*	woertlich auf). Klein geschrieben wie pedl:name, pedl:src, pedl:hash: ein
*	Traeger, keine Klasse.
*
*	Vokabular, kein Verhalten: der Knoten tut nichts, der Text wird noch nicht zu
*	Tripeln. Das waere der naechste Schritt (Turtle_handle, hardf).
*/

class PEDL_turtle extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';


function &get_Instance()
{
return new PEDL_turtle();
}


function &new_Instance()
{

				$obj = $this->get_Instance();

				$obj->link_to_class = &$this;
				$this->link_to_instance[] = $obj; // die Klasse kennt ihre Instanzen (2026-10-04)

				return $obj;
}

}

?>
