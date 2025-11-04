<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ========== INFORMATIONS DE CONNEXION ==========
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Choisissez un nom d\'utilisateur'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom d\'utilisateur',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Votre nom d\'utilisateur doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                        'maxMessage' => 'Votre nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'options' => ['attr' => ['class' => 'form-control']],
                'required' => true,
                'first_options'  => [
                    'label' => 'Mot de passe *',
                    'attr' => ['placeholder' => 'Choisissez un mot de passe']
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe *',
                    'attr' => ['placeholder' => 'Confirmez votre mot de passe']
                ],
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            
            // ========== INFORMATIONS PERSONNELLES ==========
            ->add('civilite', ChoiceType::class, [
                'label' => 'Civilité',
                'choices' => [
                    'Monsieur' => 'M.',
                    'Madame' => 'Mme',
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Choisissez une civilité',
                'required' => false,
                'mapped' => false,
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom de famille'
                ],
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom *',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ],
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre prénom',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre.email@exemple.fr'
                ],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Email([
                        'message' => 'Veuillez entrer une adresse email valide',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0123456789'
                ],
                'mapped' => false,
                'required' => false,
            ])
            
            // ========== ADRESSE ==========
            ->add('numero_voie', TextType::class, [
                'label' => 'Numéro de voie',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '123'
                ],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10,
                        'maxMessage' => 'Le numéro de voie ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('rue', TextType::class, [
                'label' => 'Rue',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Rue de la République'
                ],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 200,
                        'maxMessage' => 'La rue ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('complement_adresse', TextType::class, [
                'label' => 'Complément d\'adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Appartement, étage, bâtiment...'
                ],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le complément d\'adresse ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Paris'
                ],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('code_postal', IntegerType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '75000'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr' => [
                    'class' => 'form-control',
                    'value' => 'France'
                ],
                'mapped' => false,
                'required' => false,
                'data' => 'France',
                'constraints' => [
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Le pays ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            
            // ========== CONDITIONS ET RGPD ==========
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation *',
                'mapped' => false,
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation.',
                    ]),
                ],
            ])
            ->add('agreePrivacy', CheckboxType::class, [
                'label' => 'J\'accepte la politique de confidentialité et le traitement de mes données personnelles *',
                'mapped' => false,
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter la politique de confidentialité.',
                    ]),
                ],
            ])
            ->add('consentCommunication', CheckboxType::class, [
                'label' => 'J\'accepte de recevoir des communications relatives à la vie du club (facultatif)',
                'mapped' => false,
                'attr' => ['class' => 'form-check-input'],
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