<?php
declare(strict_types=1);

namespace Rabbit\Amqp;

use rabbit\pool\BaseManager;

/**
 * Class Manager
 * @package Rabbit\Amqp
 */
class Manager extends BaseManager
{
    /**
     * @param string $name
     * @return mixed|null
     */
    public function getConnection(string $name = 'amqp'):?Connection
    {
        if (!isset($this->connections[$name])) {
            return null;
        }
        return $this->connections[$name]->getCom();
    }
}