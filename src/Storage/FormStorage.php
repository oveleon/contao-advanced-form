<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Advanced Form.
 *
 * @package     contao-advanced-form
 * @license     AGPL-3.0
 * @author      Fabian Ekert          <https://github.com/eki89>
 * @author      Daniele Sciannimanica <https://github.com/doishub>
 * @author      Sebastian Zoglowek    <https://github.com/zoglo>
 * @copyright   Oveleon               <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoAdvancedForm\Storage;

use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FormStorage
{
    const FORM_STORAGE_IDENTIFIER = 'ADV_FORM_STORAGE';

    const FORM_INVALID_IDENTIFIER = 'ADV_FORM_INVALID';

    private readonly SessionInterface $session;

    public function __construct(
        private readonly string $identifier,
        private readonly RequestStack $requestStack,
    ) {
        $this->session = $this->requestStack->getSession();
    }

    public function saveStep(string $step, array $labels = []): void
    {
        $submitted = $this->requestStack->getCurrentRequest()->request->all()
        ;
        $files = $this->requestStack->getCurrentRequest()->files->all()
        ;

        // Make sure files are moved to our own tmp directory so they are
        // kept across php processes
        foreach ($files as $k => $file)
        {
            // If the user marked the form field to upload the file into
            // a certain directory, this check will return false and thus
            // we won't move anything.
            if (is_uploaded_file($file['tmp_name']))
            {
                $target = \sprintf('%s/system/tmp/mp_forms_%s.%s',
                    System::getContainer()->getParameter('kernel.project_dir'),
                    basename((string) $file['tmp_name']),
                    $this->guessFileExtension($file),
                );
                move_uploaded_file($file['tmp_name'], $target);
                $files[$k]['tmp_name'] = $target;
            }
        }

        $storage = $this->session->get(self::FORM_STORAGE_IDENTIFIER, []);

        $this->session->set(self::FORM_STORAGE_IDENTIFIER, array_merge($storage, [
            $this->identifier => [
                $step => [
                    'submitted' => $submitted,
                    'labels' => $labels,
                    'files' => $files,
                ],
            ],
        ]));
    }

    public function getByStep(string $step): array
    {
        return $this->getStorage()[$step] ?? [];
    }

    public function get(array $fields): array
    {
        $arrSubmitted = $arrLabels = $arrFiles = $formFields = [];

        if ($fields !== [])
        {
            $formFields = array_fill_keys(array_column($fields, 'name'), '');
        }

        foreach ($this->getStorage() as $stepData)
        {
            $arrSubmitted = array_merge($arrSubmitted, (array) $stepData['submitted']);
            $arrLabels = array_merge($arrLabels, (array) $stepData['labels']);
            $arrFiles = array_merge($arrFiles, (array) $stepData['files']);
        }

        return [
            'fieldset' => $formFields,
            'submitted' => $arrSubmitted,
            'fieldsetSubmitted' => array_merge($formFields, $arrSubmitted),
            'labels' => $arrLabels,
            'files' => $arrFiles,
        ];
    }

    public function reset(): void
    {
        $storage = $this->session->get(self::FORM_STORAGE_IDENTIFIER, []);

        unset($storage[$this->identifier]);

        $this->session->set(self::FORM_STORAGE_IDENTIFIER, $storage);
    }

    public function invalidatePreviousSteps(): void
    {
        $this->session->set(self::FORM_INVALID_IDENTIFIER, $this->identifier);
    }

    public function previousStepsWereInvalid(): bool
    {
        return $this->identifier === $this->session->get(self::FORM_INVALID_IDENTIFIER);
    }

    public function resetInvalidation(): void
    {
        $this->session->remove(self::FORM_INVALID_IDENTIFIER);
    }

    private function getStorage(): array
    {
        $storage = $this->session->get(self::FORM_STORAGE_IDENTIFIER, []);

        return $storage[$this->identifier] ?? [];
    }

    private function guessFileExtension(array $file): int|string
    {
        $extension = 'unknown';

        if (!isset($file['type']))
        {
            return $extension;
        }

        foreach ($GLOBALS['TL_MIME'] as $ext => $data)
        {
            if ($data[0] === $file['type'])
            {
                $extension = $ext;
                break;
            }
        }

        return $extension;
    }
}
