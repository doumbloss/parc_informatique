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
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // ✅ Ajout important

class EquipementType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeInventaire', TextType::class, [
                'label' => 'Code d\'inventaire',
                'attr' => [
                    'placeholder' => 'Ex: INV-001',
                    'class' => 'form-control',
                ],
                'help' => 'Entrez un code unique pour identifier l\'équipement (ex: INV-001).',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'équipement',
                'attr' => [
                    'placeholder' => 'Ex: Ordinateur portable',
                    'class' => 'form-control',
                ],
                'help' => 'Indiquez un nom descriptif pour l\'équipement.',
            ])
            ->add('marque', TextType::class, [
                'label' => 'Marque',
                'attr' => [
                    'placeholder' => 'Ex: Dell, HP, Canon',
                    'class' => 'form-control',
                ],
                'help' => 'Saisissez la marque de l\'équipement.',
            ])
            ->add('modele', TextType::class, [
                'label' => 'Modèle',
                'attr' => [
                    'placeholder' => 'Ex: Latitude 5400',
                    'class' => 'form-control',
                ],
                'help' => 'Précisez le modèle spécifique de l\'équipement.',
            ])
            ->add('numeroSerie', TextType::class, [
                'label' => 'Numéro de série',
                'attr' => [
                    'placeholder' => 'Ex: ABC123456',
                    'class' => 'form-control',
                ],
                'help' => 'Entrez le numéro de série unique de l\'équipement.',
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Ordinateur' => 'Ordinateur',
                    'Imprimante' => 'Imprimante',
                    'Projecteur' => 'Projecteur',
                    'Scanner' => 'Scanner',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => 'Choisissez une catégorie',
                'help' => 'Sélectionnez la catégorie correspondant à l\'équipement.',
            ])
            ->add('dateAchat', DateType::class, [
                'label' => 'Date d\'achat',
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Ex: 2023-06-01',
                    'class' => 'form-control',
                ],
                'help' => 'Indiquez la date d\'achat de l\'équipement.',
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix d\'achat (FG)',
                'attr' => [
                    'placeholder' => 'Ex: 999.99',
                    'class' => 'form-control',
                ],
                'help' => 'Saisissez le prix d\'achat en euros.',
            ])
            ->add('garantieFin', DateType::class, [
                'label' => 'Fin de garantie',
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Ex: 2025-06-01',
                    'class' => 'form-control',
                ],
                'help' => 'Entrez la date de fin de garantie de l\'équipement.',
            ])
            ->add('etat', TextType::class, [
                'label' => 'État',
                'attr' => [
                    'placeholder' => 'Ex: Neuf, Bon, Endommagé',
                    'class' => 'form-control',
                ],
                'help' => 'Décrivez l\'état actuel de l\'équipement.',
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaires',
                'attr' => [
                    'placeholder' => 'Ex: Utilisé principalement pour des présentations',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
                'help' => 'Ajoutez des remarques ou informations supplémentaires.',
                'required' => false,
            ])
            ->add('utilisateur', TextType::class, [
                'label' => 'Utilisateur',
                'attr' => [
                    'placeholder' => 'Ex: Jean Dupont',
                    'class' => 'form-control',
                ],
                'help' => 'Indiquez le nom de l\'utilisateur principal de l\'équipement.',
            ])
            ->add('localisation', EntityType::class, [
                'label' => 'Localisation',
                'class' => Localisation::class,
                'choice_label' => 'nomBatiment',
                'placeholder' => 'Sélectionner une localisation',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Choisissez le bâtiment ou le lieu où l\'équipement est situé.',
                'required' => true,
            ])
            ->add('licences', EntityType::class, [
                'label' => 'Licences associées',
                'class' => Licence::class,
                'choice_label' => 'nomLogiciel',
                'multiple' => true,
                'expanded' => true, // Renders as checkboxes
                'attr' => [
                    'class' => 'form-check',
                ],
                'help' => 'Cochez les licences logicielles associées à cet équipement.',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
        ]);
    }
}
