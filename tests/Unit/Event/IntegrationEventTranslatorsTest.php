<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Event;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlacedTranslator;
use Test\TinyBlocks\BuildingBlocks\Models\PaymentConfirmed;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\IntegrationEvent;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventTranslator;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventTranslators;

final class IntegrationEventTranslatorsTest extends TestCase
{
    public function testFindForReturnsFirstMatchingTranslatorAmongMultiple(): void
    {
        /** @Given an event record for an OrderPlaced event */
        $record = EventRecord::from(
            event: new OrderPlaced(item: 'book'),
            aggregateId: new OrderId(value: 'ord-1'),
            aggregateType: 'Order',
            aggregateVersion: AggregateVersion::first()
        );

        /** @And a translator that never supports any record */
        $neverMatching = new class implements IntegrationEventTranslator {
            public function supports(EventRecord $record): bool
            {
                return false;
            }

            public function translate(EventRecord $record): IntegrationEvent
            {
                return new PaymentConfirmed(orderId: '');
            }
        };

        /** @And a translator that supports the OrderPlaced record */
        $firstMatch = new OrderPlacedTranslator();

        /** @And a second translator that also supports any record */
        $secondMatch = new class implements IntegrationEventTranslator {
            public function supports(EventRecord $record): bool
            {
                return true;
            }

            public function translate(EventRecord $record): IntegrationEvent
            {
                return new PaymentConfirmed(orderId: '');
            }
        };

        /** @And a collection ordered: never-matching, first-match, second-match */
        $translators = IntegrationEventTranslators::createFrom(elements: [$neverMatching, $firstMatch, $secondMatch]);

        /** @When looking up the translator for the record */
        $result = $translators->findFor(record: $record);

        /** @Then the first matching translator is returned, not the second */
        self::assertSame($firstMatch, $result);
    }

    public function testFindForReturnsNullWhenNoTranslatorSupportsTheRecord(): void
    {
        /** @Given an event record */
        $record = EventRecord::from(
            event: new OrderPlaced(item: 'book'),
            aggregateId: new OrderId(value: 'ord-1'),
            aggregateType: 'Order',
            aggregateVersion: AggregateVersion::first()
        );

        /** @And a collection containing a translator that never supports any record */
        $translators = IntegrationEventTranslators::createFrom(
            elements: [
                new class implements IntegrationEventTranslator {
                    public function supports(EventRecord $record): bool
                    {
                        return false;
                    }

                    public function translate(EventRecord $record): IntegrationEvent
                    {
                        return new PaymentConfirmed(orderId: '');
                    }
                }
            ]
        );

        /** @When looking up the translator for the record */
        $result = $translators->findFor(record: $record);

        /** @Then null is returned because no translator handles the record */
        self::assertNull($result);
    }

    public function testFindForReturnsNullForEmptyCollection(): void
    {
        /** @Given an event record */
        $record = EventRecord::from(
            event: new OrderPlaced(item: 'book'),
            aggregateId: new OrderId(value: 'ord-1'),
            aggregateType: 'Order',
            aggregateVersion: AggregateVersion::first()
        );

        /** @And an empty translator collection */
        $translators = IntegrationEventTranslators::createFromEmpty();

        /** @When looking up the translator for the record */
        $result = $translators->findFor(record: $record);

        /** @Then null is returned because the collection has no translators */
        self::assertNull($result);
    }
}
