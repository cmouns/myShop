<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Contrôleur final pour la gestion des commandes
final class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer){

    }
    // Route pour afficher et traiter le formulaire de commande
    #[Route('/order', name: 'app_order')]
    public function index(Request $request,SessionInterface $session,EntityManagerInterface $em,Cart $cart): Response {
        $data = $cart->getCart($session); // Récupère le panier depuis la session

        $order = new Order(); // Crée une nouvelle commande
        
        $form = $this->createForm(OrderType::class, $order); // Crée le formulaire de commande
        $form->handleRequest($request); // Traite la soumission du formulaire

        if ($form->isSubmitted() && $form->isValid()) { // Si le formulaire est soumis et valide
            if ($order->isPayOnDelivery()) { // Si le paiement est à la livraison
                if(!empty($data['total'])) {
                    $order->setTotalPrice($data['total']); // Définit le prix total de la commande
                    $order->setCreatedAt(new \DateTimeImmutable()); // Définit la date de création
                    $em->persist($order); // Prépare la commande à être enregistrée
                    $em->flush(); // Enregistre la commande (nécessaire pour avoir l'ID)

                    // Pour chaque produit du panier, on crée une liaison commande-produit
                    foreach ($data['cart'] as $value) {
                        $orderProduct = new OrderProducts(); // Crée la liaison
                        $orderProduct->setOrder($order); // Lie à la commande
                        $orderProduct->setProduct($value['product']); // Lie au produit
                        $orderProduct->setQuantity($value['quantity']); // Définit la quantité
                        $em->persist($orderProduct); // Prépare à enregistrer
                        // On ne flush pas ici, on attend la fin de la boucle
                    }
                    $em->flush(); // Enregistre tous les OrderProducts en une seule fois
                }
                
            }
            // Vide le panier
            $session->set('cart', []);

            $html = $this->renderView('mail/orderConfirm.html.twig',[
                'order'=>$order
            ]);
            $email = (new Email())
            ->from('hi@demomailtrap.co')
            ->to('mounir.sebti33@gmail.com')
            ->subject('Confirmation de réception de commande')
            ->html($html);
            $this->mailer->send($email);

            // Redirige vers le panier
            return $this->redirectToRoute('app_order_validation');
        }
    
        // Affiche la page avec le formulaire et le total
        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
        ]);
    }

    // Route AJAX pour récupérer le coût de livraison d'une ville
    #[Route('/city/{id}/shipping/cost', name: 'app_city/shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost(); // Récupère le coût de livraison
        return $this->json([
            'status' => 200,
            'content' => $cityShippingPrice
        ]);
    }

    // Route pour afficher la page de validation de commande
    #[Route('/order_validation', name: 'app_order_validation')]
    public function orderValidation(): Response {
        return $this->render('order/orderValidation.html.twig');
    }

    // Route pour afficher la page de validation de commande
    #[Route('/editor/order', name: 'app_orders_show')]
    public function getAllOrder(OrderRepository $orderRepo,PaginatorInterface $paginator, Request $request ): Response {
        $orders = $orderRepo->findAll();
        $ordersPagination = $paginator->paginate(
            $orders,
            $request->query->getInt('page', 1),
            6
        );
        
        return $this->render('order/orders.html.twig', [
            "orders"=>$orders,
            "ordersPagination"=>$ordersPagination
        ]);
    }

    #[Route('/editor/order/{id}/is-competed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate(OrderRepository $orderRepo, $id, EntityManagerInterface $em ): Response {
        $order = $orderRepo->find($id);
        $order->setIsCompleted(true);
        $em->flush();
        $this->addFlash('success', 'Modification effectuée');
        return $this->redirectToRoute('app_orders_show'); 
    }

    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $em): Response {
        $em->remove($order);
        $em->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show'); 
    }
}