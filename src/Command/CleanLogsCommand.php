<?php

namespace App\Command;

use App\Service\LoggerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:logs:clean',
    description: 'Nettoie les anciens logs d\'activité de la base de données'
)]
class CleanLogsCommand extends Command
{
    private LoggerService $loggerService;

    public function __construct(LoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Nombre de jours à conserver', 90)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans suppression réelle')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force la suppression sans confirmation')
            ->setHelp('
Cette commande nettoie les anciens logs d\'activité de la base de données.

Exemples d\'utilisation:
  <info>php bin/console app:logs:clean</info>                    # Supprime les logs de plus de 90 jours
  <info>php bin/console app:logs:clean 30</info>                 # Supprime les logs de plus de 30 jours
  <info>php bin/console app:logs:clean 60 --dry-run</info>       # Simulation pour les logs de plus de 60 jours
  <info>php bin/console app:logs:clean 90 --force</info>         # Suppression forcée sans confirmation
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $daysToKeep = (int) $input->getArgument('days');
        $isDryRun = $input->getOption('dry-run');
        $isForced = $input->getOption('force');

        if ($daysToKeep <= 0) {
            $io->error('Le nombre de jours doit être supérieur à 0.');
            return Command::FAILURE;
        }

        $cutoffDate = new \DateTime("-{$daysToKeep} days");
        
        $io->title('Nettoyage des logs d\'activité');
        $io->text([
            sprintf('Suppression des logs antérieurs au: <info>%s</info>', $cutoffDate->format('d/m/Y H:i:s')),
            sprintf('Mode: <comment>%s</comment>', $isDryRun ? 'SIMULATION' : 'RÉEL')
        ]);

        if ($isDryRun) {
            $io->note('Mode simulation activé - Aucune suppression ne sera effectuée');
        }

        // Compter les logs à supprimer
        try {
            $countToDelete = $this->loggerService->countLogsToDelete($daysToKeep);
            
            if ($countToDelete === 0) {
                $io->success('Aucun log à supprimer.');
                return Command::SUCCESS;
            }

            $io->text(sprintf('Nombre de logs à supprimer: <fg=yellow>%d</fg=yellow>', $countToDelete));

            // Demander confirmation si pas en mode forcé
            if (!$isForced && !$isDryRun) {
                if (!$io->confirm('Êtes-vous sûr de vouloir supprimer ces logs ?', false)) {
                    $io->text('Opération annulée.');
                    return Command::SUCCESS;
                }
            }

            if (!$isDryRun) {
                // Effectuer la suppression réelle
                $deletedCount = $this->loggerService->cleanOldLogs($daysToKeep);
                
                $io->success(sprintf(
                    'Nettoyage terminé avec succès. %d log(s) supprimé(s).',
                    $deletedCount
                ));

                // Log de l'opération de nettoyage
                $this->loggerService->logActivity(
                    'Nettoyage automatique des logs',
                    'System',
                    null,
                    [
                        'deleted_count' => $deletedCount,
                        'days_kept' => $daysToKeep,
                        'executed_by' => 'console_command'
                    ]
                );
            } else {
                $io->success(sprintf(
                    'Simulation terminée. %d log(s) seraient supprimé(s).',
                    $countToDelete
                ));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Erreur lors du nettoyage des logs:',
                $e->getMessage()
            ]);

            // Log de l'erreur
            $this->loggerService->logBusinessError('Erreur lors du nettoyage automatique des logs', [
                'error' => $e->getMessage(),
                'days_to_keep' => $daysToKeep,
                'is_dry_run' => $isDryRun
            ]);

            return Command::FAILURE;
        }
    }
}