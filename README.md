# Kaely POS Customer Package

Un paquete Laravel 12 para la gestión completa de clientes en sistemas POS (Point of Sale), con integración de direcciones de facturación y envío, historial de compras, gestión de crédito y sistema de fidelización con puntos.


## Características

- ✅ **Gestión de Clientes**: Extiende el modelo `Person` del `kaelytechnology/auth-package`
- ✅ **Direcciones Polimórficas**: Soporte para direcciones de facturación y envío
- ✅ **Historial de Compras**: Integración con `kaely/pos-sale` para tickets
- ✅ **Gestión de Crédito**: Límites de crédito y validaciones
- ✅ **Sistema de Fidelización**: Puntos por compra con expiración
- ✅ **API REST Completa**: Endpoints con filtros avanzados
- ✅ **Autorización Granular**: Policies integradas con `kaelytechnology/auth-package`
- ✅ **Soft Deletes**: Eliminación suave con auditoría
- ✅ **Eventos y Listeners**: Sistema de eventos para auditoría y notificaciones
- ✅ **Tests Completos**: Tests con Pest para todos los componentes
- ✅ **Factories y Seeders**: Datos de prueba y desarrollo

## Instalación

### 1. Instalar el paquete

```bash
composer require kaely/pos-customer
```

### 2. Publicar configuración y migraciones

```bash
php artisan vendor:publish --tag=pos-customer-config
php artisan vendor:publish --tag=pos-customer-migrations
php artisan vendor:publish --tag=pos-customer-factories
php artisan vendor:publish --tag=pos-customer-seeders
```

### 3. Ejecutar migraciones

```bash
php artisan migrate
```

### 4. Ejecutar seeders (opcional)

```bash
php artisan db:seed --class=PosCustomerSeeder
```

## Configuración

El paquete incluye un archivo de configuración completo en `config/pos-customer.php`:

```php
return [
    'api_prefix' => env('POS_CUSTOMER_API_PREFIX', 'api/v1/pos/customers'),
    'default_active_status' => env('POS_CUSTOMER_DEFAULT_ACTIVE_STATUS', true),
    'require_rfc' => env('POS_CUSTOMER_REQUIRE_RFC', false),
    'require_tax_id' => env('POS_CUSTOMER_REQUIRE_TAX_ID', false),
    'default_customer_group' => env('POS_CUSTOMER_DEFAULT_GROUP', 'general'),
    'default_credit_limit' => env('POS_CUSTOMER_DEFAULT_CREDIT_LIMIT', 0.00),
    'default_points_balance' => env('POS_CUSTOMER_DEFAULT_POINTS', 0),
    
    'credit' => [
        'enabled' => env('POS_CUSTOMER_CREDIT_ENABLED', true),
        'max_credit_limit' => env('POS_CUSTOMER_MAX_CREDIT_LIMIT', 100000.00),
        'min_credit_limit' => env('POS_CUSTOMER_MIN_CREDIT_LIMIT', 0.00),
        'credit_precision' => env('POS_CUSTOMER_CREDIT_PRECISION', 2),
        'auto_approve_limit' => env('POS_CUSTOMER_AUTO_APPROVE_LIMIT', 1000.00),
    ],
    
    'loyalty' => [
        'enabled' => env('POS_CUSTOMER_LOYALTY_ENABLED', true),
        'points_per_currency' => env('POS_CUSTOMER_POINTS_PER_CURRENCY', 1),
        'points_currency' => env('POS_CUSTOMER_POINTS_CURRENCY', 'MXN'),
        'min_purchase_for_points' => env('POS_CUSTOMER_MIN_PURCHASE_FOR_POINTS', 1.00),
        'points_expiration_days' => env('POS_CUSTOMER_POINTS_EXPIRATION_DAYS', 365),
        'max_points_per_transaction' => env('POS_CUSTOMER_MAX_POINTS_PER_TRANSACTION', 1000),
    ],
    
    // ... más configuraciones
];
```

## Uso

### Modelos

#### Customer

