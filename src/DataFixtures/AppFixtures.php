<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur admin
        $user = new User();
        $user->setEmail('doumbouyadmin@gmail.com');
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        // Hasher le mot de passe
        $password = $this->hasher->hashPassword($user, 'adminpassword');
        $user->setPassword($password);

        // Créer l'entité Utilisateur associée
        $utilisateur = new Utilisateur();
        $utilisateur->setNom('Admin User');
        $utilisateur->setFonction('Administrateur');
        $utilisateur->setService('Administration');
        $utilisateur->setEmail('doumbouyadmin@gmail.com');
        $utilisateur->setTelephone('123456789');
        $utilisateur->setRole('admin');
        $utilisateur->setUser($user); // Relation utilisateur vers user

        $user->setUtilisateur($utilisateur); // 🔧 AJOUT ICI : relation inverse

        $manager->persist($user);
        $manager->persist($utilisateur);

        $manager->flush();
    }
}
