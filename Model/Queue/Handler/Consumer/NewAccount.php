<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Queue\Handler\Consumer;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use \Magento\Customer\Model\EmailNotification;
use OH\AsyncCustomerEmail\Model\Queue\Handler\Consumer;

class NewAccount extends Consumer
{
    /**
     * Process new account
     *
     * @param OperationInterface $operation
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function process(OperationInterface $operation): bool
    {
        $unserializedData = $this->serializer->unserialize($operation->getSerializedData());
        $customer = $this->customerRepository->getById($unserializedData['entity_id']);
        $customerEmailData = $this->customerData->getFullCustomerObject($customer);
        $types = EmailNotification::TEMPLATE_TYPES;
        $type = $unserializedData['type'];

        if (!isset($types[$type])) {
            throw new LocalizedException(
                __('The transactional account email type is incorrect. Verify and try again.')
            );
        }

        if (!$storeId = $customer->getStoreId()) {
            $storeId = $unserializedData['store']['store_id'];
        }

        try {
            $this->notifier->sendEmailTemplate(
                $customer,
                $types[$type],
                EmailNotification::XML_PATH_REGISTER_EMAIL_IDENTITY,
                ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
                $storeId
            );
        } catch (MailException $e) {
            $this->logger->critical($e);
            return false;
        }

        return true;
    }
}
