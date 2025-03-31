<?php /**
 *ContentGenerator
 *
 * Generates content by reading XML and DB-entries
 *
 * not_a_fieldname_exception.php
 */

abstract class plugin
{
    var $back = null;
    var $treepos = null;
    var $id = "";
    protected $args = [];
    protected $internal_table_values = [];
    protected $internal_table_field_types = [];
    protected $rst = false;
    private $throwTo = [];

    var $out = "";
    protected function param_out(&$param)
    {
        $this->out = &$param;
    }
    protected function &xml()
    {
        return $this->treepos;
    }

    public function set_list(&$value)
    {
        $this->rst = &$value;
    }
    public function &iter()
    {
        return $this;
    }

    protected function moveFirst()
    {
        return reset($this->internal_table_values);
    }
    protected function moveLast()
    {
        return end($this->internal_table_values);
    }
    //is never used
    //abstract public function getAdditiveSource();

    public function configuration($json)
    {
        echo "not implemented yet!";
    }
    public function prev()
    {
        return prev($this->internal_table_values);
    }
    public function next()
    {
        return next($this->internal_table_values);
    }
    public function reset()
    {
        return reset($this->internal_table_values);
    }
    public function col($columnName)
    {
        if (!current($this->internal_table_values)) {
            return false;
        }

        if (
            !is_null($res = current($this->internal_table_values)) &&
            array_key_exists($columnName, $res)
        ) {
            return $res[$columnName];
        }

        throw new NotAFieldnameException(
            $columnName . " is not part of this recordset"
        );
    }
    public function datatype($columnname)
    {
        return false;
    }
    public function fields()
    {
        return [];
    }
    public function &out()
    {
        return $this->out;
    }
    public function __toString()
    {
        return "Plug_in:" . get_Class($this);
    }
}
?>