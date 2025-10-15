<?php

namespace App\Form;

use App\Entity\Entreprise;
use App\Entity\Personne;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_entreprise')
            ->add('siret')
            ->add('siren')
            ->add('numero_voie')
            ->add('rue')
            ->add('complement_adresse')
            ->add('ville')
            ->add('code_postal')
            ->add('pays')
            ->add('telephone')
            ->add('email')
            ->add('personnes', EntityType::class, [
                'class' => Personne::class,
                'choice_label' => function(Personne $personne) {
                    return $personne->getPrenom() . ' ' . $personne->getNom();
                },
                'multiple' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
        ]);
    }
}
