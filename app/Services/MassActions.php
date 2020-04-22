<?php
declare(strict_types=1);

namespace ProjectApptiva\Services;

use Treo\Services\MassActions as Base;

/**
 * Class MassActions
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class MassActions extends Base
{
    /**
     * @inheritDoc
     */
    protected function getSelectParams(string $entityType, array $where): array
    {
        $selectParams = parent::getSelectParams($entityType, $where);

        // prepare select params
        if ($entityType == 'ProductAttributeValue') {
            $selectParams['additionalSelectColumns'] = [];
            $selectParams['customJoin'] = [];
        }

        return $selectParams;
    }
}
