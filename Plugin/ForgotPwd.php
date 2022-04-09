<?php

declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\EmailNotification;
use Magento\Store\Model\StoreManagerInterface;
use OH\AsyncCustomerEmail\Model\ConfigProvider;
use OH\AsyncCustomerEmail\Model\Operation\Ops;
use OH\AsyncCustomerEmail\Model\Queue\Handler\Scheduler;

class ForgotPwd
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
    private $storeManager;

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
     * @param EmailNotification $emailNotification
     * @param \Closure $proceed
     * @param CustomerInterface $customer
     * @return mixed|void
     */
    public function aroundPasswordReminder(EmailNotification $emailNotification, \Closure $proceed, CustomerInterface $customer)
    {
        if (!$this->configProvider->isEnable()) {
            return $proceed($customer);
        }

        $this->scheduler->execute(
            [
                'entity_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'store' => $this->storeManager->getStore()->getData()
            ],
            Ops::TOPIC_NAME_FORGOT_PWD
        );
    }

    /**
     * Save customer message
     *
     * @param EmailNotification $emailNotification
     * @param \Closure $proceed
     * @param CustomerInterface $customer
     * @return mixed|void
     */
    public function aroundPasswordResetConfirmation(EmailNotification $emailNotification, \Closure $proceed, CustomerInterface $customer)
    {
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
}