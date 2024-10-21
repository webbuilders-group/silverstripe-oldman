<?php
namespace WebbuildersGroup\CloudFlare\Extensions;

use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use Symbiote\Cloudflare\Cloudflare;
use Symbiote\Cloudflare\CloudflareResult;
use Symbiote\Cloudflare\SiteTreeExtension;

class BaseElementExtension extends DataExtension
{
    /**
     * Handles purging the page after publishing if the parent was not being recursively published
     */
    public function onAfterPublish()
    {
        if (!Cloudflare::config()->enabled) {
            return;
        }

        if (SiteTreeExtension::get_publishing_page() != $this->owner->getPage()->ID) {
            $cloudflareResult = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner->getPage());
            $this->addInformationToHeader($cloudflareResult);
        }
    }

    /**
     * Handles adding the results to the headers
     * @param CloudflareResult $cloudflareResult Cloudflare result to add
     */
    private function addInformationToHeader(CloudflareResult $cloudflareResult = null)
    {
        if (!Controller::has_curr()) {
            return false;
        }
        if (!$cloudflareResult) {
            return false;
        }
        $controller = Controller::curr();
        // NOTE(Jake): 2018-04-27
        //
        // Make this only occur in context of the CMSPageEditController as we don't
        // want to add headers in a CLI task that purges pages for example.
        //
        if (!($controller instanceof CMSPageEditController)) {
            return false;
        }
        $result = false;
        $urls = $cloudflareResult->getSuccesses();
        $errors = $cloudflareResult->getErrors();
        $response = Controller::curr()->getResponse();
        if ($urls) {
            $response->addHeader('oldman-cloudflare-cleared-links', implode(",", $urls));
            $result = true;
        }
        if ($errors) {
            $response->addHeader('oldman-cloudflare-errors', implode(",", $errors));
            $result = true;
        }
        return $result;
    }
}
