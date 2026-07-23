<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\Exercice;
use App\Entity\ModeDePaiement;
use App\Entity\Personne;
use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle')
            ->add('numero_ordre')
            ->add('date_transaction')
            ->add('montant')
            ->add('exercice', EntityType::class, [
                'class' => Exercice::class,
                'choice_label' => 'libelle',
                'required' => true,
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('e')
                             ->where('e.clos = :clos')
                             ->setParameter('clos', false)
                             ->orderBy('e.libelle', 'ASC');
                },
            ])
            ->add('type_transaction', EntityType::class, [
                'class' => TypeTransaction::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => 'Aucun type (optionnel)',
            ])
            ->add('personne', EntityType::class, [
                'class' => Personne::class,
                'choice_label' => function(Personne $personne) {
                    return $personne->getPrenom() . ' ' . $personne->getNom();
                },
                'required' => false,
                'placeholder' => 'Choisir une personne (optionnel si entreprise sélectionnée)',
            ])
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => 'nom_entreprise',
                'required' => false,
                'placeholder' => 'Choisir une entreprise (optionnel si personne sélectionnée)',
            ])
            ->add('modeDePaiement', EntityType::class, [
                'class' => ModeDePaiement::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => 'Choisir un mode de paiement (optionnel)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
