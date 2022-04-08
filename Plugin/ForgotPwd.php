<?php

declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\EmailNotification;
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

    public function __construct(
        ConfigProvider $configProvider,
        Scheduler $scheduler
    ) {
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
        if (!$this->configProvider->isEnable() || $customer->get('queue')) {
            return $proceed($customer);
        }

        $this->scheduler->execute(
            [
                'entity_id' => $customer->getId(),
                'name' => sprintf('%s %s', $customer->getFirstname(), $customer->getLastname()),
                'email' => $customer->getEmail()
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
                'name' => sprintf('%s %s', $customer->getFirstname(), $customer->getLastname()),
                'email' => $customer->getEmail()
            ],
            self::TOPIC_NAME
        );
    }
}