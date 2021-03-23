<?php
 
namespace ModocDB; 

use \Illuminate\Support\Facades\Facade;
use \Illuminate\Support\Facades\DB;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
 
 class ModocDB extends Facade { 
    
    private $host_queue = "localhost"; 
    private $port_queue = 5672; 
    private $user_queue = "guntur"; 
    private $pass_queue = "gungun123"; 
    private $vhst_queue = "myhost"; 
     
    private $modocConnection = false;
    private $now = null;
     
    
    public function __construct(){
        
        $this->now = DateTime::createFromFormat('U.u', microtime(true));
        
        $connection = new AMQPStreamConnection($this->host_queue, $this->port_queue , 
                                               $this->user_queue, $this->pass_queue , 
                                               $this->vhst_queue ); 
        $connection->channel();
        $this->modocConnection = $connection;
        
    }
    
    private function watchlog($query , $operation = null , $channel = 'logging_query'){ 
          
        $datas = array( 
            'query'     => addslashes($query), 
            'operation' => $operation , 
            'user'      => isset($_SESSION['logined_username']) ? trim($_SESSION['logined_username']) : 'public-access', 
            'datetime'  => $this->now->format("m-d-Y H:i:s.u"), 
            'remote_addr'  => $_SERVER['REMOTE_ADDR'], 
            'server_status' => $_SERVER
        );
        
        $body = json_encode($datas);
        
        $msg = new AMQPMessage($body ); 
        $d = $this->modocConnection->basic_publish($msg, '', $channel );
        
        return  DB::affectingStatement($query ); 
    }
    
    public function insert($query){ 
        
        return $this->watchlog($query , 'INSERT STATE');
    }
     
    public function update($query){ 
        
        return $this->watchlog($query , 'UPDATE STATE');
    }
    
    public function delete($query){
        
        return $this->watchlog($query , 'DELETE STATE');
    } 
    
    protected static function getFacadeAccessor()
    {
        
        return 'db';
    } 
}
