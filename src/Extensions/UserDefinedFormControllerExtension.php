<?php

namespace Innoweb\BundledUserForms\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;

class UserDefinedFormControllerExtension extends Extension
{
    public function onAfterInit()
    {
        $blockJS = $this->owner->data()->config()->get('block_default_userforms_js');

        if (!$blockJS) {
            return;
        }

        $jsBundle = [];

        $userFormsModule = ModuleLoader::getModule('silverstripe/userforms');

        $resolverClass = 'Innoweb\RequirementsResolver\RequirementsResolver';
        $resolverExists = ClassInfo::exists($resolverClass);

        $jqueryPath = '//code.jquery.com/jquery-3.3.1.min.js';
        if ($resolverExists) {
            $resolvedjQueryPath = $resolverClass::get('jquery');
            if ($resolvedjQueryPath) {
                $jqueryPath = $resolvedjQueryPath;
            }
        }
        Requirements::javascript($jqueryPath);

        $jqueryValidatePath = $userFormsModule->getResource('client/thirdparty/jquery-validate/jquery.validate.min.js')->getRelativePath();
        if ($resolverExists) {
            $resolvedValidatePath = $resolverClass::get('jquery-validate');
            if ($resolvedValidatePath) {
                $jqueryValidatePath = $resolvedValidatePath;
            }
        }
        if (isset($resolvedValidatePath) && $resolvedValidatePath) {
            Requirements::javascript($resolvedValidatePath);
        } else {
            $jsBundle[] = $jqueryValidatePath;
        }

        // Is minified
        $adminModule = ModuleLoader::getModule('silverstripe/admin');
        $jsBundle[] = $adminModule->getResource('client/dist/js/i18n.js')->getRelativePath();

        // offers a method to add alternative lang files instead of hard-coded en/US/GB
        if ($this->owner->hasMethod('addBundlei18nPaths')) {
            $jsBundle = $this->owner->addBundlei18nPaths($jsBundle);
        } else {

            // Are not minified <1kb each
            $jsBundle[] = $userFormsModule->getResource('client/lang/en.js')->getRelativePath();
            $jsBundle[] = $userFormsModule->getResource('client/lang/en_US.js')->getRelativePath();
            $jsBundle[] = $userFormsModule->getResource('client/lang/en_GB.js')->getRelativePath();
        }

        // Is minified
        $jsBundle[] = $userFormsModule->getResource('client/dist/js/userforms.js')->getRelativePath();

        $validatei18nPaths = $this->owner->getUserFormsValidatei18nPaths();
        if ($validatei18nPaths && !empty($validatei18nPaths)) {
            $jsBundle = array_merge($jsBundle, $validatei18nPaths);
        }

        // Is not minified - 6kb
        if ($this->owner->data()->config()->get('enable_are_you_sure')) {
            $jsBundle[] = $userFormsModule->getResource('client/thirdparty/jquery.are-you-sure/jquery.are-you-sure.js')->getRelativePath();
        }

        Requirements::combine_files(
            'bundled-userforms.js',
            $jsBundle
        );
    }

    public function getUserFormsValidatei18nPaths()
    {
        $module = ModuleLoader::getModule('silverstripe/userforms');

        $candidates = [
            i18n::getData()->langFromLocale(i18n::config()->get('default_locale')),
            i18n::config()->get('default_locale'),
            i18n::getData()->langFromLocale(i18n::get_locale()),
            i18n::get_locale(),
        ];

        $paths = [];

        foreach ($candidates as $candidate) {
            foreach (['messages', 'methods'] as $candidateType) {
                $localisationCandidate = "client/thirdparty/jquery-validate/localization/{$candidateType}_{$candidate}.min.js";

                $resource = $module->getResource($localisationCandidate);
                if ($resource->exists()) {
                    $paths[] = $resource->getRelativePath();
                }
            }
        }

        return $paths;
    }
}