```php
use Kaely\PosCustomer\Models\Customer;

// Crear un cliente
$customer = Customer::create([
    'person_id' => $person->id,
    'rfc' => 'PERJ850315ABC',
    'tax_id' => 'TAX-12345678',
    'customer_group' => 'vip',
    'credit_limit' => 50000.00,
    'points_balance' => 1000,
    'is_active' => true,
]);

// Relaciones
$customer->person; // Datos de Person del auth-package
$customer->addresses; // Todas las direcciones
$customer->billingAddresses; // Solo direcciones de facturación
$customer->shippingAddresses; // Solo direcciones de envío
$customer->pointsHistory; // Historial de puntos
$customer->tickets; // Tickets de venta (pos-sale)

// Scopes útiles
Customer::active()->get(); // Solo clientes activos
Customer::byRfc('PERJ850315ABC')->first(); // Buscar por RFC
Customer::byGroup('vip')->get(); // Clientes VIP
Customer::withCredit()->get(); // Clientes con crédito
Customer::withPoints()->get(); // Clientes con puntos
Customer::search('Juan Pérez')->get(); // Búsqueda general
```

#### CustomerAddress

```php
use Kaely\PosCustomer\Models\CustomerAddress;

// Crear una dirección
$address = CustomerAddress::create([
    'customer_id' => $customer->id,
    'type' => 'billing', // 'billing' o 'shipping'
    'street' => 'Av. Insurgentes Sur',
    'street_number' => '1234',
    'interior' => 'A-101',
    'neighborhood' => 'Del Valle',
    'city' => 'Ciudad de México',
    'state' => 'Ciudad de México',
    'postal_code' => '03100',
    'country' => 'MX',
    'phone' => '555-1234',
    'notes' => 'Oficina principal',
    'is_default' => true,
]);

// Atributos computados
$address->full_address; // Dirección completa formateada
$address->short_address; // Dirección corta (sin país)
$address->type_text; // Tipo en español
$address->is_billing; // true si es facturación
$address->is_shipping; // true si es envío
```

#### CustomerPointsHistory

```php
use Kaely\PosCustomer\Models\CustomerPointsHistory;

// Crear historial de puntos
$history = CustomerPointsHistory::create([
    'customer_id' => $customer->id,
    'type' => 'earned', // 'earned', 'redeemed', 'expired', 'adjusted'
    'points' => 100,
    'amount' => 100.00,
    'currency' => 'MXN',
    'description' => 'Puntos ganados por compra #1234',
    'reference_type' => 'sale',
    'reference_id' => 1234,
    'expires_at' => now()->addDays(365),
    'is_expired' => false,
]);
```

### Servicios

#### CustomerService

```php
use Kaely\PosCustomer\Services\CustomerService;

$customerService = app(CustomerService::class);

// Crear cliente con direcciones
$customer = $customerService->createCustomer([
    'name' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'rfc' => 'PERJ850315ABC',
    'customer_group' => 'vip',
    'credit_limit' => 50000.00,
    'addresses' => [
        [
            'type' => 'billing',
            'street' => 'Av. Insurgentes Sur',
            'city' => 'Ciudad de México',
            'state' => 'Ciudad de México',
            'postal_code' => '03100',
            'country' => 'MX',
            'is_default' => true,
        ],
    ],
]);

// Buscar clientes con filtros
$customers = $customerService->searchCustomers([
    'customer_group' => 'vip',
    'min_credit_limit' => 10000,
    'is_active' => true,
    'order_by' => 'total_purchases',
    'order_direction' => 'desc',
]);

// Obtener estadísticas
$statistics = $customerService->getStatistics();
```

#### LoyaltyService

```php
use Kaely\PosCustomer\Services\LoyaltyService;

$loyaltyService = app(LoyaltyService::class);

// Calcular puntos por compra
$points = $loyaltyService->calculatePoints(1500.00); // 1500 puntos

// Otorgar puntos
$history = $loyaltyService->awardPoints(
    $customer,
    1500,
    1500.00,
    'Puntos ganados por compra #1234',
    'sale',
    1234
);

// Canjear puntos
$history = $loyaltyService->redeemPoints(
    $customer,
    500,
    'Puntos canjeados por descuento',
    'redemption',
    5678
);

// Procesar puntos automáticamente después de una compra
$history = $loyaltyService->processPurchasePoints($customer, 1500.00, 'sale', 1234);
```

### API REST

El paquete proporciona una API REST completa con los siguientes endpoints:

#### Clientes

