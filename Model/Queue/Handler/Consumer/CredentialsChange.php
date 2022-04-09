<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Queue\Handler\Consumer;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use \Magento\Customer\Model\EmailNotification;
use OH\AsyncCustomerEmail\Model\Queue\Handler\Consumer;

class CredentialsChange extends Consumer
{
    /**
     * Process credentials changed
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
        $email = $unserializedData['email'];
        $origEmail = $unserializedData['orig_email'];
        $pwdChanged = $unserializedData['pwd_changed'];

        if (!$storeId = $customer->getStoreId()) {
            $storeId = $unserializedData['store']['store_id'];
        }

        $store = $this->storeManager->getStore($storeId);

        try {
            if ($origEmail != $email) {
                if ($pwdChanged) {
                    $this->notifier->sendEmailTemplate(
                        $customer,
                        EmailNotification::XML_PATH_CHANGE_EMAIL_AND_PASSWORD_TEMPLATE,
                        EmailNotification::XML_PATH_FORGOT_EMAIL_IDENTITY,
                        ['customer' => $customerEmailData, 'store' => $store],
                        $storeId,
                        $origEmail
                    );

                    $this->notifier->sendEmailTemplate(
                        $customer,
                        EmailNotification::XML_PATH_CHANGE_EMAIL_AND_PASSWORD_TEMPLATE,
                        EmailNotification::XML_PATH_FORGOT_EMAIL_IDENTITY,
                        ['customer' => $customerEmailData, 'store' => $store],
                        $storeId,
                        $customer->getEmail()
                    );

                    return true;
                }

                $this->notifier->sendEmailTemplate(
                    $customer,
                    EmailNotification::XML_PATH_CHANGE_EMAIL_TEMPLATE,
                    EmailNotification::XML_PATH_FORGOT_EMAIL_IDENTITY,
                    ['customer' => $customerEmailData, 'store' => $store],
                    $storeId,
                    $origEmail
                );

                $this->notifier->sendEmailTemplate(
                    $customer,
                    EmailNotification::XML_PATH_CHANGE_EMAIL_TEMPLATE,
                    EmailNotification::XML_PATH_FORGOT_EMAIL_IDENTITY,
                    ['customer' => $customerEmailData, 'store' => $store],
                    $storeId,
                    $customer->getEmail()
                );
                return true;
            }

            if ($pwdChanged) {
                $this->notifier->sendEmailTemplate(
                    $customer,
                    EmailNotification::XML_PATH_RESET_PASSWORD_TEMPLATE,
                    EmailNotification::XML_PATH_FORGOT_EMAIL_IDENTITY,
                    ['customer' => $customerEmailData, 'store' => $store],
                    $storeId
                );
            }
        } catch (MailException $e) {
            $this->logger->critical($e);
            return false;
        }

        return true;
    }
}
