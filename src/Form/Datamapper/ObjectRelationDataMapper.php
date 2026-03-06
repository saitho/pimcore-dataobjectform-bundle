<?php
namespace Saitho\DataObjectFormBundle\Form\Datamapper;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormTypeInterface;

class ObjectRelationDataMapper implements DataMapperInterface
{
    /**
     * @param array{string, mixed} $viewData
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        $empty = null === $viewData || [] === $viewData;

        if (!$empty && !\is_array($viewData)) {
            throw new UnexpectedTypeException($viewData, 'array or empty');
        }

        if ($empty) {
            return;
        }

        /** @var Form $field */
        foreach ($forms as $field) {
            if (!array_key_exists($field->getName(), $viewData)) {
                continue;
            }
            $value = $this->convertValue(
                $viewData[$field->getName()],
                $field->getConfig()->getType()->getInnerType()
            );
            $field->setData($value);
        }
    }

    protected function convertValue(mixed $value, FormTypeInterface $targetType): mixed
    {
        if ($targetType instanceof TextType) {
            return (string)$value;
        }
        if ($targetType instanceof CheckboxType) {
            return (boolean)$value;
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $viewData
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (null === $viewData) {
            return;
        }

        if (!\is_array($viewData) && !\is_object($viewData)) {
            throw new UnexpectedTypeException($viewData, 'object, array or empty');
        }

        /** @var Form $field */
        foreach ($forms as $field) {
            $viewData[$field->getName()] = $field->getData();
        }
    }
}
