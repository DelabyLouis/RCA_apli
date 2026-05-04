<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\Exercice;
use App\Entity\Personne;
use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use App\Entity\ModeDePaiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\AssignOp\Mod;

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
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('e')
                        ->where('e.clos = :clos')
                        ->setParameter('clos', false)
                        ->orderBy('e.libelle', 'DESC');
                },
            ])
            ->add('type_transaction', EntityType::class, [
                'class' => TypeTransaction::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => 'Aucun type (optionnel)',
            ])
            ->add('mode_de_paiement', EntityType::class, [
                'class' => ModeDePaiement::class,
                'choice_label' => 'libelle',
                'required' => false,
                'placeholder' => 'Aucun mode de paiement (optionnel)',
            ])
            ->add('tiers', ChoiceType::class, [
                'choices' => $choices,
                'required' => true,
                'placeholder' => 'Choisir un tiers (personne ou entreprise)',
                'label' => 'Tiers (Personne ou Entreprise)',
                'mapped' => false, // Ce champ ne sera pas mappé directement sur l'entité
            ])
        ;
        
        // Écouteur pour traiter le champ tiers lors de la soumission
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $transaction = $event->getData();
            $form = $event->getForm();
            
            $tiersValue = $form->get('tiers')->getData();
            error_log('[TransactionNewType LISTENER] tiers value: ' . var_export($tiersValue, true));
            
            if ($tiersValue) {
                if (strpos($tiersValue, 'personne_') === 0) {
                    // C'est une personne
                    $personneId = str_replace('personne_', '', $tiersValue);
                    error_log('[TransactionNewType LISTENER] Cherche personne ID: ' . $personneId);
                    $personne = $this->entityManager->getRepository(Personne::class)->find($personneId);
                    if ($personne) {
                        error_log('[TransactionNewType LISTENER] Personne trouvée: ' . $personne->getNom());
                        $transaction->setPersonne($personne);
                        $transaction->setEntreprise(null);
                    } else {
                        error_log('[TransactionNewType LISTENER] Personne NON trouvée avec ID: ' . $personneId);
                    }
                } elseif (strpos($tiersValue, 'entreprise_') === 0) {
                    // C'est une entreprise
                    $entrepriseId = str_replace('entreprise_', '', $tiersValue);
                    error_log('[TransactionNewType LISTENER] Cherche entreprise ID: ' . $entrepriseId);
                    $entreprise = $this->entityManager->getRepository(Entreprise::class)->find($entrepriseId);
                    if ($entreprise) {
                        error_log('[TransactionNewType LISTENER] Entreprise trouvée: ' . $entreprise->getNomEntreprise());
                        $transaction->setEntreprise($entreprise);
                        $transaction->setPersonne(null);
                    } else {
                        error_log('[TransactionNewType LISTENER] Entreprise NON trouvée avec ID: ' . $entrepriseId);
                    }
                }
            } else {
                error_log('[TransactionNewType LISTENER] tiersValue est vide/null');
            }
            
            error_log('[TransactionNewType LISTENER] Transaction finale - personne: ' . var_export($transaction->getPersonne() ? $transaction->getPersonne()->getIdPersonne() : 'NULL', true));
            error_log('[TransactionNewType LISTENER] Transaction finale - entreprise: ' . var_export($transaction->getEntreprise() ? $transaction->getEntreprise()->getIdEntreprise() : 'NULL', true));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}