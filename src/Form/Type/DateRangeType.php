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

use Carbon\CarbonInterface;
use Saitho\DataObjectFormBundle\Form\Datamapper\DateRangeDatamapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeType extends AbstractType
{
    /** @var DateType $start */
    protected DateType $start;
    /** @var DateType $end */
    protected DateType $end;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['start', 'end']);
        $resolver->addAllowedTypes('start', CarbonInterface::class);
        $resolver->addAllowedTypes('end', CarbonInterface::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setDataMapper(new DateRangeDatamapper());
        $widget = $options['widget'] ?? 'single_text';

        $builder->add('start', DateType::class, ['widget' => $widget, 'data' => $options['start']]);
        $builder->add('end', DateType::class, ['widget' => $widget, 'data' => $options['end']]);
    }
}
