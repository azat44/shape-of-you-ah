<?php
namespace App\Repository;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProfileRepository extends ServiceEntityRepository
{
   public function __construct(ManagerRegistry $registry)
   {
       parent::__construct($registry, Profile::class);
   }

   public function createOrUpdateProfile(
       User $user, 
       string $firstName, 
       string $lastName, 
       ?string $avatarUrl = null,
       ?array $bodyMeasurements = null,
       ?string $stylePreference = null
   ): Profile {
       $profile = $this->findOneBy(['user' => $user]) ?? new Profile();

       $profile->setUser($user)
           ->setFirstName($firstName)
           ->setLastName($lastName)
           ->setAvatarUrl($avatarUrl)
           ->setBodyMeasurements($bodyMeasurements)
           ->setStylePreference($stylePreference);

       $this->getEntityManager()->persist($profile);
       $this->getEntityManager()->flush();

       return $profile;
   }

   public function getFullProfileData(User $user): array
   {
       $profile = $this->findOneBy(['user' => $user]);
       
       if (!$profile) {
           return [
               'profile' => [],
               'outfitHistory' => []
           ];
       }

       return [
           'profile' => [
               'id' => $profile->getId(),
               'firstName' => $profile->getFirstName(),
               'lastName' => $profile->getLastName(),
               'avatarUrl' => $profile->getAvatarUrl(),
               'bodyMeasurements' => $profile->getBodyMeasurements(),
               'stylePreference' => $profile->getStylePreference()
           ],
           'outfitHistory' => []
       ];
   }
   
   public function findByStylePreference(string $stylePreference): array
   {
       return $this->createQueryBuilder('p')
           ->andWhere('p.stylePreference LIKE :style')
           ->setParameter('style', '%' . $stylePreference . '%')
           ->getQuery()
           ->getResult();
   }
}