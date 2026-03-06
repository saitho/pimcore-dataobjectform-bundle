<?php
namespace Saitho\DataObjectFormBundle\Form\DataTransformer;

use League\Flysystem\FilesystemOperator;
use Pimcore\Model\Asset;
use Pimcore\Tool\Storage;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;

/**
 * @implements DataTransformerInterface<Asset, File>
 */
class FileDataTransformer implements DataTransformerInterface
{
    protected Asset $asset;
    protected FilesystemOperator $storage;

    /**
     * @param string $assetStoragePath
     * @param string $type
     * @param string $fileName
     * @param Constraints\File[] $fileConstraints
     */
    public function __construct(
        protected string $assetStoragePath,
        protected string $type = 'image',
        protected string $fileName = '',
        protected array $fileConstraints = []
    ) {
        $this->storage = Storage::get('asset');
    }

    protected function getNewInstance(): Asset
    {
        return match ($this->type) {
            'image' => new Asset\Image(),
            'document' => new Asset\Document(),
            'audio' => new Asset\Audio(),
            'video' => new Asset\Video(),
            default => new Asset()
        };
    }

    public function transform(mixed $value)
    {
        if ($value instanceof Asset) {
            $this->asset = $value;
            $file = new File($this->asset->getFullPath(), false);
            if (!$this->storage->fileExists($this->asset->getFullPath())) {
                throw new FileNotFoundException($this->asset->getFullPath());
            }
            return $file;
        }
        return null;
    }

    public function reverseTransform(mixed $value)
    {
        /** @var UploadedFile|null $value */
        if ($value === null) {
            // no file uploaded
            return $this->asset;
        }

        $mimetype = MimeTypes::getDefault()->guessMimeType($value->getPathname()) ?? '';
        $assetType = Asset::getTypeFromMimeMapping($mimetype, $value->getClientOriginalName());
        if ($this->type !== $assetType) {
            throw new TransformationFailedException("Mime type $mimetype does not match with asset type: $this->type");
        }

        if ($this->fileConstraints) {
            $validator = Validation::createValidator();
            $res = $validator->validate($value, $this->fileConstraints);
            /** @var ConstraintViolation $violation */
            foreach ($res as $violation) {
                throw new TransformationFailedException($violation->getMessage());
            }
        }

        $newAsset = $this->getNewInstance();
        if (!empty($this->fileName)) {
            $newAsset->setFilename($this->fileName . '.' . $value->getClientOriginalExtension());
        } else {
            $fileName = $value->getClientOriginalName();
            $fileName = preg_replace('/(.*)\.(.*)$/', '$1.' . rand(1, 99999) . '.$2', $fileName);
            if (empty($fileName)) {
                throw new TransformationFailedException('Empty file name');
            }
            $newAsset->setFilename($fileName);
        }
        $newAsset->setData($value->getContent());
        if (!empty($this->assetStoragePath)) {
            $newAsset->setParent(Asset::getByPath($this->assetStoragePath));
        }
        $newAsset->save();
        return $newAsset;
    }
}
