<?php

namespace Kaely\PosCustomer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Kaely\PosCustomer\Models\Customer;
use Kaely\PosCustomer\Models\CustomerAddress;
use Kaely\PosCustomer\Http\Resources\CustomerAddressResource;
use Kaely\PosCustomer\Http\Resources\CustomerAddressCollection;

class CustomerAddressController extends Controller
{
    /**
     * Listar direcciones de un cliente
     */
    public function index(Customer $customer, Request $request): JsonResponse
    {
        $this->authorize('viewAny', CustomerAddress::class);

        $filters = $request->only(['type', 'is_default', 'city', 'state', 'country']);
        $perPage = $request->get('per_page', 15);

        $query = $customer->addresses();

        // Aplicar filtros
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
        }

        if (isset($filters['city'])) {
            $query->byCity($filters['city']);
        }

        if (isset($filters['state'])) {
            $query->byState($filters['state']);
        }

        if (isset($filters['country'])) {
            $query->byCountry($filters['country']);
        }

        $addresses = $query->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerAddressCollection($addresses),
            $addresses
        );
    }

    /**
     * Crear una nueva dirección
     */
    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('create', CustomerAddress::class);

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:billing,shipping',
            'street' => 'required|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'interior' => 'nullable|string|max:20',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'country' => 'nullable|string|max:3',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $validator->validated();
            $data['customer_id'] = $customer->id;

            // Si es la primera dirección de este tipo, hacerla por defecto
            if (!isset($data['is_default'])) {
                $existingAddresses = $customer->addresses()->where('type', $data['type'])->count();
                $data['is_default'] = $existingAddresses === 0;
            }

            // Si se marca como por defecto, quitar el flag de las otras
            if ($data['is_default']) {
                $customer->addresses()
                    ->where('type', $data['type'])
                    ->update(['is_default' => false]);
            }

            $address = CustomerAddress::create($data);

            return $this->successResponse(
                new CustomerAddressResource($address),
                'Dirección creada exitosamente',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear la dirección: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar una dirección específica
     */
    public function show(Customer $customer, CustomerAddress $address): JsonResponse
    {
        $this->authorize('view', $address);

        // Verificar que la dirección pertenece al cliente
        if ($address->customer_id !== $customer->id) {
            return $this->notFoundResponse('Dirección no encontrada');
        }

        return $this->successResponse(new CustomerAddressResource($address));
    }

    /**
     * Actualizar una dirección
     */
    public function update(Request $request, Customer $customer, CustomerAddress $address): JsonResponse
    {
        $this->authorize('update', $address);

        // Verificar que la dirección pertenece al cliente
        if ($address->customer_id !== $customer->id) {
            return $this->notFoundResponse('Dirección no encontrada');
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:billing,shipping',
            'street' => 'sometimes|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'interior' => 'nullable|string|max:20',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:10',
            'country' => 'nullable|string|max:3',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $validator->validated();

            // Si se está marcando como por defecto, quitar el flag de las otras
            if (isset($data['is_default']) && $data['is_default']) {
                $customer->addresses()
                    ->where('type', $address->type)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($data);

            return $this->successResponse(
                new CustomerAddressResource($address),
                'Dirección actualizada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar la dirección: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar una dirección
     */
    public function destroy(Customer $customer, CustomerAddress $address): JsonResponse
    {
        $this->authorize('delete', $address);

        // Verificar que la dirección pertenece al cliente
        if ($address->customer_id !== $customer->id) {
            return $this->notFoundResponse('Dirección no encontrada');
        }

        try {
            $address->delete();

            return $this->successResponse(null, 'Dirección eliminada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar la dirección: ' . $e->getMessage());
        }
    }

    /**
     * Restaurar una dirección eliminada
     */
    public function restore(Customer $customer, int $id): JsonResponse
    {
        $this->authorize('restore', CustomerAddress::class);

        try {
            $address = CustomerAddress::withTrashed()
                ->where('id', $id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$address) {
                return $this->notFoundResponse('Dirección no encontrada');
            }

            $address->restore();

            return $this->successResponse(
                new CustomerAddressResource($address),
                'Dirección restaurada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse('Dirección no encontrada');
        }
    }

    /**
     * Direcciones de facturación
     */
    public function billing(Customer $customer, Request $request): JsonResponse
    {
        $this->authorize('viewAny', CustomerAddress::class);

        $perPage = $request->get('per_page', 15);
        $addresses = $customer->billingAddresses()->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerAddressCollection($addresses),
            $addresses
        );
    }

    /**
     * Direcciones de envío
     */
    public function shipping(Customer $customer, Request $request): JsonResponse
    {
        $this->authorize('viewAny', CustomerAddress::class);

        $perPage = $request->get('per_page', 15);
        $addresses = $customer->shippingAddresses()->paginate($perPage);

        return $this->paginatedResponse(
            new CustomerAddressCollection($addresses),
            $addresses
        );
    }

    /**
     * Dirección por defecto de facturación
     */
    public function defaultBilling(Customer $customer): JsonResponse
    {
        $this->authorize('viewAny', CustomerAddress::class);

        $address = $customer->default_billing_address;

        if (!$address) {
            return $this->notFoundResponse('No hay dirección de facturación por defecto');
        }

        return $this->successResponse(new CustomerAddressResource($address));
    }

    /**
     * Dirección por defecto de envío
     */
    public function defaultShipping(Customer $customer): JsonResponse
    {
        $this->authorize('viewAny', CustomerAddress::class);

        $address = $customer->default_shipping_address;

        if (!$address) {
            return $this->notFoundResponse('No hay dirección de envío por defecto');
        }

        return $this->successResponse(new CustomerAddressResource($address));
    }

    /**
     * Buscar direcciones
     */
    public function search(Customer $customer, Request $request): JsonResponse
    {
        $this->authorize('viewAny', CustomerAddress::class);

        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $search = $validator->validated()['search'];
        $limit = $validator->validated()['limit'] ?? 10;

        $addresses = $customer->addresses()
            ->search($search)
            ->limit($limit)
            ->get();

        return $this->collectionResponse(new CustomerAddressCollection($addresses));
    }

    /**
     * Estadísticas de direcciones
     */
    public function statistics(Customer $customer): JsonResponse
    {
        $this->authorize('viewAny', CustomerAddress::class);

        $statistics = [
            'total_addresses' => $customer->addresses()->count(),
            'billing_addresses' => $customer->billingAddresses()->count(),
            'shipping_addresses' => $customer->shippingAddresses()->count(),
            'default_billing' => $customer->default_billing_address ? true : false,
            'default_shipping' => $customer->default_shipping_address ? true : false,
            'cities' => $customer->addresses()->distinct('city')->count(),
            'states' => $customer->addresses()->distinct('state')->count(),
            'countries' => $customer->addresses()->distinct('country')->count(),
        ];

        return $this->successResponse($statistics);
    }
} 