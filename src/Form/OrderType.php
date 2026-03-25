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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
        $compact = $options['compact'] ?? false;
        $allowOfferSelect = $options['allow_offer_select'] ?? false;
        $offerOption = $options['offer'] ?? null;

        $builder
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'label' => 'Quantité',
                'attr' => ['min' => 1] + ($compact ? ['readonly' => true] : []),
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
            ->add('paymentType', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Type de paiement',
                'choices' => [
                    'Carte' => 'card',
                    'Virement' => 'bank_transfer',
                    'Manuel' => 'manual',
                ],
                'required' => true,
            ])
        ;

        // Si non en mode compact et que la sélection d'offre est autorisée, ajouter le select d'offres
        if (!$compact && $allowOfferSelect) {
            $builder->add('offer', EntityType::class, [
                'class' => Offer::class,
                'choice_label' => 'name_offer',
                // n'afficher que les offres actives dans le select
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                        ->andWhere('o.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('o.id', 'ASC');
                },
                // ajouter un attribut data-available et data-min/data-max sur chaque option pour que le JS puisse l'utiliser
                'choice_attr' => function (?Offer $offer, $key, $value) {
                    if (!$offer) {
                        return [];
                    }
                    return [
                        'data-available' => (string)$this->unitRepository->countAvailable($offer),
                        'data-discount' => $offer->getDiscountPercent() ?? '',
                        'data-min' => $offer->getMinUnits() ?? '',
                        'data-max' => $offer->getMaxUnits() ?? '',
                    ];
                },
                'required' => false,
            ]);
        } else {
            // en mode compact on peut garder une valeur d'offre non mappée pour affichage si fournie
            if ($offerOption instanceof Offer) {
                // ajouter un champ hidden non mappé pour conserver l'id côté client si nécessaire
                $builder->add('selectedOffer', IntegerType::class, [
                    'mapped' => false,
                    'data' => $offerOption->getId(),
                ]);
            }
        }


        // Validation: s'assurer qu'il y a suffisamment d'unités disponibles et respecter min/max
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($compact, $offerOption) {
            /** @var Order|null $order */
            $order = $event->getData();
            $form = $event->getForm();

            if (!$order instanceof Order) {
                return;
            }

            $offer = $order->getOffer() ?? $offerOption;
            $quantity = $order->getQuantity();

            if (null === $quantity) {
                return; // autre validation prendra le relai (NotBlank, etc.)
            }

            // Vérifier le minimum d'unités imposé par l'offre
            if ($offer && null !== $offer->getMinUnits()) {
                $min = $offer->getMinUnits();
                if ($quantity < $min) {
                    $form->get('quantity')->addError(new FormError(sprintf('La quantité doit être au moins %d pour cette offre.', $min)));
                }
            }

            // Vérifier le maximum d'unités imposé par l'offre
            if ($offer && null !== $offer->getMaxUnits()) {
                $max = $offer->getMaxUnits();
                if ($quantity > $max) {
                    $form->get('quantity')->addError(new FormError(sprintf('La quantité doit être au plus %d pour cette offre.', $max)));
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
            'compact' => false,
            'offer' => null,
            'allow_offer_select' => false,
        ]);

        $resolver->setAllowedTypes('compact', 'bool');
        $resolver->setAllowedTypes('offer', ['null', Offer::class]);
        $resolver->setAllowedTypes('allow_offer_select', 'bool');
    }
}
