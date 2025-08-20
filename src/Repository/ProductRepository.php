<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function searchEngine(string $query) {
    // Crée un objet de requête qui permet de construire la requête de recherche.
    return $this->createQueryBuilder('p')
        // Recherche les éléments dont le nom contient la requête de recherche.
        ->where('p.name LIKE :query')
        // OU recherche les élées dont la description contient la requête de recherche.
        ->orWhere('p.description LIKE :query')
        // Défini la valeur de la variable "query" pour la requête.
        ->setParameter('query', '%' . $query . '%')
        // Exécute la requête et récupère les résultats.
        ->getQuery()
        ->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    // public function findByIdUp($value): array
    //    {
    //        return $this->createQueryBuilder('p') //retourner la requete
    //            ->andWhere('p.id > :val') // ajoute des critères val = $value
    //            ->setParameter('val', $value) //on set les parametres
    //            ->orderBy('p.id', 'ASC') //on definit les criteres
    //            ->setMaxResults(10) //definit le nbr de resultat
    //            ->getQuery() //
    //            ->getResult() //
    //        ;
    //    }
}
