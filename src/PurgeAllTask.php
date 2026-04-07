<?php

namespace Symbiote\Cloudflare;

use SilverStripe\Dev\BuildTask;

class PurgeAllTask extends BuildTask
{
    use PurgeTask;

    protected static string $commandName = 'cloudflare-purge-everything';

    protected string $title = 'Cloudflare Purge: Everything';

    protected static string $description = 'Purges everything from the cache. WARNING: You need to be *really* sure you want this.';

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeAll();
    }
}
