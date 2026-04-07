<?php
namespace Symbiote\Cloudflare;

use SilverStripe\Dev\BuildTask;

class PurgeImagesTask extends BuildTask
{
    use PurgeTask;

    protected static string $commandName = 'cloudflare-purge-images';

    protected string $title = 'Cloudflare Purge: Images';

    protected static string $description = 'Purges all image files.';

    public function callPurgeFunction(Cloudflare $client)
    {
        return $client->purgeImages();
    }
}
