<?php

namespace App\Form;

use App\Entity\Equipement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'équipement',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par nom...',
                    'class' => 'form-control search-input',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'équipement',
                'choices' => [
                    'Ordinateur portable' => 'Ordinateur portable',
                    'Ordinateur de bureau' => 'Ordinateur de bureau',
                    'Imprimante laser' => 'Imprimante laser',
                    'Imprimante jet d\'encre' => 'Imprimante jet d\'encre',
                    'Projecteur' => 'Projecteur',
                    'Scanner' => 'Scanner',
                    'Écran/Moniteur' => 'Écran/Moniteur',
                    'Serveur' => 'Serveur',
                    'Équipement réseau' => 'Équipement réseau',
                    'Périphérique' => 'Périphérique',
                ],
                'required' => false,
                'placeholder' => 'Tous les types',
                'attr' => [
                    'class' => 'form-select search-input',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'Fonctionnel' => 'fonctionnel',
                    'En panne' => 'en panne',
                    'Maintenance' => 'maintenance',
                    'Obsolète' => 'obsolete',
                ],
                'required' => false,
                'placeholder' => 'Tous les états',
                'attr' => [
                    'class' => 'form-select search-input',
                ],
            ])
            ->add('localisation', EntityType::class, [
                'class' => 'App\Entity\Localisation',
                'choice_label' => 'nomBatiment', // Ajustez selon votre entité Localisation
                'required' => false,
                'placeholder' => 'Toutes les localisations',
                'attr' => [
                    'class' => 'form-select search-input',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => true, //  CSRF pour les formulaires de recherche
        ]);
    }
}