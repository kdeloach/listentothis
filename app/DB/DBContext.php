<?php

class DBContext
{
    static $instance;
    var $db;

    function __construct($dsn=null, $user=null, $pass=null)
    {
        if($dsn == null)
        {
            $config = ConfigurationManager::instance();
            $dsn = (string)$config->setting('database/@dsn');
            $user = (string)$config->setting('database/@user');
            $pass = (string)$config->setting('database/@pass');
        }

        $this->db = new PDO($dsn, $user, $pass);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    static function instance()
    {
        if(self::$instance == null)
        {
            $context = new DBContext();
            self::$instance = $context;
        }
        return self::$instance;
    }

    function query($strQuery, $args=array())
    {
        $stmt = $this->db->prepare((string)$strQuery);
        $res = $stmt->execute($args);
        if($res !== false)
        {
            try
            {
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
            catch(Exception $ex)
            {
            }
        }
        return array();
    }

    function lastInsertID()
    {
        return $this->db->lastInsertId();
    }
}
