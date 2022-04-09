<?php

declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecure;
use Magento\Framework\Reflection\DataObjectProcessor;

class Data
{
    private $customerRegistry;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;

    public function __construct(
        CustomerRegistry $customerRegistry,
        CustomerViewHelper $customerViewHelper,
        DataObjectProcessor $dataProcessor
    ) {
        $this->customerRegistry = $customerRegistry;
        $this->customerViewHelper = $customerViewHelper;
        $this->dataProcessor = $dataProcessor;
    }


    /**
     * Create an object with data merged from Customer and CustomerSecure
     *
     * @param CustomerInterface $customer
     * @return CustomerSecure
     */
    public function getFullCustomerObject($customer): CustomerSecure
    {
        // No need to flatten the custom attributes or nested objects since the only usage is for email templates and
        // object passed for events
        $mergedCustomerData = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerData = $this->dataProcessor
            ->buildOutputDataArray($customer, CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));
        return $mergedCustomerData;
    }
}