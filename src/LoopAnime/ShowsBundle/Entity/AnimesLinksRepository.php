<?php

namespace LoopAnime\ShowsBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Animes_LinksRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnimesLinksRepository extends EntityRepository
{

    public function getLinksByEpisode($idEpisode, $getResults = true)
    {
        $query = $this->createQueryBuilder('al')
            ->select('al')
            ->where('al.idEpisode = :idEpisode')
            ->setParameter('idEpisode',$idEpisode)
            ->getQuery();

        if($getResults)
            return $query->getResult();
        else
            return $query;
    }

}
