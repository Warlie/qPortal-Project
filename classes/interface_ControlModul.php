<?php
// Deklariere das Interface 'iTemplate'
interface ControlUnit
{
	
    public function getName();
    public function getRegistrySpace();
    public function getSystemSpace();
    public function getPositionStampReg();
    public function getClassTag();
    public function getInstanceTag();
}
