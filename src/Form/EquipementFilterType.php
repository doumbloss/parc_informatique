<?php
// src/Form/EquipementFilterType.php
namespace App\Form;

 
use App\Entity\Localisation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipementFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'équipement',
                'required' => false,
                'attr' => ['placeholder' => 'Rechercher par nom...'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'équipement',
                'choices' => [
                    'Tous' => '',
                    'Ordinateur' => 'Ordinateur',
                    'Imprimante' => 'Imprimante',
                    'Projecteur' => 'Projecteur',
                    'Scanner' => 'Scanner',
                ],
                'required' => false,
                'placeholder' => 'Tous les types',
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'Tous' => '',
                    'Fonctionnel' => 'Fonctionnel',
                    'En panne' => 'En Panne',
                    'Maintenance' => 'Maintenance',
                ],
                'required' => false,
                'placeholder' => 'Tous les états',
            ])
            ->add('localisation', EntityType::class, [
                'label' => 'Localisation',
                'class' => Localisation::class,
                'choice_label' => 'nomBatiment', // Utiliser nomBatiment, pas nom
                'required' => false,
                'placeholder' => 'Toutes les localisations',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}