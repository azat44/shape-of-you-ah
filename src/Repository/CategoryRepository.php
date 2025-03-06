<?php
namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findBySearchTerm(string $term): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :term')
            ->orWhere('c.description LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findNonEmptyCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.clothingItems', 'ci')
            ->groupBy('c.id')
            ->having('COUNT(ci.id) > 0')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}