<?php

namespace App\Form;

use App\Entity\Localisation;
use App\Entity\Licence;
use App\Entity\Equipement;
use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('codeInventaire', TextType::class, [
                'label' => 'Code d\'inventaire',
                'attr' => [
                    'placeholder' => 'Généré automatiquement (ex: INV-2025-0001)',
                    'class' => 'form-control',
                    'readonly' => true,
                ],
                'help' => 'Code unique généré automatiquement au format INV-YYYY-XXXX.',
                'required' => false, // Non requis car généré par le listener
                'data' => $options['data'] ? $options['data']->getCodeInventaire() : null, // Utiliser la valeur existante ou null
            ])
            ->add('nom', ChoiceType::class, [
                'label' => 'Nom de l\'équipement',
                'choices' => [
                    'Ordinateur portable' => 'Ordinateur portable',
                    'Ordinateur de bureau' => 'Ordinateur de bureau',
                    'Station de travail' => 'Station de travail',
                    'Serveur' => 'Serveur',
                    'Mini PC' => 'Mini PC',
                    'Écran LCD' => 'Écran LCD',
                    'Écran LED' => 'Écran LED',
                    'Moniteur 4K' => 'Moniteur 4K',
                    'Clavier' => 'Clavier',
                    'Souris' => 'Souris',
                    'Webcam' => 'Webcam',
                    'Casque audio' => 'Casque audio',
                    'Haut-parleurs' => 'Haut-parleurs',
                    'Imprimante laser' => 'Imprimante laser',
                    'Imprimante jet d\'encre' => 'Imprimante jet d\'encre',
                    'Imprimante multifonction' => 'Imprimante multifonction',
                    'Photocopieur' => 'Photocopieur',
                    'Scanner' => 'Scanner',
                    'Routeur' => 'Routeur',
                    'Switch' => 'Switch',
                    'Point d\'accès WiFi' => 'Point d\'accès WiFi',
                    'Modem' => 'Modem',
                    'Pare-feu' => 'Pare-feu',
                    'Projecteur' => 'Projecteur',
                    'Écran de projection' => 'Écran de projection',
                    'Tableau interactif' => 'Tableau interactif',
                    'Disque dur externe' => 'Disque dur externe',
                    'Clé USB' => 'Clé USB',
                    'NAS' => 'NAS',
                    'Onduleur' => 'Onduleur',
                    'Téléphone IP' => 'Téléphone IP',
                    'Tablette' => 'Tablette',
                    'Smartphone' => 'Smartphone',
                    'Autre équipement' => 'Autre équipement',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => 'Sélectionnez le type d\'équipement',
                'help' => 'Choisissez le nom/type de l\'équipement.',
            ])
            ->add('marque', ChoiceType::class, [
                'label' => 'Marque',
                'choices' => [
                    'Dell' => 'Dell',
                    'HP' => 'HP',
                    'Lenovo' => 'Lenovo',
                    'Asus' => 'Asus',
                    'Acer' => 'Acer',
                    'Apple' => 'Apple',
                    'Canon' => 'Canon',
                    'Epson' => 'Epson',
                    'Brother' => 'Brother',
                    'Samsung' => 'Samsung',
                    'LG' => 'LG',
                    'Autre' => 'Autre',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => 'Sélectionnez une marque',
                'help' => 'Choisissez la marque de l\'équipement.',
            ])
            ->add('modele', ChoiceType::class, [
                'label' => 'Modèle',
                'choices' => [
                    'Dell Latitude 5420' => 'Dell Latitude 5420',
                    'Dell Latitude 7420' => 'Dell Latitude 7420',
                    'Dell OptiPlex 7090' => 'Dell OptiPlex 7090',
                    'Dell Inspiron 15 3000' => 'Dell Inspiron 15 3000',
                    'Dell XPS 13' => 'Dell XPS 13',
                    'Dell Precision 5550' => 'Dell Precision 5550',
                    'HP EliteBook 840 G8' => 'HP EliteBook 840 G8',
                    'HP ProBook 450 G8' => 'HP ProBook 450 G8',
                    'HP Pavilion 15' => 'HP Pavilion 15',
                    'HP EliteDesk 800 G6' => 'HP EliteDesk 800 G6',
                    'HP LaserJet Pro M404n' => 'HP LaserJet Pro M404n',
                    'HP OfficeJet Pro 9025' => 'HP OfficeJet Pro 9025',
                    'Lenovo ThinkPad T14' => 'Lenovo ThinkPad T14',
                    'Lenovo ThinkPad X1 Carbon' => 'Lenovo ThinkPad X1 Carbon',
                    'Lenovo IdeaPad 3' => 'Lenovo IdeaPad 3',
                    'Lenovo ThinkCentre M720q' => 'Lenovo ThinkCentre M720q',
                    'Canon PIXMA TS3150' => 'Canon PIXMA TS3150',
                    'Canon imageCLASS MF445dw' => 'Canon imageCLASS MF445dw',
                    'Canon CanoScan LiDE 300' => 'Canon CanoScan LiDE 300',
                    'Epson EcoTank L3150' => 'Epson EcoTank L3150',
                    'Epson WorkForce Pro WF-4730' => 'Epson WorkForce Pro WF-4730',
                    'Epson Perfection V600' => 'Epson Perfection V600',
                    'MacBook Air M2' => 'MacBook Air M2',
                    'MacBook Pro 14"' => 'MacBook Pro 14"',
                    'iMac 24"' => 'iMac 24"',
                    'Mac mini M2' => 'Mac mini M2',
                    'Autre modèle' => 'Autre modèle',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => 'Sélectionnez un modèle',
                'help' => 'Choisissez le modèle spécifique de l\'équipement.',
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
                    'Ordinateur portable' => 'Ordinateur portable',
                    'Ordinateur de bureau' => 'Ordinateur de bureau',
                    'Imprimante laser' => 'Imprimante laser',
                    'Imprimante jet d\'encre' => 'Imprimante jet d\'encre',
                    'Projecteur' => 'Projecteur',
                    'Scanner' => 'Scanner',
                    'Écran/Moniteur' => 'Écran/Moniteur',
                    'Serveur' => 'Serveur',
                    'Équipement réseau' => 'Équipement réseau',
                    'Périphérique' => 'Périphérique',
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
                'required' => false,
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix d\'achat (FG)',
                'attr' => [
                    'placeholder' => 'Ex: 999.99',
                    'class' => 'form-control',
                ],
                'help' => 'Saisissez le prix d\'achat en francs guinéens.',
                'required' => false,
            ])
            ->add('garantieFin', DateType::class, [
                'label' => 'Fin de garantie',
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Ex: 2025-06-01',
                    'class' => 'form-control',
                ],
                'help' => 'Entrez la date de fin de garantie de l\'équipement.',
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'État',
                'choices' => [
                    'Fonctionnel' => 'fonctionnel',
                    'En panne' => 'en panne',
                    'Maintenance' => 'maintenance',
                    'Obsolète' => 'obsolete',
                ],
                'required' => true,
                'placeholder' => 'Tous les états',
                'attr' => [
                    'class' => 'form-selec search-input',
                ],
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
            ->add('localisation', EntityType::class, [
                'class' => Localisation::class,
                'choice_label' => function(Localisation $localisation = null) {
                    if (!$localisation) {
                        return '';
                    }
                    $batiment = $localisation->getNomBatiment() ?? 'Bâtiment inconnu';
                    $salle = $localisation->getSalle() ?? 'Salle inconnue';
                    return $batiment . ' - ' . $salle;
                },
                'placeholder' => 'Sélectionnez une localisation',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Choisissez l\'emplacement de l\'équipement.',
                'required' => false,
            ])
            ->add('utilisateur', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => Utilisateur::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez un utilisateur',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Choisissez l\'utilisateur principal de l\'équipement.',
                'required' => false,
            ])
            ->add('licences', EntityType::class, [
                'label' => 'Licences associées',
                'class' => Licence::class,
                'choice_label' => function(Licence $licence = null) {
                    if (!$licence) {
                        return '';
                    }
                    return $licence->getNomLogiciel() ?? 'Licence inconnue';
                },
                'multiple' => true,
                'expanded' => true,
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