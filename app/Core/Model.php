<?php

namespace App\Core;


class Model
{

    protected $dbc;
    protected $table = __CLASS__;
    protected $object;
    protected $parameters;

    public function __construct()
    {
        $this->dbc = Database::getFactory()->getConnection();
        return $this;
    }

    public function find($id)
    {
        $stmt = $this->dbc->prepare("SELECT *FROM $this->table WHERE id = :id");
        $stmt->execute([
            ':id' => $id
        ]);
        $this->object = $stmt->fetchObject();
        return $this;
    }

    public function create()
    {
        if (!$this->checkParameters()){
            return false;
        }
        $sql = "INSERT INTO $this->table(";
        $values = "VALUES(";
        $data = [];
        foreach ($this->object as $parameter => $value){
            $sql .= $parameter;
            $values .= ':' . $parameter;
            if (end($this->object) === $value){
                $sql .= ') ';
                $values .= ')';
            }else{
                $sql .= ', ';
                $values .= ', ';
            }
            $data[':' . $parameter] = $value;
        }
        $sql .= $values;
        $stmt = $this->dbc->prepare($sql);
        $stmt->execute($data);
        if(!isset($this->object->id)){
            $this->object->id = $this->dbc->lastInsertId();
        }
        return $this;
    }

    public function save()
    {
        $sql = "UPDATE $this->table SET ";
        $data = [];
        foreach ($this->object as $parameter => $value){
            $sql .= $parameter . ' = :' . $parameter;
            $sql .= end($this->object) === $value ? ' ' : ', ';
            $data[':' . $parameter] = $value;
        }
        $sql .= "WHERE id = :id";
        $stmt = $this->dbc->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    private function checkParameters()
    {
        $object = get_object_vars($this->object);
        foreach ($this->parameters as $parameter){
            if (!key_exists($parameter, $object) || $object[$parameter] === null){
                echo 'Failed at ' . $parameter . '<br>';
                print_r($object);
                return false;
            }
        }
        return true;
    }

    public function get()
    {
        return $this->object;
    }

    public function set($parameter, $value)
    {
        $this->object = $this->object === null ? new \stdClass() : $this->object;
        $this->object->$parameter = $value;
        return $this;
    }

}