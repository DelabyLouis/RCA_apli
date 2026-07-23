<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use App\Entity\Transaction;

class TransactionFlushListener implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postUpdate,
            Events::postPersist,
            Events::postFlush,
        ];
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Transaction) {
            return;
        }

        $id = $entity->getIdTransaction();
        $numOrdre = $entity->getNumeroOrdre();
        error_log("[TransactionFlushListener::postUpdate] Transaction ID={$id}, NumeroOrdre={$numOrdre}");
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Transaction) {
            return;
        }

        $id = $entity->getIdTransaction();
        $numOrdre = $entity->getNumeroOrdre();
        error_log("[TransactionFlushListener::postPersist] Transaction ID={$id}, NumeroOrdre={$numOrdre}");
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        error_log("[TransactionFlushListener::postFlush] Flush completed successfully");
    }
}
