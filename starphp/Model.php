<?php
class Model
{
    private $dbConfig;
    public function __construct()
    {
        include(ROOT_PATH."config/config.php");
        $this->dbConfig = $config["DB"];
        $this->initTable();
        $connection = mysqli_connect($this->dbConfig["hostname"],$this->dbConfig["username"],$this->dbConfig["password"]);
        $this->connection = $connection;
        mysqli_select_db($connection,$this->dbConfig["database"]);
        mysqli_set_charset($connection,$this->dbConfig["charset"]);
    }
    private $sql=array("from"=>"",
            "where"=>"",
            "order"=>"",
            "limit"=>"",
    );
    private $connection;
    private $table;
    private function initTable()
    {
        $table = get_class($this);
        $table = $this->dbConfig["prefix"].$table;
        $this->table = $table;
    }
    public function where($where='id=1')
    {
        $this->sql["where"] = "WHERE ".$where;
        return $this;
    }
    public function order($order='id DESC') {
        $this->sql["order"] = "ORDER BY ".$order;
        return $this;
    }
 
    public function limit($start,$end) {
        $this->sql["limit"] = "LIMIT $start,".$end;
        return $this;
    }
    public function find($select='*')
    {
        $this->sql["limit"] = "LIMIT 1";
        $sqlStr = "SELECT ".$select." ".(implode(" ",$this->sql));
        echo $sqlStr;
        return $this->query($sqlStr,1);
    }
    public function select($select='*') {
        $sqlStr = "SELECT ".$select." ".(implode(" ",$this->sql));
        return $this->query($sqlStr,2);
    }
    public function add($data)
    {
        $data = $this->getInsertData($data);
        $sql = "INSERT INTO ".$this->table." ".$data[0]." VALUES ".$data[1];
        mysqli_query($this->connection,$sql);
        mysqli_close($this->connection);
    }
    public function getInsertData($data)
    {
        $fields = "(";
        $values = "(";
        foreach ($data as $key => $value) {
            if(gettype($value) == "string"){
                $value = "'".$value."'";
            }
            $fields .= $key.",";
            $values .= $value.",";
        }
        $fields = trim($fields,",");
        $values = trim($values,",");
        $fields .= ")";
        $values .= ")";
        return array($fields,$values);
    }
    public function updata($data)
    {
        $fields = $this->getUpData($data);
        $sql = "UPDATE ".$this->table." SET ".$fields." ".(implode(" ",$this->sql));
        mysqli_query($this->connection,$sql);
        mysqli_close($this->connection);
    }
    public function getUpData($data)
    {
        $fields = "";
        foreach ($data as $key => $value) {
            if(gettype($value) == "string"){
                $value = "'".$value."'";
            }
            $fields .= $key."=".$value.",";
        }
        return trim($fields,",");
    }
    public function delete()
    {
        $this->sql["from"] = "FROM ".$this->table;
        $sql = "DELETE ".(implode(" ",$this->sql));
        mysqli_query($this->connection,$sql);
        mysqli_close($this->connection);
    }
    function query($sql,$type)
    {
        $this->sql["from"] = "FROM ".$this->table;
        $result = mysqli_query($this->connection,$sql);
        while ($row=mysqli_fetch_row($result))
        {
            if($type == 1){
                $arr = $row;
                break;
            }
            $arr[] = $row;
        }
        mysqli_close($this->connection);
        return $arr;
    }
}