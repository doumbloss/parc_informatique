<?php

// src/Form/BatimentType.php

namespace App\Form;

use App\Entity\Batiment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class BatimentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $builder
        //     ->add('nom')
        //     ->add('description')
        //     ->add('longitude')
        //     ->add('latitude')
        //     ->add('adresse', TextType::class, [
        //     'label' => 'Adresse du bâtiment',
        // ]);
        $builder
    ->add('nom')
    ->add('description')
    ->add('adresse')
    ->add('superficie')
    ->add('etages');

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Batiment::class,
        ]);
    }
}
