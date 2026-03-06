<?php
namespace Saitho\DataObjectFormBundle\Form\Datamapper;

use Carbon\CarbonPeriod;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Form;

class DateRangeDatamapper implements DataMapperInterface
{
    public function mapDataToForms(mixed $viewData, \Traversable $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!($viewData instanceof CarbonPeriod)) {
            throw new UnexpectedTypeException($viewData, 'expected type CarbonPeriod');
        }

        /** @var Form $field */
        foreach ($forms as $field) {
            switch ($field->getName()) {
                case 'start':
                    $field->setData($viewData->getStartDate());
                    break;
                case 'end':
                    $field->setData($viewData->getEndDate());
                    break;
            }
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData)
    {
        if (null === $viewData) {
            return;
        }

        if (!($viewData instanceof CarbonPeriod)) {
            throw new UnexpectedTypeException($viewData, 'expected CarbonPeriod');
        }

        // Ignore annotations by Carbon resulting in error
        // see https://github.com/symfony/symfony/issues/29161
        AnnotationReader::addGlobalIgnoredName('alias');
        AnnotationReader::addGlobalIgnoredName('mixin');

        /** @var Form $field */
        foreach ($forms as $field) {
            /** @var string $data */
            $data = $field->getData();
            switch ($field->getName()) {
                case 'start':
                    $viewData->setStartDate($data);
                    break;
                case 'end':
                    $viewData->setEndDate($data);
                    break;
            }
        }
    }
}
