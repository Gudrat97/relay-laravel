# Relay: courier fleet API and dashboard

A Laravel application for a delivery company. Dispatchers create orders, assign couriers and move each order through its life cycle, while the dashboard shows revenue, delivery speed and every courier's status.

**Live UI demo:** https://gudrat97.github.io/projects/fleet-dashboard/
**Portfolio:** https://gudrat97.github.io

## Features

- REST API for orders, couriers and statistics, with JSON resources and pagination
- Order life cycle as a state machine: `pending → assigned → picked_up → delivered`, or `cancelled` before pick-up. Invalid jumps return `422`.
- Courier status kept in step with orders: assigning makes a courier busy, finishing an order frees them
- Prices and promised delivery times calculated on the server, never trusted from the client
- Dashboard statistics for 7, 30 or 90 days compared with the previous period, written in portable SQL (SQLite, MySQL, PostgreSQL)
- Token authentication with Laravel Sanctum and rate limiting on every write endpoint
- Seeder with 180 days of realistic history
- Feature and unit tests

## Tech stack

PHP 8.2+, Laravel 12, Laravel Sanctum, Eloquent ORM, Blade, PHPUnit, SQLite or MySQL.

## Project structure

```
app/
  Enums/          OrderStatus (with allowed transitions), CourierStatus, Vehicle
  Http/
    Controllers/  Api\OrderController, Api\CourierController, Api\StatsController, DashboardController
    Requests/     Form requests with validation rules and messages
    Resources/    JSON shape of orders and couriers
  Models/         Order, Courier, Zone, User
  Services/       OrderWorkflow, PricingService, StatsService
database/
  migrations/     Schema with indexes for dashboard queries
  factories/      Test data
  seeders/        180 days of demo history
tests/            Feature tests for the API, unit tests for business rules
```

## Getting started

```bash
git clone https://github.com/Gudrat97/relay-laravel.git
cd relay-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Open http://localhost:8000 for the dashboard.

Create a token for the write endpoints:

```bash
php artisan relay:token dispatcher@example.com
```

Run the tests:

```bash
php artisan test
```

## API

All responses are JSON. Endpoints that change data need `Authorization: Bearer <token>`.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/stats?days=30` | Totals, previous period, revenue per day, deliveries per zone |
| GET | `/api/couriers?status=available&search=ann&sort=deliveries` | Couriers with delivery and on-time counts |
| GET | `/api/couriers/{id}` | One courier |
| GET | `/api/orders?status=pending&zone_id=2&per_page=20` | Paginated orders |
| GET | `/api/orders/{id}` | One order |
| POST | `/api/orders` | Create an order |
| POST | `/api/orders/{id}/assign` | Assign an available courier |
| PATCH | `/api/orders/{id}/status` | Move the order to its next status |

Create an order:

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -d customer_name="Aylin Demir" -d customer_phone="+1 555 010 2030" \
  -d pickup_address="14 Mill Lane" -d dropoff_address="3 River Street" \
  -d zone_id=1 -d distance_km=5.2
```

```json
{
  "data": {
    "code": "RL-8K2QWD",
    "status": "pending",
    "next_statuses": ["assigned", "cancelled"],
    "distance_km": 5.2,
    "price": { "cents": 692, "formatted": "$6.92" },
    "zone": "Centre",
    "promised_at": "2026-11-18T14:42:00+00:00"
  }
}
```

A status change that skips a step is refused:

```json
{
  "message": "An order that is Pending cannot be changed to Delivered.",
  "errors": { "status": ["An order that is Pending cannot be changed to Delivered."] }
}
```

## License

MIT
