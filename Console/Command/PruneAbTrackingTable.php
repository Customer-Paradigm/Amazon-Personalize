<?php

namespace CustomerParadigm\AmazonPersonalize\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CustomerParadigm\AmazonPersonalize\Model\AbTracking;

class PruneAbTrackingTable extends Command
{
    protected $abTracking;

    public function __construct(
        AbTracking $abTracking
    ){
        $this->abTracking = $abTracking;
        parent::__construct();
    }

    /**
     * Sets configuration values for command
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('amazonpersonalize:abtracking:prune')
            ->setDescription('Prune A/B tracking db table. Keep data for the last 7 days.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Pruning table aws_ab_tracking...");
        try {
            // prune aws_ab_tracking table 
            $this->abTracking->pruneData();
            $output->writeln('<info>COMPLETE</info>');
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
