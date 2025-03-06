<?php
namespace App\Repository;

use App\Entity\ClothingItem;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClothingItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClothingItem::class);
    }

    public function searchItems(?string $query = null, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('i');
        
        if ($query) {
            $qb->andWhere('i.name LIKE :query OR i.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        if (!empty($filters['category'])) {
            $qb->andWhere('i.category = :category')
               ->setParameter('category', $filters['category']);
        }
        
        if (!empty($filters['color'])) {
            $qb->andWhere('i.color LIKE :color')
               ->setParameter('color', '%' . $filters['color'] . '%');
        }
        
        if (!empty($filters['style'])) {
            $qb->andWhere('i.style LIKE :style')
               ->setParameter('style', '%' . $filters['style'] . '%');
        }
        
        if (isset($filters['minPrice']) && $filters['minPrice'] !== '') {
            $qb->andWhere('i.price >= :minPrice')
               ->setParameter('minPrice', $filters['minPrice']);
        }
        
        if (isset($filters['maxPrice']) && $filters['maxPrice'] !== '') {
            $qb->andWhere('i.price <= :maxPrice')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }
        
        $qb->orderBy('i.name', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
    
    public function findByCategory(Category $category): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.category = :category')
            ->setParameter('category', $category)
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findByStyle(string $style): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.style LIKE :style')
            ->setParameter('style', '%' . $style . '%')
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findLatestItems(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}