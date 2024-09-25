<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'label' => 'Choisir un forfait',
            ])
            ->add('period', ChoiceType::class, [
                'choices' => [
                    'Matin' => 'morning',
                    'Après-midi' => 'afternoon',
                ],
                'required' => false,
                'label' => 'Sélectionnez une période',
            ])
            ->add('nbrParticipant', IntegerType::class, [
                'label' => 'Nombre de participants y compris vous (min 6)',
                'required' => true,
                'attr' => [
                    'min' => 6,
                    'value' => 6
                ],
            ])
            ->add('participants', CollectionType::class, [
                'entry_type' => ParticipantType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'render_fieldset' => false,
            'show_legend' => false,
        ]);
    }
}
