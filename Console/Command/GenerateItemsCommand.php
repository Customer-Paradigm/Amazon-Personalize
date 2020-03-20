<?php

namespace CustomerParadigm\AmazonPersonalize\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CustomerParadigm\AmazonPersonalize\Model\Data\ItemGenerator;
//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputOption;


class GenerateItemsCommand extends Command
{
    protected $itemGenerator;

    public function __construct(
        ItemGenerator $itemGenerator
    ){
        $this->itemGenerator = $itemGenerator;
        parent::__construct();
    }

    /**
     * Sets configuration values for command
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('amazonpersonalize:item:generate')
            ->setDescription('Generate csv file of catalog item/product data');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Generating items csv file...");
        try {
            // generate and export item data csv file
            $this->itemGenerator->generateCsv();
            $output->writeln('<info>COMPLETE</info>');
            $output->writeln('<info>File located at: ' .
                $this->itemGenerator->getLastCreatedFilePath() . '</info>');
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}