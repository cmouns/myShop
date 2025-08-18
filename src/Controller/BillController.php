<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BillController extends AbstractController
{
    #[Route('/editor/order/{id}/bill', name: 'app_bill')]
    public function index($id, OrderRepository $orderRepo): Response
    {
        $order = $orderRepo->find($id);

        $pdfOptions = new Options(); //definit la nouvelle instanciation de classe Options de Dompdf
        $pdfOptions->set('defaultFont','Arial'); //Définit la font
        $pdfOptions->set('isRemoteEnabled', true);
        $domPdf = new Dompdf($pdfOptions); // Instancie Dompdf et prend en compte les options
        // On génère le HTML à partir du template Twig
        $html = $this->renderView('bill/index.html.twig', [
            'orders'=>$order,
         ]); 
         // Permet de charger le HTML dans Dompdf
        $domPdf->loadHtml($html); 
        $domPdf->render(); //On crée le rendu
        $domPdf->stream('SneakHub-Facture-'.$order->getId().'.pdf',[//On concatene la facture avec la terminaison"pdf"
            'Attachment'=>false //ca permet de dire qu'on va telecharger le fichier, ou l'afficher et decider de l'imprimer et telecharger
        ]); 

        return new Response('',200,[ // On retourne une réponse vide avec le code 200 car on a déjà envoyé le PDF en streaming
            'Content-Type' => 'application/pdf' // Définit le type de contenu comme PDF
        ]);
    }
}
