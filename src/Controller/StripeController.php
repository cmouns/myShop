<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StripeController extends AbstractController
{
    #[Route('/pay/success', name: 'app_pay_success')]
    public function paymentsuccessed(): Response
    {
        return $this->redirectToRoute('app_order_validation');
    }

    #[Route('/pay/cancel', name: 'app_pay_cancel')]
    public function paymentcanceled(): Response
    {
        return $this->render('stripe/index.html.twig');
    }

     #[Route('/stripe/notify', name: 'app_stripe_notify')]
    public function stripeNotify(Request $request, 
                                OrderRepository $orderRepository,
                                EntityManagerInterface $entityManager,
                                string $stripe_secret_key,
    string $stripe_endpoint_secret): Response

    {
        // Définir la clé secrète de Stripe à partir de la variable d'environnement
        Stripe::setApiKey($stripe_secret_key);
    $endpoint_secret = $stripe_endpoint_secret;

        file_put_contents("stripe-debugg.txt", "KEY=" . ($_ENV['STRIPE_SECRET_KEY'] ?? 'null') . "\nENDPOINT=" . ($_ENV['STRIPE_ENDPOINT_SECRET'] ?? 'null'));


        // Récupérer le contenu de la requête
        $payload = $request->getContent();
        // Récupérer l'en-tête de signature de la requête
        $sigHeader = $request->headers->get('Stripe-Signature');
        // Initialiser l'événement à null
        $event = null;

        file_put_contents("test.txt", "test");

        try {
            // Construire l'événement à partir de la requête et de la signature
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpoint_secret
            );
            unlink("test.txt");
            unlink("stripe-error.txt");
            unlink("stripe-debug.txt");
            

        } catch (\UnexpectedValueException $e) {
            // Retourner une erreur 400 si le payload est invalide
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Retourner une erreur 400 si la signature est invalide
            return new Response('Invalid signature', 400);
        } catch (\Exception $e) {
            file_put_contents('stripe-error.txt', $e->getMessage());
            return new Response($e->getMessage(), 500);
        }
        
        // Gérer les différents types d'événements
        switch ($event->type) {
            case 'payment_intent.succeeded':  // Événement de paiement réussi
                // Récupérer l'objet payment_intent
                $paymentIntent = $event->data->object;
                
                // Enregistrer les détails du paiement dans un fichier
                $fileName = 'stripe-detail-'.uniqid().'.txt';

                $orderId = $paymentIntent->metadata->orderId ?? null;
                if ($orderId) {
                    $order = $orderRepository->find($orderId);
                    if ($order) {
                        $cartPrice = $order->getTotalPrice();
                        $stripeTotalAmount = $paymentIntent->amount / 100;
                        if ($cartPrice == $stripeTotalAmount) {
                            $order->setIsPaymentCompleted(1);
                            $entityManager->flush();
                        }
                    }
                }

                
                
                break;
            case 'payment_method.attached':   // Événement de méthode de paiement attachée
                // Récupérer l'objet payment_method
                $paymentMethod = $event->data->object; 
                break;
            default :
                // Ne rien faire pour les autres types d'événements
                break;
        }

        // Retourner une réponse 200 pour indiquer que l'événement a été reçu avec succès
        return new Response('Événement reçu avec succès', 200);
    }
}
