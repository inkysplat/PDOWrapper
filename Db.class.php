<?php

/**
 * A Wrapper for PHP's Own PDO Database Object
 * 
 *  
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * @category  PHP
 * @package   PDO Wrapper
 * @author    Adam Nicholls <adamnicholls1987@gmail.com>
 * @copyright 2012 Adam Nicholls
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 * @version   0.1
 * @link      http://www.ajnicholls.co.uk
 * @see       http://uk.php.net/manual/en/book.pdo.php
 */

/**
 * DB Class
 * 
 * This is a singleton class, to connect 
 * call connect();
 *
 * @filesource Db.class.php
 * @since 10-May-2012
 * @author adam
 * @encoding UTF-8
 */
class Db
{

    /**
     * Holds Instance of Self
     * @var object 
     * @access private
     * @static
     */
    private static $instance = null;

    /**
     * Holds Database Connection/PDO instance
     * @var object 
     * @access private
     */
    private $conn = null;

    /**
     * Holds PDO Statement Object
     * @var object 
     * @access private
     */
    private $stmt = null;

    /**
     * List of PDO Attributes
     * @var array  
     * @access private
     */
    private $attribute = array(
	'ATTR_ERRMODE' => 'ERRMODE_EXCEPTION',
	'ATTR_CASE' => 'CASE_NATURAL',
	'ATTR_DEFAULT_FETCH_MODE' => 'FETCH_OBJ'
    );

    /**
     * List of Default Database Login
     * @var array  
     * @access private
     */
    private $connection = array(
	'hostname' => 'localhost',
	'username' => 'root',
	'password' => '',
	'database' => '',
	'port' => '3306'
    );

    /**
     * Empty SQL String
     * @var string 
     * @access private
     */
    private $sql = '';

    /**
     * "Group By" array
     * @var array  
     * @access private
     */
    private $group = array();

    /**
     * "Order By" array
     * @var array  
     * @access private
     */
    private $order = array();

    /**
     * Stores bind-parameters
     * @var array  
     * @access private
     */
    private $values = array();

    /**
     * "limit" result set
     * @var integer
     * @access private
     */
    private $limit = 0;

    /**
     * "offset" result set
     * @var integer
     * @access private
     */
    private $offset = 0;

    /**
     * Private Constructor 
     * 
     * Unused as this is a singleton class
     * 
     * @return void
     * @access private
     */
    private function __construct()
    {
	return;
    }

    /**
     * Creates Itself.
     * 
     * @return object self
     * @access public
     * @static
     */
    public static function getInstance()
    {
	$class = __CLASS__;
	if (!(self::$instance instanceof $class))
	{
	    self::$instance = new $class;
	}

	return self::$instance;
    }

    /**
     * Magic Setter Method.
     * 
     * @param string $name  Name of Variable Setting
     * @param string $value Value of Variable Setting
     * @return boolean	    Returns True on Sucess
     * @access public 
     * @magic 
     */
    public function __set($name, $value)
    {
	if (array_key_exists($name, $this->connection))
	{
	    $this->connection[$name] = $value;
	    return true;
	}
	if (isset(PDO::$name) && isset(PDO::$value))
	{
	    $this->attribute[$name] = $value;
	    return true;
	}
	if (isset($this->$name))
	{
	    switch (strtoupper($name))
	    {
		case 'GROUP':
		    if (is_array($value))
		    {
			$this->group = $value;
		    }
		    break;
		case 'ORDER':
		    if (is_array($value))
		    {
			$this->order = $value;
		    }
		    break;
		case 'LIMIT':
		    if (is_numeric($value))
		    {
			$this->limit = $value;
		    }
		    break;
		case 'OFFSET':
		    if (is_numeric($value))
		    {
			$this->limit = $value;
		    }
		    break;
	    }
	}

	return false;
    }

    /**
     * Connects to Database and Creates PDO Object
     * 
     * @param array     $connection Connection Parameters
     * @return object    PDO Object
     * @access public   
     * @throws Exception PDO Error
     */
    public function connect($connection = array())
    {
	if ($this->conn instanceof PDO)
	{
	    return $this->conn;
	}

	$connection = array_merge($this->connection, $connection);
	extract($connection, EXTR_PREFIX_ALL, 'db');

	try
	{
	    $dsn = "mysql:host={$db_hostname};dbname={$db_database}";
	    $this->conn = new PDO($dsn, $db_username, $db_password);

	    foreach ($this->attribute as $attribute => $value)
	    {
		if (isset(PDO::$attribute) && PDO::$value)
		{
		    $this->conn->setAttribute(PDO::$attribute, PDO::$value);
		}
	    }

	    return $this->conn;
	} catch (PDOException $e)
	{
	    throw new Exception(__METHOD__ . "::PDO Error " . $e->getMessage());
	}
    }

