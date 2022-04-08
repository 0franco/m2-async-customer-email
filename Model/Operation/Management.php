<?php
declare(strict_types=1);

namespace OH\AsyncCustomerEmail\Model\Operation;

use Magento\AsynchronousOperations\Api\Data\OperationInterface as OperationInterfaceAsync;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Bulk\OperationInterface as OperationBulkInterface;
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
     * Save operation status as complete
     *
     * @param OperationInterfaceAsync $op
     * @return object
     * @throws \Exception
     */
    public function markOperationAsResolved(OperationInterfaceAsync $op)
    {
        $op->setStatus(OperationInterface::STATUS_TYPE_COMPLETE);
        return $this->entityManager->save($op);
    }

    /**
     * Save operation status as failed retriably
     *
     * @param OperationInterfaceAsync $op
     * @return false|OperationInterfaceAsync
     */
    public function markRetriablyFailed(OperationInterfaceAsync $op)
    {
        try {
            $op->setStatus(OperationBulkInterface::STATUS_TYPE_RETRIABLY_FAILED);
            $this->entityManager->save($op);
            return $op;
        } catch (\Exception $exception) {
            return false;
        }
    }
}