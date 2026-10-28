<?php

/**  PEDL_Free_Function - die FREIE Funktion (3.2.12)
*
*	Gegenstueck zu pedl:Object_Function, der GEBUNDENEN: die braucht einen Empfaenger
*	(getRefprev()->getobj()), eine freie Funktion hat keinen. Gedacht fuer den Code einer
*	PHP-Datei ausserhalb von Klassen, der beim Einbinden laeuft. tree/first/final sind
*	es NICHT - sie sind Function aus pedl:Object_Class (STW, im Registrierungsbogen).
*
*	Vokabular, kein Verhalten: der Knoten tut nichts. Gepraegt und ausgesagt wird im
*	Registrierungsbogen (ontologies/registry_surface.owl).
*/

class PEDL_Free_Function extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';


function &get_Instance()
{
return new PEDL_Free_Function();
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
