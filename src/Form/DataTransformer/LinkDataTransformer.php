<?php
namespace Saitho\DataObjectFormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Pimcore\Model\DataObject\Data\Link;

/**
 * @implements DataTransformerInterface<Link, string>
 */
class LinkDataTransformer implements DataTransformerInterface
{
    protected Link $link;

    public function transform(mixed $value)
    {
        /** @var Link $value  */
        if ($value === null) {
            return '';
        }
        $this->link = $value;
        return $value->getPath();
    }

    public function reverseTransform(mixed $value)
    {
        if (!isset($this->link)) {
            $this->link = new Link();
        }
        $this->link->setDirect($value);
        return $this->link;
    }
}
