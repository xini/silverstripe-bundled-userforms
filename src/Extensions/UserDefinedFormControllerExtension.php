<?php

namespace Innoweb\BundledUserForms\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;

class UserDefinedFormControllerExtension extends Extension
{
    public function onAfterInit()
    {
        $blockJS = $this->getOwner()->data()->config()->get('block_default_userforms_js');

        if (!$blockJS) {
            return;
        }

        $jsBundle = [];

        $userFormsModule = ModuleLoader::getModule('silverstripe/userforms');

        $deferScripts = !$this->getOwner()->hasConditionalJavascript();

        // add jquery
        $jsBundle[] = $userFormsModule->getResource('client/dist/js/jquery.min.js')->getRelativePath();

        // add jquery validate
        $jsBundle[] = $userFormsModule->getResource('client/dist/js/jquery-validation/jquery.validate.min.js')->getRelativePath();

        // add i18n script
        $adminModule = ModuleLoader::getModule('silverstripe/admin');
        $jsBundle[] = $adminModule->getResource('client/dist/js/i18n.js')->getRelativePath();

        // offers a method to add alternative userforms lang files
        if ($this->getOwner()->hasMethod('addBundlei18nPaths')) {
            $jsBundle = $this->getOwner()->addBundlei18nPaths($jsBundle);
        } else {
            // add language files
            $candidates = [
                'en',
                'en_US',
                i18n::getData()->langFromLocale(i18n::config()->get('default_locale')),
                i18n::config()->get('default_locale'),
                i18n::getData()->langFromLocale(i18n::get_locale()),
                i18n::get_locale(),
            ];

            $candidates = array_map(
                fn($candidate) => $candidate . '.js',
                $candidates ?? []
            );

            foreach ($candidates as $candidate) {
                if (($resource = $userFormsModule->getResource('client/lang/' . $candidate)) && $resource->exists()) {
                    $jsBundle[] = $resource->getRelativePath();
                }
            }
        }

        // add base userforms script
        $jsBundle[] = $userFormsModule->getResource('client/dist/js/userforms.js')->getRelativePath();

        // add jquery validate localisation files
        $validatei18nPaths = $this->getOwner()->getUserFormsValidatei18nCandidatePaths();
        if ($validatei18nPaths && !empty($validatei18nPaths)) {
            $jsBundle = array_merge($jsBundle, $validatei18nPaths);
        }

        // add are_you_sure script
        if ($this->getOwner()->data()->config()->get('enable_are_you_sure')) {
            $jsBundle[] = $userFormsModule->getResource('client/dist/js/jquery.are-you-sure/jquery.are-you-sure.js')->getRelativePath();
        }

        $jsBundle = array_unique($jsBundle);

        if ($deferScripts) {
            Requirements::combine_files(
                'bundled-userforms.js',
                $jsBundle,
                ['defer' => true]
            );
        } else {
            Requirements::combine_files(
                'bundled-userforms.js',
                $jsBundle
            );
        }
    }

    public function getUserFormsValidatei18nCandidatePaths()
    {
        $candidates = [
            i18n::getData()->langFromLocale(i18n::config()->get('default_locale')),
            i18n::config()->get('default_locale'),
            i18n::getData()->langFromLocale(i18n::get_locale()),
            i18n::get_locale(),
        ];

        $paths = [];

        $module = ModuleLoader::getModule('silverstripe/userforms');

        foreach ($candidates as $candidate) {
            foreach (['messages', 'methods'] as $candidateType) {
                $localisationCandidate = sprintf('client/dist/js/jquery-validation/localization/%s_%s.min.js', $candidateType, $candidate);

                if (($resource = $module->getResource($localisationCandidate)) && $resource->exists()) {
                    $paths[] = $resource->getRelativePath();
                }
            }
        }

        return $paths;
    }

    public function hasConditionalJavascript()
    {
        $form = $this->getOwner()->data();
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
