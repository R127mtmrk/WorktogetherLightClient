<?php

namespace App\Form;

use App\Entity\Client;
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
            ->add('quantity')
            ->add('unitPrice')
            ->add('annualPayment')
            ->add('discountPercent')
            ->add('total')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'id',
            ])
            ->add('offer', EntityType::class, [
                'class' => Offer::class,
                'choice_label' => 'id',
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
