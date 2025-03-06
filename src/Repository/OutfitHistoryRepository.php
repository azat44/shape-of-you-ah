<?php
namespace App\Repository;

use App\Entity\OutfitHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OutfitHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutfitHistory::class);
    }
    
    public function findSharedOutfits(int $limit = 20): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isShared = :isShared')
            ->setParameter('isShared', true)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    public function search(User $user, ?string $query = null, ?string $style = null, ?bool $shared = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user);
            
        if ($query) {
            $qb->andWhere('o.title LIKE :query OR o.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        if ($style) {
            $qb->andWhere('o.style = :style')
               ->setParameter('style', $style);
        }
        
        if ($shared !== null) {
            $qb->andWhere('o.isShared = :shared')
               ->setParameter('shared', $shared);
        }
        
        return $qb->orderBy('o.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }
    
    public function findByStyle(string $style, int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.style = :style')
            ->setParameter('style', $style)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}