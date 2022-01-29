<?php
declare(strict_types=1);

namespace Rabbit\Amqp;

use DI\DependencyException;
use DI\NotFoundException;
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
        $manager = service('amqp');
        if (!$manager->has($name)) {
            $conn = [
                $name =>
                    create([
                        '{}' => BasePool::class,
                        'comClass' => Connection::class,
                        'poolConfig' => create([
                            '{}' => BasePoolProperties::class,
                            'config' => $config
                        ], [], false)
                    ], [], false)];
            $manager->add($conn);
        }
    }
}