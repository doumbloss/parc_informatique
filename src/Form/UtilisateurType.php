<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('fonction', TextType::class, [
                'label' => 'Fonction',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('service', TextType::class, [
                'label' => 'Service',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('role', TextType::class, [
                'label' => 'Rôle',
                'data' => 'user', // Valeur par défaut
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => !$options['attr']['role_editable'] ?? true, // Lecture seule sauf si role_editable est vrai
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'attr' => ['role_editable' => false], // Par défaut, le rôle n'est pas éditable
        ]);
    }
}