<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Permission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('hierarchy_level', ChoiceType::class, [
                'label' => 'Niveau hiérarchique',
                'choices' => [
                    'Administrateur' => 100,
                    'Utilisateur' => 50,
                    'Invité' => 10,
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('permissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Permissions accordées',
                'required' => false,
                'attr' => ['class' => 'checkbox-container'],
                'help' => 'Sélectionnez les pages auxquelles ce rôle peut accéder'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}