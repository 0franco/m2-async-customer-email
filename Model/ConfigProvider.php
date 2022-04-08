<?php

declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ConfigProvider
 * @package OH\AsyncCustomerEmail\Model
 */
class ConfigProvider
{
    /**
     * @var string
     */
    const XML_CONFIG_PATH_ENABLED = 'customer/async_email/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeInterface;

    public function __construct(
        ScopeConfigInterface $scopeInterface
    ) {
        $this->scopeInterface = $scopeInterface;
    }

    /**
     * Check if is enabled
     *
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->scopeInterface->isSetFlag(self::XML_CONFIG_PATH_ENABLED, ScopeInterface::SCOPE_WEBSITE);
    }
}
