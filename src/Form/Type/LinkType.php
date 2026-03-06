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

use Pimcore\Model\DataObject\Data\Link;
use Saitho\DataObjectFormBundle\Form\DataTransformer\LinkDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{
    public function getParent(): string
    {
        return UrlType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['data']);
        $resolver->addAllowedTypes('data', [Link::class, 'null']);
        $resolver->setDefault('data_class', null);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new LinkDataTransformer());
    }
}
