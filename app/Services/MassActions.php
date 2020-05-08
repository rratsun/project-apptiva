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
    public function massUpdate(string $entityType, \stdClass $data): array
    {
        if ($entityType == 'ProductAttributeValue') {
            // attach attributes if it needs
            $this->attachAttribute($data);
        }

        return parent::massUpdate($entityType, $data);
    }

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

    /**
     * @param \stdClass $data
     *
     * @return bool
     */
    protected function attachAttribute(\stdClass $data): bool
    {
        // prepare params
        $params = json_decode(json_encode($data), true);

        if (empty($params['where'])) {
            return false;
        }

        foreach ($params['where'] as $row) {
            if ($row['attribute'] == 'attributeId' && $row['type'] == 'equals') {
                $attributeId = $row['value'];
            }

            if ($row['attribute'] == 'productId' && $row['type'] == 'in') {
                $productsIds = $row['value'];
            }
        }

        if (empty($attributeId) || empty($productsIds)) {
            return false;
        }

        $repository = $this->getRepository('ProductAttributeValue');

        $pavs = $repository
            ->select(['productId'])
            ->where(
                [
                    'attributeId' => $attributeId,
                    'productId'   => $productsIds,
                    'scope'       => 'Global'
                ]
            )
            ->find()
            ->toArray();

        $exists = array_column($pavs, 'productId');

        foreach ($productsIds as $productId) {
            if (!in_array($productId, $exists)) {
                $pav = $repository->get();
                $pav->set(
                    [
                        'attributeId' => $attributeId,
                        'productId'   => $productId,
                        'scope'       => 'Global'
                    ]
                );

                $this->getEntityManager()->saveEntity($pav);
            }
        }

        return true;
    }
}
