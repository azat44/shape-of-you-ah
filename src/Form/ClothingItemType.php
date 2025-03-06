<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\ClothingItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;


class ClothingItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du vêtement',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom pour ce vêtement'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: T-shirt blanc, Jean slim...'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])
            ->add('color', ChoiceType::class, [
                'label' => 'Couleur',
                'required' => false,
                'choices' => [
                    'Blanc' => 'blanc',
                    'Noir' => 'noir',
                    'Bleu' => 'bleu',
                    'Rouge' => 'rouge',
                    'Vert' => 'vert',
                    'Jaune' => 'jaune',
                    'Gris' => 'gris',
                    'Marron' => 'marron',
                    'Rose' => 'rose',
                    'Violet' => 'violet',
                    'Orange' => 'orange',
                    'Multicolore' => 'multicolore'
                ],
                'attr' => ['class' => 'form-select']
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
            ->add('imageUrl', UrlType::class, [
                'label' => 'URL de l\'image',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://exemple.com/image.jpg'
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (€)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ]
            ])
            ->add('partnerLink', UrlType::class, [
                'label' => 'Lien partenaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://partenaire.com/produit'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name', 
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie...',
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClothingItem::class,
            'csrf_protection' => true, 
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'clothing_item_form',
        ]);
    }
}