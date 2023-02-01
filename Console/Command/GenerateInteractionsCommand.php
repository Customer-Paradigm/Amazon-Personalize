<?php
/**
 * CustomerParadigm_AmazonPersonalize
 *
 * @category   CustomerParadigm
 * @package    CustomerParadigm_AmazonPersonalize
 * @copyright  Copyright (c) 2023 Customer Paradigm (https://customerparadigm.com/)
 * @license    https://github.com/Customer-Paradigm/Amazon-Personalize/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace CustomerParadigm\AmazonPersonalize\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CustomerParadigm\AmazonPersonalize\Model\Data\InteractionGenerator;

//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputOption;


class GenerateInteractionsCommand extends Command
{
    protected $interactionGenerator;

    public function __construct(
        InteractionGenerator $interactionGenerator
    ) {
        $this->interactionGenerator = $interactionGenerator;
        parent::__construct();
    }

    /**
     * Sets configuration values for command
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('amazonpersonalize:interaction:generate')
            ->setDescription('Generate csv file of user interaction data');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Generating interactions csv file...");
        try {
            // generate and export interaction data csv file
            $this->interactionGenerator->generateCsv();
            $output->writeln('<info>COMPLETE</info>');
            $output->writeln('<info>File located at: ' .
                $this->interactionGenerator->getLastCreatedFilePath() . '</info>');
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
