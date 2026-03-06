<?php

namespace Saitho\DataObjectFormBundle\Form\Type;

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

use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Saitho\DataObjectFormBundle\Form\Datamapper\ObjectRelationDataMapper;
use Saitho\DataObjectFormBundle\Form\DataTransformer\ObjectRelationDataTransformer;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjectRelationElementType extends AbstractType
{
    const MAP_DATA_EVENT = 'saitho.dataobjectform.objectRelationElement.map';

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['object', 'columns']);
        $resolver->setAllowedTypes('object', ObjectMetadata::class);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['relation_object'] = $options['object'];
    }

    /**
     * @param array{object: ObjectMetadata, columns: array{type: string, label: string, key: string}[]} $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setData($options['object']);
        $builder->setDataMapper(new ObjectRelationDataMapper());
        $builder->addModelTransformer(new ObjectRelationDataTransformer($options['object']));
        /** @var array{type: string, position: integer, key: string, label: string} $column */
        foreach ($options['columns'] as $column) {
            $type = TextType::class;
            if ($column['type'] === 'bool') {
                $type = CheckboxType::class;
            }

            // Allow modifications to type and options via event
            $event = new GenericEvent();
            $event->setArgument('type', $type);
            $event->setArgument('options', ['label' => $column['label']]);
            $event->setArgument('fieldName', $column['key']);
            $event->setArgument('builder', $builder);
            \Pimcore::getEventDispatcher()->dispatch($event, self::MAP_DATA_EVENT);

            /** @var string $type */
            $type = $event->getArgument('type');
            /** @var array<string, mixed> $options */
            $options = $event->getArgument('options');

            $builder->add($column['key'], $type, $options);
        }
    }
}
