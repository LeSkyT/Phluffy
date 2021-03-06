<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;
use const PATHINFO_BASENAME;
use function fclose;
use function fopen;
use function is_object;
use function pathinfo;

class DefaultGridFSRepository extends DocumentRepository implements GridFSRepository
{
    /**
     * @see Bucket::openDownloadStream()
     */
    public function openDownloadStream($id)
    {
        try {
            return $this->getDocumentBucket()->openDownloadStream($this->class->getDatabaseIdentifierValue($id));
        } catch (FileNotFoundException $e) {
            throw DocumentNotFoundException::documentNotFound($this->getClassName(), $id);
        }
    }

    /**
     * @see Bucket::downloadToStream
     */
    public function downloadToStream($id, $destination): void
    {
        try {
            $this->getDocumentBucket()->downloadToStream($this->class->getDatabaseIdentifierValue($id), $destination);
        } catch (FileNotFoundException $e) {
            throw DocumentNotFoundException::documentNotFound($this->getClassName(), $id);
        }
    }

    /**
     * @see Bucket::openUploadStream
     */
    public function openUploadStream(string $filename, ?UploadOptions $uploadOptions = null)
    {
        $options = $this->prepareOptions($uploadOptions);

        return $this->getDocumentBucket()->openUploadStream($filename, $options);
    }

    /**
     * @see Bucket::uploadFromStream
     */
    public function uploadFromStream(string $filename, $source, ?UploadOptions $uploadOptions = null)
    {
        $options = $this->prepareOptions($uploadOptions);

        $databaseIdentifier = $this->getDocumentBucket()->uploadFromStream($filename, $source, $options);
        $documentIdentifier = $this->class->getPHPIdentifierValue($databaseIdentifier);

        return $this->dm->getReference($this->getClassName(), $documentIdentifier);
    }

    public function uploadFromFile(string $source, ?string $filename = null, ?UploadOptions $uploadOptions = null)
    {
        $resource = fopen($source, 'r');
        if ($resource === false) {
            throw MongoDBException::cannotReadGridFSSourceFile($source);
        }

        if ($filename === null) {
            $filename = pathinfo($source, PATHINFO_BASENAME);
        }

        try {
            return $this->uploadFromStream($filename, $resource, $uploadOptions);
        } finally {
            fclose($resource);
        }
    }

    private function getDocumentBucket(): Bucket
    {
        return $this->dm->getDocumentBucket($this->documentName);
    }

    private function prepareOptions(?UploadOptions $uploadOptions = null): array
    {
        if ($uploadOptions === null) {
            $uploadOptions = new UploadOptions();
        }

        $options = [
            'chunkSizeBytes' => $uploadOptions->chunkSizeBytes ?: $this->class->getChunkSizeBytes(),
        ];

        if (is_object($uploadOptions->metadata)) {
            $options += ['metadata' => (object) $this->uow->getPersistenceBuilder()->prepareInsertData($uploadOptions->metadata)];
        }

        return $options;
    }
}
