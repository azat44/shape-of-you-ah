<?php

namespace App\Repository;

use App\Entity\AINotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AINotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AINotification::class);
    }

    public function findActiveNotifications(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status IN (:statuses)')
            ->setParameter('statuses', ['new', 'pending'])
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}