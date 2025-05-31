<?php

namespace App\Form;

use App\Entity\Licence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Equipement;

class LicenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomLogiciel')
            ->add('cle')
            ->add('dateAchat')
            ->add('dateExpiration')
            ->add('fournisseur')
            // SUPPRIMER ->add('equipementId')
            ->add('equipements', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'nom', // ou 'codeInventaire', selon ce que tu veux afficher
                'multiple' => true,
                'expanded' => true, // true = checkbox, false = select multiple
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Licence::class,
        ]);
    }
}