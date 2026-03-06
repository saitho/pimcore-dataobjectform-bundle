<?php

namespace Saitho\DataObjectFormBundle\Controller;

use Pimcore\Model\DataObject\Concrete;
use Saitho\DataObjectFormBundle\Form\ConcreteEditFormHandler;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

trait FormControllerTrait
{
    protected function prepareForm(
        string $type,
        ConcreteEditFormHandler $editFormHandler,
        Concrete $object = null
    ): FormInterface {
        $formData = null;
        if ($object) {
            /** @var string[] $fields */
            $fields = array_keys($type::getFields());
            $formData = $editFormHandler->buildFormData($object, $fields);
        }
        return $this->createForm($type, $formData);
    }

    /**
     * @return string[]
     */
    protected function handleForm(
        Request $request,
        FormInterface $form,
        Concrete $object,
        ConcreteEditFormHandler $editFormHandler
    ): array {
        $form->handleRequest($request);
        $errors = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $editFormHandler->updateObjectFromForm($object, $form);
            try {
                $object->save();
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors() as $error) {
                /** @var FormError $error */
                $errors[] = $error->getMessage();
            }
        }
        return $errors;
    }
}
