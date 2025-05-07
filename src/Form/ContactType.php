<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ton nom',
                'attr' => ['placeholder' => 'Jean Dupont']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Ton adresse email',
                'attr' => ['placeholder' => 'jean@exemple.com']
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Ton message',
                'attr' => ['placeholder' => 'Écris-nous un message...']
            ])
            ->add('quoteRequest', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Je souhaite recevoir un devis personnalisé',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}