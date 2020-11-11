<?php
declare(strict_types=1);

namespace User\Repository;

use User\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class UserRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\Query
     */
    public function findAllUsers(): Query
    {
        $entityManager = $this->getEntityManager();

        $queryBuilder = $entityManager->createQueryBuilder();

        $queryBuilder->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.dateCreated', 'DESC');

        return $queryBuilder->getQuery();
    }

    /**
     * @param string $email
     * @return object|null
     */
    public function findOneByEmail(string $email)
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @param User $user
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(User $user): void
    {
        if ($user->getId() == null) {
            $this->_em->persist($user);
        }

        $this->_em->flush();
    }
}
