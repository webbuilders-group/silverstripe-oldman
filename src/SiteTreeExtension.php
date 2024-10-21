<?php

namespace Symbiote\Cloudflare;

use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use Symbiote\Cloudflare\CloudflareResult;

class SiteTreeExtension extends DataExtension
{
    private static $_pageBeingPublished = 0;

    public function onBeforePublishRecursive()
    {
        if (!Cloudflare::config()->enabled) {
            return;
        }

        $this->owner->setField('_cfIsRecursivePublish', true);

        self::$_pageBeingPublished = $this->owner->ID;
    }

    public function onAfterPublish()
    {
        if (!Cloudflare::config()->enabled) {
            return;
        }

        if (!$this->owner->getField('_cfIsRecursivePublish')) {
            $cloudflareResult = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
            $this->addInformationToHeader($cloudflareResult);
        }
    }

    public function onAfterPublishRecursive()
    {
        if (!Cloudflare::config()->enabled) {
            return;
        }

        if ($this->owner->getField('_cfIsRecursivePublish')) {
            $cloudflareResult = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
            $this->addInformationToHeader($cloudflareResult);

            $this->owner->setField('_cfIsRecursivePublish', null);
            self::$_pageBeingPublished = 0;
        }
    }

    public function onAfterUnpublish()
    {
        if (!Cloudflare::config()->enabled) {
            return;
        }
        $cloudflareResult = Injector::inst()->get(Cloudflare::CLOUDFLARE_CLASS)->purgePage($this->owner);
        $this->addInformationToHeader($cloudflareResult);
    }

    /**
     * Gets the ID of the page being published, returns 0 if no page is being recurrsively published
     * @return int
     */
    public static function get_publishing_page()
    {
        return self::$_pageBeingPublished;
    }

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
