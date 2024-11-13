<?php
namespace Charlama\Database;

use Charlama\File\File;
use Charlama\Http\Request;
use Charlama\Url\Url;
use Exception;
use PDO;
use PDOException;

class Database
{
    protected static $instance        = null;
    protected static $connection      = null;
    protected static $query           = '';
    protected static $select          = '';
    protected static $table           = '';
    protected static $join            = '';
    protected static $where           = '';
    protected static $group_by        = '';
    protected static $having          = '';
    protected static $order_by        = '';
    protected static $limit           = '';
    protected static $offset          = '';
    protected static $setter          = '';
    protected static $binding         = [];
    protected static $where_binding   = [];
    protected static $having_binding  = [];

    public function __construct($table)
    {
        static::$table = $table;
    }
    
    private static function connect()
    {
        if (!static::$connection) {
            $config = File::require_file('config/database.php');
            extract($config);
            
            $dsn = "mysql:dbname={$database};host{$host};port={$port}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "set NAMES {$charset} COLLATE {$collation}"
            ];
            
            try {
                static::$connection = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
        }
    }
    
    private static function instance()
    {
        static::connect();
        $table = static::$table;
        if (!static::$instance) {
            self::$instance = new Database($table);
        }
        
        return self::$instance;
    }
    
    public static function query($query = null)
    {
        static::instance();
        
        if ($query == null)
        {
            if (!static::$table) {
                throw new \Exception('Unknow tabel name');
            }
            
            $query = 'SELECT ';
            $query .= static::$select ?: '*';
            $query .= ' FROM ' . static::$table;
            $query .= static::$join     . ' ';
            $query .= static::$where    . ' ';
            $query .= static::$group_by . ' ';
            $query .= static::$having   . ' ';
            $query .= static::$order_by . ' ';
            $query .= static::$limit    . ' ';
            $query .= static::$offset   . ' ';
        }
        
        static::$query = $query;
        static::$binding = array_merge(static::$where_binding, static::$having_binding);
        
        return static::instance();
    }
    
    public static function select()
    {
        static::$select = implode(', ', func_get_args());
        return static::instance();
    }
    
    public static function table($name)
    {        
        static::$table = $name;
        return static::instance();
    }
    
    public static function join($table, $first, $second, $operator = '=', $type = 'INNER')
    {
        static::$join .= " {$type} JOIN {$table} ON {$first}{$operator}{$second}";
        
        return static::instance();
    }

    public static function joinLeft($table, $first, $second, $operator = '=')
    {
        static::join($table, $first, $second, $operator, 'LEFT');
        
        return static::instance();
    }
    
    public static function joinRight($table, $first, $second, $operator = '=')
    {
        static::join($table, $first, $second, $operator, 'RIGHT');
        
        return static::instance();
    }
    
    public static function where($column, $value, $operator = '=', $type = null)
    {
        $where = "`{$column}` {$operator} ?";
        
        if (!static::$where) {
            $stmt = " WHERE {$where}";
        } else {
            if ($type == null) {
                $stmt = " AND {$where}";
            } else {
                $stmt = " {$type} {$where}";
            }
        }
        
        static::$where .= $stmt;
        static::$where_binding[] = htmlspecialchars(trim((string) $value) ?? '');
        
        return static::instance();
    }
    
    public static function orWhere($column, $value, $operator = '=')
    {
        static::where($column, $value, $operator, 'OR');
        
        return static::instance();
    }
    
    public static function groupBy()
    {
        static::$group_by = " GROUP BY " . implode(', ', func_get_args());
        return static::instance();
    }
    
    public static function having($column, $value, $operator = '=')
    {
        $having = "`{$column}` {$operator} ?";
        
        if (!static::$having) {
            $stmt = " HAVING {$having}";
        } else {
            $stmt = " AND {$having}";
        }
        
        static::$having .= $stmt;
        static::$having_binding[] = htmlspecialchars(trim((string) $value) ?? '');
        
        return static::instance();
    }
    
    public static function orderBy($column, $type = null)
    {
        $sep = static::$order_by ? ' , ' : ' ORDER BY ';
        
        $type = ($type != null && in_array(strtoupper($type), ['ASC', 'DESC'])) 
                ? strtoupper($type) 
                : 'ASC';
        
        static::$order_by .= " {$sep} {$column} {$type}";
        
        return static::instance();
    }
    
    public static function limit($limit)
    {
        static::$limit = " LIMIT {$limit}";
        return static::instance();
    }
    
    public static function offset($offset)
    {
        static::$offset = " OFFSET {$offset}";
        return static::instance();
    }
    
    public static function fetchExecute()
    {
        static::query(static::$query);
        
        $query = trim(static::$query, ' ');
        
        $data = static::$connection->prepare($query);
        $data->execute(static::$binding);
        
        static::cleare();
        
        return $data;
    }
    
