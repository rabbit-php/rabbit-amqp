<?php
declare(strict_types=1);

namespace Rabbit\Amqp;

use Rabbit\Pool\BaseManager;
use Rabbit\Pool\BasePool;
use Rabbit\Pool\BasePoolProperties;

class MakeAmqpConnection
{
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