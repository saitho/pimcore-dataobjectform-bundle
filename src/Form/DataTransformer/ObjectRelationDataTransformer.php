<?php
namespace Saitho\DataObjectFormBundle\Form\DataTransformer;

use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @implements DataTransformerInterface<ObjectMetadata, array<string, mixed>>
 */
class ObjectRelationDataTransformer implements DataTransformerInterface
{
    const TRANSFORM_OBJECTRELATION_DATA = 'saitho.objectRelationData.transformData';
    const REVERSE_TRANSFORM_OBJECTRELATION_DATA = 'saitho.objectRelationData.reverseTransformData';

    public function __construct(protected ObjectMetadata $fullObject)
    {
    }

    /**
     * @param mixed $value
     */
    public function transform(mixed $value)
    {
        $event = new GenericEvent();
        $event->setArgument('data', $this->fullObject->getData());
        $event->setArgument('object', $this->fullObject);
        \Pimcore::getEventDispatcher()->dispatch($event, self::TRANSFORM_OBJECTRELATION_DATA);

        /** @var array<string, mixed> $updatedData */
        $updatedData = $event->getArgument('data');
        return $updatedData;
    }

    public function reverseTransform(mixed $value)
    {
        $event = new GenericEvent();
        $event->setArgument('data', $value);
        $event->setArgument('object', $this->fullObject);
        \Pimcore::getEventDispatcher()->dispatch($event, self::REVERSE_TRANSFORM_OBJECTRELATION_DATA);

        /** @var array<int|string, mixed> $data */
        $data = $event->getArgument('data');
        $this->fullObject->setData($data);
        return $this->fullObject;
    }
}
