<?php

/*
  ----------------------------------------------------------------------
  LICENSE

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License (GPL)
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  To read the license please visit http://www.gnu.org/copyleft/gpl.html
  ----------------------------------------------------------------------
*/

/**
 *  Mysql Database class
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 * 
 */
class DB_Sql {
  
    /* public: connection parameters */
    private $Host;
    private $Database;
    private $User;
    private $Password;

    /* public: configuration parameters */
    private $Auto_Free = False; // Set to True for automatic mysql_free_result()
    private $Debug = False;     // Set to 1 for debugging messages.
    private $Halt_On_Error = "no"; // "yes" (halt with message), "no" (ignore errors quietly), "report" (ignore errror, but spit a warning)
    private $Seq_Table     = "db_sequence";

    /* public: result array and current row number */
    public /* FIXME */ $Record   = array();
    private $Row = 0;
    private $num_rows;

    /* public: current error number and error text */
    private $Errno;
    private $Error;

    /* private: link and query handles */
    private $Query_String;
  
    /* PDO related variables */
    private $pdo_instance = NULL;
    private $pdo_query = NULL;


    /**
     * Constructor: Connect to the database server
     */
    function __construct($db, $host, $user, $passwd) {

        $dsn = sprintf('mysql:dbname=%s;host=%s', $db, $host);

        //Force same behavior between php 5.x and php 8.x
        //https://www.php.net/manual/en/pdo.error-handling.php
        $options=array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING
        );
        try {
            $this->pdo_instance = new PDO($dsn, $user, $passwd, $options);
        } catch (PDOException $e) {
            echo "Mysql", "PDO instance", $e->getMessage();
            return FALSE;
        }
    }


    /**
     * function for MySQL database connection management
     *
     * This function manages the connection to the MySQL database.
     *
     * @param $Database name of the database
     * @param $Host DNS of the MySQL hosting server
     * @param $User the user's name
     * @param $Password the user's password
     *
     * @return the class variable $Link_ID
     */
    function connect($Database = "", $Host = "", $User = "", $Password = "") {
        $this->halt('Mysql::connect() : This function should no longer be used');
        /* Handle defaults */
        if ("" == $Database)
            $Database = $this->Database;
        if ("" == $Host)
            $Host     = $this->Host;
        if ("" == $User)
            $User     = $this->User;
        if ("" == $Password)
            $Password = $this->Password;
     
        if (!$this->pdo_instance) {
            $dsn = sprintf('mysql:dbname=%s;host=%s', $Database, $Host);

            try {
                $this->pdo_instance = new PDO($dsn, $User, $Password);
            } catch (PDOException $e) {
                $this->halt("Mysql::PDO_instance" . $e->getMessage());
                return FALSE;
            }
        }
    
        return True;
    }

    /**
     * Discard the query result 
     *
     * This function discards the last query result.
     */
    function free() {
        $this->pdo_query->closeCursor();
    }


    function is_connected() {
        return $this->pdo_instance != FALSE;
    }


    function last_error() {
        return $this->Error;
    }


    /** 
     * Perform a query 
     * 
     * This function performs the MySQL query described in the string parameter
     *
     * @param a string describing the MySQL query   
     * @param arguments is an optionnal array for future use with PDO parametrized requests
     * @return the $Query_ID class variable (null if fails)
     */
    function query($Query_String, $arguments = false) {
        global $debug_alternc;

        if (empty($Query_String) || !$this->is_connected())
            return FALSE;

        $this->Query_String = $Query_String;
        if ($this->Debug)
            printf("Debug: query = %s<br />\n", $Query_String);

        $debug_chrono_start = microtime(true);

        if ($arguments===false) {
            $this->pdo_query = $this->pdo_instance->query($Query_String);
            $exec_state = is_object($this->pdo_query);

        } else {
       
            $this->pdo_query = $this->pdo_instance->prepare($this->Query_String);
            $exec_state = ($arguments) ? $this->pdo_query->execute($arguments) 
                : $this->pdo_query->execute(); 
            // WARNING: this ternary is when we pass array() as $arguments
        }

        $debug_chrono_start = (microtime(true) - $debug_chrono_start)*1000;
        $this->Row = 0;

        if ($exec_state == FALSE) {
            if (is_object($this->pdo_query)) {
                $this->Errno = $this->pdo_query->errorCode();
                $this->Error = $this->pdo_query->errorInfo();
            } else {
                $this->Errno = $this->pdo_instance->errorCode();
                $this->Error = $this->pdo_instance->errorInfo();
            }

            if( defined("THROW_EXCEPTIONS") && THROW_EXCEPTIONS ){
                $error_msg=$this->Error[2];
                throw new \Exception("Mysql query failed : $error_msg");
            }
            $this->halt("SQL Error: ".$Query_String);
            return FALSE;
        }
     
        if (isset($debug_alternc)) {
            $debug_alternc->add("SQL Query : (".substr($debug_chrono_start,0,5)." ms)\t $Query_String");
            $debug_alternc->nb_sql_query++;
            $debug_alternc->tps_sql_query += $debug_chrono_start;
        }

        return TRUE;
    }

   
    /**
     * walk result set 
     *
     * This function tests if a new record is available in the current
     * query result.
     *
     * @return TRUE if a new record is available
     */
    function next_record() {
        if (!$this->pdo_query) {
            $this->halt("next_record called with no query pending.");
            return FALSE;
        }

        $this->Record = $this->pdo_query->fetch(PDO::FETCH_BOTH);
        $this->Row++;
        $this->Errno = $this->pdo_query->errorCode();
        $this->Error = $this->pdo_query->errorInfo();

        if ($this->Record == FALSE) {
            if ($this->Auto_Free) 
                $this->free();
            return FALSE;
        }

        return TRUE;
    }

    /* pdo equivalent of fetchAll() */
    function fetchAll() {
        if (!$this->pdo_query) {
            $this->halt("next_record called with no query pending.");
            return FALSE;
        }

        $data = $this->pdo_query->fetchAll(PDO::FETCH_BOTH);
        $this->Errno = $this->pdo_query->errorCode();
        $this->Error = $this->pdo_query->errorInfo();

        if ($data == FALSE) {
            if ($this->Auto_Free) 
                $this->free();
            return FALSE;
        }

        return $data;
    }

    /* pdo equivalent of fetch() */
    function fetch($mode=PDO::FETCH_ASSOC) {
        if (!$this->pdo_query) {
            $this->halt("next_record called with no query pending.");
            return FALSE;
        }

        $data = $this->pdo_query->fetch($mode);
        $this->Errno = $this->pdo_query->errorCode();
        $this->Error = $this->pdo_query->errorInfo();

        if ($data == FALSE) {
            if ($this->Auto_Free) 
                $this->free();
            return FALSE;
        }

        return $data;
    }

    /**
     * table locking
     */
    function lock($table, $mode="write") {
        if (!$this->is_connected())
            return FALSE;
    
        $query="lock tables ";
        if (is_array($table)) {
            foreach($table as $key=>$value) {
                if ($key=="read" && $key!=0) {
                    $query.="$value read, ";
                } else {
                    $query.="$value $mode, ";
                }
            }
            $query=substr($query,0,-2);
        } else {
            $query.="$table $mode";
        }
     

        if (!$this->query($query)) {
            $this->halt("lock($table, $mode) failed.");
            return FALSE;
        }

        return TRUE;

    }
  
    /**
     * table unlocking
     */
    function unlock() {
        if (!$this->is_connected())
            return FALSE;

        if (!$this->query('unlock tables')) {
            $this->halt("unlock() failed.");
            return FALSE;
        }
    }


    /**
     * evaluate the result (size, width)
     */
    function affected_rows() {
        if (!$this->pdo_query) return 0;
        return $this->pdo_query->rowCount();
    }
    function num_rows() {
        if (!$this->pdo_query) return 0;
        return $this->pdo_query->rowCount();
    }

    function num_fields() {
        if (!$this->pdo_query) return 0;
        return $this->pdo_query->columnCount();
    }

    /**
     *  shorthand notation
     */
    function nf() {
        return $this->num_rows();
    }

    function np() {
        print $this->num_rows();
    }


    /**
     * @param string $Name
     * @return integer
     */
    function f($Name) {
        if (isset($this->Record[$Name]))
            return $this->Record[$Name];
        else
            return false;
    }


    function current_record() {
        return $this->Record;
    }


    function p($Name) {
        print $this->Record[$Name];
    }


    function lastid() {
        return $this->pdo_instance->lastInsertId();
    }


    /**
     *  Escape a string to use it into a SQL PDO query
     *  @param  string  string to escape
     *  @return string  escaped string
     */
    function quote($string) {
        return $this->pdo_instance->quote($string);
    }


    /**
     *  Execute a direct query, not getting any result back
     *  @param  query  string query to execute
     *  @return integer the number of affected rows
     */
    function exec($query) {
        return $this->pdo_instance->exec($query);
    }


    /**
     * get next sequence numbers
     */
    function nextid($seq_name) {
        if (!$this->is_connected())
            return FALSE;

        if ($this->lock($this->Seq_Table)) {
            /* get sequence number (locked) and increment */
            $q  = sprintf("select nextid from %s where seq_name = '%s'",
            $this->Seq_Table,
            $seq_name);
            $this->query($q);
            $this->next_record();
        
            $id = $this->f('nextid');
      
            /* No current value, make one */
            if (!$id) {
                $currentid = 0;
                $q = sprintf("insert into %s values('%s', %s)",
                $this->Seq_Table,
                $seq_name,
                $currentid);
                $this->query($q);
            } else {
                $currentid = $id;
            }
        
            $nextid = $currentid + 1;
            $q = sprintf("update %s set nextid = '%s' where seq_name = '%s'",
            $this->Seq_Table,
            $nextid,
            $seq_name);
            $this->query($q);
            $this->unlock();
        } else {
            $this->halt("cannot lock ".$this->Seq_Table." - has it been created?");
            return FALSE;
        }
     
        return $nextid;
    }


    /** 
     * DEPRECATED return table metadata
     */
    function metadata($table='',$full=false) {
        global $msg;
        $msg->raise("ERROR", 'Mysql', 'function is no longer implemented (metadata())');
        return FALSE;
    }

    /**
     *  private: error handling
     */
    function halt($msg) {
        if ($this->Halt_On_Error == "no")
            return;

        $this->haltmsg($msg);

        if ($this->Halt_On_Error != "report")
            die("Session halted.");
    }


    /**
     *  private: error handling
     */
    function haltmsg($msg) {
        printf("</td></tr></table><b>Database error:</b> %s<br />\n", $msg);
        printf("<b>MySQL Error</b>: %s (%s)<br />\n",
        $this->Errno,
        implode("\n", $this->Error));
    }


    function table_names() {
        $this->query("SHOW TABLES");
        $return = array();
        while ($this->next_record())
            $return[] = array('table_name' => $this->p(0), 'tablespace_name' => $this->Database, 'database' => $this->Database);

        return $return;
    }

} /* Class DB_Sql */

