<?php
declare(strict_types=1);

namespace ProjectApptiva\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class Metadata
 *
 * @author Roman Ratsun <r.ratsun@gmail.com>
 */
class Metadata extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function modify(Event $event)
    {
        // prepare metadata
        $metadata = $event->getArgument('data');

        // replace view
        if (isset($metadata['clientDefs']['Product']['bottomPanels'])) {
            foreach ($metadata['clientDefs']['Product']['bottomPanels']['detail'] as $k => $row) {
                if ($row['name'] == 'variantConfiguration') {
                    $metadata['clientDefs']['Product']['bottomPanels']['detail'][$k]['view'] = 'project-apptiva:views/product/record/panels/variants-configuration';
                }
            }
        }

        // skip validation
        $metadata['productVariant']['validation']['skipAttributesValidation'] = true;

        $event->setArgument('data', $metadata);
    }
}
