<?php

namespace App\Form;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

class OfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name_offer', TextType::class, [
                'label' => 'Nom de l\'offre',
                'constraints' => [new Length(max: 255)],
            ])
            ->add('discountPercent', NumberType::class, [
                'label' => 'Réduction (%)',
                'required' => false,
                'scale' => 2,
                'attr' => ['min' => 0, 'max' => 100],
                'constraints' => [new Range(min: 0, max: 100)],
            ])
            ->add('minUnits', IntegerType::class, [
                'label' => 'Quantité minimale',
                'required' => false,
                'attr' => ['min' => 0],
                'constraints' => [new Positive()],
            ])
            ->add('maxUnits', IntegerType::class, [
                'label' => 'Quantité maximale',
                'required' => false,
                'attr' => ['min' => 0],
                'constraints' => [new Positive()],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}
