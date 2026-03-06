<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Saitho\DataObjectFormBundle\Form;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Form\FormInterface;

class ConcreteEditFormHandler
{
    /**
     * @var string[]
     */
    protected array $buildFields = [];

    /**
     * Maps form values to customer
     *
     * @param Concrete $object
     * @param FormInterface $form
     */
    public function updateObjectFromForm(Concrete $object, FormInterface $form): void
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new \RuntimeException('Form must be submitted and valid to apply form data');
        }

        $formData = $form->getData();
        foreach ($this->buildFields as $fieldName) {
            $setter = 'set' . ucfirst($fieldName);

            if (!is_array($formData)) {
                continue;
            }

            $value = $formData[$fieldName] ?? null;
            if ($value === null) {
                continue;
            }

            $object->$setter($value);
        }
    }

    /**
     * Builds initial form data
     *
     * @param Concrete $event
     * @param string[] $fields
     *
     * @return string[]
     */
    public function buildFormData(Concrete $event, array $fields): array
    {
        $this->buildFields = $fields;
        $formData = [];
        foreach ($fields as $fieldName) {
            $getter = 'get' . ucfirst($fieldName);

            $value = $event->$getter();
            if (!$value) {
                continue;
            }

            $formData[$fieldName] = $value;
        }
        // set special property required to evaluate "disabled_when_published" special flag
        $formData['__published'] = $event->isPublished();

        return $formData;
    }
}
