<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Dev\BuildTask;

class PurgeCSSAndJavascriptTask extends BuildTask
{
    use PurgeTask;

    protected static string $commandName = 'cloudflare-purge-css-javascript';

    protected string $title = 'Cloudflare Purge: CSS and JavaScript';

    protected static string $description = 'Purges all CSS and JavaScript files.';

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeCSSAndJavascript();
    }
}
