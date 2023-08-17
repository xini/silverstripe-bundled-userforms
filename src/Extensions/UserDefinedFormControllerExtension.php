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

        if ($blockJS) {
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

        // offers a method to add alternative userforms lang files
        if ($this->owner->hasMethod('addBundlei18nPaths')) {
            $jsBundle = $this->owner->addBundlei18nPaths($jsBundle);
        } else {
            $candidates = [
                'en',
                'en_US',
                i18n::getData()->langFromLocale(i18n::config()->get('default_locale')),
                i18n::config()->get('default_locale'),
                i18n::getData()->langFromLocale(i18n::get_locale()),
                i18n::get_locale(),
            ];

            $candidates = array_map(
                function ($candidate) {
                    return $candidate . '.js';
                },
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

        // load jquery validate localisation files
        if (isset($resolvedValidatePath) && $resolvedValidatePath) {
            $validatei18nPaths = $this->owner->getUserFormsValidatei18nCandidatePaths($resolvedValidatePath);
            if ($validatei18nPaths && !empty($validatei18nPaths)) {
                foreach ($validatei18nPaths as $path) {
                    if ($deferScripts) {
                        Requirements::javascript($path, ['defer' => true]);
                    } else {
                        Requirements::javascript($path);
                    }
                }
            }
        } else {
            $validatei18nPaths = $this->owner->getUserFormsValidatei18nCandidatePaths($resolvedValidatePath);
            if ($validatei18nPaths && !empty($validatei18nPaths)) {
                $jsBundle = array_merge($jsBundle, $validatei18nPaths);
            }
        }

        // add are_you_sure script
        if ($this->owner->data()->config()->get('enable_are_you_sure')) {
            $jsBundle[] = $userFormsModule->getResource('client/dist/js/jquery.are-you-sure/jquery.are-you-sure.js')->getRelativePath();
        }

        $jsBundle = array_unique($jsBundle);

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

    public function getUserFormsValidatei18nCandidatePaths($externalBaseScript = null)
    {
        $candidates = [
            i18n::getData()->langFromLocale(i18n::config()->get('default_locale')),
            i18n::config()->get('default_locale'),
            i18n::getData()->langFromLocale(i18n::get_locale()),
            i18n::get_locale(),
        ];

        $paths = [];

        $module = ModuleLoader::getModule('silverstripe/userforms');

        if ($externalBaseScript) {
            $externalBaseScript = substr($externalBaseScript, 0, strrpos( $externalBaseScript, '/'));
        }

        foreach ($candidates as $candidate) {
            foreach (['messages', 'methods'] as $candidateType) {
                if ($externalBaseScript) {
                    $paths[] = $externalBaseScript . "/localization/{$candidateType}_{$candidate}.min.js";
                } else {
                    $localisationCandidate = "client/dist/js/jquery-validation/localization/{$candidateType}_{$candidate}.min.js";

                    $resource = $module->getResource($localisationCandidate);
                    if ($resource->exists()) {
                        $paths[] = $resource->getRelativePath();
                    }
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
