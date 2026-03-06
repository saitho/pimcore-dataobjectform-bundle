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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjectRelationType extends AbstractType
{
    /** @var ObjectMetadata[] $objects */
    protected array $objects;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['data', 'columns']);
        $resolver->setDefaults(['visible_fields' => '']);
        $resolver->setAllowedTypes('data', 'array');
        $resolver->setAllowedTypes('columns', 'array');
        $resolver->setAllowedTypes('visible_fields', ['array', 'string']);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['relation_columns'] = $options['columns'];
        $view->vars['relation_visible_fields'] = is_array($options['visible_fields']) ? $options['visible_fields'] : [$options['visible_fields']];
        parent::finishView($view, $form, $options);
    }

    /**
     * @param array{columns: string[], data: ObjectMetadata[]} $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $columns = $options['columns'];

        $counter = 0;
        foreach ($options['data'] as $object) {
            if (!$object instanceof ObjectMetadata) {
                throw new \Exception('data must be instance of Pimcore ObjectMetadata class');
            }
            $builder->add((string)$counter++, ObjectRelationElementType::class, ['object' => $object, 'columns' => $columns]);
        }
    }
}
