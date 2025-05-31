<?php

namespace App\Form;

use App\Entity\Localisation;
use App\Entity\Licence;
use App\Entity\Equipement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // ✅ Ajout important

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeInventaire')
            ->add('nom')
            ->add('marque')
            ->add('modele')
            ->add('numeroSerie')
            ->add('categorie', ChoiceType::class, [
            'choices' => [
                'Ordinateur' => 'Ordinateur',
                'Imprimante' => 'Imprimante',
                'Projecteur' => 'Projecteur',
                'Scanner' => 'Scanner',
             ],
            ])
            ->add('dateAchat')
            ->add('prix')
            ->add('garantieFin')
            ->add('etat')
            ->add('commentaire')
            ->add('utilisateur')
            ->add('localisation', EntityType::class, [
                'class' => Localisation::class,
                'choice_label' => 'nomBatiment', // Vous pouvez choisir un autre attribut si nécessaire
                'placeholder' => 'Sélectionner une localisation',
                'required' => true,
            ])
        
            ->add('licences', EntityType::class, [
                'class' => Licence::class,
                'choice_label' => 'nomLogiciel',
                'multiple' => true,
                'expanded' => true, // case à cocher si tu veux
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
        ]);
    }
}
