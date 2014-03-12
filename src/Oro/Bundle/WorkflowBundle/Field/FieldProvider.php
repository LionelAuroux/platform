<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class FieldProvider extends EntityFieldProvider
{
    /**
     * @var array
     */
    protected $workflowFields;

    /**
     * @param string $field
     * @return bool
     */
    protected function isWorkflowField($field)
    {
        if (!$this->workflowFields) {
            $this->workflowFields = array(
                FieldGenerator::PROPERTY_WORKFLOW_ITEM,
                FieldGenerator::PROPERTY_WORKFLOW_STEP,
                ExtendConfigDumper::FIELD_PREFIX . FieldGenerator::PROPERTY_WORKFLOW_ITEM,
                ExtendConfigDumper::FIELD_PREFIX.  FieldGenerator::PROPERTY_WORKFLOW_STEP,
            );
        }

        return in_array($field, $this->workflowFields);
    }

    /**
     * {@inheritDoc}
     */
    protected function addFields(array &$result, $className, EntityManager $em, $translate)
    {
        // only configurable entities are supported
        if ($this->entityConfigProvider->hasConfig($className)) {
            $metadata = $em->getClassMetadata($className);

            // add regular fields
            foreach ($metadata->getFieldNames() as $fieldName) {
                $fieldLabel = $this->getFieldLabel(
                    $className,
                    $this->getFieldNameToTranslate($className, $fieldName)
                );
                $this->addField(
                    $result,
                    $fieldName,
                    $metadata->getTypeOfField($fieldName),
                    $fieldLabel,
                    $metadata->isIdentifier($fieldName),
                    $translate
                );
            }

            // add single association field
            foreach ($metadata->getAssociationNames() as $associationName) {
                if (!$this->isWorkflowField($associationName)
                    && $metadata->isSingleValuedAssociation($associationName)
                ) {
                    $fieldLabel = $this->getFieldLabel(
                        $className,
                        $this->getFieldNameToTranslate($className, $associationName)
                    );
                    $this->addField(
                        $result,
                        $associationName,
                        null,
                        $fieldLabel,
                        false,
                        $translate
                    );
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function addRelations(
        array &$result,
        $className,
        EntityManager $em,
        $withEntityDetails,
        $relationDeepLevel,
        $lastDeepLevelRelations,
        $translate
    ) {
        // only configurable entities are supported
        if ($this->entityConfigProvider->hasConfig($className)) {
            $metadata = $em->getClassMetadata($className);
            foreach ($metadata->getAssociationNames() as $associationName) {
                $targetClassName = $metadata->getAssociationTargetClass($associationName);
                // skip workflow and collection relations
                if (!$this->isWorkflowField($associationName)
                    && $metadata->isSingleValuedAssociation($associationName)
                    && $this->entityConfigProvider->hasConfig($targetClassName)
                ) {
                    // skip 'default_' extend field
                    if (strpos($associationName, ExtendConfigDumper::DEFAULT_PREFIX) === 0) {
                        $guessedFieldName = substr($associationName, strlen(ExtendConfigDumper::DEFAULT_PREFIX));
                        if ($this->isExtendField($className, $guessedFieldName)) {
                            continue;
                        }
                    }

                    $targetFieldName = $metadata->getAssociationMappedByTargetField($associationName);
                    $targetMetadata  = $em->getClassMetadata($targetClassName);
                    $fieldLabel      = $this->getFieldLabel(
                        $className,
                        $this->getFieldNameToTranslate($className, $associationName)
                    );
                    $relationData    = array(
                        'name'                => $associationName,
                        'type'                => $targetMetadata->getTypeOfField($targetFieldName),
                        'label'               => $fieldLabel,
                        'relation_type'       => $this->getRelationType($className, $associationName),
                        'related_entity_name' => $targetClassName
                    );
                    $this->addRelation(
                        $result,
                        $relationData,
                        $withEntityDetails,
                        $relationDeepLevel,
                        $lastDeepLevelRelations,
                        $translate
                    );
                }
            }
        }
    }
}
