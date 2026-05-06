# Building Blocks

[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/tiny-blocks/building-blocks/blob/main/LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    + [Entity](#entity)
    + [Aggregate](#aggregate)
    + [Domain events with transactional outbox](#domain-events-with-transactional-outbox)
    + [Event sourcing](#event-sourcing)
    + [Consuming events](#consuming-events)
    + [Snapshots](#snapshots)
        - [Built-in conditions](#built-in-conditions)
    + [Upcasting](#upcasting)
        - [Defining an upcaster](#defining-an-upcaster)
        - [Upcasting an event](#upcasting-an-event)
        - [Chaining upcasters](#chaining-upcasters)
        - [Reconstituting from an iterable](#reconstituting-from-an-iterable)
        - [Default values for new fields](#default-values-for-new-fields)
* [FAQ](#faq)
* [License](#license)
* [Contributing](#contributing)

## Overview

Implements tactical DDD building blocks for PHP, covering entities, single and compound identities, aggregate roots,
domain events, event records, snapshots, and upcasters. Supports both the transactional outbox pattern and event
sourcing through sibling aggregate variants. Persistence-agnostic and PSR-14 friendly, keeping infrastructure concerns
out of the domain layer.

## Installation

```
composer require tiny-blocks/building-blocks
```

## How to use

The library exposes three styles of aggregate modeling through sibling interfaces:

* `AggregateRoot` for plain DDD modeling without events.
* `EventualAggregateRoot` for aggregates that persist state and emit events as side effects via a transactional
  outbox.
* `EventSourcingRoot` for aggregates whose state is derived entirely from their ordered event stream.

### Entity

Every entity exposes identity through `EntityBehavior`. The protected `identityName()` method returns the name of
the property that holds the `Identity` and defaults to `'id'`. Override it only when the property has a different
name.

#### Single-field identity

* `SingleIdentity`: identity backed by a single scalar value (UUID, auto-increment integer, etc.).

  ```php
  use TinyBlocks\BuildingBlocks\Entity\SingleIdentity;
  use TinyBlocks\BuildingBlocks\Entity\SingleIdentityBehavior;

  final readonly class OrderId implements SingleIdentity
  {
      use SingleIdentityBehavior;

      public function __construct(public string $value)
      {
      }
  }

  $orderId = new OrderId(value: 'ord-1');
  $orderId->getIdentityValue();
  ```

#### Compound identity

* `CompoundIdentity`: identity composed of multiple fields treated as a tuple.

  ```php
  use TinyBlocks\BuildingBlocks\Entity\CompoundIdentity;
  use TinyBlocks\BuildingBlocks\Entity\CompoundIdentityBehavior;

  final readonly class AppointmentId implements CompoundIdentity
  {
      use CompoundIdentityBehavior;

      public function __construct(
          public string $tenantId,
          public string $appointmentId
      ) {
      }
  }

  $appointmentId = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');
  $appointmentId->getIdentityValue();
  ```

#### Identity access

* `getIdentity`, `getIdentityValue`, `sameIdentityOf`, `identityEquals`: provided by `EntityBehavior` for any entity
  that implements `identityName()`.

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRootBehavior;

  final class User implements AggregateRoot
  {
      use AggregateRootBehavior;

      private function __construct(private UserId $userId, private string $email)
      {
      }

      protected function identityName(): string
      {
          return 'userId';
      }
  }

  $user->sameIdentityOf(other: $otherUser);
  $user->identityEquals(other: new UserId(value: 'usr-1'));
  ```

### Aggregate

`AggregateRoot` adds two pragmatic fields to Evans' aggregate: a monotonic `SequenceNumber` for optimistic concurrency
control and a `ModelVersion` for schema evolution of the aggregate type itself.

* `getSequenceNumber`: the current sequence number, starting at zero for a blank aggregate.

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRootBehavior;

  final class User implements AggregateRoot
  {
      use AggregateRootBehavior;

      protected function identityName(): string
      {
          return 'userId';
      }
  }

  $user->getSequenceNumber();
  ```

* `getModelVersion`: resolved from the protected `modelVersion()` method, defaults to zero when not overridden.

  ```php
  final class Cart implements AggregateRoot
  {
      use AggregateRootBehavior;

      protected function identityName(): string
      {
          return 'cartId';
      }

      protected function modelVersion(): int
      {
          return 1;
      }
  }

  $cart->getModelVersion();
  ```

* `buildAggregateName`: short class name, used as the aggregate type identifier on each `EventRecord`.

  ```php
  $user->buildAggregateName();
  ```

### Domain events with transactional outbox

`EventualAggregateRoot` records domain events during the unit of work. State is the source of truth; events are
emitted as side effects and must be delivered at-least-once.

#### Declaring events

* `DomainEvent`: interface declaring `revision()`. A domain event is a plain PHP object. Use
  `DomainEventBehavior` to get the default revision of 1; override `revision()` only when bumping schema.

  ```php
  use TinyBlocks\BuildingBlocks\Event\DomainEvent;
  use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;

  final readonly class OrderPlaced implements DomainEvent
  {
      use DomainEventBehavior;

      public function __construct(public string $item)
      {
      }
  }
  ```

  When a schema change requires a new revision, override `revision()`:

  ```php
  use TinyBlocks\BuildingBlocks\Event\DomainEvent;
  use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;
  use TinyBlocks\BuildingBlocks\Event\Revision;

  final readonly class OrderPlacedV2 implements DomainEvent
  {
      use DomainEventBehavior;

      public function __construct(public string $item, public string $currency)
      {
      }

      public function revision(): Revision
      {
          return Revision::of(value: 2);
      }
  }
  ```

#### Emitting events from the aggregate

* `push`: protected method on `EventualAggregateRootBehavior`. Increments the sequence number and appends a
  fully-built `EventRecord` to the recorded buffer. The `Revision` is read from the event via `revision()`.

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;

  final class Order implements EventualAggregateRoot
  {
      use EventualAggregateRootBehavior;

      private function __construct(private OrderId $id)
      {
      }

      public static function place(OrderId $orderId, string $item): Order
      {
          $order = new Order(id: $orderId);
          $order->push(event: new OrderPlaced(item: $item));

          return $order;
      }
  }
  ```

#### Draining events in the repository

* `recordedEvents`: returns a fresh copy of the buffer, safe to iterate without mutating the aggregate.
* `clearRecordedEvents`: discards the buffer, typically called after persisting the events.

  ```php
  $order = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'book');

  foreach ($order->recordedEvents() as $record) {
      $outbox->append(record: $record);
  }

  $order->clearRecordedEvents();
  ```

### Event sourcing

`EventSourcingRoot` stores no state of its own; state is derived by replaying the event stream.

#### Applying events to state

* `when`: protected method that records the event and immediately applies it to state. By default, it dispatches
  to a `when<EventShortName>` method. Alternatively, register an explicit handler map via `eventHandlers()`.
  Override `identityName()` only when the identity property is not named `id` (for example, `Cart` uses `cartId`).

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

  final class Cart implements EventSourcingRoot
  {
      use EventSourcingRootBehavior;

      private CartId $cartId;
      private array $productIds = [];

      public function addProduct(string $productId): void
      {
          $this->when(event: new ProductAdded(productId: $productId));
      }

      public function applySnapshot(Snapshot $snapshot): void
      {
          $this->productIds = $snapshot->getAggregateState()['productIds'] ?? [];
      }

      protected function identityName(): string
      {
          return 'cartId';
      }

      protected function whenProductAdded(ProductAdded $event): void
      {
          $this->productIds[] = $event->productId;
      }
  }
  ```

  To register handlers explicitly instead of relying on the `when<EventShortName>` convention, override
  `eventHandlers()`. When the map is non-empty, only listed event classes are dispatched; any other event
  causes a `LogicException`.

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

  final class Cart implements EventSourcingRoot
  {
      use EventSourcingRootBehavior;

      private CartId $cartId;
      private array $productIds = [];

      public function addProduct(string $productId): void
      {
          $this->when(event: new ProductAdded(productId: $productId));
      }

      public function applySnapshot(Snapshot $snapshot): void
      {
          $this->productIds = $snapshot->getAggregateState()['productIds'] ?? [];
      }

      public function eventHandlers(): array
      {
          return [
              ProductAdded::class => function (ProductAdded $event): void {
                  $this->productIds[] = $event->productId;
              }
          ];
      }

      protected function identityName(): string
      {
          return 'cartId';
      }
  }
  ```

#### Creating a blank aggregate

* `blank`: factory that instantiates the aggregate without invoking its constructor. All state must come from events
  or from a snapshot.

  ```php
  $cart = Cart::blank(identity: new CartId(value: 'cart-1'));
  ```

#### Replaying an event stream

* `reconstitute`: replays an ordered stream of `EventRecord` instances, optionally starting from a snapshot to skip
  earlier events. When a snapshot is provided, its sequence number is authoritative.

  ```php
  $cart = Cart::reconstitute(identity: new CartId(value: 'cart-1'), records: $records);
  ```

  ```php
  $cart = Cart::reconstitute(
      identity: new CartId(value: 'cart-1'),
      records: $laterRecords,
      snapshot: $snapshot
  );
  ```

### Consuming events

Domain events travel between services through whatever broker the consumer chooses (SQS, Kafka, RabbitMQ, etc.).
The library is intentionally silent about the transport: it produces and consumes `EventRecord` envelopes,
which the consumer is responsible for serializing and deserializing.

A typical consumer integration deserializes the broker payload back into an `EventRecord` and dispatches
the wrapped `DomainEvent` to a handler. Sketch of the consumer side:

```php
$record = new EventRecord(
    id: Uuid::fromString($payload['event_id']),
    type: EventType::fromString(value: $payload['event_type']),
    event: $eventDeserializer->deserialize(type: $payload['event_type'], data: $payload['event_data']),
    identity: $identityDeserializer->deserialize(
        type: $payload['aggregate_type'],
        value: $payload['aggregate_id']
    ),
    revision: Revision::of(value: $payload['revision']),
    occurredOn: Instant::fromString($payload['occurred_on']),
    snapshotData: new SnapshotData(payload: json_decode($payload['snapshot'], true)),
    aggregateType: $payload['aggregate_type'],
    sequenceNumber: SequenceNumber::of(value: $payload['sequence_number'])
);

$handler->handle(record: $record);
```

The aggregate identity, aggregate type, sequence number, and revision are all available on the envelope.
Handlers receive the full `EventRecord` rather than just the `DomainEvent`, so they can route or filter
based on envelope metadata without that metadata leaking into the event itself.

The library does not ship deserializers because the format depends entirely on the consumer's transport
and storage choices. Consumers typically maintain a small registry mapping `EventType` values to concrete
`DomainEvent` classes, and a similar mapping for identity types.

### Snapshots

Snapshots let the event store skip replay of early events when reconstituting a long-lived aggregate.

#### Capturing a snapshot

* `Snapshot::fromAggregate`: reads all declared properties except `recordedEvents` and `sequenceNumber`. Both are
  tracked outside `aggregateState` because the snapshot has dedicated fields for them.

  ```php
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

  $snapshot = Snapshot::fromAggregate(aggregate: $cart);
  ```

#### Persisting a snapshot

* `Snapshotter`: port for snapshot persistence. The `SnapshotterBehavior` trait captures the snapshot and delegates
  storage to a concrete `persist` hook.

  ```php
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshotter;
  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotterBehavior;

  final class FileSnapshotter implements Snapshotter
  {
      use SnapshotterBehavior;

      protected function persist(Snapshot $snapshot): void
      {
          file_put_contents('/var/snapshots/cart.json', $snapshot->getAggregateState());
      }
  }

  $snapshotter = new FileSnapshotter();
  $snapshotter->take(aggregate: $cart);
  ```

#### Deciding when to snapshot

* `SnapshotCondition`: strategy for deciding whether a snapshot should be taken at a given point.

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotCondition;

  final class EveryHundredEvents implements SnapshotCondition
  {
      public function shouldSnapshot(EventSourcingRoot $aggregate): bool
      {
          return $aggregate->getSequenceNumber()->value % 100 === 0;
      }
  }
  ```

#### Built-in conditions

Two ready-made implementations ship with the library:

* `SnapshotEvery::events(count: N)` — returns `true` when the sequence number is a positive multiple of `N`.
  Throws `InvalidArgumentException` when `N < 1`.

  ```php
  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotEvery;

  $condition = SnapshotEvery::events(count: 100);
  $condition->shouldSnapshot(aggregate: $cart); # true at sequences 100, 200, 300, …
  ```

* `SnapshotNever::create()` — always returns `false`. Useful in tests or to explicitly disable snapshotting.

  ```php
  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotNever;

  $condition = SnapshotNever::create();
  ```

### Upcasting

Upcasters migrate serialized events across schema changes without touching the event classes.

#### Defining an upcaster

* `Upcaster`: transforms one `(type, revision)` pair forward by one step. Chains of upcasters handle multistep
  evolution. The `SingleUpcasterBehavior` trait binds the upcaster to a specific migration via three class constants.

  ```php
  use TinyBlocks\BuildingBlocks\Upcast\SingleUpcasterBehavior;
  use TinyBlocks\BuildingBlocks\Upcast\Upcaster;

  final class ProductV1Upcaster implements Upcaster
  {
      use SingleUpcasterBehavior;

      private const string EXPECTED_EVENT_TYPE = 'ProductAdded';
      private const int FROM_REVISION = 1;
      private const int TO_REVISION = 2;

      protected function doUpcast(array $data): array
      {
          return [...$data, 'quantity' => 1];
      }
  }
  ```

#### Upcasting an event

* `upcast`: transforms the event if it matches the expected `(type, revision)`, otherwise returns it unchanged.

  ```php
  use TinyBlocks\BuildingBlocks\Event\EventType;
  use TinyBlocks\BuildingBlocks\Event\Revision;
  use TinyBlocks\BuildingBlocks\Upcast\IntermediateEvent;

  $event = new IntermediateEvent(
      type: EventType::fromString(value: 'ProductAdded'),
      revision: Revision::initial(),
      serializedEvent: ['productId' => 'prod-1']
  );

  $upcasted = new ProductV1Upcaster()->upcast(event: $event);
  ```

#### Chaining upcasters

* `Upcasters`: ordered collection of `Upcaster` instances. `chain` folds them left-to-right over an
  `IntermediateEvent`, applying each upcaster in sequence. Upcasters that do not match the current `(type, revision)`
  pair pass the event through unchanged.

  ```php
  use TinyBlocks\BuildingBlocks\Upcast\Upcasters;

  $upcasters = Upcasters::createFrom(elements: [
      new ProductV1Upcaster(),
      new ProductV2Upcaster(),
  ]);

  $upcasted = $upcasters->chain(event: $event);
  ```

#### Reconstituting from an iterable

* `IntermediateEvent` implements `ObjectMapper`, so it can be reconstituted from an iterable of typed field values.
  Pass already-constructed `EventType` and `Revision` instances — the mapper maps each field by name.

  ```php
  use TinyBlocks\BuildingBlocks\Event\EventType;
  use TinyBlocks\BuildingBlocks\Event\Revision;
  use TinyBlocks\BuildingBlocks\Upcast\IntermediateEvent;

  $event = IntermediateEvent::fromIterable(iterable: [
      'type' => EventType::fromString(value: 'ProductAdded'),
      'revision' => Revision::of(value: 2),
      'serializedEvent' => ['productId' => 'prod-1', 'quantity' => 1]
  ]);
  ```

#### Default values for new fields

* `DefaultValues`: type-to-default-value map for common primitive types, used when an upcast introduces a new field.

  ```php
  use TinyBlocks\BuildingBlocks\Upcast\DefaultValues;

  $defaults = DefaultValues::get();
  ```

## FAQ

### 01. Why does `DomainEvent` only declare `revision()`?

`DomainEvent` declares one method, `revision()`, because schema versioning is an intrinsic property of the
event's structure: it tells consumers which fields the event carries and what semantics they have.
All other concerns — aggregate identity, aggregate type, sequence number, and serialization format —
belong to `EventRecord`, not to the event itself. Keeping those out of `DomainEvent` prevents
infrastructure from leaking into the domain model.

### 02. Why does `EventualAggregateRoot` store `EventRecord` instead of `DomainEvent`?

Only the aggregate has the context needed to build the complete envelope: identity, sequence number, aggregate type
name. Storing raw events and wrapping them later would either duplicate that context or require a second pass.
`push` builds the full `EventRecord` immediately, and the outbox adapter reads them as-is with no translation.

### 03. Why are `EventualAggregateRoot` and `EventSourcingRoot` siblings instead of a hierarchy?

Outbox and event sourcing are mutually exclusive persistence strategies. An aggregate either persists its state and
emits events as side effects, or persists only its events as the source of truth. A common base beyond `AggregateRoot`
would imply the two patterns can coexist on the same aggregate, which they cannot.

### 04. Why does `blank` skip the constructor?

`EventSourcingRootBehavior::blank` instantiates the aggregate via reflection without invoking its constructor because
all aggregate state in an event-sourced model must come from events or from a snapshot. Any invariants established by
the constructor would contradict that principle. Concrete aggregates should treat their constructor as private and
reserved for internal use.

### 05. Why are `recordedEvents` and `sequenceNumber` excluded from `Snapshot::aggregateState`?

`recordedEvents` belongs to the current unit of work, not to the aggregate's intrinsic state. `sequenceNumber` is
already carried by the snapshot as a first-class field, so duplicating it inside `aggregateState` would force
consumers to decide which copy is authoritative.

### 06. Why are custom exceptions declared under `Internal\Exceptions` instead of the root namespace?

Custom exceptions such as `InvalidEventType`, `InvalidRevision`, `InvalidSequenceNumber`, and
`MissingIdentityProperty` are implementation details. They extend `InvalidArgumentException` or
`RuntimeException` from the PHP standard library, so consumers that catch the broad standard types continue to work;
consumers that need precise handling can catch the specific classes.

### 07. Why did `IDENTITY` and `MODEL_VERSION` move from constants to methods?

Class constants read by reflection inside traits are invisible to static analyzers such as PHPStan and Psalm. Every
concrete aggregate had to annotate `@phpstan-ignore-next-line` or equivalent suppressions just to satisfy level-9
analysis. Replacing them with a protected `identityName(): string` method and a protected `modelVersion(): int`
method makes the contract explicit in PHP's type system: the compiler enforces implementation, IDEs can navigate to
it, and static analyzers raise no warnings — in the library or at consumer sites.

### 08. Why do `Revision`, `SequenceNumber`, and `EventType` now have private constructors?

These value objects have named static factories that carry semantic meaning: `Revision::initial()` communicates
"first schema revision", `SequenceNumber::first()` communicates "first recorded event", and
`EventType::fromEvent($event)` communicates "derive the type name from this event". Leaving the constructor public
allowed `new Revision(value: 1)` at call sites, which bypasses the semantic intent and mixes raw construction with
factory conventions. A private constructor forces all creation through the factories, making the intent visible at
every call site. The `of()` factory on `Revision` and `SequenceNumber` covers the loading-from-persistence path.

### 09. Should I add `identity()`, `aggregateType()`, or `toSnapshot()` to my `DomainEvent`?

No. These three concerns live elsewhere:

- **Identity and aggregate type** are envelope metadata. They are added by the aggregate when it builds
  the `EventRecord` (see `AggregateRootBehavior::buildEventRecord`) and are accessed on the consumer
  side through the envelope, not the event.
- **Serialization** is an infrastructure concern. The event remains a pure PHP object; serialization
  happens in the outbox writer and the consumer deserializer, both of which live in the consumer
  project.

A `DomainEvent` that grows methods like `identity()`, `aggregateType()`, or `toSnapshot()` is duplicating
envelope data already on the `EventRecord` and pulling infrastructure into the domain layer. If you find
yourself reaching for these methods, the likely root cause is that consumer code is not unwrapping the
envelope correctly. See the *Consuming events* section above for the intended consumer-side pattern.

## License

Building Blocks is licensed under [MIT](https://github.com/tiny-blocks/building-blocks/blob/main/LICENSE).

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
