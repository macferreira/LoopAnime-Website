<?php

namespace LoopAnime\UsersBundle\Entity;

use Doctrine\ORM\EntityRepository;
use LoopAnime\ShowsBundle\Entity\Animes;

/**
 * Users_FavoritesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UsersFavoritesRepository extends EntityRepository
{

    public function getTotFav(Users $user)
    {
        $idUser = $user->getId();
        $query = $this->createQueryBuilder("usersFavorites")
            ->select('COUNT(usersFavorites.id)')
            ->where("usersFavorites.idUser = :idUser")
            ->setParameter('idUser',$idUser)
            ->getQuery();
        return $query->getSingleScalarResult();
    }

    public function getTrackNewEpisodes(Users $user)
    {
        $idUser = $user->getId();
        $lastLogin = $user->getLastLogin();

        $query = $this->createQueryBuilder("usersFavorites")
            ->select('COUNT(usersFavorites.id)')
            ->join("usersFavorites.anime",'animes')
            ->join("animes.animesSeasons",'seasons')
            ->join('seasons.animesEpisodes','episodes')
            ->leftJoin('episodes.episodeViews','views')
            ->where("views.idUser = :idUser")
            ->andWhere("episodes.airDate BETWEEN '".$lastLogin->format('Y-m-d H:i:s')."' AND CURRENT_TIMESTAMP()")
            ->setParameter('idUser',$idUser)
            ->getQuery();
        return $query->getSingleScalarResult();
    }

    public function getTrackToSeeEpisodes(Users $user)
    {
        $idUser = $user->getId();
        $query = $this->createQueryBuilder("usersFavorites")
            ->select('COUNT(usersFavorites.id)')
            ->join("usersFavorites.anime",'animes')
            ->join("animes.animesSeasons",'seasons')
            ->join('seasons.animesEpisodes','episodes')
            ->leftJoin('episodes.episodeViews','views')
            ->where("views.idUser = :idUser")
            ->andWhere("(views.id IS NULL OR views.completed = 0)")
            ->setParameter('idUser',$idUser)
            ->getQuery();
        return $query->getSingleScalarResult();
    }

    /**
     * @param Users $user
     * @param $idAnime
     * @return bool
     */
    public function isAnimeFavorite(Users $user, $idAnime)
    {
        $q = $this->createQueryBuilder('uf')
                ->select('uf')
                ->where('uf.idUser = :idUser')
                ->andWhere('uf.idAnime = :idAnime')
                ->setParameter('idUser',$user->getId())
                ->setParameter('idAnime',$idAnime)
                ->getQuery()
                ->getOneOrNullResult();
        if($q !== null) {
            return true;
        }
        return false;
    }

    /**
     * @param $idAnime
     * @param $idUser
     * @return null|UsersFavorites
     */
    public function getAnimeFavorite($idAnime, $idUser)
    {
        $q = $this->createQueryBuilder('uf')
                ->select('uf')
                ->where('uf.idAnime = :idAnime')
                ->andWhere('uf.idUser = :idUser')
                ->setParameter('idAnime',$idAnime)
                ->setParameter('idUser',$idUser)
                ->getQuery();

        return $q->getOneOrNullResult();
    }

    public function getUsersFavoriteAnimes(Users $user, $getQuery = true)
    {

        $query = $this->createQueryBuilder("users_favorites")
            ->select('users_favorites')
            ->addselect("animes")
            ->addSelect('(SELECT COUNT(animesSeasons2.id) FROM LoopAnime\ShowsBundle\Entity\AnimesSeasons animesSeasons2 WHERE animesSeasons2.idAnime = animes.id) AS total_seasons')
            ->addSelect('users_favorites.id')
            ->addSelect('SUM(animes_seasons.numberEpisodes)')
            ->addSelect('(SELECT COUNT(views.id) FROM LoopAnime\ShowsBundle\Entity\Views views
                            JOIN views.animeEpisodes animes_episodes2
                            JOIN animes_episodes2.animesSeasons animes_seasons3
                            JOIN animes_seasons3.animes animes3
                            WHERE animes3.id = animes.id) AS total_saw')
            ->join('users_favorites.anime','animes')
            ->join('animes.animesSeasons','animes_seasons')
            ->join('animes_seasons.animesEpisodes','animes_episodes')
            ->where('users_favorites.idUser = :idUser')
            ->setParameter('idUser',$user->getId())
            ->groupBy('animes.id');

//        $where_clause = "users_favorites.id_user = '".$user->getId()."'";
//
//        $query = "SELECT
//					users_favorites.id_favorite,
//					animes.id AS `id_anime`,
//					animes.title,
//					animes.last_updated,
//					animes.status,
//					animes.poster,
//					(SELECT COUNT(*) FROM LoopAnime\ShowsBundle\Entity\AnimesSeasons animes_seasons WHERE animes_seasons.id_anime = animes.id) AS `total_seasons`,
//					SUM(animes_seasons.number_episodes) AS `total_episodes`,
//					(SELECT COUNT(*) FROM LoopAnime\ShowsBundle\Entity\Views views
//						JOIN LoopAnime\ShowsBundle\Entity\AnimesEpisodes animes_episodes
//						JOIN LoopAnime\ShowsBundle\Entity\AnimesSeasons animes_seasons
//						JOIN LoopAnime\ShowsBundle\Entity\Animes animes
//					WHERE animes.id = @id_anime AND views.id_user = users_favorites.id_user) AS `total_saw`
//				  FROM
//						LoopAnime\UsersBundle\Entity\UsersFavorites users_favorites
//						JOIN LoopAnime\ShowsBundle\Entity\Animes animes
//						JOIN LoopAnime\ShowsBundle\Entity\AnimesSeasons animes_seasons
//				  WHERE
//						$where_clause
//				  GROUP BY animes.id_anime";

        if($getQuery) {
            return $query->getQuery();
        } else {
            return $query->getQuery()->getResult();
        }
    }

    public function setAnimeAsFavorite(Users $user, $idAnime) {
        if(!empty($idAnime)) {

            $favorite = $this->isAnimeFavorite($user,$idAnime);

            // If is set remove -- else insert
            if($favorite) {
                $userFavorite = $this->getAnimeFavorite($idAnime,$user->getId());
                $this->_em->remove($userFavorite);
            } else {

                $anime = $this->getEntityManager()->getRepository('LoopAnimeShowsBundle:Animes')->find($idAnime);

                $userFavorite = new UsersFavorites();
                $userFavorite->setCreateTime(new \DateTime("now"));
                $userFavorite->setIdAnime($idAnime);
                $userFavorite->setAnime($anime);
                $userFavorite->setIdUser($user->getId());
                $this->_em->persist($userFavorite);
            }
            $this->_em->flush();
        }

        return true;
    }
}
