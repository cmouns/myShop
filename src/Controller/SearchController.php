<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepo): Response
    {

         // Vérifie si la requête est de type GET
         if ($request->isMethod('GET')){
            // Récupère les données de la requête
            $data = $request->query->all();
            // Récupère le mot-clé de recherche
            $word = $data['word'];
            
            // Appelle la méthode searchEngine du repository pour récupérer les résultats de recherche
            $results = $productRepo->searchEngine($word);
        }

        // Rendu de la vue search/index.html.twig avec les résultats de recherche
        return $this->render('search/index.html.twig', [
            'products' => $results,
            'word' => $word,
        ]);
    }
}