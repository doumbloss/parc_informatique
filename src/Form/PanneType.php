<?php

namespace App\Form;

use App\Entity\Equipement;
use App\Entity\Panne;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PanneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeInventaire', TextType::class, [
                'label' => 'Code d\'inventaire',
                'required' => true,
            ])
            ->add('dateSignalement', DateTimeType::class, [
                'label' => 'Date de signalement',
                'required' => true,
                'widget' => 'single_text',
                'data' => new \DateTime(), // Pré-remplir avec la date actuelle
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => 'En cours',
                    'Résolu' => 'Résolu',
                    'En attente' => 'En attente',
                ],
                'required' => true,
                'data' => 'En cours', // Valeur par défaut
                'attr' => ['class' => 'form-control'],
            ])
            
          ->add('statutEquipement', ChoiceType::class, [
                'label' => 'Nouvel état de l\'équipement',
                'choices' => [
                    'Fonctionnel' => 'fonctionnel',
                    'En panne' => 'en panne',
                    'Maintenance' => 'maintenance',
                    'Obsolète' => 'obsolete',
                ],
                'required' => true,
                'mapped' => false, // Ne mappe pas directement à l'entité Panne, utilisé pour la logique
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateResolution', DateTimeType::class, [
                'label' => 'Date de résolution',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('typeIntervention', ChoiceType::class, [
                'label' => 'Type d\'intervention',
                'choices' => [
                    'Réparation' => 'Réparation',
                    'Maintenance' => 'Maintenance',
                    'Remplacement' => 'Remplacement',
                ],
                'required' => false,
                'placeholder' => 'Sélectionnez un type',
            ])
            ->add('intervenantId', TextType::class, [
                'label' => 'ID de l\'intervenant',
                'required' => false,
            ])
            ->add('equipement', EntityType::class, [
                'label' => 'Équipement',
                'class' => Equipement::class,
                'choice_label' => 'codeInventaire', // Affiche le codeInventaire de l'équipement
                'required' => true,
                'placeholder' => 'Sélectionnez un équipement',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Panne::class,
        ]);
    }
}