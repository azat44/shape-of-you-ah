<?php

namespace App\Form;

use App\Entity\ClothingItem;
use App\Entity\Outfit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;


class OutfitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'] ?? null;
        
        $builder
            ->add('title', TextType::class, [
                'label' => 'Nom de la tenue',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez donner un nom à cette tenue'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Tenue décontractée weekend'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description de la tenue...'
                ]
            ])
            ->add('style', ChoiceType::class, [
                'label' => 'Style',
                'required' => false,
                'choices' => [
                    'Casual' => 'casual',
                    'Formel' => 'formel',
                    'Sport' => 'sport',
                    'Soirée' => 'soirée'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('clothingItems', EntityType::class, [
                'class' => ClothingItem::class,
                'choice_label' => function (ClothingItem $item) {
                    $price = $item->getPrice() ? number_format($item->getPrice(), 2) . '€' : 'N/A';
                    return $item->getName() . ' - ' . $price;
                },
                'query_builder' => function (EntityRepository $er) use ($user) {
                    if ($user) {
                        return $er->createQueryBuilder('ci')
                            ->join('App\Entity\UserWardrobe', 'uw', 'WITH', 'uw.clothingItem = ci')
                            ->where('uw.user = :user')
                            ->setParameter('user', $user)
                            ->orderBy('ci.name', 'ASC');
                    }
                    
                    return $er->createQueryBuilder('ci')
                        ->orderBy('ci.name', 'ASC');
                },
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select',
                    'size' => 10
                ],
                'label' => 'Vêtements',
                'help' => 'Sélectionnez les vêtements qui composent cette tenue (Ctrl+clic pour sélectionner plusieurs)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Outfit::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'outfit_form',
            'user' => null,
        ]);
        
        $resolver->setAllowedTypes('user', ['null', 'App\Entity\User']);
    }
}