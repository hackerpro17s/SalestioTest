<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExchangeRate>
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    public function findOneByCurrency(string $currency): ?ExchangeRate
    {
        return $this->findOneBy(['currency' => strtoupper($currency)]);
    }

    public function upsert(string $currency, string $rate, string $baseCurrency, \DateTimeImmutable $updatedAt): ExchangeRate
    {
        $currency = strtoupper($currency);
        $entity = $this->findOneByCurrency($currency);

        if (null === $entity) {
            $entity = (new ExchangeRate())
                ->setCurrency($currency);
            $this->getEntityManager()->persist($entity);
        }

        $entity
            ->setRate($rate)
            ->setBaseCurrency($baseCurrency)
            ->setUpdatedAt($updatedAt);

        return $entity;
    }
}
