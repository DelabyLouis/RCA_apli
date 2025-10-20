<?php

namespace App\Form;

use App\Entity\Personne;
use App\Entity\Role;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('password')
            ->add('personne', EntityType::class, [
                'class' => Personne::class,
                'choice_label' => function(Personne $personne) {
                    return $personne->getPrenom() . ' ' . $personne->getNom();
                },
                'placeholder' => 'Sélectionner une personne',
                'required' => true,
                'label' => 'Personne associée',
            ])
            ->add('userRoles', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'libelle',
                'multiple' => true,
                'expanded' => true, // affiche des checkboxes
                'by_reference' => false,
                'label' => 'Rôles',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
