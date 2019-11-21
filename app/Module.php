<?php
/**
 * ProjectApptiva
 * Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <http://treopim.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace ProjectApptiva;

use Espo\Core\Utils\Json;
use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 *
 * @author r.ratsun@treolabs.com
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 9999;
    }

    /**
     * @inheritDoc
     */
    public function loadMetadata(\stdClass &$data)
    {
        parent::loadMetadata($data);

        // parse metadata
        $metadata = Json::decode(Json::encode($data), true);

        // replace view
        if (isset($metadata['clientDefs']['Product']['bottomPanels'])) {
            foreach ($metadata['clientDefs']['Product']['bottomPanels']['detail'] as $k => $row) {
                if ($row['name'] == 'variantConfiguration') {
                    $metadata['clientDefs']['Product']['bottomPanels']['detail'][$k]['view'] = 'project-apptiva:views/product/record/panels/variants-configuration';
                }
            }
        }

        // skip validation
        $metadata['productVariant']['validation']['skipIsAttributesUnique'] = true;

        // parse metadata
        $data = Json::decode(Json::encode($metadata));
    }
}
