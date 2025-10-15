<?php

namespace App\Form;

use App\Entity\TypeTransaction;
use App\Entity\Transaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle')
            ->add('description')
            ->add('transactions', EntityType::class, [
                'class' => Transaction::class,
                'choice_label' => function(Transaction $transaction) {
                    return $transaction->getLibelle() . ' (' . $transaction->getMontant() . '€)';
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-list-dropdown'
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
