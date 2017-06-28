<?php

/*
* This file is part of the Integrated package.
*
* (c) e-Active B.V. <integrated@e-active.nl>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Integrated\Bundle\ThemeBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Christiaan Goslinga
 */
class ThemePathRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function findOneByThemePath($path, $content)
    {
        return $this->createQueryBuilder('themepath')
            ->andWhere('themepath.path = :path')
            ->setParameter('path', $path)
            ->andWhere('themepath.content != :content')
            ->setParameter('content', $content)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
