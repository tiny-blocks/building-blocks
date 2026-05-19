# Building Blocks

[![License](https://img.shields.io/badge/license-MIT-green)](https://github.com/tiny-blocks/building-blocks/blob/main/LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    + [Entity](#entity)
        - [Single-field identity](#single-field-identity)
        - [Compound identity](#compound-identity)
        - [Identity access on entities](#identity-access-on-entities)
    + [Aggregate](#aggregate)
    + [Domain events with transactional outbox](#domain-events-with-transactional-outbox)
        - [Declaring events](#declaring-events)
        - [Emitting events from the aggregate](#emitting-events-from-the-aggregate)
        - [Draining events](#draining-events)
        - [Restoring aggregate version on reload](#restoring-aggregate-version-on-reload)
        - [Constructing event records directly](#constructing-event-records-directly)
    + [Event sourcing](#event-sourcing)
        - [Applying events to state](#applying-events-to-state)
        - [Creating a blank aggregate](#creating-a-blank-aggregate)
        - [Replaying an event stream](#replaying-an-event-stream)
    + [Snapshots](#snapshots)
        - [Capturing aggregate state](#capturing-aggregate-state)
        - [Taking a snapshot](#taking-a-snapshot)
        - [Persisting snapshots](#persisting-snapshots)
        - [Built-in conditions](#built-in-conditions)
    + [Upcasting](#upcasting)
        - [Defining an upcaster](#defining-an-upcaster)
        - [Chaining upcasters](#chaining-upcasters)
        - [Default values for new fields](#default-values-for-new-fields)
* [FAQ](#faq)
* [License](#license)
* [Contributing](#contributing)

## Overview

The `Building Blocks` library provides the tactical design building blocks of Domain-Driven Design: `Entity`,
`Identity`, `AggregateRoot`, and the infrastructure required to carry domain events through a transactional outbox
or an event-sourced store.

This library implements the tactical patterns from Evans (Entity, Identity, Aggregate Root, Value Object) and Vernon
(Domain Event) together with pragmatic extensions that production code needs but the original DDD literature does
not address: aggregate versioning for optimistic offline locking (Fowler PEAA), model versioning and rolling
snapshots for event-sourced aggregates (Greg Young), event upcasting for schema evolution (Greg Young), and an
event envelope decoupling domain events from infrastructure metadata (Hohpe/Woolf EIP). Every extension is annotated
in its own PHPDoc with its source.

It is persistence-agnostic and framework-agnostic. It depends only on the other `tiny-blocks` primitives
(`immutable-object`, `value-object`, `collection`, `time`) and `ramsey/uuid` for event identifiers.

Domain events defined here are plain PHP objects fully compatible with any PSR-14 dispatcher. The library does not
replace PSR-14, it defines what flows through it. Serialization to wire formats is delegated to adapters such as
[`tiny-blocks/outbox`](https://github.com/tiny-blocks/outbox).

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

Every entity declares which property holds its `Identity`. By default, the property is named `id`, aggregates with a
differently named property override `identityProperty()`.

#### Single-field identity

* `SingleIdentity`: identity backed by a single scalar value (UUID, auto-increment integer, slug).

  ```php
  <?php

  declare(strict_types=1);

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
  $orderId->identityValue();
  ```

#### Compound identity

* `CompoundIdentity`: identity composed of multiple fields treated as a tuple. Fields may carry any
  type the application requires, including primitive scalars (`int`, `string`) and value objects.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Entity\CompoundIdentity;
  use TinyBlocks\BuildingBlocks\Entity\CompoundIdentityBehavior;

  final readonly class AppointmentId implements CompoundIdentity
  {
      use CompoundIdentityBehavior;

      public function __construct(
          public string $tenantId,
          public int $practitionerId
      ) {
      }
  }

  $appointmentId = new AppointmentId(tenantId: 'tenant-1', practitionerId: 42);
  $appointmentId->identityValue();
  ```

#### Identity access on entities

* `identity()`, `identityValue()`, `sameIdentityOf()`, `identityEquals()`: provided by `EntityBehavior` for any
  entity that declares its identity property.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRootBehavior;

  final class User implements AggregateRoot
  {
      use AggregateRootBehavior;

      private function __construct(private UserId $id, private string $email)
      {
      }
  }

  $user->identity();
  $user->identityValue();
  $user->sameIdentityOf(other: $otherUser);
  $user->identityEquals(other: new UserId(value: 'usr-1'));
  ```

* Override `identityProperty()` only when the identity property has a name other than `id`:

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRootBehavior;

  final class Cart implements AggregateRoot
  {
      use AggregateRootBehavior;

      private CartId $cartId;

      protected function identityProperty(): string
      {
          return 'cartId';
      }
  }
  ```

### Aggregate

`AggregateRoot` adds two pragmatic fields to Evans' aggregate: a monotonic `AggregateVersion` for optimistic
concurrency control, and a `ModelVersion` for schema evolution of the aggregate type.

* `aggregateVersion()`: the current aggregate version, starting at zero for a blank aggregate and advancing by one
  for every recorded event. `AggregateVersion::isAfter()` and `AggregateVersion::isBefore()` compare two
  versions when reasoning about replay progress or concurrency conflicts.

  ```php
  $user->aggregateVersion();
  ```

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;

  $previous = AggregateVersion::initial();
  $current = AggregateVersion::of(value: 5);

  $current->isAfter(other: $previous);   # true
  $previous->isBefore(other: $current);  # true
  ```

* `modelVersion()`: typed as `ModelVersion`. Defaults to `ModelVersion::initial()` (value `0`). Override on
  aggregates that have a versioned schema. `ModelVersion::isAfter()` and `ModelVersion::isBefore()` compare two
  schema versions during migration logic.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateRootBehavior;
  use TinyBlocks\BuildingBlocks\Aggregate\ModelVersion;

  final class Cart implements AggregateRoot
  {
      use AggregateRootBehavior;

      public function modelVersion(): ModelVersion
      {
          return ModelVersion::of(value: 2);
      }
  }

  $cart->modelVersion();
  ```

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\ModelVersion;

  $previous = ModelVersion::initial();
  $current = ModelVersion::of(value: 2);

  $current->isAfter(other: $previous);   # true
  $previous->isBefore(other: $current);  # true
  ```

* `aggregateType()`: short class name, used as the aggregate type identifier on each `EventRecord`.

  ```php
  $user->aggregateType();
  ```

### Domain events with transactional outbox

`EventualAggregateRoot` records domain events during the unit of work. State is the source of truth, events are
emitted as side effects and must be delivered at-least-once.

Aggregates of this type are **use-once**: after the application service drains `recordedEvents()` into the outbox,
the aggregate instance must be discarded. The recorded-events buffer is never cleared, re-saving the same instance
fails by design with a duplicate-event error from the outbox.

#### Declaring events

* `DomainEvent`: contract for a fact that happened in the domain. The only required method is `revision()`,
  defaulted to `Revision::initial()` by `DomainEventBehavior`. Override only when bumping the event schema.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Event\DomainEvent;
  use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;
  use TinyBlocks\BuildingBlocks\Event\Revision;

  final readonly class OrderPlaced implements DomainEvent
  {
      use DomainEventBehavior;

      public function __construct(public string $item)
      {
      }
  }
  ```

  Bumping a revision:

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Event\DomainEvent;
  use TinyBlocks\BuildingBlocks\Event\DomainEventBehavior;
  use TinyBlocks\BuildingBlocks\Event\Revision;

  final readonly class OrderPlacedV2 implements DomainEvent
  {
      use DomainEventBehavior;

      public function __construct(public string $item, public int $quantity)
      {
      }

      public function revision(): Revision
      {
          return Revision::of(value: 2);
      }
  }
  ```

  Comparing revisions:

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Event\Revision;

  $previous = Revision::initial();
  $current = Revision::of(value: 2);

  $current->isAfter(other: $previous);   # true
  $previous->isBefore(other: $current);  # true
  ```

#### Emitting events from the aggregate

* `push()`: protected method on `EventualAggregateRootBehavior`. Increments the aggregate version and appends a
  fully-built `EventRecord` to the recorded buffer.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;

  final class Order implements EventualAggregateRoot
  {
      use EventualAggregateRootBehavior;

      private function __construct(private OrderId $id)
      {
      }

      public static function place(OrderId $id, string $item): Order
      {
          $order = new Order(id: $id);
          $order->push(event: new OrderPlaced(item: $item));

          return $order;
      }
  }
  ```

#### Draining events

* `recordedEvents()`: returns a copy of the buffer, safe to iterate. The aggregate's own buffer is not mutated by
  external iteration. The buffer is **never cleared** by the library, the aggregate is use-once.

  ```php
  <?php

  declare(strict_types=1);

  $order = Order::place(id: new OrderId(value: 'ord-1'), item: 'book');

  foreach ($order->recordedEvents() as $record) {
      $outbox->append(record: $record);
  }
  ```

#### Restoring aggregate version on reload

* `reconstitute()`: static factory that state-based repositories invoke when rehydrating an
  `EventualAggregateRoot` from persistence. The default implementation provided by
  `EventualAggregateRootBehavior` instantiates the aggregate without invoking its constructor, assigns the
  identity to the property declared by `identityProperty()`, hydrates the remaining state by reflection
  from the `$state` map (entries with keys absent from the aggregate are silently ignored), and assigns
  the aggregate version so subsequent events advance from the correct value. The buffer of recorded events
  starts empty, the use-once contract still holds for any new operation.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;

  # Default path: the aggregate does not declare reconstitute(), the repository calls the
  # trait default with the persisted identity, version, and state map.
  $reservation = Reservation::reconstitute(
      identity: new ReservationId(value: 'res-1'),
      aggregateVersion: AggregateVersion::of(value: 7),
      state: ['status' => 'pending']
  );
  ```

  Aggregates may override the factory to enforce a concrete identity type at the entry point. The static
  signature cannot narrow the parameter type per LSP, so the override keeps `Identity` in the signature
  and guards with `instanceof` inside:

  ```php
  <?php

  declare(strict_types=1);

  use InvalidArgumentException;
  use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventualAggregateRootBehavior;
  use TinyBlocks\BuildingBlocks\Entity\Identity;

  final class Order implements EventualAggregateRoot
  {
      use EventualAggregateRootBehavior;

      private function __construct(private OrderId $id)
      {
      }

      public static function reconstitute(
          Identity $orderId,
          AggregateVersion $aggregateVersion,
          array $state = []
      ): static {
          if (!$orderId instanceof OrderId) {
              $template = 'Expected identity of type <%s>, got <%s>.';

              throw new InvalidArgumentException(message: sprintf($template, OrderId::class, $orderId::class));
          }

          $order = new Order(id: $orderId);
          $order->aggregateVersion = $aggregateVersion;

          return $order;
      }
  }
  ```

#### Constructing event records directly

Every envelope carries `$id`, `$event`, `$revision`, `$eventType`, `$occurredAt`, `$aggregateId`,
`$aggregateType`, and `$aggregateVersion`. The aggregate normally builds the record, so consumers
read these fields off `EventRecord` directly without instantiating one.

* `EventRecord::of()`: factory for the rare cases that require building an envelope outside the aggregate boundary,
  typically test code that fabricates envelopes as inputs to handlers, or consumer-side code deserializing payloads
  from a wire format. The `id` and `occurredAt` parameters fall back to sensible defaults (`Uuid::uuid4()` and
  `Instant::now()`) when omitted.

  | Parameter          | Type               | Required | Description                                                        |
    |--------------------|--------------------|----------|--------------------------------------------------------------------|
  | `id`               | `?UuidInterface`   | No       | Explicit envelope identifier; defaults to a fresh `Uuid::uuid4()`. |
  | `event`            | `DomainEvent`      | Yes      | The event being recorded.                                          |
  | `occurredAt`       | `?Instant`         | No       | Explicit occurrence timestamp; defaults to `Instant::now()`.       |
  | `aggregateId`      | `Identity`         | Yes      | The aggregate identity that produced the event.                    |
  | `aggregateType`    | `string`           | Yes      | The short class name of the aggregate.                             |
  | `aggregateVersion` | `AggregateVersion` | Yes      | The aggregate version assigned to this envelope.                   |

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
  use TinyBlocks\BuildingBlocks\Event\EventRecord;

  $record = EventRecord::of(
      event: new OrderPlaced(item: 'book'),
      aggregateId: new OrderId(value: 'ord-1'),
      aggregateType: 'Order',
      aggregateVersion: AggregateVersion::first()
  );
  ```

### Event sourcing

`EventSourcingRoot` stores no state of its own, state is derived by replaying the event stream.

#### Applying events to state

* `when()`: protected method that records the event and immediately applies it to state by dispatching to a
  `when<EventShortName>` method by reflection.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

  final class Cart implements EventSourcingRoot
  {
      use EventSourcingRootBehavior;

      private CartId $id;
      private array $productIds = [];

      public function addProduct(string $productId): void
      {
          $this->when(event: new ProductAdded(productId: $productId));
      }

      public function applySnapshot(Snapshot $snapshot): void
      {
          $this->productIds = $snapshot->aggregateState()['productIds'] ?? [];
      }

      protected function whenProductAdded(ProductAdded $event): void
      {
          $this->productIds[] = $event->productId;
      }
  }
  ```

* `eventHandlers()`: explicit registration. Returns a map of `class-string<DomainEvent>` to callable. When the map
  is non-empty, the trait dispatches through it instead of using the implicit `when<X>` convention. Use this when
  handler names should not follow the convention or when static analysis on dispatch is desired.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;

  final class ExplicitCart implements EventSourcingRoot
  {
      use EventSourcingRootBehavior;

      private CartId $id;
      private array $productIds = [];

      public function eventHandlers(): array
      {
          return [
              ProductAdded::class => $this->onProductAdded(...)
          ];
      }

      private function onProductAdded(ProductAdded $event): void
      {
          $this->productIds[] = $event->productId;
      }
  }
  ```

#### Creating a blank aggregate

* `blank()`: factory that instantiates the aggregate via reflection without invoking its constructor. All state
  must come from events or from a snapshot.

  ```php
  $cart = Cart::blank(identity: new CartId(value: 'cart-1'));
  ```

#### Replaying an event stream

* `reconstitute()`: replays an ordered stream of `EventRecord` instances, optionally starting from a snapshot to
  skip earlier events. When a snapshot is provided, its aggregate version is authoritative.

  ```php
  $cart = Cart::reconstitute(identity: new CartId(value: 'cart-1'), records: $records);
  ```

  ```php
  <?php

  declare(strict_types=1);

  $cart = Cart::reconstitute(
      identity: new CartId(value: 'cart-1'),
      records: $laterRecords,
      snapshot: $snapshot
  );
  ```

### Snapshots

Snapshots let the event store skip replay of early events when reconstituting a long-lived aggregate. A snapshot
captures the aggregate's state at a specific version so that reconstitution can resume from that point instead of
replaying the entire history.

#### Capturing aggregate state

Aggregates control what fields enter the snapshot by overriding `snapshotState()`. The default captures every
declared property except `recordedEvents` and `aggregateVersion` (which are tracked separately on the envelope).

  ```php
  <?php

  declare(strict_types=1);

  use Psr\Log\LoggerInterface;
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRootBehavior;

  final class CartWithLogger implements EventSourcingRoot
  {
      use EventSourcingRootBehavior;

      private CartId $id;
      private array $productIds = [];
      private LoggerInterface $logger;

      public function snapshotState(): array
      {
          return ['id' => $this->id, 'productIds' => $this->productIds];
      }
  }
  ```

#### Taking a snapshot

* `Snapshot::fromAggregate()`: captures the aggregate's current state via the `snapshotState()` hook.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

  $snapshot = Snapshot::fromAggregate(aggregate: $cart);
  $snapshot->aggregateState();
  $snapshot->aggregateVersion();
  ```

#### Persisting snapshots

* `Snapshotter`: port for snapshot persistence. The `SnapshotterBehavior` trait captures the snapshot and delegates
  storage to a `persist` hook implemented by the consumer.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;
  use TinyBlocks\BuildingBlocks\Snapshot\Snapshotter;
  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotterBehavior;

  final class FileSnapshotter implements Snapshotter
  {
      use SnapshotterBehavior;

      protected function persist(Snapshot $snapshot): void
      {
          file_put_contents('/var/snapshots/cart.json', json_encode($snapshot->aggregateState()));
      }
  }

  new FileSnapshotter()->take(aggregate: $cart);
  ```

#### Built-in conditions

* `SnapshotCondition`: strategy for deciding whether a snapshot should be taken at a given point.
* `SnapshotEvery::events(count: N)`: ready-made condition that triggers every `N` events (skipping version `0`).
* `SnapshotNever::create()`: condition that never triggers, useful in tests and when snapshotting is explicitly
  disabled.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotEvery;
  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotNever;

  $every100 = SnapshotEvery::events(count: 100);
  $never = SnapshotNever::create();
  ```

  Custom conditions implement the interface directly:

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
  use TinyBlocks\BuildingBlocks\Snapshot\SnapshotCondition;

  final class WhenStatusChanges implements SnapshotCondition
  {
      public function shouldSnapshot(EventSourcingRoot $aggregate): bool
      {
          # domain-specific logic
      }
  }
  ```

### Upcasting

Upcasters migrate serialized events across schema changes without touching the event classes.

#### Defining an upcaster

* `Upcaster`: transforms one `(type, revision)` pair forward by one step. Returns the event unchanged when the
  type or revision does not match.
* `SingleUpcasterBehavior`: binds the upcaster to a specific migration via three class constants and delegates the
  payload transformation to an abstract `doUpcast()` method.

  ```php
  <?php

  declare(strict_types=1);

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

#### Chaining upcasters

* `Upcasters::chain()`: runs every upcaster in insertion order in a single forward pass. Upcasters whose type or
  revision does not match pass the event through.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Event\EventType;
  use TinyBlocks\BuildingBlocks\Event\Revision;
  use TinyBlocks\BuildingBlocks\Upcast\IntermediateEvent;
  use TinyBlocks\BuildingBlocks\Upcast\Upcasters;

  $event = new IntermediateEvent(
      type: EventType::fromString(value: 'ProductAdded'),
      revision: Revision::initial(),
      serializedEvent: ['productId' => 'prod-1']
  );

  $chain = Upcasters::createFrom(elements: [
      new ProductV1Upcaster(),
      new ProductV2Upcaster()
  ]);

  $upcasted = $chain->chain(event: $event);
  ```

#### Default values for new fields

* `DefaultValues::get()`: type-to-default-value map for common primitive types, used when an upcast introduces a
  new field with a sensible zero-value default.

  ```php
  <?php

  declare(strict_types=1);

  use TinyBlocks\BuildingBlocks\Upcast\DefaultValues;

  $defaults = DefaultValues::get();
  ```

## FAQ

### 01. Why is `DomainEvent` close to a marker interface?

A domain event is a fact about something that happened in the domain. The contract carries only `revision()` so
the library can route schema migrations through upcasters. Everything else (aggregate identity, aggregate version,
aggregate type, occurrence timestamp) is envelope metadata that belongs to `EventRecord`. Keeping the event itself
minimal prevents infrastructure concerns from leaking into the domain model.

> Vaughn Vernon, *Implementing Domain-Driven Design* (Addison-Wesley, 2013), Chapter 8, "Domain Events".

### 02. Why does `EventualAggregateRoot` store `EventRecord` instead of `DomainEvent`?

Only the aggregate has the context needed to build the complete envelope: identity, aggregate version, aggregate
type name. Storing raw events and wrapping them later would either duplicate that context or require a second
pass. `push()` builds the full `EventRecord` immediately, and the outbox adapter reads them as-is with no
translation.

> Gregor Hohpe and Bobby Woolf, *Enterprise Integration Patterns* (Addison-Wesley, 2003), "Envelope Wrapper".

### 03. Why are `EventualAggregateRoot` and `EventSourcingRoot` siblings instead of a hierarchy?

Outbox and event sourcing are mutually exclusive persistence strategies. An aggregate either persists its state
and emits events as side effects, or persists only its events as the source of truth. A common base beyond
`AggregateRoot` would imply the two patterns can coexist on the same aggregate, which they cannot.

> Martin Fowler, *Event Sourcing* (martinfowler.com, 2005).
> Chris Richardson, *Microservices Patterns* (Manning, 2018), Chapter 3, "Transactional Outbox".

### 04. Why does `Revision` live on the `DomainEvent` instead of the call site?

The revision of an event is a property of the event's schema. Keeping it on the event means the call site (`push`,
`when`) does not need to know the schema version, the event class is the single source of truth. Bumping a
revision is always paired with a payload change (added field, removed field, renamed field), so creating a new
event class to carry the new revision is the natural unit of work.

> Greg Young, *Versioning in an Event Sourced System* (Leanpub, 2017).

### 05. Why does `blank()` skip the constructor?

`EventSourcingRootBehavior::blank()` instantiates the aggregate via reflection without invoking its constructor
because all aggregate state in an event-sourced model must come from events or from a snapshot. Any invariants
established by the constructor would contradict that principle. Concrete aggregates should treat their constructor
as private and reserved for internal use during command handling.

> Greg Young, *CQRS Documents* (2010), "Event Sourcing" section.

### 06. Why doesn't the library serialize envelopes to JSON or any other wire format?

Serialization is an infrastructure concern. Putting encoding methods on domain value objects mixes that concern
into the domain layer, which contradicts the library's persistence-agnostic stance. Adapters such as
`tiny-blocks/outbox` provide dedicated serializer ports. The domain layer exposes `EventRecord`, `Snapshot`, and
the value objects as pure data, downstream adapters decide how to map them onto bytes.

> Alistair Cockburn, *Hexagonal Architecture* (alistair.cockburn.us, 2005).

### 07. What is the difference between `ModelVersion` and `AggregateVersion`?

`AggregateVersion` counts events per aggregate instance. It is the basis for optimistic concurrency control: a
save fails if the aggregate version in storage differs from the in-memory version the aggregate believed it had.

`ModelVersion` versions the aggregate type itself. When the aggregate schema changes in a backwards-incompatible
way (a property is removed, renamed, or its semantics shift), bumping the model version gives migration code a
single source of truth to branch on.

The two are different concepts that happen to share an integer representation. They are typed as separate value
objects to prevent accidental comparisons across them at compile time.

> Martin Fowler, *Patterns of Enterprise Application Architecture* (Addison-Wesley, 2002), "Optimistic Offline
> Lock", source of `AggregateVersion` semantics.
> Greg Young, *Versioning in an Event Sourced System* (Leanpub, 2017), source of `ModelVersion` semantics.

### 08. Why is the `EventualAggregateRoot` use-once?

The recorded-events buffer is never cleared by the library. After the application service drains
`recordedEvents()` into the outbox, the aggregate instance must be discarded. Re-saving the same instance pushes
the same envelopes again and deterministically fails with a duplicate-event error from the outbox.

This is intentional. It surfaces re-save bugs at the database layer instead of hiding them via implicit state
mutation. Applications that genuinely need to mutate the same logical aggregate twice in one process must reload
from the repository between operations.

> Eric Evans, *Domain-Driven Design* (Addison-Wesley, 2003), Chapter 6, "Aggregates" (single transactional unit
> per aggregate per request).

### 09. Should I add `identity()`, `aggregateType()`, or `toArray()` to my `DomainEvent`?

No. These three concerns live elsewhere:

* Identity and aggregate type are envelope metadata. They are added by the aggregate when it builds the
  `EventRecord` (see `AggregateRootBehavior::buildEventRecord`) and are accessed on the consumer side through the
  envelope, not the event.
* Serialization is an infrastructure concern. The event remains a pure PHP object, serialization happens in the
  outbox writer and the consumer deserializer, both of which live downstream of the library.

A `DomainEvent` that grows methods like these duplicates envelope data already on the `EventRecord` and pulls
infrastructure into the domain layer.

### 10. Why does the library include `AggregateVersion` and `ModelVersion` if Evans never mentioned them?

Evans defined the tactical patterns of DDD, but optimistic concurrency control and aggregate schema evolution
are concerns that emerged later in mainstream production code. `AggregateVersion` carries the optimistic offline
lock formalized by Fowler in PEAA: the value travels with the aggregate, the persistence adapter compares the
in-memory value against the stored one, and a mismatch raises a concurrency exception instead of overwriting
another process's change. `ModelVersion` carries Greg Young's schema versioning for aggregate types, so migration
code has a single source of truth to branch on when older shapes show up in storage.

> Martin Fowler, *Patterns of Enterprise Application Architecture* (Addison-Wesley, 2002), "Optimistic Offline
> Lock".
> Greg Young, *Versioning in an Event Sourced System* (Leanpub, 2017).

### 11. Why is `reconstitute()` static on the interface even though PHP's polymorphism for static methods is limited?

The interface declaration documents the contract: every `EventualAggregateRoot` exposes a static factory with the
shape `(Identity, AggregateVersion, array): static` that repositories can call. PHP does not dispatch static calls
through interfaces at runtime, so the consumer always names the concrete class (`Order::reconstitute(...)`,
`Reservation::reconstitute(...)`). The interface still earns its keep: it forces aggregates to expose the factory,
the trait default provides one for free, and overrides remain bound to the declared signature. The parameter name
is free per LSP, so an override can rename `$identity` to `$orderId` for readability, but the type must remain
`Identity` — narrowing to a concrete identity class would break LSP. Concrete types are enforced inside the
override with `instanceof`.

> Barbara Liskov and Jeannette Wing, *A Behavioral Notion of Subtyping* (ACM TOPLAS, 1994).

### 12. Why was `reconstituteAggregateVersion()` removed?

It was never part of the external contract. The only caller was the trait's own `reconstitute()` factory, which
needed to set the aggregate version on the instance it had just built. Exposing that internal step as a public
instance method invited misuse (repositories calling it on aggregates they had not just reconstituted) without
adding any expressiveness over assigning the property directly. The factory now writes `$aggregate->aggregateVersion`
directly inside the trait, which is legal because the assignment happens in the static method of the same class
after the trait flattens into the aggregate. Eliminating the public method tightens the surface and removes the
documentation burden of explaining when calling it is correct.

## License

Building Blocks is licensed under [MIT](LICENSE).

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
