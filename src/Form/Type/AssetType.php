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

use Pimcore\Model\Asset;
use Saitho\DataObjectFormBundle\Form\DataTransformer\FileDataTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as SymfonyFormType;
use Symfony\Component\Validator\Constraints;

class AssetType extends SymfonyFormType\FileType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['data', 'asset_type'])
            ->setAllowedValues('asset_type', ['image', 'document', 'video', 'audio', 'asset'])
            ->addAllowedTypes('data', [Asset::class, 'null'])
            ->setDefaults(['asset_storage' => '/', 'file_constraints' => [], 'accept' => 'default', 'show_preview' => false])
            ->addAllowedTypes('file_constraints', [Constraints\File::class, Constraints\File::class . '[]']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        if ($options['accept'] === 'default') {
            $options['accept'] = match ($options['asset_type']) {
                'image' => 'image/*',
                'document' => 'document/*',
                'video' => 'video/*',
                'audio' => 'audio/*',
                default => '*'
            };
        }
        $view->vars['attr'] = ['accept' => $options['accept']];
    }

    /**
     * @param array{asset_storage: string, asset_type: string, file_constraints: Constraints\File[]} $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->addModelTransformer(new FileDataTransformer(
            $options['asset_storage'],
            $options['asset_type'],
            '',
            $options['file_constraints'],
        ));
    }
}
