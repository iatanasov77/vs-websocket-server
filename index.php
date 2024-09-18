<?php
/**
 * MANUAL: http://socketo.me/docs/hello-world
 */
error_reporting( E_ERROR | E_WARNING | E_PARSE );
require dirname(__FILE__) . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocket implements MessageComponentInterface
{
    /** @var \SplObjectStorage */
    protected $clients;
    
    /** @var string */
    protected $logFile;
    
    public function __construct()
    {
        $this->clients  = new \SplObjectStorage;
        $this->logFile  = '/var/log/websocket.log';
    }
    
    public function onOpen( ConnectionInterface $conn )
    {
        // Store the new connection to send messages to later
        $this->clients->attach( $conn );
        $this->log( "New connection! ({$conn->resourceId})\n" );
    }
    
    public function onMessage( ConnectionInterface $from, $msg )
    {
        /*
        $numRecv = count( $this->clients ) - 1;
        echo sprintf( 
            'Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's'
        );
        */
        
        $this->log( "Sending Message: {$msg}\n" );
        foreach ( $this->clients as $client ) {
            if ( $from !== $client ) {
                // The sender is not the receiver, send to each client connected
                $client->send( $msg );
            }
        }
    }
    
    public function onClose( ConnectionInterface $conn )
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach( $conn );
        $this->log( "Connection {$conn->resourceId} has disconnected\n" );
    }
    
    public function onError( ConnectionInterface $conn, \Exception $e )
    {
        $this->log( "An error has occurred: {$e->getMessage()}\n" );
        $conn->close();
    }
    
    private function log( $logData ): void
    {
        \file_put_contents( $this->logFile, $logData, FILE_APPEND | LOCK_EX );
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocket()
        )
    ),
    $argv[1]
);

$server->run();
