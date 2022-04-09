<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Queue\Handler;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Bulk\OperationInterface as OperationBulkInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Serialize\SerializerInterface;
use OH\AsyncCustomerEmail\Model\Operation\Management;
use OH\AsyncCustomerEmail\Model\Operation\Ops;
use OH\Core\Logger\OHLogger;

class Consumer
{
    /**
     * @var OHLogger
     */
    private OHLogger $logger;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var Management
     */
    private Management $operationManagement;

    /**
     * @var EmailNotificationInterface
     */
    private EmailNotificationInterface $emailNotification;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var \OH\AsyncCustomerEmail\Model\Customer\Data
     */
    private $customerData;

    /**
     * @var \OH\AsyncCustomerEmail\Model\Notifier
     */
    private $notifier;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \OH\AsyncCustomerEmail\Model\Notifier $notifier,
        \OH\AsyncCustomerEmail\Model\Customer\Data $customerData,
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
    }

    /**
     * Run operation
     *
     * @param OperationInterface $operation
     * @return void
     */
    public function execute(OperationInterface $operation): void
    {
        try {
            $this->runOperation($operation);
            $this->operationManagement->changeOpStatus($operation);
            $this->logger->debug(sprintf('Consumer %s executed', get_class($this)));
        } catch (\Exception $e) {
            $this->operationManagement->changeOpStatus($operation, OperationBulkInterface::STATUS_TYPE_RETRIABLY_FAILED);
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Runs operations based on topic
     *
     * @param $operation
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    private function runOperation($operation)
    {
        $this->logger->debug('TOPIC NAME: ' . $operation->getTopicName());

        switch ($operation->getTopicName()) {
            case Ops::TOPIC_NAME_FORGOT_PWD:
                $this->processForgotPwd($operation);
                break;
            default:
                throw new \Exception('Invalid operation');
        }
    }

    /**
     * Process forgot password
     *
     * @param $operation
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processForgotPwd($operation)
    {
        $unserializedData = $this->serializer->unserialize($operation->getSerializedData());
        $customer = $this->customerRepository->getById($unserializedData['entity_id']);
        $customerEmailData = $this->customerData->getFullCustomerObject($customer);

        if (!$storeId = $customer->getStoreId()) {
            $storeId = $unserializedData['store']['store_id'];
        }

        try {
            $this->notifier->sendEmailTemplate(
                $customer,
                \Magento\Customer\Model\EmailNotification::XML_PATH_FORGOT_EMAIL_TEMPLATE,
                \Magento\Customer\Model\EmailNotification::XML_PATH_FORGOT_EMAIL_IDENTITY,
                ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
                $storeId
            );
        } catch (MailException $e) {
            $this->logger->critical($e);
        }
    }
}
