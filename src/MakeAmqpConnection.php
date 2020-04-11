<?php
declare(strict_types=1);

namespace Rabbit\Amqp;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use rabbit\compool\BaseCompool;
use rabbit\compool\ComPoolProperties;
use rabbit\core\ObjectFactory;

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
        if (!$manager->hasConnection($name)) {
            $conn = [
                $name =>
                    ObjectFactory::createObject([
                        'class' => BaseCompool::class,
                        'comClass' => Connection::class,
                        'poolConfig' => ObjectFactory::createObject([
                            'class' => ComPoolProperties::class,
                            'config' => $config
                        ], [], false)
                    ], [], false)];
            $manager->addConnection($conn);
        }
    }
}