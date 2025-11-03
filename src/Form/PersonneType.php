<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\Personne;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('civilite')
            ->add('numero_voie')
            ->add('rue')
            ->add('complement_adresse')
            ->add('ville')
            ->add('code_postal')
            ->add('pays')
            ->add('telephone')
            ->add('email')
            ->add('entreprise', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => 'nom_entreprise',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => [
                    'class' => 'checkbox-list-container'
                ],
                'help' => 'Cochez les entreprises à associer à cette personne'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personne::class,
        ]);
    }
}