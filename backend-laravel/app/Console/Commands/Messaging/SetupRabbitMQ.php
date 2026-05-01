<?php

namespace App\Console\Commands\Messaging;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

class SetupRabbitMQ extends Command
{
    protected $signature = 'messaging:setup-rabbitmq';
    protected $description = 'Setup RabbitMQ exchanges and queues for HR-Manager messaging';

    public function handle(): int
    {
        $host = config('queue.connections.rabbitmq.host', '127.0.0.1');
        $port = config('queue.connections.rabbitmq.port', 5672);
        $user = config('queue.connections.rabbitmq.user', 'guest');
        $password = config('queue.connections.rabbitmq.password', 'guest');
        $vhost = config('queue.connections.rabbitmq.vhost', '/');
        $exchange = config('queue.connections.rabbitmq.exchange', 'hr_manager_messages');

        try {
            $this->info('Connecting to RabbitMQ...');
            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $channel = $connection->channel();

            $this->info("Declaring exchange: {$exchange}");
            $channel->exchange_declare(
                $exchange,
                AMQPExchangeType::DIRECT,
                false,
                true,
                false
            );

            $queues = [
                'hr_messages' => 'hr.to.manager',
                'manager_messages' => 'manager.to.hr',
            ];

            foreach ($queues as $queueName => $routingKey) {
                $this->info("Declaring queue: {$queueName} with routing key: {$routingKey}");
                $channel->queue_declare($queueName, false, true, false, false);
                $channel->queue_bind($queueName, $exchange, $routingKey);
            }

            $channel->close();
            $connection->close();

            $this->info('RabbitMQ setup completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to setup RabbitMQ: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}