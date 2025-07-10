<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Contao Advanced Form.
 *
 * @package     contao-advanced-form
 * @license     proprietary
 * @author      Fabian Ekert          <https://github.com/eki89>
 * @author      Daniele Sciannimanica <https://github.com/doishub>
 * @author      Sebastian Zoglowek    <https://github.com/zoglo>
 * @copyright   Oveleon               <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoAdvancedForm\Widget;

use Contao\BackendTemplate;
use Contao\FilesModel;
use Contao\System;
use Contao\Widget;

/**
 * @property string $singleSRC
 * @property bool   $imageSubmit
 * @property string $src
 * @property bool   $addCondition
 * @property string $condition
 */
class PageSwitch extends Widget
{
    protected $strTemplate = 'form_pageSwitch';

    protected $strPrefix = 'widget widget-pageswitch';

    /**
     * Skip form field validation.
     */
    public function validator(mixed $varInput): mixed
    {
        return $varInput;
    }

    /**
     * Parse the template file and return it as string.
     *
     * @param array|null $arrAttributes An optional attributes array
     *
     * @return string The template markup
     */
    public function parse($arrAttributes = null): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->title = $this->label;

            if ($this->addCondition)
            {
                $objTemplate->wildcard = $this->condition;
            }

            return $objTemplate->parse();
        }

        if ($this->imageSubmit && $this->singleSRC)
        {
            $objModel = FilesModel::findByUuid($this->singleSRC);

            if ($objModel !== null && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objModel->path))
            {
                $this->src = $objModel->path;
            }
        }

        return parent::parse($arrAttributes);
    }

    /**
     * Old generate() method that must be implemented due to abstract declaration.
     *
     * @throws \BadMethodCallException
     */
    public function generate(): void
    {
        throw new \BadMethodCallException('Calling generate() has been deprecated, you must use parse() instead!');
    }
}
