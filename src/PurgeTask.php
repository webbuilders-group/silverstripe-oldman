<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

//
// NOTE(Jake): 2018-04-26
//
// We changed this from a class extending BuildTask to a trait as
// any classes that extended this abstract class wouldn't appear in
// the dev/tasks list.
//
trait PurgeTask
{
    abstract protected function callPurgeFunction(Cloudflare $client);

    public function endRun(InputInterface $input, PolyOutput $output)
    {
        $client = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS);
        if (!$client->config()->enabled) {
            $output->writeln('Cloudflare is not currently enabled in YML.');
            return;
        }

        // If accessing via web-interface, add an "are you sure" message.
        if (!Director::is_cli()) {
            if ($input->getOption('purge') != true) {
                $output->writeln('Append "?purge=true" to the URL to confirm execution.');
                return;
            }
        }

        // Process
        $startTime = microtime(true);
        $result = $this->callPurgeFunction($client);
        $timeTakenInSeconds = number_format(microtime(true) - $startTime, 2, '.', '');

        $errors = $result->getErrors();

        if ($errors) {
            $status = 'PURGE ERRORS';
            if ($errors) {
                echo Director::is_cli() ? "\n" : '<br/>';
                $output->writeln('Error(s):');
                foreach ($errors as $error) {
                    $output->writeln($error);
                }
            }
        }

        // If no successes or errors, assume success.
        // ie. this is for purge everything.
        if (!$errors) {
            $output->writeln('SUCCESS');
        } else {
            $output->writeln($status . '. (' . count($errors) . ' failed)');
            return Command::FAILURE;
        }

        $output->writeln('Time taken: ' . $timeTakenInSeconds . ' seconds.');
        return Command::SUCCESS;
    }

    public function execute(InputInterface $input, PolyOutput $output): int
    {
        return $this->endRun($input, $output);
    }

    public function getOptions(): array
    {
        return [
            new InputOption('input', null, InputOption::VALUE_OPTIONAL, 'Whether to actually perform the purge or not'),
        ];
    }
}
