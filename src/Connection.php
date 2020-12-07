<?php

declare(strict_types=1);

namespace Rabbit\Amqp;

use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Rabbit\Base\Exception\NotSupportedException;
use Rabbit\Pool\AbstractBase;

/**
 * Class Connection
 * @package Rabbit\Amqp
 */
class Connection extends AbstractBase
{
    public ?string $queue = null;
    public ?string $exchange = null;
    protected array $queueDeclare = [];
    protected array $exchangeDeclare = [];
    protected array $queueBind = [];
    protected ?AMQPStreamConnection $conn = null;
    protected ?AMQPChannel $channel = null;
    protected string $dsn = 'tcp://127.0.0.1:5672';

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->channel && $this->channel->close();
        $this->conn && $this->conn->close();
    }

    public function init(): void
    {
        $parseArr = parse_url($this->dsn);
        $this->conn = new AMQPStreamConnection(...[
            $parseArr['host'] ?? '127.0.0.1',
            $parseArr['port'] ?? 5672,
            $parseArr['user'] ?? '',
            $parseArr['pass'] ?? '',
            $parseArr['path'] ?? '/'
        ]);
        $this->channel = $this->conn->channel();
        array_unshift($this->queueDeclare, $this->queue);
        $this->channel->queue_declare(...$this->queueDeclare);
        array_unshift($this->exchangeDeclare, $this->exchange);
        $this->channel->exchange_declare(...$this->exchangeDeclare);
        $bind = array_merge([$this->queue, $this->exchange], $this->queueBind);
        $this->channel->queue_bind(...$bind);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws NotSupportedException
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this->channel, $name)) {
            throw new NotSupportedException("have not $name");
        }
        $result = $this->channel->$name(...$arguments);
        if (in_array($name, ['basic_publish', 'batch_basic_publish'])) {
            $this->release();
        }
        return $result;
    }

    /**
     * @param string $queue
     * @param string $consumer_tag
     * @param bool $no_local
     * @param bool $no_ack
     * @param bool $exclusive
     * @param bool $nowait
     * @param callable|null $callback
     * @param int|null $ticket
     * @param array $arguments
     * @throws ErrorException
     */
    public function consume(
        string $consumer_tag = '',
        bool $no_local = false,
        bool $no_ack = false,
        bool $exclusive = false,
        bool $nowait = false,
        callable $callback = null,
        int $ticket = null,
        array $arguments = []
    ) {
        $this->channel->basic_consume($this->queue, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, function (AMQPMessage $message) use ($callback) {
            call_user_func($callback, $message);
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            if ($message->body === 'quit') {
                $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
            }
        }, $ticket, $arguments);
        return $this;
    }

    /**
     * @throws ErrorException
     */
    public function wait(bool &$wait = true): void
    {
        while ($wait) {
            if ($this->channel->is_consuming()) {
                $this->channel->wait();
            }
        }
    }
}
