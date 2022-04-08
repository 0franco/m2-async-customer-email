<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Queue\Handler;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\EntityManager\EntityManager;
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
     * @var CustomerInterfaceFactory
     */
    private CustomerInterfaceFactory $customerFactory;

    private $customerRepository;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        EmailNotificationInterface $emailNotification,
        Management $operationManagement,
        OHLogger $logger,
        EntityManager $entityManager,
        SerializerInterface $serializer
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->emailNotification = $emailNotification;
        $this->operationManagement = $operationManagement;
        $this->logger = $logger;
        $this->serializer = $serializer;
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
            $this->operationManagement->markOperationAsResolved($operation)
            $this->logger->debug(sprintf('Consumer %s executed', get_class($this)));
        } catch (\Exception $e) {
            $this->operationManagement->markRetriablyFailed($operation);
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
        $this->emailNotification->passwordReminder($customer);
    }
}
