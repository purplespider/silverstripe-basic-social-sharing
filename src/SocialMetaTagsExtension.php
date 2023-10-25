<?php

namespace PurpleSpider\BasicSocialSharing;

use SilverStripe\Assets\Image;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use Silverstripe\ORM\DataExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Forms\TextareaField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\AssetAdmin\Forms\UploadField;

class BasicSocialSharingExtension extends DataExtension {
    private static $db = array(
        "SocialMetaTitle" => "Text",
        "SocialMetaDescription" => "Text"
    );

    private static $has_one = array(
        "SocialMetaImage"   => Image::class
    );

    private static $owns = array(
        "SocialMetaImage"
    );

    public function updateCMSFields(FieldList $fields) {
        $owner = $this->getOwner();

        if (is_subclass_of($owner, SiteTree::class) && !is_a($owner, RedirectorPage::class) && !is_a($owner, ErrorPage::class)) {
            $fields->addFieldToTab('Root.Main',
                ToggleCompositeField::create(
                    'SocialMedia',
                    'Social Media Sharing',
                    [
                        LiteralField::create('SocialInfo', '<div class="field" style="margin-bottom:1.23rem"><p class="alert" style="padding-left:0">Customise the details displayed in preview cards when this page is shared on Facebook, Twitter, LinkedIn etc:</p></div>'),
                        TextField::create("SocialMetaTitle",'Title')->setAttribute('placeholder', $owner->findSocialTitleText($forPlaceholder = true)),
                        TextareaField::create("SocialMetaDescription",'Description')->setRows(3)->setAttribute('placeholder', $owner->findSocialMetaDescription($forPlaceholder = true)),
                        UploadField::create("SocialMetaImage",'Image'),
                        LiteralField::create('Debug', '<div class="field" style="margin-bottom:1.23rem"><p class="alert alert-warning">If you change these <i>after</i> sharing the page on Facebook, you may need to instruct Facebook to "re-scrape" this page before it will display the updated details. You can do this using the <strong>Scrape Again</strong> button on the <a href="https://developers.facebook.com/tools/debug/?q=' . $this->owner->AbsoluteLink() . '" target="_blank"><strong>Facebook Sharing Debugger</strong></a>.</div>'),
                        LiteralField::create('ShareLink', '<div class="field" style="margin-bottom:1.23rem"><p style="font-weight:bold; font-size:16px; padding-bottom:3px;display:block;">Share to: <a target="_blank"  href="http://www.facebook.com/share.php?u='.$owner->AbsoluteLink().'">Facebook</a> / <a target="_blank"  href="http://twitter.com/share?text=&url='.$owner->AbsoluteLink().'">Twitter</a> / <a target="_blank"  href="https://www.linkedin.com/sharing/share-offsite/?url='.$owner->AbsoluteLink().'">LinkedIn</a></p></div>')
                    ]
                )->setHeadingLevel(4)
            );

            // Generate Image field description
            if($this->owner->findSocialMetaImage() && $this->owner->findSocialMetaImage() != $this->owner->SocialMetaImage()) {
                $fields->dataFieldByName('SocialMetaImage')->setDescription('Using image: <a target="_blank" href="'.$owner->findSocialMetaImage()->AbsoluteLink().'"><img src="'.$owner->findSocialMetaImage()->ScaleHeight(32)->URL.'">&nbsp;'.$owner->findSocialMetaImage()->Name.'</a>');
            } else if(!$this->owner->findSocialMetaImage()){
                $fields->dataFieldByName('SocialMetaImage')->setDescription('If no image is set, Facebook and Twitter will try to detect the main image on the page automatically.');
            }

            // Generate Description field description
            if(!$this->owner->findSocialMetaDescription()) {
                $fields->dataFieldByName('SocialMetaDescription')->setDescription('If no description is set, Facebook and Twitter will try to obtain a description from the page content.');
            }
        }
    }

    function findSocialTitleText($forPlaceholder = false) {
        $owner = $this->getOwner();
        $className = $owner->ClassName;
        $configClass = "InnisMaggiore\\SocialMetaTags";

        $titlesConfig = Config::inst()->get($configClass, 'titles') ?: array();
        $defaultTitle = Config::inst()->get($configClass, 'default_title') ?: "Title";


        // 1. Return Social Media Title if specifed in CMS
        if ($owner->SocialMetaTitle != "" && !$forPlaceholder) {
            return Convert::raw2att($owner->SocialMetaTitle);
        
        // 2. Return class specific title field from YML config
        } else if (array_key_exists($className, $titlesConfig)) {
            $titleTextField = $titlesConfig[$className];
            if (isset($owner->$titleTextField) && $owner->$titleTextField != "") {
                return Convert::raw2att($owner->$titleTextField);
            }

        // 3. Return MetaTitle if set in CMS
        } else if (isset($owner->MetaTitle) && $owner->MetaTitle != "") {
            return Convert::raw2att($owner->MetaTitle);
        
        // 4. Return value from default title field (e.g. Title), if set in YML
        } else if ($defaultTitle != "" && isset($owner->$defaultTitle) && $owner->$defaultTitle != "") {
            return Convert::raw2att($owner->$defaultTitle);
        }

        // 5. Fall back to page Title
        return $owner->Title;
    }

