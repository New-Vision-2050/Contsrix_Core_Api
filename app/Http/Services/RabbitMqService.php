<?php

namespace App\Http\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqService
{
    private $connection;
    private $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD'),
            env('RABBITMQ_VHOST')
        );

        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare(
            config('queue.connections.rabbitmq.options.exchange.name'),
            config('queue.connections.rabbitmq.options.exchange.type'),
            false,
            true,
            false
        );
    }

    public function sendMessage(string $queue,  $message)
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->queue_bind($queue, config('queue.connections.rabbitmq.options.exchange.name'));

        $message = json_encode($message);
        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, '', $queue);
    }

    public function consumeMessages(string $queue, callable $callback)
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->queue_bind($queue, config('queue.connections.rabbitmq.options.exchange.name'));
        $this->channel->basic_consume($queue, '', false, true, false, false, $callback);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }


    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

}
