<?php

namespace Newageerp\SfSocket\Service;

use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use \Predis\Client;
use Psr\Log\LoggerInterface;

class WsUpdateService
{
    protected ?Client $client = null;

    protected LoggerInterface $ajLogger;

    protected ProducerInterface $producer;

    protected EntityManagerInterface $em;

    protected SocketService $socketService;


    public function __construct(
        LoggerInterface $ajLogger,
        ProducerInterface $producer,
        EntityManagerInterface $em,
        SocketService $socketService,
    ) {
        $this->ajLogger = $ajLogger;
        $this->producer = $producer;
        $this->em = $em;
        $this->socketService = $socketService;

        // $this->ajLogger->warning('WsUpdateService CONNECT');
    }

    public function onEntityUpdateFromClass(string $class, int $id)
    {
        // $this->ajLogger->warning('onEntityUpdateFromClass ' . $class . ' ' . $id);

        $repo = $this->em->getRepository($class);
        $entity = $repo->find($id);
        if ($entity) {
            $this->onEntityUpdate($entity);
        }
    }

    public function onEntityUpdate($entity)
    {
        $timeStart = microtime(true);
        $list = $this->getClient()->keys("*");

        foreach ($list as $key) {
            $checkList = json_decode($this->getClient()->get($key), true);
            $isNeedWsSend = 0;
            if (!$checkList) {
                continue;
            }
            foreach ($checkList as $pathKey => $value) {
                $lastVal = $entity;

                $path = explode(".", $pathKey);
                $schema = $path[0];
                $entityClass = $this->convertSchemaToEntity($schema);
                unset($path[0]);
                $path = array_values($path);

                $isInstance = $entity::class === $entityClass;

                if ($isInstance) {
                    // $this->ajLogger->warning(implode(" ", [$entity::class, $entityClass, $isInstance ? "TRUE" : "FALSE"]));

                    foreach ($path as $getKey) {
                        $getMethod = 'get' . $getKey;
                        if (is_object($lastVal) && method_exists($lastVal, $getMethod)) {
                            $lastVal = $lastVal->$getMethod();
                        } else {
                            break;
                        }
                    }
                    if ($lastVal === $value) {
                        $isNeedWsSend++;
                    }
                }
            }
            // $this->ajLogger->warning(implode(" ", [$isNeedWsSend, count($checkList)]));
            if ($isNeedWsSend === count($checkList)) {
                // $this->ajLogger->warning(implode(" ", [$key, count($checkList)]));

                $this->socketService->addToPool(
                    [
                        'room' => $key,
                        'action' => $key,
                        'body' => [
                            'id' => $entity->getId(),
                            'class' => str_replace('App\Entity\\', '', $entity::class)
                        ]
                    ]
                );
            }
        }
        $timeFinish = microtime(true);

        // $this->ajLogger->warning('onEntityUpdate ' . $entity::class .' ' . number_format($timeFinish - $timeStart, 5));
    }

    protected function convertSchemaToEntity(string $schema)
    {
        $entityClass = implode('', array_map('ucfirst', explode("-", $schema)));

        return 'App\Entity\\' . $entityClass;
    }

    /**
     * Get the value of client
     *
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client($_ENV['WS_REDIS_DSN'], ['parameters' => ['database' => 2]]);
        }
        return $this->client;
    }
}