    function findSocialMetaDescription($forPlaceholder = false)
    {
        $owner = $this->getOwner();
        $className = $owner->ClassName;
        $configClass = "InnisMaggiore\\SocialMetaTags";
        $defaultDescription = Config::inst()->get($configClass, 'default_description') ?: "MetaDescription";
        $descriptionsConfig = Config::inst()->get($configClass,'descriptions') ?: array();        

        // 1. Return Social Media Description if specifed in CMS
        if ($owner->SocialMetaDescription != "" && !$forPlaceholder) {
            return Convert::raw2att($owner->SocialMetaDescription);
        
        // 2. Return class specific description field from YML config
        } else if (array_key_exists($className, $descriptionsConfig)) {
            $descriptionTextField = $descriptionsConfig[$className];
            if (isset($owner->$descriptionTextField) && $owner->$descriptionTextField != "") {
                return Convert::raw2att($owner->$descriptionTextField);
            }
        
        // 3. Return MetaDescription if set in CMS
        } else if (isset($owner->MetaDescription) && $owner->MetaDescription != "") {
            return Convert::raw2att($owner->MetaDescription);

        // 4. Return value from default description (e.g. Content), if set in YML
        } else if ($defaultDescription != "" && isset($owner->$defaultDescription) && $owner->$defaultDescription != "") {
            if($defaultDescription == 'Content') {
                return Convert::raw2att(strip_tags($owner->obj('Content')->LimitCharacters(250,'...')));
            }
            return Convert::raw2att($owner->$defaultDescription);
        }
    }

    function findSocialMetaImage() {
        $owner = $this->getOwner();
        $className = $owner->ClassName;
        $configClass = "InnisMaggiore\\SocialMetaTags";
        $imagesConfig = Config::inst()->get($configClass, 'images') ?: array();

        
        // if customized image field isn't populated, fall back to social meta image
        $image = $owner->SocialMetaImage();
        if ($image && $image->ID != 0) {
            return $image;
        }
        
        // get image by customized field
        if (!isset($imageLink)) {
            if (array_key_exists($className, $imagesConfig)) {
                $imageField = $imagesConfig[$className];
                $image = $owner->$imageField();
                if ($image && $image->ID != 0) {
                    return $image;
                }
            }
        }

        return false;
    }

    public function MetaTags(&$tags) {
        $owner = $this->getOwner();
        $className = $owner->ClassName;

        if ($className != ErrorPage::class) {
            $siteConfig = SiteConfig::current_site_config();
            $siteTitle = Convert::raw2att($siteConfig->Title);

            $ogType = "website";

            // get specified fields
            $configClass = "InnisMaggiore\\SocialMetaTags";
            $twitterSite = Config::inst()->get($configClass, 'twitter_site');

            // get customized fields
            $typesConfig = Config::inst()->get($configClass, 'types') ?: array();

            $titleText = $this->findSocialTitleText();

            if($this->findSocialMetaImage() && $this->findSocialMetaImage()->exists()) {
                $imageLink = $this->findSocialMetaImage()->AbsoluteLink();
            } else {
                $imageLink = false;
            }

            // if customized type is set, use it.
            if (array_key_exists($className, $typesConfig)) {
                $ogType = $typesConfig[$className];
            }

            $descriptionText = $this->findSocialMetaDescription();


            // get link
            $link = $owner->AbsoluteLink();

            /****************************************
             *  Add Social Meta Tags to tag output  *
             ****************************************/

            // OpenGraph
            $tags .= "\n<!-- OpenGraph Meta Tags -->\n";

            // og:type
            $tags .= "<meta property=\"og:site_name\" content=\"{$siteTitle}\" />\n";

            // og:site_name
            $tags .= "<meta property=\"og:type\" content=\"{$ogType}\" />\n";

            // og:title
            if (isset($titleText)) {
                $tags .= "<meta property=\"og:title\" content=\"{$titleText}\" />\n";
            }

            // og:image
            if ($imageLink) {
                $tags .= "<meta property=\"og:image\" content=\"{$imageLink}\" />\n";
            }

            // og:description
            if (isset($descriptionText)) {
                $tags .= "<meta property=\"og:description\" content=\"{$descriptionText}\" />\n";
            }

            // og:url
            if ($link != "") {
                $tags .= "<meta property=\"og:url\" content=\"{$link}\" />\n";
            }

            // Twitter
            $tags .= "\n<!-- Twitter Meta Tags -->\n";

            // twitter:site
            if ($twitterSite) {
                $tags .= "<meta name=\"twitter:site\" content=\"{$twitterSite}\" />\n";
            }

            // twitter:title
            if (isset($titleText)) {
                $tags .= "<meta name=\"twitter:title\" content=\"{$titleText}\" />\n";
            }

            // twitter:image
            if (isset($imageLink)) {
                $tags .= "<meta name=\"twitter:image\" content=\"{$imageLink}\" />\n";
            }

            // twitter:description
            if (isset($descriptionText)) {
                $tags .= "<meta name=\"twitter:description\" content=\"{$descriptionText}\" />\n";
            }

            // twitter:card - summary / summary_large_image
            if (isset($imageLink)) {
                $tags .= "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
            } else {
                $tags .= "<meta name=\"twitter:card\" content=\"summary\" />\n";
            }

        }
    }
}