```
GET    /api/v1/pos/customers                    # Listar clientes
POST   /api/v1/pos/customers                    # Crear cliente
GET    /api/v1/pos/customers/{id}               # Mostrar cliente
PUT    /api/v1/pos/customers/{id}               # Actualizar cliente
DELETE /api/v1/pos/customers/{id}               # Eliminar cliente
POST   /api/v1/pos/customers/{id}/restore       # Restaurar cliente
POST   /api/v1/pos/customers/{id}/activate      # Activar cliente
POST   /api/v1/pos/customers/{id}/deactivate    # Desactivar cliente

# Filtros específicos
GET    /api/v1/pos/customers/rfc/{rfc}          # Por RFC
GET    /api/v1/pos/customers/email/{email}      # Por email
GET    /api/v1/pos/customers/group/{group}      # Por grupo
GET    /api/v1/pos/customers/with-credit        # Con crédito
GET    /api/v1/pos/customers/with-points        # Con puntos
GET    /api/v1/pos/customers/last-purchase/{date} # Por fecha última compra
POST   /api/v1/pos/customers/search             # Búsqueda
GET    /api/v1/pos/customers/statistics         # Estadísticas
```

#### Direcciones

```
GET    /api/v1/pos/customers/{id}/addresses     # Listar direcciones
POST   /api/v1/pos/customers/{id}/addresses     # Crear dirección
GET    /api/v1/pos/customers/{id}/addresses/{address_id} # Mostrar dirección
PUT    /api/v1/pos/customers/{id}/addresses/{address_id} # Actualizar dirección
DELETE /api/v1/pos/customers/{id}/addresses/{address_id} # Eliminar dirección

# Direcciones específicas
GET    /api/v1/pos/customers/{id}/addresses/billing      # De facturación
GET    /api/v1/pos/customers/{id}/addresses/shipping     # De envío
GET    /api/v1/pos/customers/{id}/addresses/default-billing  # Por defecto facturación
GET    /api/v1/pos/customers/{id}/addresses/default-shipping # Por defecto envío
```

#### Utilidades

```
GET    /api/v1/pos/customers/utils/customer-groups  # Grupos de clientes
GET    /api/v1/pos/customers/utils/countries        # Países
GET    /api/v1/pos/customers/utils/mexican-states   # Estados mexicanos
GET    /api/v1/pos/customers/utils/address-types    # Tipos de dirección
GET    /api/v1/pos/customers/utils/point-types      # Tipos de puntos
```

### Filtros de API

La API soporta múltiples filtros:

```php
// Ejemplo de filtros
$filters = [
    'rfc' => 'PERJ850315ABC',
    'email' => 'juan@example.com',
    'customer_group' => 'vip',
    'is_active' => true,
    'search' => 'Juan Pérez',
    'min_credit_limit' => 10000,
    'max_credit_limit' => 100000,
    'min_points' => 100,
    'max_points' => 5000,
    'min_total_purchases' => 50000,
    'max_total_purchases' => 500000,
    'last_purchase_after' => '2024-01-01',
    'last_purchase_before' => '2024-12-31',
    'order_by' => 'total_purchases',
    'order_direction' => 'desc',
    'per_page' => 25,
];
```

### Eventos

El paquete dispara varios eventos:

```php
use Kaely\PosCustomer\Events\CustomerCreated;
use Kaely\PosCustomer\Events\CustomerUpdated;
use Kaely\PosCustomer\Events\CustomerAddressCreated;
use Kaely\PosCustomer\Events\PointsEarned;

// Escuchar eventos
Event::listen(CustomerCreated::class, function ($event) {
    // Lógica cuando se crea un cliente
});

Event::listen(PointsEarned::class, function ($event) {
    // Lógica cuando se ganan puntos
});
```

### Listeners

Los listeners incluidos:

- `LogCustomerActivity`: Registra actividades en el log
- `NotifyCustomerChange`: Envía notificaciones sobre cambios
- `UpdateLoyaltyPoints`: Actualiza puntos automáticamente

### Policies

El paquete incluye policies para autorización granular:

```php
// Verificar permisos
if (auth()->user()->can('pos.customers.view')) {
    // Usuario puede ver clientes
}

if (auth()->user()->can('pos.customers.manage_credit', $customer)) {
    // Usuario puede gestionar crédito del cliente
}
```

### Testing

Ejecutar los tests:

