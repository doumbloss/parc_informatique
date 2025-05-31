<?php
// src/Form/EquipementFilterType.php
namespace App\Form;

use App\Entity\Batiment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\TonEntite;


class EquipementFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('batiment', EntityType::class, [
                'class' => Batiment::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Tous les bâtiments',
            ])
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Fonctionnel' => 'fonctionnel',
                    'En panne' => 'panne',
                ],
                'required' => false,
                'placeholder' => 'Tous les états',
            ]);
    }
}
