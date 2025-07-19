<?php

namespace Kaely\PosCustomer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Kaely\PosCustomer\Models\Customer;
use Kaely\PosCustomer\Services\CustomerService;
use Kaely\PosCustomer\Http\Resources\CustomerResource;
use Kaely\PosCustomer\Http\Resources\CustomerCollection;

class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Listar clientes
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $filters = $request->only([
            'rfc', 'email', 'customer_group', 'is_active', 'search',
            'min_credit_limit', 'max_credit_limit', 'min_points', 'max_points',
            'min_total_purchases', 'max_total_purchases', 'last_purchase_after',
            'last_purchase_before', 'order_by', 'order_direction'
        ]);

        $perPage = $request->get('per_page', 15);
        $query = $this->customerService->searchCustomers($filters);
        $customers = $query->with(['person'])->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Crear un nuevo cliente
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $validator = Validator::make($request->all(), [
            // Datos de Person
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:persons,email',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            
            // Datos de Customer
            'rfc' => 'nullable|string|max:13|unique:customers,rfc|regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
            'tax_id' => 'nullable|string|max:50',
            'customer_group' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0|max:999999.99',
            'points_balance' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            
            // Direcciones
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'required|in:billing,shipping',
            'addresses.*.street' => 'required|string|max:255',
            'addresses.*.street_number' => 'nullable|string|max:20',
            'addresses.*.interior' => 'nullable|string|max:20',
            'addresses.*.neighborhood' => 'nullable|string|max:100',
            'addresses.*.city' => 'required|string|max:100',
            'addresses.*.state' => 'required|string|max:100',
            'addresses.*.postal_code' => 'required|string|max:10',
            'addresses.*.country' => 'nullable|string|max:3',
            'addresses.*.phone' => 'nullable|string|max:20',
            'addresses.*.notes' => 'nullable|string',
            'addresses.*.is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $customer = $this->customerService->createCustomer($validator->validated());
            
            return $this->successResponse(
                new CustomerResource($customer),
                'Cliente creado exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar un cliente específico
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $customer->load([
            'person', 
            'addresses', 
            'pointsHistory' => function ($query) {
                $query->latest()->limit(10);
            },
            'tickets' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);

        return $this->successResponse(new CustomerResource($customer));
    }

    /**
     * Actualizar un cliente
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validator = Validator::make($request->all(), [
            // Datos de Person
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:persons,email,' . $customer->person_id,
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            
            // Datos de Customer
            'rfc' => 'nullable|string|max:13|unique:customers,rfc,' . $customer->id . '|regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
            'tax_id' => 'nullable|string|max:50',
            'customer_group' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0|max:999999.99',
            'points_balance' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $customer = $this->customerService->updateCustomer($customer, $validator->validated());
            
            return $this->successResponse(
                new CustomerResource($customer),
                'Cliente actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un cliente
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        try {
            $this->customerService->deleteCustomer($customer);
            
            return $this->successResponse(null, 'Cliente eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Restaurar un cliente eliminado
     */
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Customer::class);

        try {
            $customer = $this->customerService->restoreCustomer($id);
            
            return $this->successResponse(
                new CustomerResource($customer),
                'Cliente restaurado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse('Cliente no encontrado');
        }
    }

    /**
     * Activar un cliente
     */
    public function activate(Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        try {
            $customer = $this->customerService->activateCustomer($customer);
            
            return $this->successResponse(
                new CustomerResource($customer),
                'Cliente activado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al activar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Desactivar un cliente
     */
    public function deactivate(Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        try {
            $customer = $this->customerService->deactivateCustomer($customer);
            
            return $this->successResponse(
                new CustomerResource($customer),
                'Cliente desactivado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al desactivar el cliente: ' . $e->getMessage());
        }
    }

    /**
     * Buscar clientes
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $search = $validator->validated()['search'];
        $limit = $validator->validated()['limit'] ?? 10;

        $customers = Customer::search($search)
            ->with(['person'])
            ->limit($limit)
            ->get();

        return $this->collectionResponse(new CustomerCollection($customers));
    }

    /**
     * Estadísticas de clientes
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Customer::class);

        $statistics = $this->customerService->getStatistics();

        return $this->successResponse($statistics);
    }

    /**
     * Clientes por RFC
     */
    public function byRfc(string $rfc, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::byRfc($rfc)
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Clientes por email
     */
    public function byEmail(string $email, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::byEmail($email)
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Clientes por grupo
     */
    public function byGroup(string $group, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::byGroup($group)
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Clientes con crédito disponible
     */
    public function withCredit(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::withCredit()
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Clientes con puntos
     */
    public function withPoints(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::withPoints()
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Clientes por fecha de última compra
     */
    public function byLastPurchaseDate(string $date, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::byLastPurchaseDate($date)
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Clientes que compraron después de una fecha
     */
    public function purchasedAfter(string $date, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::purchasedAfter($date)
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }

    /**
     * Clientes que compraron antes de una fecha
     */
    public function purchasedBefore(string $date, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = $request->get('per_page', 15);
        $customers = Customer::purchasedBefore($date)
            ->with(['person'])
            ->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerCollection($customers),
            $customers
        );
    }
} 