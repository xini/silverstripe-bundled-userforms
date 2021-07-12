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

        $deferScripts = !$this->owner->hasConditionalJavascript();

        $jqueryPath = '//code.jquery.com/jquery-3.6.0.min.js';
        if ($resolverExists) {
            $resolvedjQueryPath = $resolverClass::get('jquery');
            if ($resolvedjQueryPath) {
                $jqueryPath = $resolvedjQueryPath;
            }
        }
        if ($deferScripts) {
            Requirements::javascript($jqueryPath, ['defer' => true]);
        } else {
            Requirements::javascript($jqueryPath);
        }

        $jqueryValidatePath = $userFormsModule->getResource('client/dist/js/jquery-validation/jquery.validate.min.js')->getRelativePath();
        if ($resolverExists) {
            $resolvedValidatePath = $resolverClass::get('jquery-validate');
            if ($resolvedValidatePath) {
                $jqueryValidatePath = $resolvedValidatePath;
            }
        }
        if (isset($resolvedValidatePath) && $resolvedValidatePath) {
            if ($deferScripts) {
                Requirements::javascript($resolvedValidatePath, ['defer' => true]);
            } else {
                Requirements::javascript($resolvedValidatePath);
            }
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
            $jsBundle[] = $userFormsModule->getResource('client/dist/js/jquery.are-you-sure/jquery.are-you-sure.js')->getRelativePath();
        }

        if ($deferScripts) {
            Requirements::combine_files(
                'bundled-userforms.js',
                $jsBundle,
                ['defer' => $deferScripts]
            );
        } else {
            Requirements::combine_files(
                'bundled-userforms.js',
                $jsBundle
            );
        }
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

    public function hasConditionalJavascript()
    {
        $form = $this->owner->data();
        if (!$form) {
            return false;
        }
        $formFields = $form->Fields();

        if ($formFields) {
            /** @var EditableFormField $field */
            foreach ($formFields as $field) {
                if ($field->formatDisplayRules()) {
                    return true;
                }
            }
        }
        return false;
    }

}
