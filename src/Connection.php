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
    /** @var string */
    public ?string $queue = null;
    /** @var string */
    public ?string $exchange = null;
    /** @var array */
    protected array $connParams = [];
    /** @var array */
    protected array $queueDeclare = [];
    /** @var array */
    protected array $exchangeDeclare = [];
    /** @var array */
    protected array $queueBind = [];
    /** @var AMQPStreamConnection */
    protected AMQPStreamConnection $conn;
    /** @var AMQPChannel */
    protected AMQPChannel $channel;

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
        $this->conn = new AMQPStreamConnection(...$this->connParams);
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
        if (in_array($name, ['basic_publish'])) {
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
    public function consume(string $consumer_tag = '',
                            bool $no_local = false,
                            bool $no_ack = false,
                            bool $exclusive = false,
                            bool $nowait = false,
                            callable $callback = null,
                            int $ticket = null,
                            array $arguments = [])
    {
        $this->channel->basic_consume($this->queue, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, function (AMQPMessage $message) use ($callback) {
            call_user_func($callback, $message);
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            if ($message->body === 'quit') {
                $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
            }
        }, $ticket, $arguments);
        $this->wait();
    }

    /**
     * @throws ErrorException
     */
    public function wait(): void
    {
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }
}