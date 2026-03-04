<?php

namespace App\Repository;

use App\Entity\Unit;
use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Unit>
 */
class UnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Unit::class);
    }

    /**
     * Compte les unités disponibles (is_free = true).
     * Si une Offer est fournie et qu'il existe une relation entre Unit et Offer,
     * adaptez la requête pour filtrer par offre.
     *
     * @param Offer|null $offer
     * @return int
     */
    public function countAvailable(?Offer $offer = null): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.is_free = :free')
            ->setParameter('free', true);

        // TODO: si Unit est lié à Offer via une relation, filtrer ici par offer
        // ex: ->andWhere('u.offer = :offer')->setParameter('offer', $offer)

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Récupère un ensemble d'unités libres (is_free = true), limitée par $limit.
     * Si une Offer est fournie et qu'il existe une relation entre Unit et Offer,
     * adaptez la requête pour filtrer par offre.
     *
     * @param Offer|null $offer
     * @param int|null $limit
     * @return Unit[]
     */
    public function findAvailable(?Offer $offer = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.is_free = :free')
            ->setParameter('free', true);

        // TODO: filtrer par offer si la relation existe
        // if ($offer) {
        //     $qb->andWhere('u.offer = :offer')->setParameter('offer', $offer);
        // }

        $qb->orderBy('u.id', 'ASC');

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Unit[] Returns an array of Unit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Unit
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
