<?php
/**
 * Created by PhpStorm.
 * User: Christiaan Goslinga
 * Date: 5-4-2017
 * Time: 14:51
 */

namespace Integrated\Bundle\ThemeBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ThemePathRepository extends EntityRepository
{
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
