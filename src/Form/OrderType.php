<?php

namespace App\Form;

use App\Entity\Offer;
use App\Entity\Order;
use App\Repository\UnitRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Doctrine\ORM\EntityRepository;

class OrderType extends AbstractType
{
    private UnitRepository $unitRepository;

    public function __construct(UnitRepository $unitRepository)
    {
        $this->unitRepository = $unitRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'label' => 'Quantité',
                'attr' => ['min' => 1],
                'constraints' => [
                    new NotBlank(message: 'Veuillez indiquer la quantité.'),
                    new Positive(message: 'La quantité doit être strictement positive.'),
                ],
            ])
            ->add('unitPrice', null, [
                'disabled' => true,
                'label' => 'Prix unitaire (calculé)',
            ])
            ->add('annualPayment')
            ->add('discountPercent')
            ->add('total', null, [
                'disabled' => true,
                'label' => 'Total (calculé)'
            ])
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('offer', EntityType::class, [
                'class' => Offer::class,
                'choice_label' => 'id',
                // n'afficher que les offres actives dans le select
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                        ->andWhere('o.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('o.id', 'ASC');
                },
                // ajouter un attribut data-available sur chaque option pour que le JS puisse l'utiliser
                'choice_attr' => function (?Offer $offer, $key, $value) {
                    if (!$offer) {
                        return [];
                    }
                    return ['data-available' => (string)$this->unitRepository->countAvailable($offer)];
                },
                'required' => false,
            ])
            // champ non mappé pour demander une simulation
            ->add('simulate', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Faire une simulation (aucune modification en base)'
            ])
        ;

        // Validation: s'assurer qu'il y a suffisamment d'unités disponibles
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Order|null $order */
            $order = $event->getData();
            $form = $event->getForm();

            if (!$order instanceof Order) {
                return;
            }

            $offer = $order->getOffer();
            $quantity = $order->getQuantity();

            if (null === $quantity) {
                return; // autre validation prendra le relai (NotBlank, etc.)
            }

            // Vérifier le minimum d'unités imposé par l'offre
            if ($offer && null !== $offer->getMinUnits()) {
                $min = $offer->getMinUnits();
                if ($quantity < $min) {
                    $form->get('quantity')->addError(new FormError(sprintf('La quantité doit être au moins %d pour cette offre.', $min)));
                    // on continue afin d'afficher toutes les erreurs éventuelles
                }
            }

            // Compter les unités disponibles pour l'offre (ou global si offer=null)
            $available = $this->unitRepository->countAvailable($offer);

            if ($quantity > $available) {
                $form->get('quantity')->addError(new FormError(sprintf('Il n\'y a que %d unité(s) disponible(s).', $available)));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
