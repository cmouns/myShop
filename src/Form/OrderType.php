<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('name')
            ->add('firstName',null, [
                'required' => true,
                'label' => 'Prénom',
                'attr' => ['class'=>'form-control mb-3', 'placeholder' => 'Prénom']
            ])
            ->add('lastName', null, [
                'required' => true,
                'label' => 'Nom',
                'attr' => ['class'=>'form-control mb-3', 'placeholder' => 'Nom']
            ])
            ->add('phone', null, [
                'required' => true,
                'label' => 'Téléphone',
                'attr' => ['class'=>'form-control mb-3', 'placeholder' => 'Numéro de téléphone']
            ])
            ->add('adress', null, [
                'required' => true,
                'label' => 'Adresse',
                'attr' => ['class'=>'form-control mb-3', 'placeholder' => 'Adresse']
            ])
            // ->add('createdAt', null, [
            //     'widget' => 'single_text',
            // ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'attr' => ['class'=>'form-control mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
