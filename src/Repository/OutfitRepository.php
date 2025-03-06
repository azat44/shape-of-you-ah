<?php 
namespace App\Repository;

use App\Entity\Outfit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OutfitRepository extends ServiceEntityRepository 
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Outfit::class);
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
    
    public function findByStyle(string $style, int $limit = 20): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.style = :style')
            ->setParameter('style', $style)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    public function search(User $user, ?string $query = null, ?string $style = null): array
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
        
        return $qb->orderBy('o.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }
    
    public function findWithMinItems(int $minItems = 3): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.clothingItems', 'ci')
            ->groupBy('o.id')
            ->having('COUNT(ci.id) >= :minItems')
            ->setParameter('minItems', $minItems)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}