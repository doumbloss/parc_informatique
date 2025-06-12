<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AddAdminCommand extends Command
{
    protected static $defaultName = 'app:add-admin';
    protected static $defaultDescription = 'Ajoute un nouvel administrateur dans le système.';

    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'L\'email de l\'administrateur')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Le prénom de l\'administrateur')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Le nom de l\'administrateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Le mot de passe de l\'administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $password = $input->getArgument('password');

        // Vérifier si l'email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $output->writeln('<error>Un utilisateur avec cet email existe déjà.</error>');
            return Command::FAILURE;
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setIsAdmin(true); // Définir comme admin
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Créer l'entité Utilisateur associée
        $utilisateur = new Utilisateur();
        $utilisateur->setNom($firstName . ' ' . $lastName);
        $utilisateur->setEmail($email);
        $utilisateur->setFonction('Administrateur');
        $utilisateur->setService('Administration');
        $utilisateur->setTelephone('123456789');
        $utilisateur->setRole('admin');
        $utilisateur->setUser($user);

        $user->setUtilisateur($utilisateur);

        $this->entityManager->persist($user);
        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        $output->writeln('<info>Administrateur ' . $email . ' ajouté avec succès.</info>');
        return Command::SUCCESS;
    }
}