    public static function get()
    {
        return self::fetchExecute()->fetchAll();
    }

    public static function first()
    {
        return self::fetchExecute()->fetch();
    }
    
    public static function execute($data, $query, $where = null)
    {
        static::instance();
        
        if (!static::$table) {
            throw new \Exception('Table name unknown');
        }
        
        if ($data) {
            foreach ($data as $key => $value)
            {
                static::$setter .= "`{$key}` = ?, ";
                static::$binding[] = filter_var($value, FILTER_UNSAFE_RAW);
            }
        }
        
        static::$setter = trim(static::$setter, ', ');
        
        $query .= static::$setter;
        $query .= ($where != null) ? static::$where : '';
        
        static::$binding = ($where != null) 
                ? array_merge(static::$binding, static::$where_binding) 
                : static::$binding;
        
        $data = static::$connection->prepare($query);
        
        $data->execute(static::$binding);
        
        static::cleare();
    }
    
    public static function insert($data)
    {
        $table = static::$table;
        
        $query = "INSERT INTO {$table} SET ";
        
        static::execute($data, $query);
        
        $object_id = static::$connection->lastInsertId();
        
        $object = static::table($table)->where('id', $object_id)->first();
        
        return $object ?? null;
    }
    
    public static function update($data)
    {
        $query = "UPDATE " . static::$table . " SET ";
        
        static::execute($data, $query, true);
        
        return true;
    }
    
    public static function delete()
    {
        $query = "DELETE FROM " . static::$table;
        
        static::execute([], $query, true);
        
        return true;
    }
    
    public static function paginate($item_per_page = 15)
    {
        static::query(static::$query);
        
        $query = trim(static::$query, ' ');
        
        $data = static::$connection->prepare($query);
        $data->execute();
        
        $pages = ceil($data->rowCount() / $item_per_page);
        
        $page = Request::get('page');
        $current_page = (!is_numeric($page) || Request::get('page') < 1) ? '1' : $page;
        
        $offset = ($current_page - 1) * $item_per_page;
        static::limit($item_per_page);
        static::offset($offset);
        
        static::query();
        
        $data = static::fetchExecute();
        $resuts = $data->fetchAll();
        
        $response = [
            'data' => $resuts,
            'items_per_page' => $item_per_page,
            'pages' => $pages,
            'current_page' => $current_page
        ];
        
        return $response;
    }
    
    public static function links($current_page, $pages)
    {
        $links = '';
        
        $from   = $current_page - 2;
        $to     = $current_page + 2;
        
        if ($from < 2) {
            $from = 2;
            $to = $from + 4;
        }
        
        if ($to >= $pages) {
            $diff   = $to - $pages + 1;
            $from   = ($from > 2) ? $from - $diff : 2;
            $to     = $pages - 1;
        }
        
        if ($from < 2) {
            $from = 1;
        }
        
        if ($to >= $pages) {
            $to = ($pages - 1);
        }
        
        if ($pages > 1) {
            $links .= "<ul class='pagination'>";
            $full_link = Url::path(Request::fullUrl());
            $full_link = preg_replace('/\?page=(.*)/', '', $full_link);
            $full_link = preg_replace('/\&page=(.*)/', '', $full_link);
            
            $current_page_active = $current_page == 1 ? 'active' : '';
            $href = strpos($full_link, '?') ? ($full_link . '&page=1') : ($full_link . '?page=1');
            $links .= "<li class='link' {$current_page_active}><a href='{$href}'>First</a></li>";
            
            for($i = $from; $i <= $to; $i++)
            {
                $current_page_active = $current_page == $i ? 'active' : '';
                $href = strpos($full_link, '?') ? ($full_link . '&page=' . $is) : ($full_link . '?page=' . $i);
                $links .= "<li class='link' {$current_page_active}><a href='{$href}'>{$i}</a></li>";
            }
            
            
            if ($pages > 1) {
                $current_page_active = $current_page == $pages ? 'active' : '';
                $href = strpos($full_link, '?') ? ($full_link . '&page=' . $pages) : ($full_link . '?page=' . $pages);
                $links .= "<li class='link' {$current_page_active}><a href='{$href}'>Last</a></li>";
            }
            
            $links .= "</ul>";
        }
        
        return $links;
    }

    public static function cleare()
    {
        static::$instance        = null;
        //static::$connection      = null;
        static::$query           = '';
        static::$select          = '';
        //static::$table           = '';
        static::$join            = '';
        static::$where           = '';
        static::$group_by        = '';
        static::$having          = '';
        static::$order_by        = '';
        static::$limit           = '';
        static::$offset          = '';
        //static::$setter          = '';
        static::$binding         = [];
        static::$where_binding   = [];
        static::$having_binding  = [];
    }
}
