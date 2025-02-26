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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FormStorage
{
    const FORM_STORAGE_IDENTIFIER = 'ADV_FORM_STORAGE';

    const FORM_INVALID_IDENTIFIER = 'ADV_FORM_INVALID';

    const FILE_STORAGE_IDENTIFIER = 'advf';

    private readonly SessionInterface $session;

    public function __construct(
        private readonly string $identifier,
        private readonly string $projectDir,
        private readonly RequestStack $requestStack,
    ) {
        $this->session = $this->requestStack->getSession();
    }

    public function saveStep(string $step, array $submitted, array $labels = [], array $files = []): void
    {
        // $submitted = $this->requestStack->getCurrentRequest()->request->all();
        // $files = $this->requestStack->getCurrentRequest()->files->all();

        // Make sure files are moved to our own tmp directory so they are kept across php processes
        foreach ($files as &$upload)
        {
            if (!\is_array($upload))
            {
                continue;
            }

            $this->handleTemporaryUploadedFiles($upload);
        }

        $storage = $this->session->get(self::FORM_STORAGE_IDENTIFIER, []);

        $this->session->set(self::FORM_STORAGE_IDENTIFIER, array_replace_recursive($storage, [
            $this->identifier => [
                $step => [
                    'submitted' => $submitted,
                    'labels' => $labels,
                    'files' => $files,
                ],
            ],
        ]));
    }

    /*private function normalizeSymfonyFileUpload(UploadedFile $file): array
    {
        return [
            'name' => pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME),
            'type' => $file->guessExtension(),
            'tmp_name' => $file->getPathname(),
            'error' => $file->getError(),
            'size' => $file->getSize(),
            'uploaded' => true,
            'uuid' => null,
        ];
    }*/

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
            unset($formFields['']); // Unset the empty key
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

    private function handleTemporaryUploadedFiles(array &$upload): void
    {
        $temp = [];

        foreach ($upload as $key => &$uploadedFile)
        {
            if (!\is_array($uploadedFile))
            {
                continue;
            }

            if (!\array_key_exists('tmp_name', $uploadedFile))
            {
                continue;
            }

            if (is_uploaded_file($uploadedFile['tmp_name']))
            {
                $tempDir = $this->projectDir . DIRECTORY_SEPARATOR . 'system'. DIRECTORY_SEPARATOR . 'tmp';
                $target = (new Filesystem())->tempnam($tempDir, self::FILE_STORAGE_IDENTIFIER);
                move_uploaded_file($uploadedFile['tmp_name'], $target);
                $upload[$key]['tmp_name'] = $target;

                // If the file was uploaded through a PHP process, it should not be treated as an uploaded file
                $upload[$key]['uploaded'] = false;
            }

            $temp = $upload[$key];
        }

        $upload = $temp;
    }
}
