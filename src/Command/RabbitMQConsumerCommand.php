<?php

namespace App\Command;

use App\Entity\Device;
use App\Factory\DeviceDataFactory;
use App\Factory\UnresolvedDeviceDataFactory;
use App\Repository\DeviceRepository;
use App\Service\Alarm\ValidatorCollection;
use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
        private EntityManagerInterface $entityManager,
        private UnresolvedDeviceDataFactory $unresolvedDeviceDataFactory,
        private DeviceDataFactory $deviceDataFactory,
        private DeviceRepository $deviceRepository,
        private ValidatorCollection $validatorCollection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('RabbitMQ Consumer');

        // Create lock factory
        $store = new FlockStore(sys_get_temp_dir());
        $factory = new LockFactory($store);
        $lock = $factory->createLock(self::LOCK_NAME);

        // Try to acquire the lock
        if (!$lock->acquire()) {
            $io->error('Another consumer is already running');
            return Command::FAILURE;
        }

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

        $callback = function (AMQPMessage $msg) use ($io, $channel) {
            try {
                $data = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);

                $device = $this->deviceRepository->findOneBy(['serialNumber' => $data['ID']]);

                if (!$device) {
                    $this->saveUnresolvedDeviceData($data);
                } else {
                    $this->processMessage($device, $data);
                }

                // Acknowledge the message
                $channel->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                $io->error(sprintf('Error processing message: %s, Content: %s', $e->getMessage(), $msg->body));
                // Reject the message and requeue
                $channel->basic_nack($msg->delivery_info['delivery_tag'], false, true);
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
    }

    private function processMessage(Device $device, array $data): void
    {
        $deviceData = $this->deviceDataFactory->createFromArray($device, $data);

        $this->entityManager->persist($deviceData);
        $this->entityManager->flush();

        $this->validatorCollection->validate($deviceData, $device->getClient()->getClientSetting());
    }

    private function saveUnresolvedDeviceData(array $data): void
    {
        $unresolvedDeviceData = $this->unresolvedDeviceDataFactory->createFromArray($data);
        $this->entityManager->persist($unresolvedDeviceData);
        $this->entityManager->flush();
    }
} 