```bash
# Ejecutar todos los tests
./vendor/bin/pest

# Ejecutar tests específicos
./vendor/bin/pest tests/CustomerTest.php
./vendor/bin/pest tests/CustomerAddressTest.php
./vendor/bin/pest tests/CustomerPointsHistoryTest.php
```

### Factories

```php
use Kaely\PosCustomer\Database\Factories\CustomerFactory;
use Kaely\PosCustomer\Database\Factories\CustomerAddressFactory;
use Kaely\PosCustomer\Database\Factories\CustomerPointsHistoryFactory;

// Crear clientes de prueba
Customer::factory()->create();
Customer::factory()->vip()->create();
Customer::factory()->corporate()->create();
Customer::factory()->withCredit()->create();
Customer::factory()->withPoints()->create();

// Crear direcciones de prueba
CustomerAddress::factory()->billing()->create();
CustomerAddress::factory()->shipping()->create();
CustomerAddress::factory()->mexican()->create();

// Crear historial de puntos de prueba
CustomerPointsHistory::factory()->earned()->create();
CustomerPointsHistory::factory()->redeemed()->create();
CustomerPointsHistory::factory()->expired()->create();
```

## Integración con otros paquetes

### kaelytechnology/auth-package

El paquete extiende el modelo `Person` del auth-package:

```php
// El modelo Customer tiene relación con Person
$customer->person; // Instancia de Person
$customer->person->name; // Nombre del cliente
$customer->person->email; // Email del cliente
```

### kaely/pos-sale

Integración con el paquete de ventas:

```php
// Relación con tickets de venta
$customer->tickets; // Tickets del cliente

// Actualizar estadísticas automáticamente
$customerService->updatePurchaseStatistics($customer, 1500.00);

// Procesar puntos automáticamente
$loyaltyService->processPurchasePoints($customer, 1500.00, 'sale', 1234);
```

## Configuración de Variables de Entorno

```env
# API
POS_CUSTOMER_API_PREFIX=api/v1/pos/customers

# Clientes
POS_CUSTOMER_DEFAULT_ACTIVE_STATUS=true
POS_CUSTOMER_REQUIRE_RFC=false
POS_CUSTOMER_REQUIRE_TAX_ID=false
POS_CUSTOMER_DEFAULT_GROUP=general
POS_CUSTOMER_DEFAULT_CREDIT_LIMIT=0.00
POS_CUSTOMER_DEFAULT_POINTS=0

# Crédito
POS_CUSTOMER_CREDIT_ENABLED=true
POS_CUSTOMER_MAX_CREDIT_LIMIT=100000.00
POS_CUSTOMER_MIN_CREDIT_LIMIT=0.00
POS_CUSTOMER_AUTO_APPROVE_LIMIT=1000.00

# Fidelización
POS_CUSTOMER_LOYALTY_ENABLED=true
POS_CUSTOMER_POINTS_PER_CURRENCY=1
POS_CUSTOMER_POINTS_CURRENCY=MXN
POS_CUSTOMER_MIN_PURCHASE_FOR_POINTS=1.00
POS_CUSTOMER_POINTS_EXPIRATION_DAYS=365
POS_CUSTOMER_MAX_POINTS_PER_TRANSACTION=1000

# Notificaciones
POS_CUSTOMER_NOTIFICATIONS_ENABLED=true
POS_CUSTOMER_NOTIFY_CUSTOMER_CREATED=true
POS_CUSTOMER_NOTIFY_CUSTOMER_UPDATED=true
POS_CUSTOMER_NOTIFY_POINTS_EARNED=true

# Auditoría
POS_CUSTOMER_AUDIT_ENABLED=true
POS_CUSTOMER_LOG_ACTIVITIES=true
POS_CUSTOMER_LOG_CHANGES=true

# Integraciones
POS_CUSTOMER_INTEGRATE_AUTH_PACKAGE=true
POS_CUSTOMER_INTEGRATE_POS_SALE=true
POS_CUSTOMER_AUTO_UPDATE_POINTS=true
POS_CUSTOMER_AUTO_UPDATE_HISTORY=true
```

## Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este paquete es de código abierto bajo la [Licencia MIT](LICENSE).

## Soporte

Para soporte, por favor contacta al equipo de desarrollo de Kaely Technology.

---

**Desarrollado por Kaely Technology** 