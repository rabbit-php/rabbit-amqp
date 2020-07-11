<?php
declare(strict_types=1);

namespace Rabbit\Amqp;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Rabbit\Pool\BaseManager;
use Rabbit\Pool\BasePool;
use Rabbit\Pool\BasePoolProperties;
use Throwable;

/**
 * Class MakeAmqpConnection
 * @package Rabbit\Amqp
 */
class MakeAmqpConnection
{
    /**
     * @param string $name
     * @param array $config
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Throwable
     */
    public static function addConnection(string $name, array $config = []): void
    {
        /** @var BaseManager $manager */
        $manager = getDI('amqp');
        if (!$manager->has($name)) {
            $conn = [
                $name =>
                    create([
                        'class' => BasePool::class,
                        'comClass' => Connection::class,
                        'poolConfig' => create([
                            'class' => BasePoolProperties::class,
                            'config' => $config
                        ], [], false)
                    ], [], false)];
            $manager->add($conn);
        }
    }
}