<?php
declare(strict_types=1);

namespace Rabbit\Amqp;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use rabbit\core\ObjectFactory;
use rabbit\pool\BasePool;
use rabbit\pool\BasePoolProperties;

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
     * @throws Exception
     */
    public static function addConnection(string $name, array $config = []): void
    {
        /** @var Manager $manager */
        $manager = getDI('amqp');
        if (!$manager->has($name)) {
            $conn = [
                $name =>
                    ObjectFactory::createObject([
                        'class' => BasePool::class,
                        'comClass' => Connection::class,
                        'poolConfig' => ObjectFactory::createObject([
                            'class' => BasePoolProperties::class,
                            'config' => $config
                        ], [], false)
                    ], [], false)];
            $manager->add($conn);
        }
    }
}