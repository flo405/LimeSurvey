<?php

namespace LimeSurvey\Model\Service;

use LimeSurvey\Model\Service\SurveyPatch\Path;

class SurveyPatch
{
    /**
     * Apply patches
     *
     * @param int $surveyId
     * @param array $patch
     * @return array
     */
    public function apply($surveyId, $patches)
    {
        $validationResult = $this->validatePatches($patches);

        $result = [
            'updatePatch' => [],
            'errors' => []
        ];

        if ($validationResult !== true) {
            $result['errors'] = $validationResult;
        } else {
            foreach ($patches as $patch) {
                $meta = $this->getPatchMeta($patch);
                if (!$meta) {
                    // unsupported patch
                }
            }
        }

        return  $result;
    }

    protected function getPatchMeta($patch)
    {
        // The order of definition is important
        // - more specific paths should be listed first
        $defaults = Path::getDefaults();

        $result = null;
        foreach ($defaults as $path) {
            if ($meta = $path->match($patch)) {
                $result = $meta;
                break;
            }
        }

        return $result;
    }

    /**
     * Validate patches
     *
     * @param array $patch
     * @return boolean|array
     */
    protected function validatePatches($patches)
    {
        $errors = [];
        foreach ($patches as $k => $patch) {
            $patchErrors = $this->validatePatch($patch);
            if ($patchErrors !== true) {
                $errors[$k] = $patchErrors;
            }
        }
        return empty($errors) ?: $errors;
    }

    /**
     * Validate patch
     *
     * @param array $patch
     * @return boolean|array
     */
    protected function validatePatch($patch)
    {
        $errors = [];
        if (!isset($patch['op'])) {
            $errors[] = 'Invalid operation';
        }
        if (!isset($patch['path'])) {
            $errors[] = 'Invalid path';
        }
        if (array_key_exists('value', $patch)) {
            $errors[] = 'No value set';
        }
        return empty($errors) ?: $errors;
    }
}
