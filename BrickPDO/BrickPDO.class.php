<?php
namespace DYuriev\BrickPDO;
/**
 * Created by PhpStorm.
 * User: d.yuriev
 * Date: 30.07.14
 * Time: 13:18
 */

class BrickPDO
{
    protected $pdo = null;
    protected $table_prefix = '';

    protected $what=array('*');
    protected $from_table=null;
    protected $from_table_alias='';
    protected $where=null;
    protected $and_where=array();
    protected $or_where=array();
    protected $group_by=null;
    protected $having=null;
    protected $order_by=null;

    protected $sql=null;


    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function resetState()
    {
        unset($this->from_table,$this->where,$this->group_by,$this->having,$this->order_by,$this->sql);
        $this->what=array('*');
        $this->$and_where=$this->or_where=array();
    }

    public function setTablePrefix($table_prefix = null)
    {
        if(!is_null($table_prefix)) {
            $this->table_prefix = $table_prefix;
        }

        return $this;
    }

    public function select()
    {
        $this->resetState();

        if(count(func_get_args()) > 0) {
            $this->what=func_get_args();
        }

        return $this;
    }



    public function from($table_name,$alias=null)
    {
        $this->from_table=$this->table_prefix.$table_name;

        if(!is_null($alias)) {
            $this->from_table_alias=$alias;
        }

        return $this;
    }

    public function where($arg1,$condition,$arg2)
    {
        $this->where=array($arg1,$condition,$arg2);
        return $this;
    }

    public function andWhere($arg1,$condition,$arg2)
    {
        $this->and_where[]=array($arg1,$condition,$arg2);
        return $this;
    }

    public function orWhere($arg1,$condition,$arg2)
    {
        $this->or_where[]=array($arg1,$condition,$arg2);
        return $this;
    }

    public function groupBy()
    {
        if (func_num_args() > 0) {
            $this->group_by = func_get_args();
        }

        return $this;
    }

    public function having($arg1,$condition,$arg2)
    {
        $this->having=array($arg1,$condition,$arg2);
        return $this;
    }

    public function orderBy($field_name)
    {
        if (func_num_args() > 0) {
            $this->order_by = func_get_args();
        }

        return $this;
    }

    protected function addAlias2Fields(Array $fields)
    {
        $_this=$this;

        return array_map(function($field) use ($_this) {
            return $_this->addAlias2Field($field);
        }, $fields);
    }

    public function addAlias2Field($field)
    {
        $field=trim($field);

        if (!empty($this->from_table_alias) && preg_match('/^\[(.+)\]$/', $field, $matches)) {
            return $this->from_table_alias.'.'.$matches[1];
        } elseif (empty($this->from_table_alias) && preg_match('/^\[(.+)\]$/', $field, $matches)) {
            return $matches[1];
        }

        return $field;
    }

    public function buildSelect()
    {
        unset($this->sql);
        $this->sql='SELECT ';

        if ($this->what == array('*')) {
            $this->sql.='* ';
        } else {
            $this->what=$this->addAlias2Fields($this->what);
            $this->sql.=implode(', ',$this->what);
        }

        $this->sql .= "\nFROM ";
        $this->sql .= $this->from_table;

        if (!empty($this->from_table_alias)) {
            $this->sql .= ' AS ' . $this->from_table_alias;
        }

        if (!is_null($this->where)) {
            $this->sql .= "\n" . 'WHERE ' . $this->addAlias2Field($this->where[0]) . ' ' . $this->where[1] . ' ' . $this->addAlias2Field($this->where[2]);
        }

        if (count($this->and_where) > 0) {

            foreach($this->and_where as $and_where) {
                $this->sql .= "\n" . 'AND ' . $this->addAlias2Field($and_where[0]) . ' ' . $and_where[1] . ' ' . $this->addAlias2Field($and_where[2]);
            }
        }

        if (count($this->or_where) > 0) {

            foreach($this->or_where as $or_where) {
                $this->sql .= "\n" . 'OR ' . $this->addAlias2Field($or_where[0]) . ' ' . $or_where[1] . ' ' . $this->addAlias2Field($or_where[2]);
            }
        }

        if (count($this->group_by) > 0) {

            $this->group_by=$this->addAlias2Fields($this->group_by);
            $this->sql .= "\n" . 'GROUP BY '.implode(', ',$this->group_by);
        }

        if (!is_null($this->having)) {
            $this->sql .= "\n" . 'HAVING ' . $this->having[0] . ' ' . $this->having[1] . ' ' . $this->having[2];
        }

        if (!is_null($this->order_by)) {
            $this->order_by = $this->addAlias2Fields($this->order_by);
            $this->sql .= "\n" . 'ORDER BY '.implode(', ',$this->order_by);
        }

        return $this;
    }

    public function query($input_parameters=array())
    {
        $pdo_statement = $this->pdo->prepare($this->sql);
        $pdo_statement->closeCursor();
        $pdo_statement->execute($input_parameters);
        return $pdo_statement->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function update()
    {

    }

    public function insert()
    {

    }

    public function delete()
    {

    }


} 