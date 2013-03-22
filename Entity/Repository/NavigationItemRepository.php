<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

/**
 * NavigationItem Repository
 */
class NavigationItemRepository extends EntityRepository implements NavigationRepositoryInterface
{
    /**
     * Find all Favorite items for specified user
     *
     * @param \Oro\Bundle\UserBundle\Entity\User $user
     * @param string $type
     *
     * @return array
     */
    public function getNavigationItems($user, $type)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->add(
            'select',
            new Expr\Select(
                array(
                    'ni.id',
                    'ni.url',
                    'ni.title',
                    'ni.type'
                )
            )
        )
        ->add('from', new Expr\From('Oro\Bundle\NavigationBundle\Entity\NavigationItem', 'ni'))
        ->add(
            'where',
            $qb->expr()->andx(
                $qb->expr()->eq('ni.user', ':user'),
                $qb->expr()->eq('ni.type', ':type')
            )
        )
        ->add('orderBy', new Expr\OrderBy('ni.position', 'ASC'))
        ->setParameters(array('user' => $user, 'type' => $type));

        return $qb->getQuery()->getArrayResult();
    }
}
