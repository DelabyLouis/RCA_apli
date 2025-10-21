<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\Exercice;
use App\Entity\Personne;
use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;

class TransactionNewType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer toutes les personnes et entreprises
        $personnes = $this->entityManager->getRepository(Personne::class)->findAll();
        $entreprises = $this->entityManager->getRepository(Entreprise::class)->findAll();

        // Créer les choix combinés
        $choices = [];
        
        // Ajouter les personnes
        if (!empty($personnes)) {
            $personnesChoices = [];
            foreach ($personnes as $personne) {
                $personnesChoices[$personne->getPrenom() . ' ' . $personne->getNom()] = 'personne_' . $personne->getIdPersonne();
            }
            $choices['Personnes'] = $personnesChoices;
        }
        
        // Ajouter les entreprises
        if (!empty($entreprises)) {
            $entreprisesChoices = [];
            foreach ($entreprises as $entreprise) {
                $entreprisesChoices[$entreprise->getNomEntreprise()] = 'entreprise_' . $entreprise->getIdEntreprise();
            }
            $choices['Entreprises'] = $entreprisesChoices;
        }

        $builder
            ->add('libelle')
            ->add('date_transaction')
            ->add('montant')
            ->add('exercice', EntityType::class, [
                'class' => Exercice::class,
                'choice_label' => 'libelle',
                'required' => true,
            ])
            ->add('type_transaction', EntityType::class, [
                'class' => TypeTransaction::class,
                'choice_label' => 'libelle',
                'required' => true,
            ])
            ->add('tiers', ChoiceType::class, [
                'choices' => $choices,
                'required' => false,
                'placeholder' => 'Choisir un tiers (personne ou entreprise)',
                'label' => 'Tiers (Personne ou Entreprise)',
                'mapped' => false, // Ce champ ne sera pas mappé directement sur l'entité
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