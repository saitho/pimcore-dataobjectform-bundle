<?php
namespace Saitho\DataObjectFormBundle\Form;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox;
use Pimcore\Model\DataObject\ClassDefinition\Data\Country;
use Pimcore\Model\DataObject\ClassDefinition\Data\Countrymultiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Concrete;
use Saitho\DataObjectFormBundle\Form\Type\DateRangeType;
use Saitho\DataObjectFormBundle\Form\Type\AssetType;
use Saitho\DataObjectFormBundle\Form\Type\LinkType;
use Saitho\DataObjectFormBundle\Form\Type\ObjectRelationType;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraints;

trait MapDataClassValuesToFormTrait
{
    const MAP_DATA_EVENT = 'saitho.dataobjectform.mapDataClassValuesToForm';

    private static Concrete $mappingInstanceSample;

    protected static function setMappingInstanceExample(Concrete $anyInstance): void
    {
        self::$mappingInstanceSample = $anyInstance;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string $fieldName
     * @param Concrete|null $anyInstance any instance of the target class. this is not necessarily the actual class containing data!
     * @param string|null $type
     * @param array<string, mixed> $options
     * @return void
     * @throws \Exception
     */
    protected function map(
        FormBuilderInterface $builder,
        string $fieldName,
        Concrete $anyInstance = null,
        string $type = null,
        array $options = []
    ) {
        if ($anyInstance === null && isset(self::$mappingInstanceSample)) {
            $anyInstance = self::$mappingInstanceSample;
        }
        if ($anyInstance === null) {
            throw new \Exception('Missing $anyInstance. Pass to variable or set via self::setMappingInstanceExample()');
        }
        $definition = $anyInstance->getClass()->getFieldDefinition($fieldName);
        if (!$definition) {
            throw new \Exception('Missing definition for field ' . $fieldName . ' on class ' . get_class($anyInstance));
        }
        /** @var array<string, mixed> $formData */
        $formData = $builder->getOptions()['data'] ?? [];
        /** @var array<string, mixed> $data */
        $data = $builder->getData();

        $defaults = [
            'label' => !empty($definition->getTitle()) ? $definition->getTitle() : $fieldName,
            'help' => $definition->getTooltip() ?? '',
            'required' => $definition->getMandatory(),
            'disabled' => $definition->getNoteditable()
        ];
        // todo: add to documentation
        if (isset($options['disabled_when_published']) && $options['disabled_when_published']) {
            if (isset($formData['__published']) && $formData['__published']) {
                $defaults['disabled'] = true;
            }
            unset($options['disabled_when_published']);
        }

        if ($defaults['label'] !== $fieldName && !array_key_exists('label', $options) && !array_key_exists('help', $options)) {
            // Disable translations handling, if labels are not overwritten via options,
            // as labels from object class definition are expected to be readable
            // todo: document behaviour in readme and how to disable this by overwriting via options
            $defaults['translation_domain'] = false;
        }

        if ($type === null) {
            switch ($definition->getFieldType()) {
                case 'advancedManyToManyObjectRelation':
                    /** @var AdvancedManyToManyObjectRelation $definition */
                    $type = ObjectRelationType::class;
                    $defaults['data'] = $data[$fieldName] ?? []; // todo: use $formValue?
                    $defaults['columns'] = $definition->getColumns();
                    $defaults['visible_fields'] = $definition->getVisibleFields();
                    if (isset($options['hide_columns'])) {
                        if (is_array($options['hide_columns'])) {
                            $defaults['columns'] = array_filter(
                                $defaults['columns'],
                                fn (array $column) => !in_array($column['key'], $options['hide_columns'])
                            );
                        }
                        unset($options['hide_columns']);
                    }
                    break;
                case 'select':
                case 'gender':
                case 'country':
                    $type = ChoiceType::class;
                    $defaults['choices'] = $this->mapChoices($fieldName, $anyInstance);
                    break;
                case 'checkbox':
                    /** @var Checkbox $definition */
                    $type = CheckboxType::class;
                    $defaults['data'] = (bool)$definition->getDefaultValue();
                    break;
                case 'input':
                case 'firstname':
                case 'lastname':
                    $type = TextType::class;
                    break;
                case 'dateRange':
                    $type = DateRangeType::class;
                    $defaults['start'] = new Carbon();
                    $defaults['end'] = new Carbon();
                    $formValue = $data[$fieldName] ?? null;
                    if ($formValue instanceof CarbonPeriod) {
                        $defaults['start'] = $formValue->getStartDate();
                        $defaults['end'] = $formValue->getEndDate();
                    }
                    break;
                case 'image':
                    $type = AssetType::class;
                    $defaults['data'] = $data[$fieldName] ?? null;
                    $defaults['asset_type'] = 'image';
                    if (isset($options['constraints']) && is_array($options['constraints'])) {
                        // native constraints currently are not supported for UploadedFiles
                        // but there is a manual check in place, see FileDataTransformer::reverseTransform
                        // as well as native file type check based on field type
                        $options['file_constraints'] = array_filter($options['constraints'], fn ($c) => $c instanceof Constraints\File);
                        $options['constraints'] = array_filter($options['constraints'], fn ($c) => !$c instanceof Constraints\File);
                    }
                    break;
                case 'link':
                    $type = LinkType::class;
                    $defaults['data'] = $data[$fieldName] ?? null;
                    break;
                case 'textarea':
                    $type = TextareaType::class;
                    break;
                default:
                    throw new \Exception('Type "' . $definition->getFieldType() . '" is not supported yet.');
            }
            if ($definition->getInvisible()) {
                $type = HiddenType::class;
            }
        }
        $finalOptions = array_merge($defaults, $options);

        // Allow modifications to type and options via event
        $event = new GenericEvent();
        $event->setArgument('type', $type);
        $event->setArgument('options', $finalOptions);
        $event->setArgument('fieldName', $fieldName);
        $event->setArgument('builder', $builder);
        \Pimcore::getEventDispatcher()->dispatch($event, self::MAP_DATA_EVENT);

        /** @var string $type */
        $type = $event->getArgument('type');
        /** @var array<string, mixed> $options */
        $options = $event->getArgument('options');
        $builder->add($fieldName, $type, $options);
    }

    /**
     * @return array<string, mixed>|null
     * @throws \Exception
     */
    protected function mapChoices(string $fieldDefinitionKey, Concrete $anyInstance): array|null
    {
        $definition = $anyInstance->getClass()->getFieldDefinition($fieldDefinitionKey);
        if ($definition instanceof Country || $definition instanceof Countrymultiselect) {
            return $this->mapChoicesForCountryField($fieldDefinitionKey, $anyInstance);
        }
        if ($definition instanceof Select) {
            return $this->mapChoicesForSelectField($fieldDefinitionKey, $anyInstance);
        }
        return null;
    }

    /**
     * @return array<string, mixed>|null
     * @throws \Exception
     */
    protected function mapChoicesForCountryField(string $fieldDefinitionKey, Concrete $anyInstance): array|null
    {
        $definition = $anyInstance->getClass()->getFieldDefinition($fieldDefinitionKey);

        if (!$definition instanceof Country && !$definition instanceof Countrymultiselect) {
            return null;
        }

        $restrictions = array_filter(
            explode(',', $definition->getRestrictTo() ?? ''),
            fn ($a) => !empty($a)
        );
        if (!empty($restrictions)) {
            return array_filter(
                array_map(fn ($val) => Countries::getAlpha2Code($val), array_flip(Countries::getAlpha3Names())),
                function ($code) use ($restrictions) {
                    return in_array($code, $restrictions);
                }
            );
        }
        return [];
    }

    /**
     * @return array<string, string>|null
     * @throws \Exception
     */
    protected function mapChoicesForSelectField(string $fieldDefinitionKey, Concrete $anyInstance): array|null
    {
        $definition = $anyInstance->getClass()->getFieldDefinition($fieldDefinitionKey);

        if (!$definition instanceof Select) {
            return null;
        }

        $options = $definition->getOptions();
        if (!is_array($options)) {
            return [];
        }

        $fields = [];
        foreach ($options as $option) {
            $fields[$option['key']] = $option['value'];
        }
        return $fields;
    }
}
