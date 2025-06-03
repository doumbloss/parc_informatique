<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Batiment;
use App\Entity\Localisation;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => 'Nom de l\'équipement'
            ])
            ->add('type', TextType::class, [
                'required' => false,
                'label' => 'Type'
            ])
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Fonctionnel' => 'fonctionnel',
                    'En panne' => 'panne',
                    'Maintenance' => 'maintenance',
                ],
                'placeholder' => 'Tous',
                'required' => false,
            ])
            ->add('localisation', EntityType::class, [
                'class' => Localisation::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Toutes localisations'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
