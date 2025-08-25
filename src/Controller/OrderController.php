<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use App\Service\StripePayment;
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
            if (!empty($data['total'])) {
                $order->setTotalPrice($data['total'] + $order->getCity()->getShippingCost());
                $order->setCreatedAt(new \DateTimeImmutable());
                $order->setIsPaymentCompleted(0);
                $em->persist($order);
                $em->flush();

                foreach ($data['cart'] as $value) {
                    $orderProduct = new OrderProducts();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($value['product']);
                    $orderProduct->setQuantity($value['quantity']);
                    $em->persist($orderProduct);
                }
                $em->flush();
            }

            

            // if ($order->isPayOnDelivery()) {
            //     dd($order);

            //     $session->set('cart', []);
            //     // Paiement en main propre
            //     $html = $this->renderView('mail/orderConfirm.html.twig', [
            //         'orders' => $order
            //     ]);
            //     $email = (new Email())
            //         ->from('shoptafigurine@gmail.com')
            //         ->to($order->getEmail())
            //         ->subject('Confirmation de réception de commande')
            //         ->html($html);
            //     $this->mailer->send($email);

            //     return $this->redirectToRoute('app_order_validation');
            // } else {
                // Paiement Stripe
                $paymentStripe = new StripePayment();
                $shippingCost = $order->getCity() ? $order->getCity()->getShippingCost() : 0;
                $paymentStripe->startPayment($data, $shippingCost, $order->getId());
                $stripeRedirectUrl = $paymentStripe->getStripeRedirectUrl();

                return $this->redirect($stripeRedirectUrl);
            
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
    #[Route('/editor/order/{type}', name: 'app_orders_show')]
    public function getAllOrder($type, OrderRepository $orderRepo, PaginatorInterface $paginator, Request $request): Response
    {
        if ($type == 'is-completed') {
            $data = $orderRepo->findBy(['isCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'pay-on-stripe-not-delivered') {
            $data = $orderRepo->findBy(['isCompleted' => null, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'pay-on-stripe-is-delivered') {
            $data = $orderRepo->findBy(['isCompleted' => 1, 'payOnDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } else if ($type == 'no_delivery') {
            $data = $orderRepo->findBy(['isCompleted' => null, 'payOnDelivery' => 0, 'isPaymentCompleted' => 0], ['id' => 'DESC']);
        } else {
            $data = $orderRepo->findAll();
        }

        $ordersPagination = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('order/orders.html.twig', [
            "ordersPagination" => $ordersPagination,
            "orders" => $data
        ]);
    }

    #[Route('/editor/order/{id}/is-competed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate(Request $request, OrderRepository $orderRepo, $id, EntityManagerInterface $em ): Response {
        $order = $orderRepo->find($id);
        $order->setIsCompleted(true);
        $em->flush();
        $this->addFlash('success', 'Modification effectuée');
        return $this->redirect($request->headers->get('referer')); 
    }

    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $em): Response {
        $em->remove($order);
        $em->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show'); 
    }
}