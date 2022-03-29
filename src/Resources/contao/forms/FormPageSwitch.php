<?php

/*
 * This file is part of Oveleon ContaoAdvancedForm.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoAdvancedForm;

use Contao\BackendTemplate;
use Contao\FilesModel;
use Contao\System;
use Contao\Widget;

/**
 * Class FormSubmit
 *
 * @property string  $singleSRC
 * @property boolean $imageSubmit
 * @property string  $src
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
class FormPageSwitch extends Widget
{
    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_pageSwitch';

    /**
     * The CSS class prefix
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-pageswitch';

    /**
     * Do not validate this form field
     *
     * @param string
     *
     * @return string
     */
    public function validator($input)
    {
        return $input;
    }

    /**
     * Parse the template file and return it as string
     *
     * @param array|null $arrAttributes An optional attributes array
     *
     * @return string The template markup
     */
    public function parse($arrAttributes=null)
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FFL']['pageSwitch'][0]) . ' :: ' . $this->label . ' ###' . ($this->addCondition ? ' (' . $this->condition . ')' : '');

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
     * @throws BadMethodCallException
     */
    public function generate()
    {
        throw new BadMethodCallException('Calling generate() has been deprecated, you must use parse() instead!');
    }
}
