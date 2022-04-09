<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Operation;

class Ops
{
    /**
     * @var string
     */
    const TOPIC_NAME_FORGOT_PWD = 'customer.forgot.pwd';

    /**
     * @var string
     */
    const TOPIC_NAME_NEW_ACCOUNT = 'customer.new.account';

    /**
     * @var string
     */
    const TOPIC_NAME_CREDENTIALS_CHANGED = 'customer.cred.change';
}