<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Queue\Handler\Consumer;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Exception\MailException;
use OH\AsyncCustomerEmail\Model\Queue\Handler\Consumer;

class ForgotPwd extends Consumer
{
    /**
     * Process forgot password
     *
     * @param OperationInterface $operation
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function process(OperationInterface $operation): bool
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
            return false;
        }

        return true;
    }
}
