<?php
/**
 * User repository.
 */

namespace App\Repository;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @param PasswordAuthenticatedUserInterface $user              User entity
     * @param string                             $newHashedPassword New hashed password
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        //        if (!$user instanceof User) {
        //            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        //        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * Add user function.
    //     */
    //    public function add(User $entity, bool $flush = false): void
    //    {
    //        $this->getEntityManager()->persist($entity);
    //
    //        if ($flush) {
    //            $this->getEntityManager()->flush();
    //        }
    //    }

    /**
     * Delete user function.
     *
     * @param User $entity User entity
     * @param bool $flush  Flush
     */
    public function delete(User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Save user function.
     *
     * @param User $user User entity
     */
    public function save(User $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Register user function.
     *
     * @param User   $user           User entity
     * @param string $hashedPassword Hashed password
     */
    public function registerUser(User $user, string $hashedPassword): void
    {
        $user->setPassword($hashedPassword);
        $user->setRoles([UserRole::ROLE_USER->value]);
        assert($this->_em instanceof EntityManager);
        $this->_em->persist($user);
        $this->_em->flush();
        $newHashedPassword = $hashedPassword;
        $this->upgradePassword($user, $newHashedPassword);
    }

    /**
     * Query all records.
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder()
            ->select(
                'user'
            )
            ->orderBy('user.id', 'DESC');
    }

    /**
     * Get or create new query builder.
     *
     * @param QueryBuilder|null $queryBuilder Query builder
     *
     * @return QueryBuilder Query builder
     */
    private function getOrCreateQueryBuilder(?QueryBuilder $queryBuilder = null): QueryBuilder
    {
        return $queryBuilder ?? $this->createQueryBuilder('user');
    }
}
