<?php

namespace App\Form;

use App\Entity\Localisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class LocalisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
     
    ->add('nomBatiment')
    ->add('etage')
    // ->add('nom', TextType::class, [
    //     'label' => 'Nom de la localisation',
    //     'required' => true,
    // ])
    ->add('salle')
    ->add('codeLocal')
    ->add('responsable')
    ->add('latitude', NumberType::class, [
        'required' => false,
        'attr' => ['readonly' => true],
    ])
    ->add('longitude', NumberType::class, [
        'required' => false,
        'attr' => ['readonly' => true],
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Localisation::class,
        ]);
    }
}
