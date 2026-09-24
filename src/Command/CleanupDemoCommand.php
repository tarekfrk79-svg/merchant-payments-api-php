<?php
declare(strict_types=1);
namespace App\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name: 'app:demo:cleanup', description: 'Preview or delete explicitly marked demo data older than 24 hours')]
final class CleanupDemoCommand extends Command
{
    public function __construct(private readonly Connection $connection) { parent::__construct(); }
    protected function configure(): void { $this->addOption('execute', null, InputOption::VALUE_NONE, 'Delete expired demo records; otherwise preview only'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cutoff = (new \DateTimeImmutable('-24 hours', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $where = 'is_demo = TRUE AND created_at < ?';
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM merchants WHERE '.$where, [$cutoff]);
        if (!$input->getOption('execute')) { $output->writeln('Expired demo merchants: '.$count.'. Preview only; use --execute to delete.'); return Command::SUCCESS; }
        $this->connection->transactional(function (Connection $db) use ($where, $cutoff): void {
            $db->executeStatement('DELETE FROM payments WHERE merchant_id IN (SELECT id FROM merchants WHERE '.$where.')', [$cutoff]);
            $db->executeStatement('DELETE FROM merchants WHERE '.$where, [$cutoff]);
        });
        $output->writeln('Expired demo data removed. Regular API merchants preserved.');
        return Command::SUCCESS;
    }
}
