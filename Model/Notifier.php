<?php

declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;

class Notifier
{
    /**#@-*/

    /**#@-*/
    private $customerRegistry;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var Emulation
     */
    private $emulation;

    public function __construct(
        CustomerRegistry $customerRegistry,
        TransportBuilder $transportBuilder,
        CustomerViewHelper $customerViewHelper,
        ScopeConfigInterface $scopeConfig,
        SenderResolverInterface $senderResolver = null,
        Emulation $emulation = null
    ) {
        $this->customerRegistry = $customerRegistry;
        $this->transportBuilder = $transportBuilder;
        $this->customerViewHelper = $customerViewHelper;
        $this->scopeConfig = $scopeConfig;
        $this->senderResolver = $senderResolver ?? ObjectManager::getInstance()->get(SenderResolverInterface::class);
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
    }


    /**
     * Send corresponding email template
     *
     * @param CustomerInterface $customer
     * @param string $template configuration path of email template
     * @param string $sender configuration path of email identity
     * @param array $templateParams
     * @param int|null $storeId
     * @param string $email
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendEmailTemplate(
        $customer,
        $template,
        $sender,
        $templateParams = [],
        $storeId = null,
        $email = null
    ): void {

        $templateId = $this->scopeConfig->getValue($template, ScopeInterface::SCOPE_STORE, $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }

        /** @var array $from */
        $from = $this->senderResolver->resolve(
            $this->scopeConfig->getValue($sender, ScopeInterface::SCOPE_STORE, $storeId),
            $storeId
        );

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($templateParams)
            ->setFromByScope($from)
            ->addTo($email, $this->customerViewHelper->getCustomerName($customer))
            ->getTransport();

        $this->emulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND);
        $transport->sendMessage();
        $this->emulation->stopEnvironmentEmulation();
    }
}