# Building Blocks

[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/tiny-blocks/building-blocks/blob/main/LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    + [Entity](#entity)
    + [Aggregate](#aggregate)
    + [Domain events with transactional outbox](#domain-events-with-transactional-outbox)
    + [Event sourcing](#event-sourcing)
    + [Snapshots](#snapshots)
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

The `Building Blocks` library provides the tactical design building blocks of Domain-Driven Design: `Entity`,
`Identity`, `AggregateRoot`, and the infrastructure required to carry domain events through a transactional outbox
or an event-sourced store. It is persistence-agnostic and framework-agnostic.

Domain events defined here are plain PHP objects fully compatible with any PSR-14 dispatcher. The library does not
replace PSR-14; it defines what flows through it.

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

Every entity implements a protected `identityName()` method returning the name of the property that holds its
`Identity`.

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

* `DomainEvent`: empty marker interface. A domain event is a plain PHP object.

  ```php
  use TinyBlocks\BuildingBlocks\Event\DomainEvent;

  final readonly class OrderPlaced implements DomainEvent
  {
      public function __construct(public string $item)
      {
      }
  }
  ```

#### Emitting events from the aggregate

* `push`: protected method on `EventualAggregateRootBehavior`. Increments the sequence number and appends a
  fully-built `EventRecord` to the recorded buffer. The `Revision` is provided on the call site, so the event class
  stays pure.

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
  use TinyBlocks\BuildingBlocks\Event\Revision;

  final class Order implements EventualAggregateRoot
  {
      use EventualAggregateRootBehavior;

      private function __construct(private OrderId $orderId)
      {
      }

      public static function place(OrderId $orderId, string $item): Order
      {
          $order = new Order(orderId: $orderId);
          $order->push(event: new OrderPlaced(item: $item), revision: Revision::initial());

          return $order;
      }

      protected function identityName(): string
      {
          return 'orderId';
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

* `when`: protected method that records the event and immediately applies it to state by dispatching to a
  `when<EventShortName>` method by reflection.

  ```php
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
  use TinyBlocks\BuildingBlocks\Event\Revision;
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

  final class Cart implements EventSourcingRoot
  {
      use EventSourcingRootBehavior;

      private CartId $cartId;
      private array $productIds = [];

      public function addProduct(string $productId): void
      {
          $this->when(event: new ProductAdded(productId: $productId), revision: Revision::initial());
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
      'serializedEvent' => ['productId' => 'prod-1', 'quantity' => 1],
  ]);
  ```

#### Default values for new fields

* `DefaultValues`: type-to-default-value map for common primitive types, used when an upcast introduces a new field.

  ```php
  use TinyBlocks\BuildingBlocks\Upcast\DefaultValues;

  $defaults = DefaultValues::get();
  ```

## FAQ

### 01. Why is `DomainEvent` an empty marker interface?

A domain event is a fact about something that happened in the domain. It has no technical contract beyond being that
fact. Persistence and transport concerns (type name, revision, aggregate identity) belong to `EventRecord`, not to
the event itself. Keeping the event pure prevents infrastructure concerns from leaking into the domain model.

### 02. Why does `EventualAggregateRoot` store `EventRecord` instead of `DomainEvent`?

Only the aggregate has the context needed to build the complete envelope: identity, sequence number, aggregate type
name. Storing raw events and wrapping them later would either duplicate that context or require a second pass.
`push` builds the full `EventRecord` immediately, and the outbox adapter reads them as-is with no translation.

### 03. Why are `EventualAggregateRoot` and `EventSourcingRoot` siblings instead of a hierarchy?

Outbox and event sourcing are mutually exclusive persistence strategies. An aggregate either persists its state and
emits events as side effects, or persists only its events as the source of truth. A common base beyond `AggregateRoot`
would imply the two patterns can coexist on the same aggregate, which they cannot.

### 04. Why does `Revision` live on the call site instead of on the event class?

Keeping `Revision` on the `push` or `when` call site makes the aggregate the author of schema evolution. The
event class stays pure. Bumping the revision of an existing event does not require creating a new class.

### 05. Why does `blank` skip the constructor?

`EventSourcingRootBehavior::blank` instantiates the aggregate via reflection without invoking its constructor because
all aggregate state in an event-sourced model must come from events or from a snapshot. Any invariants established by
the constructor would contradict that principle. Concrete aggregates should treat their constructor as private and
reserved for internal use.

### 06. Why are `recordedEvents` and `sequenceNumber` excluded from `Snapshot::aggregateState`?

`recordedEvents` belongs to the current unit of work, not to the aggregate's intrinsic state. `sequenceNumber` is
already carried by the snapshot as a first-class field, so duplicating it inside `aggregateState` would force
consumers to decide which copy is authoritative.

### 07. Why are custom exceptions declared under `Internal\Exceptions` instead of the root namespace?

Custom exceptions such as `InvalidEventType`, `InvalidRevision`, `InvalidSequenceNumber`, and
`MissingIdentityProperty` are implementation details. They extend `InvalidArgumentException` or
`RuntimeException` from the PHP standard library, so consumers that catch the broad standard types continue to work;
consumers that need precise handling can catch the specific classes.

### 08. Why did `IDENTITY` and `MODEL_VERSION` move from constants to methods?

Class constants read by reflection inside traits are invisible to static analyzers such as PHPStan and Psalm. Every
concrete aggregate had to annotate `@phpstan-ignore-next-line` or equivalent suppressions just to satisfy level-9
analysis. Replacing them with a protected `identityName(): string` method and a protected `modelVersion(): int`
method makes the contract explicit in PHP's type system: the compiler enforces implementation, IDEs can navigate to
it, and static analyzers raise no warnings — in the library or at consumer sites.

### 09. Why do `Revision`, `SequenceNumber`, and `EventType` now have private constructors?

These value objects have named static factories that carry semantic meaning: `Revision::initial()` communicates
"first schema revision", `SequenceNumber::first()` communicates "first recorded event", and
`EventType::fromEvent($event)` communicates "derive the type name from this event". Leaving the constructor public
allowed `new Revision(value: 1)` at call sites, which bypasses the semantic intent and mixes raw construction with
factory conventions. A private constructor forces all creation through the factories, making the intent visible at
every call site. The `of()` factory on `Revision` and `SequenceNumber` covers the loading-from-persistence path.

## License

Building Blocks is licensed under [MIT](https://github.com/tiny-blocks/building-blocks/blob/main/LICENSE).

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
