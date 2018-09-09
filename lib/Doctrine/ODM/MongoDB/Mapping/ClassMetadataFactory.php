<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Id\AbstractIdGenerator;
use Doctrine\ODM\MongoDB\Id\AlnumGenerator;
use Doctrine\ODM\MongoDB\Id\AutoGenerator;
use Doctrine\ODM\MongoDB\Id\IncrementGenerator;
use Doctrine\ODM\MongoDB\Id\UuidGenerator;
use function get_class;
use function get_class_methods;
use function in_array;
use function ucfirst;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a document database.
 *
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var string */
    protected $cacheSalt = '$MONGODBODMCLASSMETADATA';

    /** @var DocumentManager The DocumentManager instance */
    private $dm;

    /** @var Configuration The Configuration instance */
    private $config;

    /** @var MappingDriver The used metadata driver. */
    private $driver;

    /** @var EventManager The event manager instance */
    private $evm;

    public function setDocumentManager(DocumentManager $dm): void
    {
        $this->dm = $dm;
    }

    public function setConfiguration(Configuration $config): void
    {
        $this->config = $config;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    protected function initialize(): void
    {
        $this->driver = $this->config->getMetadataDriverImpl();
        $this->evm = $this->dm->getEventManager();
        $this->initialized = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName): string
    {
        return $this->config->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService): void
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService): void
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(ClassMetadataInterface $class): bool
    {
        return ! $class->isMappedSuperclass && ! $class->isEmbeddedDocument && ! $class->isQueryResultDocument;
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents = []): void
    {
        /** @var $class ClassMetadata */
        /** @var $parent ClassMetadata */
        if ($parent) {
            $class->setInheritanceType($parent->inheritanceType);
            $class->setDiscriminatorField($parent->discriminatorField);
            $class->setDiscriminatorMap($parent->discriminatorMap);
            $class->setDefaultDiscriminatorValue($parent->defaultDiscriminatorValue);
            $class->setIdGeneratorType($parent->generatorType);
            $this->addInheritedFields($class, $parent);
            $this->addInheritedRelations($class, $parent);
            $this->addInheritedIndexes($class, $parent);
            $this->setInheritedShardKey($class, $parent);
            $class->setIdentifier($parent->identifier);
            $class->setVersioned($parent->isVersioned);
            $class->setVersionField($parent->versionField);
            $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
            $class->setAlsoLoadMethods($parent->alsoLoadMethods);
            $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);
            $class->setReadPreference($parent->readPreference, $parent->readPreferenceTags);
            $class->setWriteConcern($parent->writeConcern);
            if ($parent->isMappedSuperclass) {
                $class->setCustomRepositoryClass($parent->customRepositoryClassName);
            }
        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (\ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }

        $this->validateIdentifier($class);

        if ($parent && $rootEntityFound && $parent->generatorType === $class->generatorType) {
            if ($parent->generatorType) {
                $class->setIdGeneratorType($parent->generatorType);
            }
            if ($parent->generatorOptions) {
                $class->setIdGeneratorOptions($parent->generatorOptions);
            }
            if ($parent->idGenerator) {
                $class->setIdGenerator($parent->idGenerator);
            }
        } else {
            $this->completeIdGeneratorMapping($class);
        }

        if ($parent && $parent->isInheritanceTypeSingleCollection()) {
            $class->setDatabase($parent->getDatabase());
            $class->setCollection($parent->getCollection());
        }

        $class->setParentClasses($nonSuperclassParents);

        if (! $this->evm->hasListeners(Events::loadClassMetadata)) {
            return;
        }

        $eventArgs = new LoadClassMetadataEventArgs($class, $this->dm);
        $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
    }

    /**
     * Validates the identifier mapping.
     *
     * @throws MappingException
     */
    protected function validateIdentifier(ClassMetadata $class): void
    {
        if (! $class->identifier && ! $class->isMappedSuperclass && ! $class->isEmbeddedDocument && ! $class->isQueryResultDocument) {
            throw MappingException::identifierRequired($class->name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function newClassMetadataInstance($className): ClassMetadata
    {
        return new ClassMetadata($className);
    }

    private function completeIdGeneratorMapping(ClassMetadata $class): void
    {
        $idGenOptions = $class->generatorOptions;
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_AUTO:
                $class->setIdGenerator(new AutoGenerator());
                break;
            case ClassMetadata::GENERATOR_TYPE_INCREMENT:
                $incrementGenerator = new IncrementGenerator();
                if (isset($idGenOptions['key'])) {
                    $incrementGenerator->setKey((string) $idGenOptions['key']);
                }
                if (isset($idGenOptions['collection'])) {
                    $incrementGenerator->setCollection((string) $idGenOptions['collection']);
                }
                if (isset($idGenOptions['startingId'])) {
                    $incrementGenerator->setStartingId((int) $idGenOptions['startingId']);
                }
                $class->setIdGenerator($incrementGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_UUID:
                $uuidGenerator = new UuidGenerator();
                isset($idGenOptions['salt']) && $uuidGenerator->setSalt((string) $idGenOptions['salt']);
                $class->setIdGenerator($uuidGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_ALNUM:
                $alnumGenerator = new AlnumGenerator();
                if (isset($idGenOptions['pad'])) {
                    $alnumGenerator->setPad((int) $idGenOptions['pad']);
                }
                if (isset($idGenOptions['chars'])) {
                    $alnumGenerator->setChars((string) $idGenOptions['chars']);
                } elseif (isset($idGenOptions['awkwardSafe'])) {
                    $alnumGenerator->setAwkwardSafeMode((bool) $idGenOptions['awkwardSafe']);
                }

                $class->setIdGenerator($alnumGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_CUSTOM:
                if (empty($idGenOptions['class'])) {
                    throw MappingException::missingIdGeneratorClass($class->name);
                }

                $customGenerator = new $idGenOptions['class']();
                unset($idGenOptions['class']);
                if (! $customGenerator instanceof AbstractIdGenerator) {
                    throw MappingException::classIsNotAValidGenerator(get_class($customGenerator));
                }

                $methods = get_class_methods($customGenerator);
                foreach ($idGenOptions as $name => $value) {
                    $method = 'set' . ucfirst($name);
                    if (! in_array($method, $methods)) {
                        throw MappingException::missingGeneratorSetter(get_class($customGenerator), $name);
                    }

                    $customGenerator->$method($value);
                }
                $class->setIdGenerator($customGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_NONE:
                break;
            default:
                throw new MappingException('Unknown generator type: ' . $class->generatorType);
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            if (! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if (! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedFieldMapping($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }


    /**
     * Adds inherited association mappings to the subclass mapping.
     *
     * @throws MappingException
     */
    private function addInheritedRelations(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->associationMappings as $field => $mapping) {
            if ($parentClass->isMappedSuperclass) {
                $mapping['sourceDocument'] = $subClass->name;
            }

            if (! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if (! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedAssociationMapping($mapping);
        }
    }

    /**
     * Adds inherited indexes to the subclass mapping.
     */
    private function addInheritedIndexes(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        foreach ($parentClass->indexes as $index) {
            $subClass->addIndex($index['keys'], $index['options']);
        }
    }

    /**
     * Adds inherited shard key to the subclass mapping.
     */
    private function setInheritedShardKey(ClassMetadata $subClass, ClassMetadata $parentClass): void
    {
        if (! $parentClass->isSharded()) {
            return;
        }

        $subClass->setShardKey(
            $parentClass->shardKey['keys'],
            $parentClass->shardKey['options']
        );
    }
}
