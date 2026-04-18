<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

/**
 * Transforms an older revision of a serialized event into a newer revision during load.
 *
 * <p>A single upcaster advances exactly one <code>(type, revision)</code> pair forward by one step.
 * Chains of upcasters handle multistep schema evolution: each upcaster in the chain either transforms
 * the event when the type and revision match, or returns it unchanged. This preserves compatibility with
 * historical events already stored at older revisions without requiring retroactive event rewrites.</p>
 *
 * <p>The shipped {@see SingleUpcasterBehavior} trait binds an upcaster to a specific
 * <code>(EXPECTED_EVENT_TYPE, FROM_REVISION, TO_REVISION)</code> triple through class constants and
 * delegates the payload transformation to an abstract <code>doUpcast()</code> hook.</p>
 *
 * @see Greg Young, <em>Versioning in an Event Sourced System</em> (Leanpub, 2017),
 *      "Basic Type Based Conversion" and "Upcasting".
 */
interface Upcaster
{
    /**
     * Transforms the given intermediate event if it matches the expected type and revision.
     *
     * @param IntermediateEvent $event The event to transform.
     * @return IntermediateEvent The transformed event, or the input unchanged when this upcaster does
     *                           not apply.
     */
    public function upcast(IntermediateEvent $event): IntermediateEvent;
}
