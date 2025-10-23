<?php

namespace App\Form;

use App\Entity\TypeTransaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle')
            ->add('description')
            ->add('type_montant_autorise', ChoiceType::class, [
                'label' => 'Type de montant autorisé',
                'choices' => [
                    'Débit et Crédit' => 'both',
                    'Débit uniquement' => 'debit',
                    'Crédit uniquement' => 'credit',
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => ['class' => 'form-label fw-bold mb-3'],
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'form-check-input me-2'];
                },
                'attr' => [
                    'class' => 'form-check-group-spaced'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TypeTransaction::class,
        ]);
    }
}