<?php
namespace App\Repository;

use App\Entity\UserWardrobe;
use App\Entity\User;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserWardrobeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserWardrobe::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('uw')
            ->andWhere('uw.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findMostUsedItems(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('uw')
            ->andWhere('uw.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uw.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findFavoriteItems(User $user): array
    {
        return $this->createQueryBuilder('uw')
            ->andWhere('uw.user = :user')
            ->andWhere('uw.isFavorite = :isFavorite')
            ->setParameter('user', $user)
            ->setParameter('isFavorite', true)
            ->getQuery()
            ->getResult();
    }
    
    public function search(User $user, string $searchTerm): array
    {
        return $this->createQueryBuilder('uw')
            ->join('uw.clothingItem', 'ci')
            ->andWhere('uw.user = :user')
            ->andWhere('ci.name LIKE :searchTerm OR ci.description LIKE :searchTerm')
            ->setParameter('user', $user)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();
    }
    
    public function findByCategory(User $user, Category $category): array
    {
        return $this->createQueryBuilder('uw')
            ->join('uw.clothingItem', 'ci')
            ->andWhere('uw.user = :user')
            ->andWhere('ci.category = :category')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();
    }
}