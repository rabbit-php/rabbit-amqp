<?php
declare(strict_types=1);

namespace Rabbit\Amqp;

use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use rabbit\compool\AbstractCom;
use rabbit\exception\NotSupportedException;

/**
 * Class Connection
 * @package Rabbit\Amqp
 */
class Connection extends AbstractCom
{
    /** @var string */
    public $queue;
    /** @var string */
    public $exchange;
    /** @var array */
    protected $connParams = [];
    /** @var array */
    protected $queueDeclare = [];
    /** @var array */
    protected $exchangeDeclare = [];
    /** @var array */
    protected $queueBind = [];
    /** @var AMQPStreamConnection */
    protected $conn;
    /** @var AMQPChannel */
    protected $channel;

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
     * @throws ErrorException
     */
    public function wait(): void
    {
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }
}