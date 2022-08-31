<?php

namespace Newageerp\SfSocket\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class SocketService
{
    protected AMQPStreamConnection $connection;

    protected AMQPChannel $channel;

    protected $pool = [];

    public function __construct()
    {
        $this->connect();
    }

    public function connect() {
        $this->connection = new AMQPStreamConnection(
            $_ENV['NAE_SFS_RBQ_HOST'],
            (int)$_ENV['NAE_SFS_RBQ_PORT'],
            $_ENV['NAE_SFS_RBQ_USER'],
            $_ENV['NAE_SFS_RBQ_PASSWORD']
        );
        $this->channel = $this->connection->channel();
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function sendTo($room, $action, $data)
    {
        $this->client->emit(
            'send',
            [
                'room' => $room,
                'payload' => [
                    'action' => $action,
                    'data' => $data
                ]
            ]
        );
    }

    public function addToPool($data) {
        $this->pool[] = $data;
    }

    public function clearPool()
    {
        $this->pool = [];
    }

    public function sendPool()
    {
        $count = count($this->pool);

        foreach ($this->pool as $el) {
            $msg = new AMQPMessage(json_encode($el));
            $this->channel->basic_publish($msg, '', $_ENV['NAE_SFS_RBQ_QUEUE']);
        }

        $this->clearPool();
        return $count;
    }
}
