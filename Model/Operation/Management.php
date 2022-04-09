<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Operation;

use Magento\AsynchronousOperations\Api\Data\OperationInterface as OperationInterfaceAsync;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;

class Management
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Change op status
     *
     * @param OperationInterfaceAsync $op
     * @param int $status
     * @return false|OperationInterfaceAsync
     */
    public function changeOpStatus(OperationInterfaceAsync $op, $status = OperationInterface::STATUS_TYPE_COMPLETE)
    {
        try {
            $op->setStatus($status);
            $this->entityManager->save($op);
            return $op;
        } catch (\Exception $exception) {
            return false;
        }
    }
}