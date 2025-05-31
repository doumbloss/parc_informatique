<?php

namespace App\Form;

use App\Entity\Panne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PanneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeInventaire')
            ->add('dateSignalement')
            ->add('description')
            ->add('statut')
            ->add('dateResolution')
            ->add('typeIntervention')
            ->add('intervenantId')
            // ->add('equipementId')
            ->add('equipement')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Panne::class,
        ]);
    }
}
