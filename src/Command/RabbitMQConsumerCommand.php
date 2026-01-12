<?php

namespace App\Command;

use App\Entity\Device;
use App\Factory\DeviceDataFactory;
use App\Factory\UnresolvedDeviceDataFactory;
use App\Repository\DeviceRepository;
use App\Service\Alarm\ValidatorCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(
    name: 'app:rabbitmq:consume',
    description: 'Consume messages from RabbitMQ'
)]
class RabbitMQConsumerCommand extends Command
{
    private const string QUEUE_NAME = 'RHTq';
    private const string LOCK_NAME = 'rabbitmq_consumer_lock';

    public function __construct(
        private readonly string $rabbitmqHost,
        private readonly int $rabbitmqPort,
        private readonly string $rabbitmqUser,
        private readonly string $rabbitmqPassword,
        private readonly ManagerRegistry $managerRegistry,
        private UnresolvedDeviceDataFactory $unresolvedDeviceDataFactory,
        private DeviceDataFactory $deviceDataFactory,
        private ValidatorCollection $validatorCollection,
        private readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $em = $this->managerRegistry->getManager();
        if (!$em->isOpen()) {
            $this->logger?->warning('EntityManager was closed, resetting...');
            $this->managerRegistry->resetManager();
            $em = $this->managerRegistry->getManager();
        }
        return $em;
    }

    private function getDeviceRepository(): DeviceRepository
    {
        return $this->getEntityManager()->getRepository(Device::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create lock factory
        $store = new FlockStore(sys_get_temp_dir());
        $factory = new LockFactory($store);
        $lock = $factory->createLock(self::LOCK_NAME);

        // Try to acquire the lock
        if (!$lock->acquire()) {
            $io->error('Another consumer is already running');
            return Command::FAILURE;
        }

        // Temporarily suppress deprecation notices from php-amqplib (PHP 8.2 dynamic properties)
        $originalErrorReporting = error_reporting();
        error_reporting($originalErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            $connection = new AMQPStreamConnection(
                $this->rabbitmqHost,
                $this->rabbitmqPort,
                $this->rabbitmqUser,
                $this->rabbitmqPassword,
                '/',     // vhost
                false,   // insist
                'AMQPLAIN', // login method
                null,    // login response
                'en_US', // locale
                10.0,    // connection timeout
                10.0,    // read timeout
                null,    // write timeout
            );

            $channel = $connection->channel();
            // Set prefetch count to 1 to process one message at a time
            $channel->basic_qos(null, 1, null);

            // Check if queue exists and get message count
            $queueInfo = $channel->queue_declare(
                self::QUEUE_NAME,
                true,   // passive - only check if queue exists
                false,  // durable
                false,  // exclusive
                false,  // auto delete
                false   // nowait
            );

            // If queue is empty, close connection and exit
            if ($queueInfo[1] == 0) {
                $channel->close();
                $connection->close();
                return Command::SUCCESS;
            }

            $callback = function (AMQPMessage $msg) use ($channel) {
                try {
                    $data = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);

                    $device = $this->getDeviceRepository()->findOneBy(['serialNumber' => $data['ID']]);

                    if (!$device) {
                        $this->saveUnresolvedDeviceData($data, $data['ID'] ?? null);
                    } else {
                        $this->processMessage($device, $data);
                    }

                    // Acknowledge the message
                    $channel->basic_ack($msg->delivery_info['delivery_tag']);
                } catch (\Exception $e) {
                    $this->logger?->error('Error processing RabbitMQ message: ' . $e->getMessage(), [
                        'exception' => $e,
                        'message_body' => substr($msg->body, 0, 500),
                    ]);

                    try {
                        $this->saveUnresolvedDeviceDataFromString($msg->body);
                    } catch (\Exception $saveException) {
                        $this->logger?->error('Failed to save unresolved device data: ' . $saveException->getMessage());
                    }

                    $channel->basic_ack($msg->delivery_info['delivery_tag']);
                }
            };

            // Start consuming
            $channel->basic_consume(
                self::QUEUE_NAME,
                '',     // consumer tag
                false,  // no local
                false,  // no ack
                false,  // exclusive
                false,  // no wait
                $callback
            );

            // Consume messages until queue is empty
            while (count($channel->callbacks)) {
                $channel->wait();

                // Check if queue is empty after each message
                $queueInfo = $channel->queue_declare(
                    self::QUEUE_NAME,
                    true,   // passive - only check if queue exists
                    false,  // durable
                    false,  // exclusive
                    false,  // auto delete
                    false   // nowait
                );

                if ($queueInfo[1] == 0) {
                    break;
                }
            }

            $channel->close();
            $connection->close();

            return Command::SUCCESS;
        } finally {
            // Restore previous error reporting level
            error_reporting($originalErrorReporting);
        }
    }

    private function processMessage(Device $device, array $data): void
    {
        $deviceData = $this->deviceDataFactory->createFromArray($device, $data);

        $em = $this->getEntityManager();
        $em->persist($deviceData);
        $em->flush();

        $this->validatorCollection->validate($deviceData, $device->getClient()->getClientSetting());
    }

    private function saveUnresolvedDeviceDataFromString(string $content): void
    {
        $unresolvedDeviceData = $this->unresolvedDeviceDataFactory->createFromString($content);
        $em = $this->getEntityManager();
        $em->persist($unresolvedDeviceData);
        $em->flush();
    }

    private function saveUnresolvedDeviceData(array $data, ?string $identifier = null): void
    {
        $unresolvedDeviceData = $this->unresolvedDeviceDataFactory->createFromArray($data, $identifier);
        $em = $this->getEntityManager();
        $em->persist($unresolvedDeviceData);
        $em->flush();
    }
} 