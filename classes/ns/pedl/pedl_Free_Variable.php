<?php

/**  PEDL_Free_Variable - der FREIE benannte Platz (3.2.19)
*
*	Gegenstueck zu pedl:Object_Parameter, dem benannten Platz AN einem Objekt
*	(PhpParameter, PhpProperty, PhpConstant). Ein freier gehoert keinem Objekt: eine
*	Konstante oder Variable auf oberster Ebene einer PHP-Datei. "Variable" statt
*	"Parameter", weil ein solcher Platz nichts uebergeben bekommt (STW).
*
*	Vokabular, kein Verhalten: der Knoten tut nichts. Gepraegt und ausgesagt wird im
*	Registrierungsbogen (ontologies/registry_surface.owl).
*/

class PEDL_Free_Variable extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';


function &get_Instance()
{
return new PEDL_Free_Variable();
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
