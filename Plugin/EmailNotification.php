<?php

declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Store\Model\StoreManagerInterface;
use OH\AsyncCustomerEmail\Model\ConfigProvider;
use OH\AsyncCustomerEmail\Model\Operation\Ops;
use OH\AsyncCustomerEmail\Model\Queue\Handler\Scheduler;

class EmailNotification
{
    /**
     * @var Scheduler
     */
    private Scheduler $scheduler;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        Scheduler $scheduler
    ) {
        $this->storeManager = $storeManager;
        $this->scheduler = $scheduler;
        $this->configProvider = $configProvider;
    }

    /**
     * Save customer message
     *
     * @param \Magento\Customer\Model\EmailNotificationInterface $emailNotification
     * @param \Closure $proceed
     * @param CustomerInterface $customer
     * @param $type
     * @param $backUrl
     * @param $storeId
     * @param $sendemailStoreId
     * @return mixed|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundNewAccount(
        \Magento\Customer\Model\EmailNotificationInterface $emailNotification,
        \Closure $proceed,
        CustomerInterface $customer,
        $type = \Magento\Customer\Model\EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if (!$this->configProvider->isEnable()) {
            return $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
        }

        $this->scheduler->execute(
            [
                'entity_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'store_id' => $this->storeManager->getStore()->getId(),
                'type' => $type
            ],
            Ops::TOPIC_NAME_NEW_ACCOUNT
        );
    }

    /**
     * Save customer message
     *
     * @param \Magento\Customer\Model\EmailNotificationInterface $emailNotification
     * @param \Closure $proceed
     * @param CustomerInterface $customer
     * @return mixed|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundPasswordReminder(
        \Magento\Customer\Model\EmailNotificationInterface $emailNotification,
        \Closure $proceed,
        CustomerInterface $customer
    ) {
        if (!$this->configProvider->isEnable()) {
            return $proceed($customer);
        }

        $this->scheduler->execute(
            [
                'entity_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'store_id' => $this->storeManager->getStore()->getId()
            ],
            Ops::TOPIC_NAME_FORGOT_PWD
        );
    }

    /**
     * Save customer message
     *
     * @param \Magento\Customer\Model\EmailNotificationInterface $emailNotification
     * @param \Closure $proceed
     * @param CustomerInterface $customer
     * @return mixed|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundPasswordResetConfirmation(
        \Magento\Customer\Model\EmailNotificationInterface $emailNotification,
        \Closure $proceed,
        CustomerInterface $customer
    ) {
        if (!$this->configProvider->isEnable()) {
            return $proceed($customer);
        }

        $this->scheduler->execute(
            [
                'entity_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'store_id' => $this->storeManager->getStore()->getId()
            ],
            Ops::TOPIC_NAME_FORGOT_PWD
        );
    }

    /**
     * Save customer message
     *
     * @param \Magento\Customer\Model\EmailNotificationInterface $emailNotification
     * @param \Closure $proceed
     * @param CustomerInterface $savedCustomer
     * @param string $origCustomerEmail
     * @param bool $isPasswordChanged
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundCredentialsChanged(
        \Magento\Customer\Model\EmailNotificationInterface $emailNotification,
        \Closure $proceed,
        CustomerInterface $savedCustomer,
        $origCustomerEmail,
        $isPasswordChanged = false
    ) {
        if (!$this->configProvider->isEnable()) {
            return $proceed($savedCustomer, $origCustomerEmail, $isPasswordChanged);
        }

        $this->scheduler->execute(
            [
                'entity_id' => $savedCustomer->getId(),
                'email' => $savedCustomer->getEmail(),
                'store_id' => $this->storeManager->getStore()->getId(),
                'orig_email' => $origCustomerEmail,
                'pwd_changed' => $isPasswordChanged
            ],
            Ops::TOPIC_NAME_CREDENTIALS_CHANGED
        );
    }
}