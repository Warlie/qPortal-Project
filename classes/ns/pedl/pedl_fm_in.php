<?php

/**  PEDL_fm_in - Marke fuer formale Methoden: was hineingeht - Vorbedingung, Eingabe (3.2.18)
*
*	fm steht fuer formal method. Worauf die Marke zeigt, ist bewusst offen (STW). Liegt
*	ein Verweis vor, ist er rdf:resource - <pedl:fm_in rdf:resource="#bedingung"/>,
*	in PHP "pedl:fm_in:: #bedingung" im /*@ ... @*\/-Block -, sonst steht freier Text
*	darin. Gegenstueck: pedl:fm_out.
*
*	Vokabular, kein Verhalten: der Knoten tut nichts.
*/

class PEDL_fm_in extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';


function &get_Instance()
{
return new PEDL_fm_in();
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
