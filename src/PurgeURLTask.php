<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class PurgeURLTask extends BuildTask
{
    use PurgeTask {
        getOptions as purgeTaskOptions;
    }

    protected static string $commandName = 'cloudflare-purge-url';

    protected string $title = 'Cloudflare Purge: URL';

    protected static string $description = 'Purges a single or multiple URLs, with an absolute or relative URL (ie. url="admin/,Security/" or url="http://myproductionsite.com/admin, http://myproductionsite.com/Security")';

    protected $param_url = [];

    public function run(InputInterface $input, PolyOutput $output): int
    {
        $url = $input->getOption('purge_url');
        if (!$url) {
            $output->writeln('Missing "purge_url" parameter.');
            return Command::FAILURE;
        }

        // Allow multiple URLs
        $urlList = explode(',', $url);
        foreach ($urlList as $i => $url) {
            $url = trim($url);
            // Remove URL if it's a blank string, this allows trailing commas
            if (!$url) {
                unset($urlList[$i]);
            }
        }

        $this->param_url = $urlList;

        return $this->endRun($input, $output);
    }

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeURLs($this->param_url);
    }

    public function getOptions(): array
    {
        return array_merge(
            [
                new InputOption('purge_url', null, InputOption::VALUE_REQUIRED, 'Url to purge'),
            ],
            $this->purgeTaskOptions(),
        );
    }
}
