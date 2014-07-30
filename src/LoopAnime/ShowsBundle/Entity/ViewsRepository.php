<?php

namespace LoopAnime\ShowsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use LoopAnime\UsersBundle\Entity\Users;

/**
 * ViewsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ViewsRepository extends EntityRepository
{

    public function getTotViews(Users $user, $completed = true)
    {
        $completed = $completed ? 1 : 0;

        $idUser = $user->getId();
        $query = $this->createQueryBuilder("views")
            ->select('COUNT(views.id)')
            ->where("views.idUser = :idUser")
            ->andWhere("views.completed = :completed")
            ->setParameter('idUser',$idUser)
            ->setParameter('completed',$completed)
            ->getQuery();
        return $query->getSingleScalarResult();
    }

    /**
     * @param Users $user
     * @param $idEpisode
     * @return bool
     */
    public function isEpisodeSeen(Users $user, $idEpisode)
    {
        $r = $this->createQueryBuilder('views')
                ->select('views.id')
                ->where('views.idUser = :idUser')
                ->andWhere('views.idEpisode = :idEpisode')
                ->setParameter('idUser',$user->getId())
                ->setParameter('idEpisode',$idEpisode)
                ->getQuery()
                ->getOneOrNullResult();
        if($r !== null) {
            return true;
        }
        return false;
    }

    public function setEpisodeAsSeen(Users $user, $idEpisode, $idLink)
    {
        if(!empty($idEpisode) && !empty($idLink)) {

            $isSeen = $this->isEpisodeSeen($user,$idEpisode);

            // If is set remove -- else insert
            if($isSeen) {
                $view = $this->getView($idEpisode,$user->getId());
                $view->setCompleted(0);
            } else {

                $episode = $this->getEntityManager()->getRepository('LoopAnimeShowsBundle:AnimesEpisodes')->find($idEpisode);

                $view = new Views();
                $view->setIdUser($user->getId());
                $view->setIdLink($idLink);
                $view->setIdEpisode($idEpisode);
                $view->setAnimeEpisodes($episode);
                $view->setCompleted(1);
                $view->setWatchedTime(time());
                $view->setViewTime(new \DateTime("now"));
            }
            $this->_em->persist($view);
            $this->_em->flush();
        }

        return true;
    }

    /**
     * @param $idEpisode
     * @param $idUser
     * @return mixed|Views
     */
    private function getView($idEpisode, $idUser)
    {
        $q = $this->createQueryBuilder('views')
                ->select('views')
                ->where('views.idEpisode = :idEpisode')
                ->andWhere('views.idUser = :idUser')
                ->setParameter('idEpisode',$idEpisode)
                ->setParameter('idUser',$idUser)
                ->getQuery();
        return $q->getOneOrNullResult();
    }


}