    /**
     * Executes Prepared SQL Query
     * 
     * @return boolean   Sucessful Execution
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     * @throws exception PDO Failure
     */
    public function execute()
    {

	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (!($this->stmt instanceof PDOStatement))
	{
	    throw new exception(__METHOD__ . "::No PDO Statement Found");
	}

	try
	{
	    if (!empty($this->values))
	    {
		$this->stmt->execute($this->values);
	    } else
	    {
		$this->stmt->execute();
	    }
	    return true;
	} catch (PDOException $e)
	{
	    throw new exception(__METHOD__ . "::PDO Error " . $e->getMessage());
	}
    }

    /**
     * Returns All Result As Array
     * 
     * @param string    $type Type of Results to Return
     * @return object|array
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     */
    public function fetchAll($type = 'OBJECT')
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (!($this->stmt instanceof PDOStatement))
	{
	    throw new exception(__METHOD__ . "::No PDO Statement Found");
	}

	$this->_fetchMode($type);

	return $this->stmt->fetchAll();
    }

    /**
     * Fetches A Row Of Data
     * 
     * @param string    $type Data Type to Return
     * @return object|array   Returned Data
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     */
    public function fetch($type = 'OBJECT')
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (!($this->stmt instanceof PDOStatement))
	{
	    throw new exception(__METHOD__ . "::No PDO Statement Found");
	}

	$this->_fetchMode($type);

	return $this->stmt->fetch();
    }

    /**
     * Sets PDO Fetch Mode
     * 
     * @param string $type 
     * @return void   
     * @access private
     */
    private function _fetchMode($type)
    {
	switch ($type)
	{
	    case 'ASSOC':
		$mode = 'FETCH_ASSOC';
		break;
	    case 'NUM':
		$mode = 'FETCH_NUM';
		break;
	    default:
	    case 'OBJECT':
	    case 'OBJ':
		$mode = 'FETCH_OBJ';
		break;
	}

	if (isset(PDO::$mode))
	{
	    $this->stmt->setFetchMode(PDO::$mode);
	}
    }

    /**
     * Counts Columns on Executed Query
     * 
     * @return int
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     */
    public function countColumns()
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (!($this->stmt instanceof PDOStatement))
	{
	    throw new exception(__METHOD__ . "::No PDO Statement Found");
	}

	return $this->stmt->columnCount();
    }

    /**
     * Counts Rows on Executed Query
     * 
     * @return int
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     */
    public function countRows()
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (!($this->stmt instanceof PDOStatement))
	{
	    throw new exception(__METHOD__ . "::No PDO Statement Found");
	}

	return $this->stmt->rowCount();
    }

    /**
     * Get Last Insert ID
     * 
     * @param string    $key	Column to Return
     * @return boolean|mixed	False on Failure	
     * @access public   
     * @throws exception	Missing PDO Instance
     */
    public function getLastId($key = '')
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if ($key != '')
	{
	    $id = $this->conn->lastInsertID($key);
	} else
	{
	    $id = $this->conn->lastInsertID();
	}

	if (!empty($id))
	{
	    return $id;
	} else
	{
	    return false;
	}
    }

    /**
     * Insert Data Into Table
     * 
     * @param string	$table  Table Name
     * @param array     $values Array of Data to Insert
     * @return void     
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     * @throws exception Empty Values
     */
    public function insert($table, $values = array())
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (empty($table))
	{
	    throw new exception(__METHOD__ . "::No Table Specified");
	}

	if (empty($values))
	{
	    throw new exception(__METHOD__ . "::No Values Specified");
	}

	$this->_clear();

	$this->_insert();
	$this->_table($table);
	$this->_values($values);

	$this->stmt = $this->conn->prepare($this->sql);
    }

    /**
     * Replace Data In Table
     * 
     * @param string	$table  Table Name
     * @param array     $values Array of Data to Insert
     * @return void     
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     * @throws exception Empty Values
     */
    public function replace($table, $values = array())
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (empty($table))
	{
	    throw new exception(__METHOD__ . "::No Table Specified");
	}

	if (empty($values))
	{
	    throw new exception(__METHOD__ . "::No Values Specified");
	}

	$this->_clear();

	$this->_replace();
	$this->_table($table);
	$this->_values($values);

	$this->stmt = $this->conn->prepare($this->sql);
    }

    /**
     * Insert Ignore Data into Table
     * 
     * @param string	$table  Table Name
     * @param array     $values Array of Data to Insert
     * @return void     
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Missing PDO Statement Object
     * @throws exception Empty Values
     */
    public function insertIgnore($table, $values = array())
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (empty($table))
	{
	    throw new exception(__METHOD__ . "::No Table Specified");
	}

	if (empty($values))
	{
	    throw new exception(__METHOD__ . "::No Values Specified");
	}

	$this->_clear();

	$this->_insertIgnore();
	$this->_table($table);
	$this->_values($values);

	$this->stmt = $this->conn->prepare($this->sql);
    }

    /**
     * INSERT SQL Query String
     * 
     * @return void   
     * @access private
     */
    private function _insert()
    {
	$this->sql = "INSERT INTO ";
    }

    /**
     * REPLACE INTO SQL Query String
     * 
     * @return void   
     * @access private
     */
    private function _replace()
    {
	$this->sql = "REPLACE INTO ";
    }

    /**
     * IGNORE INTO SQL Query String
     * 
     * @return void   
     * @access private
     */
    private function _insertIgnore()
    {
	$this->sql = "INSERT IGNORE INTO ";
    }

    /**
     * Table Name To Use
     * 
     * @param string|array   $table Accepted Aliases as array keys, and multiple table names
     * @return void   
     * @access private
     */
    private function _table($table)
    {
	if (is_string($table))
	{
	    $this->sql.= " `" . $table . "` ";
	}

	if (is_array($table))
	{
	    $tables = array();
	    foreach ($table as $alias => $name)
	    {
		if (!empty($alias))
		{
		    $tables[] = " `" . $name . "` AS " . $alias;
		} else
		{
		    $tables[] = " `" . $name . "` ";
		}
	    }
	    $this->sql.= implode(", ", $tables);
	}
    }

    /**
     * INSERT VALUES SQL Query
     * 
     * @param array   $values Array of values to insert
     * @return void   
     * @access private
     */
    private function _values($values)
    {
	if (is_numeric(key($values)) && is_array($values[0]))
	{
	    $rows = array_slice($values, 1);
	    $values = $values[0];
	}

	if (is_string(key($values)))
	{
	    $columns = array_keys($values);
	    $this->sql.= "(" . implode(", ", $columns) . "";
	    $this->sql.= ") VALUES(";
	    $this->sql.= " :v_" . implode(", :v_", $columns);
	    $this->sql.= ")";

	    foreach ($values as $column => $value)
	    {
		$this->values[":v_" . $column] = $value;
	    }
	}

	if (!empty($rows))
	{
	    $count = 0;
	    foreach ($rows as $row)
	    {
		if (is_array($row))
		{
		    if (!is_array($columns))
		    {
			$columns = array_keys($row);
		    }

		    $this->sql.= ", (:v_" . implode($count . ", :v_", $columns) . $count . ")";

		    foreach ($row as $column => $value)
		    {
			$this->values[":v_" . $column . $count] = $value;
		    }

		    $count++;
		}
	    }
	}
    }

    /**
     * Clears previous statement's data
     * 
     * @return void   
     * @access private
     */
    private function _clear()
    {

	$this->sql = '';
	$this->values = array();
	$this->order = array();
	$this->group = array();
	$this->limit = 0;
	$this->offset = 0;
    }

    /**
     * Update Data In Table
     * 
     * @param string	$table  Table Name
     * @param array     $values Array of data to update
     * @param array     $where  Array of conditions for query
     * @return void     
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Empty Table
     * @throws exception Empty Values
     */
    public function update($table, $values = array(), $where = array())
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (empty($table))
	{
	    throw new exception(__METHOD__ . "::No Table Specified");
	}

	if (empty($values))
	{
	    throw new exception(__METHOD__ . "::No Values Specified");
	}

	$this->_clear();

	$this->sql = "UPDATE ";
	$this->_table($table);
	$this->sql.= " SET ";
	$this->_update($values);
	$this->_where($where);

	if (!empty($this->order))
	{
	    $this->_order($this->order);
	}

	if ($this->limit <> 0)
	{
	    $this->_limit($this->limit);
	}

	if ($this->offset <> 0)
	{
	    $this->_offset($this->offset);
	}

	$this->stmt = $this->conn->prepare($this->sql);
    }

    /**
     * UPDATE SET Columns SQL Query
     * 
     * @param array   $values Columns to Update
     * @return void   
     * @access private
     */
    private function _update($values = array())
    {
	if (!empty($values))
	{
	    $clause = array();
	    foreach ($values as $column => $value)
	    {
		$clause[] = $column . " = :u_" . $column;

		$this->values[':u_' . $column] = $value;
	    }

	    $this->sql .= implode(", ", $clause);
	}
    }

    /**
     * Delete From Table
     * 
     * @param string	$table Table Name
     * @param array     $where Array of conditions for query
     * @return void     
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Empty Table Name
     */
    public function delete($table, $where = array())
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (empty($table))
	{
	    throw new exception(__METHOD__ . "::No Table Specified");
	}

	$this->_clear();

	$this->sql = "DELETE FROM ";
	$this->_table($table);
	$this->_where($where);

	if (!empty($this->group))
	{
	    $this->_group($this->group);
	}

	if (!empty($this->order))
	{
	    $this->_order($this->order);
	}

	if ($this->limit <> 0)
	{
	    $this->_limit($this->limit);
	}

	if ($this->offset <> 0)
	{
	    $this->_offset($this->offset);
	}

	$this->stmt = $this->conn->prepare($this->sql);
    }

    /**
     * Select Data From Table
     * 
     * @param string	$table   Table Name
     * @param array     $where   Array of conditions for query
     * @param array     $columns Columns to return
     * @return void     
     * @access public   
     * @throws exception Missing PDO Instance
     * @throws exception Empty Table Name
     */
    public function select($table, $where = array(), $columns = array())
    {
	if (!($this->conn instanceof PDO))
	{
	    throw new exception(__METHOD__ . "::No PDO Connection Found");
	}

	if (empty($table))
	{
	    throw new exception(__METHOD__ . "::No Table Specified");
	}

	$this->_clear();

	$this->sql = "SELECT ";
	$this->_columns($columns);
	$this->sql.= " FROM ";
	$this->_table($table);
	$this->_where($where);

	if (!empty($this->group))
	{
	    $this->_group($this->group);
	}

	if (!empty($this->order))
	{
	    $this->_order($this->order);
	}

	if ($this->limit <> 0)
	{
	    $this->_limit($this->limit);
	}

	if ($this->offset <> 0)
	{
	    $this->_offset($this->offset);
	}

	$this->stmt = $this->conn->prepare($this->sql);
    }

    /**
     * SELECT COLUMNS SQL Query
     * 
     * Defaults to wildcard if none provided
     * 
     * @param array   $columns Array of Columns to Select
     * @return void   
     * @access private
     */
    private function _columns($columns = array())
    {
	if (empty($columns))
	{
	    $this->sql.= "* ";
	} else
	{
	    $this->sql.= implode(", ", $columns);
	}
    }

    /**
     * WHERE SQL Query
     * 
     * Defaults to AND behaviour
     * 
     * @param array   $where Array of conditions to evaluate
     * @return void   
     * @access private
     */
    private function _where($where)
    {

	if (!empty($where))
	{
	    $operand = 'AND';

	    $this->sql .= " WHERE ";

	    if (array_key_exists('AND', $where) && is_aray($where['AND']))
	    {
		$operand = 'AND';

		$data = $where['AND'];
	    }

	    if (array_key_exists('OR', $where) && is_array($where['OR']))
	    {
		$operand = 'OR';

		$data = $where['OR'];
	    }

	    if (!isset($data) || !is_array($data))
	    {
		$data = $where;
	    }

	    $clause = array();
	    foreach ($data as $column => $value)
	    {
		$clause[] = $column . " = :w_" . $column;

		$this->values[':w_' . $column] = $value;
	    }

	    switch ($operand)
	    {
		case 'OR':
		    $this->sql.= implode(" OR ", $clause);
		default:
		case 'AND':
		    $this->sql.= implode(" AND ", $clause);
		    break;
	    }
	}
    }

    /**
     * GROUP BY SQL Query
     * 
     * @param array   $group Array of Columns to Group By
     * @return void   
     * @access private
     */
    private function _group($group = array())
    {
	if (!empty($group))
	{
	    $this->sql.= " GROUP BY ";
	    $this->sql.= implode(", ", $group);
	}
    }

    /**
     * ORDER BY SQL Query
     * 
     * accepts multi-dimentional array, expects 'ASC'/'DESC' key names
     * 
     * @param array   $order Array of Columns to Order By
     * @return void   
     * @access private
     */
    private function _order($order = array())
    {
	if (!empty($order))
	{
	    $this->sql.= " ORDER BY ";

	    if (array_key_exists('ASC', $order))
	    {
		$this->sql.= implode(", ", $order['ASC']);
		$this->sql.= " ASC";
	    }
	    if (array_key_exists('DESC', $order))
	    {
		$this->sql.= implode(", ", $order['DESC']);
		$this->sql.= " DESC";
	    }
	}
    }

    /**
     * LIMIT SQL Query
     * 
     * @param mixed   $limit Limit Result Set
     * @return void   
     * @access private
     */
    private function _limit($limit = 0)
    {
	if ($limit <> 0)
	{
	    $this->sql .= " LIMIT " . $limit;
	}
    }

    /**
     * OFFSET SQL Query
     * 
     * @param mixed   $offset Offset Result Set
     * @return void   
     * @access private
     */
    private function _offset($offset = 0)
    {
	if ($offset <> 0)
	{
	    $this->sql.= " OFFSET " . $offset;
	}
    }

}

?>
