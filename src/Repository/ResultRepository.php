<?php

namespace App\Repository;

use App\Entity\Result;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Result|null find($id, $lockMode = null, $lockVersion = null)
 * @method Result|null findOneBy(array $criteria, array $orderBy = null)
 * @method Result[]    findAll()
 * @method Result[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<Result>
 */
class ResultRepository extends ServiceEntityRepository implements ResultRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    /**
     * @inheritDoc
     */
    public function findAllSorted(?string $sort, string $order = 'ASC'): array
    {
        $sortField = $sort ?: 'id';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        return $this->findBy([], [$sortField => $order]);
    }

    /**
     * @inheritDoc
     */
    public function findByUserSorted(UserInterface $user, ?string $sort, string $order = 'ASC'): array
    {
        $sortField = $sort ?: 'id';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        return $this->findBy(
            ['user' => $user],
            [$sortField => $order]
        );
    }

    public function findById(int $resultId): Result|null {
        return $this->findOneBy(['id'=>$resultId]);
    }

    public function save(Result $result):void{
        $this->getEntityManager()->persist($result);
        $this->getEntityManager()->flush();
    }
    public function remove(Result $result): void{
        $this->getEntityManager()->remove($result);
        $this->getEntityManager()->flush();
    }
}
