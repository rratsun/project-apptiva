<?php
declare(strict_types=1);

namespace ProjectApptiva\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Templates\Controllers\Base;
use Treo\Core\Slim\Http\Request;

/**
 * Class ApptivaProduct
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class ApptivaProduct extends Base
{
    /**
     * @param array     $params
     * @param \stdClass $data
     * @param Request   $request
     *
     * @return mixed
     * @throws BadRequest
     * @throws Forbidden
     */
    public function actionGetAttributesForMassUpdate($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest('Only POST method available');
        }

        if (empty($data->productsIds)) {
            throw new BadRequest($this->translate('Please, select at least one product', 'exceptions'));
        }

        if (!$this->getAcl()->check('Product', 'edit') && !$this->getAcl()->check('Attribute', 'edit')) {
            throw new Forbidden();
        }

        return $this->getService('ApptivaProduct')->getAttributesForMassUpdate($data->productsIds);
    }

    /**
     * @param string $key
     * @param string $label
     * @param string $scope
     *
     * @return string
     */
    protected function translate(string $key, string $label = 'labels', string $scope = 'Product'): string
    {
        return $this->getContainer()->get('language')->translate($key, $label, $scope);
    }
}
