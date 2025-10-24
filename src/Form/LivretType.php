<?php

namespace App\Form;

use App\Entity\Livret;
use App\Entity\Exercice;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LivretType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du livret',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Livret A, Épargne...'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description optionnelle du livret...'
                ]
            ])
            ->add('solde_initial', MoneyType::class, [
                'label' => 'Solde initial',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0'
                ]
            ])
            ->add('date_creation', DateType::class, [
                'label' => 'Date de création',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
            
        // Ajouter le champ exercice seulement si ce n'est pas verrouillé
        if (!$options['exercice_locked']) {
            $builder->add('exercice', EntityType::class, [
                'class' => Exercice::class,
                'choice_label' => 'libelle',
                'label' => 'Exercice',
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Sélectionner un exercice'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livret::class,
            'exercice_locked' => false,
        ]);
    }
}