<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Queue\Handler;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Bulk\OperationInterface as OperationBulkInterface;
use Magento\Framework\Serialize\SerializerInterface;
use OH\AsyncCustomerEmail\Model\ConfigProvider;
use OH\AsyncCustomerEmail\Model\Operation\Management;
use OH\Core\Logger\OHLogger;
use Magento\Store\Model\StoreManagerInterface;
use OH\AsyncCustomerEmail\Model\Notifier;
use OH\AsyncCustomerEmail\Model\Customer\Data as CustomerData;

abstract class Consumer
{
    /**
     * @var OHLogger
     */
    protected OHLogger $logger;

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @var Management
     */
    protected Management $operationManagement;

    /**
     * @var EmailNotificationInterface
     */
    protected EmailNotificationInterface $emailNotification;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerData
     */
    protected CustomerData $customerData;

    /**
     * @var Notifier
     */
    protected Notifier $notifier;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ConfigProvider
     */
    protected ConfigProvider $configProvider;

    public function __construct(
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        Notifier $notifier,
        CustomerData $customerData,
        CustomerRepositoryInterface $customerRepository,
        EmailNotificationInterface $emailNotification,
        Management $operationManagement,
        OHLogger $logger,
        SerializerInterface $serializer
    ) {
        $this->storeManager = $storeManager;
        $this->notifier = $notifier;
        $this->customerRepository = $customerRepository;
        $this->emailNotification = $emailNotification;
        $this->operationManagement = $operationManagement;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->customerData = $customerData;
        $this->configProvider = $configProvider;
    }

    /**
     * Process operation
     *
     * @param OperationInterface $operation
     * @return bool
     */
    protected abstract function process(OperationInterface $operation): bool;

    /**
     * Run operation
     *
     * @param OperationInterface $operation
     * @return void
     */
    public function execute(OperationInterface $operation): void
    {
        try {
            $this->debug('TOPIC NAME: ' . $operation->getTopicName());

            $this->operationManagement->changeOpStatus($operation,
                $this->process($operation) ? OperationBulkInterface::STATUS_TYPE_COMPLETE :
                    OperationBulkInterface::STATUS_TYPE_RETRIABLY_FAILED
            );
        } catch (\Exception $e) {
            $this->operationManagement->changeOpStatus($operation, OperationBulkInterface::STATUS_TYPE_RETRIABLY_FAILED);
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Debug
     *
     * @param $data
     * @return void
     */
    protected function debug($data)
    {
        if ($this->configProvider->isDebugEnabled()) {
            $this->logger->debug($data);
        }
    }
}
